<?php
/**
 * Fallback template (blog posts / generic archives).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap page-wrap">
	<h1 class="page-title"><?php echo is_search() ? esc_html( sprintf( /* translators: %s query */ __( 'Résultats pour « %s »', 'itbenz' ), get_search_query() ) ) : wp_kses_post( get_the_archive_title() ?: get_bloginfo( 'name' ) ); ?></h1>
	<?php if ( have_posts() ) : ?>
		<div class="prose">
		<?php
		while ( have_posts() ) :
			the_post();
			printf( '<h2><a href="%s">%s</a></h2>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
			the_excerpt();
		endwhile;
		the_posts_pagination();
		?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Aucun résultat.', 'itbenz' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
