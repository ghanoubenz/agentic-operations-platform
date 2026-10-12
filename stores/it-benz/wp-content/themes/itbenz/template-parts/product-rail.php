<?php
/**
 * Reusable product section. Args: title, accent (italic word), query (wc_get_products), min_sales.
 */
defined( 'ABSPATH' ) || exit;
$args  = wp_parse_args( $args ?? array(), array( 'title' => '', 'accent' => '', 'query' => array(), 'link' => '', 'min_sales' => 0 ) );
$query = array_merge( array( 'status' => 'publish', 'visibility' => 'catalog' ), $args['query'] );
$products = wc_get_products( $query );
if ( $args['min_sales'] ) {
	$products = array_filter( $products, static fn( $p ) => $p->get_total_sales() >= $args['min_sales'] );
}
if ( ! $products ) {
	return;
}
$link = $args['link'] ?: wc_get_page_permalink( 'shop' );
?>
<section class="section wrap">
	<div class="section__head">
		<h2 class="section__title"><?php echo esc_html( $args['title'] ); ?> <em><?php echo esc_html( $args['accent'] ); ?></em></h2>
		<a class="pill" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Tout voir', 'itbenz' ); ?><?php echo itbenz_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>
	<ul class="products columns-4">
	<?php
	foreach ( $products as $product ) {
		$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $GLOBALS['post'] );
		wc_get_template_part( 'content', 'product' );
	}
	wp_reset_postdata();
	?>
	</ul>
</section>
