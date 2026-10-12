<?php
/**
 * Hero — centred display headline + subtitle, then a wide rounded photo.
 * Source: first published "Bannière". Falls back to the brand name.
 */
defined( 'ABSPATH' ) || exit;
$b = $args['banner'] ?? null;
?>
<section class="hero wrap">
	<div class="hero__head">
		<?php if ( $b && $b['eyebrow'] ) : ?><p class="eyebrow"><?php echo esc_html( $b['eyebrow'] ); ?></p><?php endif; ?>
		<h1 class="display"><?php echo esc_html( $b ? $b['title'] : 'IT-BENZ' ); ?></h1>
		<p class="lead">
			<?php echo esc_html( $b && $b['subtitle'] ? $b['subtitle'] : __( 'Matériel informatique, accessoires et équipements tech — paiement à la livraison.', 'itbenz' ) ); ?>
		</p>
		<?php
		$url   = $b && $b['url'] ? $b['url'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '' );
		$label = $b && $b['cta'] ? $b['cta'] : __( 'Voir la boutique', 'itbenz' );
		if ( $url ) :
			?>
			<a class="pill" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?><?php echo itbenz_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
	</div>
	<?php if ( $b && $b['image_id'] ) : ?>
		<figure class="hero__media stage--<?php echo esc_attr( $b['color'] ); ?>">
			<?php
			echo wp_get_attachment_image(
				$b['image_id'],
				'itbenz-hero',
				false,
				array(
					'sizes'         => '(max-width: 1232px) calc(100vw - 32px), 1200px',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'decoding'      => 'async',
				)
			);
			?>
		</figure>
	<?php endif; ?>
</section>
