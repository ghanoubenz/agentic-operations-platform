<?php
/**
 * "Acheter par catégorie" — horizontal strip of colour-stage tiles.
 */
defined( 'ABSPATH' ) || exit;
$cats = itbenz_top_categories( 12 );
if ( ! $cats ) {
	return;
}
?>
<section class="section wrap" aria-labelledby="cat-title">
	<div class="section__head section__head--center">
		<h2 id="cat-title" class="section__title section__title--sm"><?php esc_html_e( 'Acheter par catégorie', 'itbenz' ); ?></h2>
	</div>
	<ul class="cat-strip">
	<?php foreach ( $cats as $i => $term ) : ?>
		<?php $thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true ); ?>
		<li>
			<a class="cat-tile" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<span class="cat-tile__stage stage--<?php echo esc_attr( itbenz_tile_color( $i ) ); ?>">
					<?php
					if ( $thumb ) {
						echo wp_get_attachment_image( $thumb, 'itbenz-tile', false, array( 'loading' => 'lazy', 'sizes' => '180px', 'alt' => '' ) );
					} else {
						echo '<span class="cat-tile__initial" aria-hidden="true">' . esc_html( mb_substr( $term->name, 0, 1 ) ) . '</span>';
					}
					?>
				</span>
				<span class="cat-tile__label"><?php echo esc_html( $term->name ); ?></span>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
</section>
