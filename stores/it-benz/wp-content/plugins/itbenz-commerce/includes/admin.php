<?php
/**
 * Simplified back-office for the "Gestionnaire de boutique" (shop_manager) role.
 *
 * Administrators keep the full WordPress admin. Shop managers see only what they
 * need day-to-day: orders, products, banners, coupons, delivery prices, statistics
 * and shop info. Maintenance (plugins, themes, users, settings) stays admin-only.
 */

defined( 'ABSPATH' ) || exit;

function itbenz_is_shop_manager_only(): bool {
	return current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' );
}

add_action(
	'admin_menu',
	static function (): void {
		if ( ! itbenz_is_shop_manager_only() ) {
			return;
		}
		foreach ( array( 'edit.php', 'edit-comments.php', 'tools.php', 'edit.php?post_type=page', 'themes.php' ) as $slug ) {
			remove_menu_page( $slug );
		}
		// WooCommerce marketplace / upsell screens are not useful for daily operations.
		foreach ( array( 'wc-addons', 'woocommerce-marketplace', 'wc-admin&path=/extensions' ) as $slug ) {
			remove_submenu_page( 'woocommerce', $slug );
		}
	},
	999
);

add_action(
	'wp_dashboard_setup',
	static function (): void {
		if ( itbenz_is_shop_manager_only() ) {
			foreach ( array( 'dashboard_primary', 'dashboard_quick_press', 'dashboard_site_health', 'dashboard_activity', 'dashboard_right_now' ) as $id ) {
				remove_meta_box( $id, 'dashboard', 'normal' );
				remove_meta_box( $id, 'dashboard', 'side' );
			}
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			wp_add_dashboard_widget( 'itbenz_quick', __( 'IT-BENZ — Accès rapide', 'itbenz' ), 'itbenz_dashboard_widget' );
		}
	},
	999
);

function itbenz_dashboard_widget(): void {
	$counts = array();
	foreach ( array( 'processing', 'confirmed', 'preparing', 'shipped' ) as $s ) {
		$counts[ $s ] = count( wc_get_orders( array( 'status' => $s, 'limit' => 200, 'return' => 'ids' ) ) );
	}
	$labels = wc_get_order_statuses();
	echo '<ul style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:0">';
	foreach ( $counts as $s => $n ) {
		printf(
			'<li style="background:#f6f7f7;padding:12px;border-radius:8px"><a href="%1$s"><strong style="font-size:22px">%2$d</strong><br>%3$s</a></li>',
			esc_url( admin_url( 'admin.php?page=wc-orders&status=wc-' . $s ) ),
			(int) $n,
			esc_html( $labels[ 'wc-' . $s ] ?? $s )
		);
	}
	echo '</ul><p style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">';
	$links = array(
		'post-new.php?post_type=product'                    => __( '+ Nouveau produit', 'itbenz' ),
		'edit.php?post_type=product&page=product_importer'  => __( 'Importer CSV', 'itbenz' ),
		'edit.php?post_type=itbenz_banner'                  => __( 'Bannières', 'itbenz' ),
		'admin.php?page=itbenz-delivery'                    => __( 'Tarifs livraison', 'itbenz' ),
		'edit.php?post_type=shop_coupon'                    => __( 'Codes promo', 'itbenz' ),
		'admin.php?page=wc-admin'                           => __( 'Statistiques', 'itbenz' ),
		'admin.php?page=itbenz-settings'                    => __( 'Infos boutique', 'itbenz' ),
	);
	foreach ( $links as $url => $label ) {
		printf( '<a class="button" href="%s">%s</a>', esc_url( admin_url( $url ) ), esc_html( $label ) );
	}
	echo '</p>';
}

// Show wilaya / commune / phone directly in the orders list.
add_filter(
	'manage_woocommerce_page_wc-orders_columns',
	static function ( array $cols ): array {
		$cols['itbenz_dest'] = __( 'Wilaya / téléphone', 'itbenz' );
		return $cols;
	}
);
add_filter( 'manage_edit-shop_order_columns', static fn( $cols ) => $cols + array( 'itbenz_dest' => __( 'Wilaya / téléphone', 'itbenz' ) ) );

function itbenz_render_dest_column( $column, $order ): void {
	if ( 'itbenz_dest' !== $column ) {
		return;
	}
	$order  = $order instanceof WC_Order ? $order : wc_get_order( $order );
	$states = WC()->countries->get_states( 'DZ' );
	if ( ! $order ) {
		return;
	}
	printf(
		'%s<br><small>%s</small><br><a href="tel:%3$s">%3$s</a>',
		esc_html( $states[ $order->get_billing_state() ] ?? $order->get_billing_state() ),
		esc_html( $order->get_billing_city() ),
		esc_html( $order->get_billing_phone() )
	);
}
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'itbenz_render_dest_column', 10, 2 );
add_action( 'manage_shop_order_posts_custom_column', 'itbenz_render_dest_column', 10, 2 );

// WhatsApp shortcut on the order screen (opens the merchant's own WhatsApp; nothing is sent automatically).
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	static function ( WC_Order $order ): void {
		$phone = ITBENZ_Geo::normalize_phone( $order->get_billing_phone() );
		if ( ! $phone ) {
			return;
		}
		$text = sprintf(
			/* translators: 1: name, 2: order number, 3: total */
			__( 'Bonjour %1$s, IT-BENZ vous contacte pour confirmer votre commande %2$s (total %3$s).', 'itbenz' ),
			$order->get_billing_first_name(),
			$order->get_order_number(),
			wp_strip_all_tags( wc_price( $order->get_total() ) )
		);
		printf(
			'<p><a class="button" target="_blank" rel="noopener" href="%s">%s</a></p>',
			esc_url( 'https://wa.me/213' . substr( $phone, 1 ) . '?text=' . rawurlencode( $text ) ),
			esc_html__( 'Confirmer par WhatsApp', 'itbenz' )
		);
	}
);
