<?php
/**
 * Small template helpers. All shop data comes from the IT-BENZ Commerce plugin;
 * these wrappers keep the theme working (with empty output) if it is disabled.
 */

defined( 'ABSPATH' ) || exit;

function itbenz_opt( string $key ): string {
	return function_exists( 'itbenz_setting' ) ? itbenz_setting( $key ) : '';
}

function itbenz_wa( string $text = '' ): string {
	return function_exists( 'itbenz_whatsapp_url' ) ? itbenz_whatsapp_url( $text ) : '';
}

/** Inline SVG icons (no icon font, no extra request). */
function itbenz_icon( string $name ): string {
	$paths = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'cart'     => '<path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 8H6.2"/><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'whatsapp' => '<path d="M20 11.6a8.2 8.2 0 0 1-12.1 7.2L4 20l1.3-3.8A8.2 8.2 0 1 1 20 11.6Z"/><path d="M9 8.6c.2 2.9 2.4 5.3 5.4 5.9l1-1.2-1.6-.9-.8.7a4.4 4.4 0 0 1-2.4-2.4l.7-.8-.9-1.6-1.4.3Z"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'truck'    => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
		'shield'   => '<path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'return'   => '<path d="M4 10h11a5 5 0 0 1 0 10H8"/><path d="m8 6-4 4 4 4"/>',
		'chat'     => '<path d="M4 5h16v11H8l-4 4z"/>',
		'cash'     => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon icon-' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/** The seven category stage colours from the design system, assigned in order. */
function itbenz_tile_color( int $index ): string {
	$colors = array( 'royal', 'powder', 'forest', 'mint', 'butter', 'crimson', 'sky' );
	return $colors[ $index % count( $colors ) ];
}

/** Top-level product categories (excluding "Non classé"). */
function itbenz_top_categories( int $limit = 12 ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'number'     => $limit,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'orderby'    => 'meta_value_num',
			'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery -- tiny taxonomy, cached.
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

function itbenz_cart_count(): int {
	return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

// Never show menu links to unpublished pages (e.g. policy drafts still being written).
add_filter(
	'wp_nav_menu_objects',
	static function ( array $items ): array {
		return array_values(
			array_filter(
				$items,
				static fn( $item ) => 'post_type' !== $item->type || 'publish' === get_post_status( (int) $item->object_id )
			)
		);
	}
);
