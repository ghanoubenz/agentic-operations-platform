<?php
/**
 * Homepage — editorial layout (approved reference: centred serif headline over a wide
 * photo, colour category tiles, then repeating "collections").
 *
 * Content sources (all editable in the dashboard):
 *   Bannières #1        → hero headline, subtitle, button, photo
 *   Bannières #2        → "inspiration" card under the categories
 *   Bannières #3+       → coloured card + photo pair closing each collection
 *   Catégories          → collections (tick "Afficher sur l'accueil", photo, highlight text)
 * Every block hides itself when it has nothing real to show.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$banners = function_exists( 'itbenz_get_banners' ) ? itbenz_get_banners( 10 ) : array();
$hero    = array_shift( $banners );
$inspire = array_shift( $banners );

get_template_part( 'template-parts/hero', null, array( 'banner' => $hero ) );
get_template_part( 'template-parts/categories' );
if ( $inspire ) {
	get_template_part( 'template-parts/inspire', null, array( 'banner' => $inspire ) );
}

if ( function_exists( 'wc_get_products' ) ) {
	$collections = function_exists( 'itbenz_home_collections' ) ? itbenz_home_collections( 4 ) : array();
	foreach ( $collections as $i => $term ) {
		get_template_part(
			'template-parts/collection',
			null,
			array(
				'term'   => $term,
				'index'  => $i,
				'banner' => $banners[ $i ] ?? null,
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
