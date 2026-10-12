<?php
/**
 * "Inspiration" block: text on the left, coloured card with serif headline + photo on the right.
 */
defined( 'ABSPATH' ) || exit;
$b = $args['banner'];
?>
<section class="section wrap inspire">
	<div class="inspire__text">
		<?php if ( $b['eyebrow'] ) : ?><p class="eyebrow"><?php echo esc_html( $b['eyebrow'] ); ?></p><?php endif; ?>
		<h2 class="inspire__title"><?php echo esc_html( $b['subtitle'] ?: $b['title'] ); ?></h2>
		<?php if ( $b['cta'] && $b['url'] ) : ?>
			<a class="pill" href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['cta'] ); ?><?php echo itbenz_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
	</div>
	<a class="inspire__card stage--<?php echo esc_attr( $b['color'] ); ?>" href="<?php echo esc_url( $b['url'] ?: '#' ); ?>">
		<span class="inspire__big"><?php echo esc_html( $b['title'] ); ?></span>
		<?php
		if ( $b['image_id'] ) {
			echo wp_get_attachment_image( $b['image_id'], 'itbenz-hero-sm', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 760px) 100vw, 400px', 'alt' => '' ) );
		}
		?>
	</a>
</section>
