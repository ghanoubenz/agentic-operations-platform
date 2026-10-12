<?php
/**
 * Site footer: only verified contact information from "IT-BENZ → Infos boutique".
 */
defined( 'ABSPATH' ) || exit;
$phone   = itbenz_opt( 'phone' );
$email   = itbenz_opt( 'email' );
$address = itbenz_opt( 'address' );
$hours   = itbenz_opt( 'hours' );
$wa      = itbenz_wa();
$socials = array_filter( array( 'Facebook' => itbenz_opt( 'facebook' ), 'Instagram' => itbenz_opt( 'instagram' ), 'TikTok' => itbenz_opt( 'tiktok' ) ) );
?>
</main>
<footer class="site-footer">
	<div class="wrap footer-grid">
		<div>
			<p class="brand__word footer-brand">IT<span>-</span>BENZ</p>
			<p class="muted"><?php bloginfo( 'description' ); ?></p>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Boutique', 'itbenz' ); ?></h2>
			<?php get_template_part( 'template-parts/nav-categories' ); ?>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Aide', 'itbenz' ); ?></h2>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'nav-list', 'depth' => 1 ) );
			}
			?>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Contact', 'itbenz' ); ?></h2>
			<ul class="nav-list contact-list">
				<?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo itbenz_icon( 'phone' ) . esc_html( $phone ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li><?php endif; ?>
				<?php if ( $wa ) : ?><li><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo itbenz_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>WhatsApp</a></li><?php endif; ?>
				<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
				<?php if ( $address ) : ?><li class="muted"><?php echo nl2br( esc_html( $address ) ); ?></li><?php endif; ?>
				<?php if ( $hours ) : ?><li class="muted"><?php echo esc_html( $hours ); ?></li><?php endif; ?>
			</ul>
			<?php if ( $socials ) : ?>
				<p class="socials">
				<?php foreach ( $socials as $label => $url ) : ?>
					<a class="pill" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<div class="wrap footer-bottom">
		<p class="muted">© <?php echo esc_html( gmdate( 'Y' ) ); ?> IT-BENZ — <?php esc_html_e( 'Prix en dinars algériens (DZD).', 'itbenz' ); ?></p>
	</div>
</footer>
<?php if ( $wa ) : ?>
	<a class="wa-float" href="<?php echo esc_url( itbenz_wa( __( 'Bonjour IT-BENZ, j’ai une question.', 'itbenz' ) ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Nous écrire sur WhatsApp', 'itbenz' ); ?>"><?php echo itbenz_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
