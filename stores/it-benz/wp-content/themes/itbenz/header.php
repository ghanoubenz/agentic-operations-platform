<?php
/**
 * Site header: announcement, logo, search, categories, WhatsApp, account, cart.
 */
defined( 'ABSPATH' ) || exit;
$announcement = itbenz_opt( 'announcement' );
$wa           = itbenz_wa( __( 'Bonjour IT-BENZ, j’ai une question.', 'itbenz' ) );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Aller au contenu', 'itbenz' ); ?></a>

<?php if ( $announcement ) : ?>
	<div class="announce"><p><?php echo esc_html( $announcement ); ?></p></div>
<?php endif; ?>

<header class="site-header">
	<div class="wrap header-row">
		<details class="mobile-nav">
			<summary aria-label="<?php esc_attr_e( 'Menu', 'itbenz' ); ?>"><?php echo itbenz_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
			<nav class="mobile-nav__panel" aria-label="<?php esc_attr_e( 'Catégories', 'itbenz' ); ?>">
				<?php get_template_part( 'template-parts/nav-categories' ); ?>
			</nav>
		</details>

		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( has_custom_logo() ) {
				echo wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'brand__logo', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager' ) );
			} else {
				echo '<span class="brand__word">IT<span>-</span>BENZ</span>';
			}
			?>
		</a>

		<form class="search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="site-search"><?php esc_html_e( 'Rechercher un produit', 'itbenz' ); ?></label>
			<input id="site-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Souris, clavier, écran…', 'itbenz' ); ?>">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="<?php esc_attr_e( 'Rechercher', 'itbenz' ); ?>"><?php echo itbenz_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</form>

		<div class="header-actions">
			<?php if ( $wa ) : ?>
				<a class="pill pill--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Nous écrire sur WhatsApp', 'itbenz' ); ?>"><?php echo itbenz_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>WhatsApp</span></a>
			<?php endif; ?>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="icon-btn" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'Mon compte', 'itbenz' ); ?>"><?php echo itbenz_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="icon-btn cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Panier', 'itbenz' ); ?>">
					<?php echo itbenz_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php itbenz_cart_badge(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<nav class="wrap cat-nav" aria-label="<?php esc_attr_e( 'Catégories', 'itbenz' ); ?>">
		<?php get_template_part( 'template-parts/nav-categories' ); ?>
	</nav>
</header>
<main id="main" class="site-main">
