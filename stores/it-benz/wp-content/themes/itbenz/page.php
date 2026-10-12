<?php
/**
 * Pages (cart, checkout, account, policies…).
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article class="wrap page-wrap">
		<h1 class="page-title"><?php the_title(); ?></h1>
		<div class="prose"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
