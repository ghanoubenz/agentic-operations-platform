<?php
/**
 * "IT-BENZ → Infos boutique": contact details and customer-facing policies.
 *
 * Every field starts EMPTY on purpose: nothing about delivery times, warranty or
 * returns is shown on the site until the merchant writes the real policy here.
 */

defined( 'ABSPATH' ) || exit;

function itbenz_setting_fields(): array {
	return array(
		'announcement' => array( __( 'Bandeau d’annonce (haut de page)', 'itbenz' ), 'text' ),
		'phone'        => array( __( 'Téléphone affiché', 'itbenz' ), 'text' ),
		'whatsapp'     => array( __( 'Numéro WhatsApp (format 213XXXXXXXXX)', 'itbenz' ), 'text' ),
		'email'        => array( __( 'E-mail de contact', 'itbenz' ), 'email' ),
		'address'      => array( __( 'Adresse du magasin', 'itbenz' ), 'textarea' ),
		'hours'        => array( __( 'Horaires', 'itbenz' ), 'text' ),
		'delivery'     => array( __( 'Livraison — texte court vérifié (ex. zones, délais réels)', 'itbenz' ), 'textarea' ),
		'warranty'     => array( __( 'Garantie — politique réelle', 'itbenz' ), 'textarea' ),
		'returns'      => array( __( 'Retours / échanges — politique réelle', 'itbenz' ), 'textarea' ),
		'support'      => array( __( 'Service client — texte court', 'itbenz' ), 'textarea' ),
		'facebook'     => array( __( 'Lien Facebook', 'itbenz' ), 'url' ),
		'instagram'    => array( __( 'Lien Instagram', 'itbenz' ), 'url' ),
		'tiktok'       => array( __( 'Lien TikTok', 'itbenz' ), 'url' ),
	);
}

/** Read one shop setting (empty string when not filled in). */
function itbenz_setting( string $key ): string {
	$all = get_option( 'itbenz_settings', array() );
	return is_array( $all ) ? trim( (string) ( $all[ $key ] ?? '' ) ) : '';
}

function itbenz_whatsapp_url( string $text = '' ): string {
	$num = preg_replace( '/\D+/', '', itbenz_setting( 'whatsapp' ) );
	if ( ! $num ) {
		return '';
	}
	return 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

add_action(
	'admin_menu',
	static function (): void {
		add_menu_page(
			__( 'Infos boutique', 'itbenz' ),
			__( 'IT-BENZ', 'itbenz' ),
			'manage_woocommerce',
			'itbenz-settings',
			'itbenz_render_settings_page',
			'dashicons-store',
			56
		);
	}
);

add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'itbenz_settings',
			'itbenz_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $input ): array {
					$out = array();
					foreach ( itbenz_setting_fields() as $key => [ , $type ] ) {
						$v           = (string) ( $input[ $key ] ?? '' );
						$out[ $key ] = match ( $type ) {
							'email'    => sanitize_email( $v ),
							'url'      => esc_url_raw( $v ),
							'textarea' => sanitize_textarea_field( $v ),
							default    => sanitize_text_field( $v ),
						};
					}
					return $out;
				},
			)
		);
	}
);

// Shop managers may save this option group.
add_filter( 'option_page_capability_itbenz_settings', static fn() => 'manage_woocommerce' );

function itbenz_render_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Infos boutique IT-BENZ', 'itbenz' ); ?></h1>
		<p><?php esc_html_e( 'Ces informations s’affichent sur le site (en-tête, pied de page, fiches produit). Un champ vide n’est pas affiché : n’indiquez que des informations vérifiées.', 'itbenz' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'itbenz_settings' ); ?>
			<table class="form-table" role="presentation">
			<?php foreach ( itbenz_setting_fields() as $key => [ $label, $type ] ) : ?>
				<tr>
					<th scope="row"><label for="itbenz-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
					<?php if ( 'textarea' === $type ) : ?>
						<textarea class="large-text" rows="3" id="itbenz-<?php echo esc_attr( $key ); ?>" name="itbenz_settings[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( itbenz_setting( $key ) ); ?></textarea>
					<?php else : ?>
						<input class="regular-text" type="<?php echo esc_attr( $type ); ?>" id="itbenz-<?php echo esc_attr( $key ); ?>" name="itbenz_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( itbenz_setting( $key ) ); ?>">
					<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
