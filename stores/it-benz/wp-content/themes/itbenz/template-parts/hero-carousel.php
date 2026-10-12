<?php
/**
 * Top carousel of ready-made banner visuals (16:9, title drawn in the image).
 * CSS scroll-snap; JS only adds dots sync + gentle autoplay. First image loads eagerly.
 */
defined( 'ABSPATH' ) || exit;
$banners = $args['banners'];
$count   = count( $banners );
?>
<section class="hero-carousel wrap" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Offres du moment', 'itbenz' ); ?>">
	<h1 class="screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) . ' — ' . get_bloginfo( 'description' ) ); ?></h1>
	<div class="hero-carousel__track" data-carousel>
	<?php foreach ( $banners as $i => $b ) : ?>
		<a class="hero-carousel__slide" id="slide-<?php echo (int) $i; ?>" href="<?php echo esc_url( $b['url'] ?: '#' ); ?>" aria-roledescription="slide" aria-label="<?php echo esc_attr( $b['title'] . ' (' . ( $i + 1 ) . '/' . $count . ')' ); ?>">
			<?php
			echo wp_get_attachment_image(
				$b['image_id'],
				'full',
				false,
				array(
					'alt'           => $b['title'],
					'sizes'         => '(max-width: 1232px) calc(100vw - 32px), 1200px',
					'loading'       => 0 === $i ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $i ? 'high' : 'auto',
					'decoding'      => 'async',
				)
			);
			?>
		</a>
	<?php endforeach; ?>
	</div>
	<?php if ( $count > 1 ) : ?>
		<div class="hero-carousel__dots">
			<?php foreach ( $banners as $i => $b ) : ?>
				<button type="button" data-slide="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( $b['title'] ); ?>" <?php echo 0 === $i ? 'aria-current="true"' : ''; ?>></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
