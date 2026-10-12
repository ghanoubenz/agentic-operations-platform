<?php
/**
 * Minimal SEO without an SEO plugin: meta description, Open Graph, canonical is core.
 * WooCommerce already outputs Product JSON-LD on product pages.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		$desc  = get_bloginfo( 'description' );
		$image = '';
		if ( function_exists( 'is_product' ) && is_product() ) {
			$product = wc_get_product( get_the_ID() );
			$desc    = $product ? wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ) : $desc;
			$image   = $product ? (string) wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : '';
		} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$desc = wp_strip_all_tags( term_description() ) ?: single_term_title( '', false );
		}
		$desc = wp_trim_words( $desc, 30, '…' );
		if ( $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		echo '<meta property="og:locale" content="fr_DZ">' . "\n";
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
	},
	5
);

// Do not index cart / checkout / account pages.
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}
);
