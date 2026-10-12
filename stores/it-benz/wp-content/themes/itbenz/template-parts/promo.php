<?php
/**
 * Promotions: crimson editorial card + products currently on sale.
 */
defined( 'ABSPATH' ) || exit;
$ids = array_slice( wc_get_product_ids_on_sale(), 0, 40 );
if ( ! $ids ) {
	return;
}
$products = wc_get_products( array( 'include' => $ids, 'limit' => 3, 'status' => 'publish', 'visibility' => 'catalog', 'stock_status' => 'instock' ) );
if ( ! $products ) {
	return;
}
?>
<section class="section wrap">
	<div class="section__head">
		<h2 class="section__title"><?php esc_html_e( 'Les offres de la', 'itbenz' ); ?> <em><?php esc_html_e( 'semaine', 'itbenz' ); ?></em></h2>
		<a class="pill" href="<?php echo esc_url( add_query_arg( 'promo', '1', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Toutes les promotions', 'itbenz' ); ?><?php echo itbenz_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>
	<div class="promo-grid">
		<div class="stat-card stage--crimson">
			<p class="eyebrow"><?php esc_html_e( 'Promotions', 'itbenz' ); ?></p>
			<p class="stat-card__big">
				<?php
				/* translators: %d: number of products on sale */
				echo esc_html( sprintf( _n( '%d produit en promotion', '%d produits en promotion', count( $ids ), 'itbenz' ), count( $ids ) ) );
				?>
			</p>
			<p><?php esc_html_e( 'Prix barrés valables dans la limite des stocks disponibles.', 'itbenz' ); ?></p>
		</div>
		<ul class="products columns-3">
		<?php
		foreach ( $products as $product ) {
			$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			setup_postdata( $GLOBALS['post'] );
			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
		?>
		</ul>
	</div>
</section>
