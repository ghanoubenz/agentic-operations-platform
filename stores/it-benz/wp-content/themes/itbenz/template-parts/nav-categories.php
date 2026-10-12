<?php
/**
 * Category navigation: uses the "Menu principal" menu when set, else top-level product categories.
 */
defined( 'ABSPATH' ) || exit;
if ( has_nav_menu( 'primary' ) ) {
	wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-list', 'depth' => 1 ) );
	return;
}
echo '<ul class="nav-list">';
if ( function_exists( 'wc_get_page_permalink' ) ) {
	printf( '<li><a href="%s">%s</a></li>', esc_url( wc_get_page_permalink( 'shop' ) ), esc_html__( 'Tous les produits', 'itbenz' ) );
}
foreach ( itbenz_top_categories( 10 ) as $term ) {
	printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
}
echo '</ul>';
