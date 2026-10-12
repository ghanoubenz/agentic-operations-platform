<?php
/**
 * COD order workflow:
 *
 *   Nouvelle (processing) → Confirmée → En préparation → Expédiée → Livrée (completed)
 *                       ↘ Annulée (cancelled)                    ↘ Retournée
 *
 * WooCommerce sets cash-on-delivery orders to "processing"; we label it "Nouvelle".
 * Stock is reduced when the order is placed. "Annulée" restocks (WooCommerce core);
 * "Retournée" restocks too (below).
 */

defined( 'ABSPATH' ) || exit;

function itbenz_custom_statuses(): array {
	return array(
		'wc-confirmed' => _x( 'Confirmée', 'Order status', 'itbenz' ),
		'wc-preparing' => _x( 'En préparation', 'Order status', 'itbenz' ),
		'wc-shipped'   => _x( 'Expédiée', 'Order status', 'itbenz' ),
		'wc-returned'  => _x( 'Retournée', 'Order status', 'itbenz' ),
	);
}

add_action(
	'init',
	static function (): void {
		foreach ( itbenz_custom_statuses() as $slug => $label ) {
			register_post_status(
				$slug,
				array(
					'label'                     => $label,
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'itbenz' ), // phpcs:ignore WordPress.WP.I18n
				)
			);
		}
	}
);

add_filter(
	'wc_order_statuses',
	static function ( array $statuses ): array {
		$out = array();
		foreach ( $statuses as $key => $label ) {
			if ( 'wc-processing' === $key ) {
				$label = _x( 'Nouvelle', 'Order status', 'itbenz' );
			} elseif ( 'wc-completed' === $key ) {
				$label = _x( 'Livrée', 'Order status', 'itbenz' );
			} elseif ( 'wc-cancelled' === $key ) {
				$label = _x( 'Annulée', 'Order status', 'itbenz' );
			}
			$out[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$out += array_slice( itbenz_custom_statuses(), 0, 3, true );
			}
		}
		$out['wc-returned'] = itbenz_custom_statuses()['wc-returned'];
		return $out;
	}
);

// Keep the workflow statuses visible in WooCommerce Analytics / reports and editable.
add_filter(
	'woocommerce_reports_order_statuses',
	static function ( $statuses ) {
		return is_array( $statuses ) ? array_unique( array_merge( $statuses, array( 'confirmed', 'preparing', 'shipped' ) ) ) : $statuses;
	}
);
add_filter( 'wc_order_is_editable', static fn( $editable, $order ) => $editable || $order->has_status( array( 'confirmed' ) ), 10, 2 );

// Bulk actions in the orders list.
foreach ( array( 'edit-shop_order', 'woocommerce_page_wc-orders' ) as $screen ) {
	add_filter(
		"bulk_actions-{$screen}",
		static function ( array $actions ): array {
			foreach ( itbenz_custom_statuses() as $slug => $label ) {
				/* translators: %s: status */
				$actions[ 'mark_' . substr( $slug, 3 ) ] = sprintf( __( 'Passer en « %s »', 'itbenz' ), $label );
			}
			return $actions;
		}
	);
}

// Returned goods go back into stock (once).
add_action(
	'woocommerce_order_status_returned',
	static function ( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order && $order->get_data_store()->get_stock_reduced( $order_id ) ) {
			wc_increase_stock_levels( $order );
			$order->get_data_store()->set_stock_reduced( $order_id, false );
			$order->add_order_note( __( 'Commande retournée : stock réintégré.', 'itbenz' ) );
		}
	}
);

// Status colours in the admin list (same family as the storefront palette).
add_action(
	'admin_head',
	static function (): void {
		echo '<style>
		.order-status.status-processing{background:#d2e3fc;color:#113979}
		.order-status.status-confirmed{background:#e8f0fe;color:#113979}
		.order-status.status-preparing{background:#ffedb8;color:#5c4400}
		.order-status.status-shipped{background:#c58af9;color:#2a0b4a}
		.order-status.status-completed{background:#a8dab5;color:#073618}
		.order-status.status-returned{background:#f6d0d0;color:#980b0b}
		</style>';
	}
);

// Same labels in the orders list filters ("Nouvelle (3) | Livrée (12)…").
add_filter(
	'woocommerce_register_shop_order_post_statuses',
	static function ( array $statuses ): array {
		$rename = array(
			'wc-processing' => _x( 'Nouvelle', 'Order status', 'itbenz' ),
			'wc-completed'  => _x( 'Livrée', 'Order status', 'itbenz' ),
			'wc-cancelled'  => _x( 'Annulée', 'Order status', 'itbenz' ),
		);
		foreach ( $rename as $key => $label ) {
			if ( isset( $statuses[ $key ] ) ) {
				$statuses[ $key ]['label']       = $label;
				$statuses[ $key ]['label_count'] = _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'itbenz' ); // phpcs:ignore WordPress.WP.I18n
			}
		}
		return $statuses;
	}
);
