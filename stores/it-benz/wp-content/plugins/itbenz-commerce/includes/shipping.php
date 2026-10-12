<?php
/**
 * "Livraison par wilaya" shipping method.
 *
 * Rates live in one option (itbenz_delivery_rates) edited from
 * WooCommerce → Livraison IT-BENZ. Each wilaya has an optional home-delivery
 * price and an optional stop-desk price (DZD). An empty value means the option
 * is not offered for that wilaya; a wilaya with no price at all cannot check out,
 * so the merchant never ships at an unknown cost.
 */

defined( 'ABSPATH' ) || exit;

final class ITBENZ_Delivery_Rates {
	public const OPTION = 'itbenz_delivery_rates';

	/** @return array<string,array{home:string,desk:string}> keyed by "DZ-NN" */
	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		$out   = array();
		foreach ( array_keys( ITBENZ_Geo::wilayas() ) as $state ) {
			$out[ $state ] = array(
				'home' => (string) ( $saved[ $state ]['home'] ?? '' ),
				'desk' => (string) ( $saved[ $state ]['desk'] ?? '' ),
			);
		}
		return $out;
	}

	public static function for_state( string $state ): array {
		return self::all()[ $state ] ?? array( 'home' => '', 'desk' => '' );
	}

	public static function free_threshold(): float {
		return (float) get_option( 'itbenz_free_shipping_threshold', 0 );
	}
}

add_action(
	'woocommerce_shipping_init',
	static function (): void {
		if ( class_exists( 'ITBENZ_Shipping_Wilaya', false ) ) {
			return;
		}
		final class ITBENZ_Shipping_Wilaya extends WC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'itbenz_wilaya';
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = __( 'Livraison par wilaya (IT-BENZ)', 'itbenz' );
				$this->method_description = __( 'Tarifs domicile / stop desk par wilaya, modifiables dans WooCommerce → Livraison IT-BENZ.', 'itbenz' );
				$this->supports           = array( 'shipping-zones', 'instance-settings' );
				$this->enabled            = 'yes';
				$this->title              = __( 'Livraison', 'itbenz' );
			}

			public function calculate_shipping( $package = array() ): void {
				$state = (string) ( $package['destination']['state'] ?? '' );
				if ( 'DZ' !== ( $package['destination']['country'] ?? '' ) || '' === $state ) {
					return;
				}
				$rates = ITBENZ_Delivery_Rates::for_state( $state );
				$free  = ITBENZ_Delivery_Rates::free_threshold();
				$total = (float) ( $package['contents_cost'] ?? 0 );

				$options = array(
					'home' => __( 'Livraison à domicile', 'itbenz' ),
					'desk' => __( 'Retrait au bureau du transporteur (stop desk)', 'itbenz' ),
				);
				foreach ( $options as $key => $label ) {
					if ( '' === $rates[ $key ] || ! is_numeric( $rates[ $key ] ) ) {
						continue;
					}
					$cost = ( $free > 0 && $total >= $free ) ? 0 : (float) $rates[ $key ];
					$this->add_rate(
						array(
							'id'        => $this->get_rate_id( $key ),
							'label'     => $label,
							'cost'      => $cost,
							'meta_data' => array( 'itbenz_delivery_type' => $key ),
						)
					);
				}
			}
		}
	}
);

add_filter(
	'woocommerce_shipping_methods',
	static function ( array $methods ): array {
		$methods['itbenz_wilaya'] = 'ITBENZ_Shipping_Wilaya';
		return $methods;
	}
);

// Clear message when a wilaya has no configured price.
add_filter(
	'woocommerce_no_shipping_available_html',
	static fn() => esc_html__( 'La livraison n’est pas encore disponible pour cette wilaya en ligne. Contactez-nous par téléphone ou WhatsApp pour commander.', 'itbenz' )
);
add_filter( 'woocommerce_cart_no_shipping_available_html', static fn( $html ) => apply_filters( 'woocommerce_no_shipping_available_html', $html ) );

/**
 * Admin page: one table, 69 rows, two prices — editable by the shop manager.
 */
add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page(
			'woocommerce',
			__( 'Tarifs de livraison', 'itbenz' ),
			__( 'Livraison IT-BENZ', 'itbenz' ),
			'manage_woocommerce',
			'itbenz-delivery',
			'itbenz_render_delivery_page'
		);
	},
	60
);

