<?php
/**
 * Coloured editorial card + photo, side by side (alternating). Source: a "Bannière".
 */
defined( 'ABSPATH' ) || exit;
$b       = $args['banner'];
$reverse = ! empty( $args['reverse'] );
?>
<div class="feature-pair<?php echo $reverse ? ' feature-pair--reverse' : ''; ?>">
	<a class="feature-pair__card stage--<?php echo esc_attr( $b['color'] ); ?>" href="<?php echo esc_url( $b['url'] ?: '#' ); ?>">
		<?php if ( $b['eyebrow'] ) : ?><span class="eyebrow"><?php echo esc_html( $b['eyebrow'] ); ?></span><?php endif; ?>
		<span class="feature-pair__text"><?php echo esc_html( $b['title'] ); ?></span>
		<?php if ( $b['subtitle'] ) : ?><span class="feature-pair__sub"><?php echo esc_html( $b['subtitle'] ); ?></span><?php endif; ?>
	</a>
	<?php if ( $b['image_id'] ) : ?>
		<div class="feature-pair__media">
			<?php echo wp_get_attachment_image( $b['image_id'], 'itbenz-hero-sm', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 760px) 100vw, 600px' ) ); ?>
		</div>
	<?php endif; ?>
</div>
