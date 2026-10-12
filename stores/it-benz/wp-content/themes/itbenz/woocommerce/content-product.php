<?php
/**
 * Product card (overrides woocommerce/templates/content-product.php).
 *
 * @version 9.4.0
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
$brands = get_the_terms( $product->get_id(), 'product_brand' );
?>
<li <?php wc_product_class( 'card', $product ); ?>>
	<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( $product->is_on_sale() ) {
			echo apply_filters( 'woocommerce_sale_flash', '', get_post(), $product ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'sizes' => '(max-width: 600px) 50vw, 280px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</a>
	<div class="card__body">
		<?php if ( $brands && ! is_wp_error( $brands ) ) : ?>
			<p class="card__brand"><?php echo esc_html( $brands[0]->name ); ?></p>
		<?php endif; ?>
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<div class="card__price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<p class="card__stock"><?php echo itbenz_stock_label( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>
