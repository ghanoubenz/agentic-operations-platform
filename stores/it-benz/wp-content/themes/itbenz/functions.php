<?php
/**
 * IT-BENZ theme bootstrap.
 *
 * Presentation only. Orders, delivery, statuses and settings come from the
 * "IT-BENZ Commerce" plugin; the theme degrades gracefully if it is missing.
 */

defined( 'ABSPATH' ) || exit;

define( 'ITBENZ_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'itbenz', get_template_directory() . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 220, 'flex-width' => true, 'flex-height' => true ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 600,
				'single_image_width'    => 1000,
				'product_grid'          => array( 'default_columns' => 4, 'default_rows' => 4, 'min_columns' => 2, 'max_columns' => 4 ),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary' => __( 'Menu principal (catégories)', 'itbenz' ),
				'footer'  => __( 'Pied de page', 'itbenz' ),
			)
		);

		add_image_size( 'itbenz-hero', 1600, 900, true );
		add_image_size( 'itbenz-hero-sm', 800, 800, true );
		add_image_size( 'itbenz-tile', 400, 400, true );
	}
);

// Modern formats: WordPress (6.5+) generates WebP/AVIF sub-sizes when the server supports them.
add_filter(
	'image_editor_output_format',
	static function ( array $formats ): array {
		$avif = wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) );
		$to   = $avif ? 'image/avif' : 'image/webp';
		$formats['image/jpeg'] = $to;
		$formats['image/png']  = $to;
		return $formats;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$uri = get_template_directory_uri();
		wp_enqueue_style( 'itbenz', $uri . '/assets/css/main.css', array(), ITBENZ_THEME_VERSION );
		wp_enqueue_script( 'itbenz', $uri . '/assets/js/main.js', array(), ITBENZ_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

		// Block-library CSS is unused by this classic theme.
		if ( ! is_singular( 'post' ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'classic-theme-styles' );
		}
		// Keep the header cart counter fresh on cached pages.
		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-cart-fragments' );
		}
	},
	20
);

// Preload the two fonts used above the fold.
add_action(
	'wp_head',
	static function (): void {
		$base = get_template_directory_uri() . '/assets/fonts/';
		foreach ( array( 'inter-latin-wght-normal.woff2', 'crimson-pro-latin-wght-normal.woff2' ) as $f ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . $f ) );
		}
		echo '<meta name="theme-color" content="#202124">' . "\n";
	},
	1
);

// Keep WooCommerce's structural layout CSS, replace its colour/skin layer with ours.
add_filter(
	'woocommerce_enqueue_styles',
	static function ( array $styles ): array {
		unset( $styles['woocommerce-general'] );
		return $styles;
	}
);

require get_template_directory() . '/inc/helpers.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/seo.php';
