<?php
/**
 * One homepage collection (a product category):
 *   header  — serif title with the last word in italics + "Voir la collection" pill
 *   row 1   — large photo  |  coloured highlight card
 *   row 2   — 4 products
 *   row 3   — optional coloured card + photo pair (from a Bannière)
 */
defined( 'ABSPATH' ) || exit;
$term   = $args['term'];
$i      = (int) $args['index'];
$banner = $args['banner'] ?? null;

$products = wc_get_products(
	array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => 4,
		'category'   => array( $term->slug ),
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	)
);
if ( ! $products ) {
	return;
}

// "Souris & claviers" → "Souris &" + <em>claviers</em>
$words  = preg_split( '/\s+/u', trim( $term->name ) );
$last   = array_pop( $words );
$lead   = implode( ' ', $words );
$cover  = (int) get_term_meta( $term->term_id, 'itbenz_cover_id', true );
$cover  = $cover ?: (int) $products[0]->get_image_id();
$text   = (string) get_term_meta( $term->term_id, 'itbenz_highlight', true );
if ( '' === $text ) {
	/* translators: 1: number of products, 2: category name */
	$text = sprintf( _n( '%1$d produit disponible dans %2$s.', '%1$d produits disponibles dans %2$s.', (int) $term->count, 'itbenz' ), (int) $term->count, $term->name );
}
$color = array( 'butter', 'powder', 'mint', 'sky' )[ $i % 4 ];
?>
<section class="section wrap collection" aria-labelledby="col-<?php echo (int) $term->term_id; ?>">
	<div class="section__head">
		<div>
			<h2 class="section__title" id="col-<?php echo (int) $term->term_id; ?>"><?php echo esc_html( $lead ); ?> <em><?php echo esc_html( $last ); ?></em></h2>
			<?php if ( $term->description ) : ?><p class="section__sub"><?php echo esc_html( wp_strip_all_tags( $term->description ) ); ?></p><?php endif; ?>
		</div>
		<a class="pill pill--dark" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php esc_html_e( 'Voir la collection', 'itbenz' ); ?><?php echo itbenz_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>

	<div class="collection__lead">
		<a class="collection__photo" href="<?php echo esc_url( get_term_link( $term ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $cover ? wp_get_attachment_image( $cover, 'large', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 760px) 100vw, 600px', 'alt' => '' ) ) : ''; ?>
		</a>
		<div class="collection__card stage--<?php echo esc_attr( $color ); ?>">
			<p class="eyebrow"><?php echo esc_html( $term->name ); ?></p>
			<p class="collection__text"><?php echo esc_html( $text ); ?></p>
		</div>
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

	<?php
	if ( $banner ) {
		get_template_part( 'template-parts/feature-pair', null, array( 'banner' => $banner, 'reverse' => (bool) ( $i % 2 ) ) );
	}
	?>
</section>