add_action(
	'admin_post_itbenz_save_delivery',
	static function (): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'itbenz' ), 403 );
		}
		check_admin_referer( 'itbenz_delivery' );
		$input = (array) wp_unslash( $_POST['rates'] ?? array() );
		$clean = array();
		foreach ( array_keys( ITBENZ_Geo::wilayas() ) as $state ) {
			foreach ( array( 'home', 'desk' ) as $k ) {
				$v                     = trim( (string) ( $input[ $state ][ $k ] ?? '' ) );
				$clean[ $state ][ $k ] = ( '' !== $v && is_numeric( $v ) && $v >= 0 ) ? (string) (int) round( (float) $v ) : '';
			}
		}
		update_option( ITBENZ_Delivery_Rates::OPTION, $clean, false );
		$threshold = (string) wp_unslash( $_POST['free_threshold'] ?? '' );
		update_option( 'itbenz_free_shipping_threshold', is_numeric( $threshold ) ? max( 0, (int) $threshold ) : 0 );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		wp_safe_redirect( add_query_arg( 'updated', 1, admin_url( 'admin.php?page=itbenz-delivery' ) ) );
		exit;
	}
);

function itbenz_render_delivery_page(): void {
	$rates  = ITBENZ_Delivery_Rates::all();
	$labels = ITBENZ_Geo::wilayas();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Tarifs de livraison par wilaya (DZD)', 'itbenz' ); ?></h1>
		<p><?php esc_html_e( 'Laissez une case vide si l’option n’est pas proposée. Une wilaya sans aucun tarif ne peut pas commander en ligne. Saisissez les tarifs réels de votre transporteur.', 'itbenz' ); ?></p>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Tarifs enregistrés.', 'itbenz' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="itbenz_save_delivery">
			<?php wp_nonce_field( 'itbenz_delivery' ); ?>
			<p>
				<label><?php esc_html_e( 'Livraison gratuite à partir de (DZD, 0 = jamais) :', 'itbenz' ); ?>
					<input type="number" min="0" step="1" name="free_threshold" value="<?php echo esc_attr( (string) ITBENZ_Delivery_Rates::free_threshold() ); ?>">
				</label>
			</p>
			<p>
				<button type="button" class="button" id="itbenz-fill-home"><?php esc_html_e( 'Appliquer un tarif domicile à toutes les cases vides…', 'itbenz' ); ?></button>
				<button type="button" class="button" id="itbenz-fill-desk"><?php esc_html_e( 'Appliquer un tarif stop desk à toutes les cases vides…', 'itbenz' ); ?></button>
			</p>
			<table class="widefat striped" style="max-width:760px">
				<thead><tr><th><?php esc_html_e( 'Wilaya', 'itbenz' ); ?></th><th><?php esc_html_e( 'À domicile', 'itbenz' ); ?></th><th><?php esc_html_e( 'Stop desk', 'itbenz' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $rates as $state => $r ) : ?>
					<tr>
						<td><?php echo esc_html( $labels[ $state ] ); ?></td>
						<td><input class="itbenz-home" type="number" min="0" step="1" name="rates[<?php echo esc_attr( $state ); ?>][home]" value="<?php echo esc_attr( $r['home'] ); ?>"></td>
						<td><input class="itbenz-desk" type="number" min="0" step="1" name="rates[<?php echo esc_attr( $state ); ?>][desk]" value="<?php echo esc_attr( $r['desk'] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Enregistrer les tarifs', 'itbenz' ) ); ?>
		</form>
	</div>
	<script>
	['home','desk'].forEach(function (k) {
		document.getElementById('itbenz-fill-' + k).addEventListener('click', function () {
			var v = prompt('<?php echo esc_js( __( 'Tarif en DZD :', 'itbenz' ) ); ?>');
			if (v === null || v === '' || isNaN(v)) return;
			document.querySelectorAll('.itbenz-' + k).forEach(function (i) { if (i.value === '') i.value = v; });
		});
	});
	</script>
	<?php
}

// Before rates are calculated WooCommerce shows a generic hint; make it explicit for Algeria.
add_filter(
	'woocommerce_shipping_may_be_available_html',
	static function ( $html ) {
		$state = WC()->customer ? (string) WC()->customer->get_shipping_state() : '';
		if ( $state && str_starts_with( $state, 'DZ-' ) ) {
			$r = ITBENZ_Delivery_Rates::for_state( $state );
			if ( '' === $r['home'] && '' === $r['desk'] ) {
				return apply_filters( 'woocommerce_no_shipping_available_html', $html );
			}
		}
		return esc_html__( 'Choisissez votre wilaya et votre commune pour afficher les frais de livraison.', 'itbenz' );
	}
);
