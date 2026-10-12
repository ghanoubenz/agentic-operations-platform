<?php
/**
 * Homepage — editorial layout (approved reference: centred serif headline over a wide
 * photo, colour category tiles, then repeating "collections").
 *
 * Content sources (all editable in the dashboard):
 *   Bannières → "Emplacement": top carousel, inspiration card, or "after collection X".
 *   "Visuel complet" banners (text drawn in the image) are shown whole, never cropped.
 *   Catégories          → collections (tick "Afficher sur l'accueil", photo, highlight text)
 * Every block hides itself when it has nothing real to show.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$has_banners = function_exists( 'itbenz_banners_for' );
$hero        = $has_banners ? itbenz_banners_for( 'hero' ) : array();
$inspire     = $has_banners ? itbenz_banners_for( 'inspire' ) : array();

// Ready-made visuals (title drawn in the image) → full-width carousel; otherwise the editorial hero.
if ( $hero && $hero[0]['full'] ) {
	get_template_part( 'template-parts/hero-carousel', null, array( 'banners' => $hero ) );
} else {
	get_template_part( 'template-parts/hero', null, array( 'banner' => $hero[0] ?? null ) );
}
get_template_part( 'template-parts/categories' );
if ( $inspire ) {
	get_template_part( 'template-parts/inspire', null, array( 'banner' => $inspire[0] ) );
}

if ( function_exists( 'wc_get_products' ) ) {
	$collections = function_exists( 'itbenz_home_collections' ) ? itbenz_home_collections( 5 ) : array();
	foreach ( $collections as $i => $term ) {
		get_template_part(
			'template-parts/collection',
			null,
			array(
				'term'   => $term,
				'index'  => $i,
				'banner' => $has_banners ? ( itbenz_banners_for( (string) $term->term_id )[0] ?? null ) : null,
			)
		);
	}
	get_template_part( 'template-parts/promo' );
	get_template_part(
		'template-parts/product-rail',
		null,
		array(
			'title'     => __( 'Les plus', 'itbenz' ),
			'accent'    => __( 'demandés', 'itbenz' ),
			'query'     => array( 'limit' => 4, 'orderby' => 'popularity', 'order' => 'DESC', 'stock_status' => 'instock' ),
			'min_sales' => 1,
		)
	);
	get_template_part(
		'template-parts/product-rail',
		null,
		array(
			'title'  => __( 'Nouveaux', 'itbenz' ),
			'accent' => __( 'arrivages', 'itbenz' ),
			'query'  => array( 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC' ),
		)
	);
}

get_template_part( 'template-parts/trust' );
get_footer();
