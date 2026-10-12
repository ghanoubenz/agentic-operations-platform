<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap page-wrap center">
	<h1 class="display display--md">404</h1>
	<p class="lead"><?php esc_html_e( 'Cette page n’existe pas ou a été déplacée.', 'itbenz' ); ?></p>
	<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
		<p><a class="pill pill--accent" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Voir la boutique', 'itbenz' ); ?></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();
