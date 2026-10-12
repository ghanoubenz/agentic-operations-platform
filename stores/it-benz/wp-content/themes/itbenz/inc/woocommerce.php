<?php
/**
 * WooCommerce presentation hooks.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	function itbenz_cart_badge(): void {}
	return;
}

/* ---------- Layout wrappers ---------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_action( 'woocommerce_before_main_content', static fn() => print( '<div class="wrap shop-wrap">' ), 10 );
add_action( 'woocommerce_after_main_content', static fn() => print( '</div>' ), 10 );

add_filter(
	'woocommerce_breadcrumb_defaults',
	static fn( $d ) => array_merge( $d, array( 'delimiter' => '<span aria-hidden="true"> / </span>', 'home' => __( 'Accueil', 'itbenz' ) ) )
);

add_filter( 'loop_shop_columns', static fn() => 4 );
add_filter( 'loop_shop_per_page', static fn() => 16 );

/* ---------- "?promo=1" on the shop = products on sale ---------- */
add_action(
	'woocommerce_product_query',
	static function ( WP_Query $q ): void {
		if ( ! empty( $_GET['promo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'post__in', array_merge( array( 0 ), wc_get_product_ids_on_sale() ) );
		}
	}
);

/* ---------- Header cart badge (kept fresh by cart fragments) ---------- */
function itbenz_cart_badge(): void {
	$n = itbenz_cart_count();
	printf( '<span class="cart-count%s" aria-live="polite">%s</span>', $n ? '' : ' is-empty', $n ? (int) $n : '' );
}
add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( array $fragments ): array {
		ob_start();
		itbenz_cart_badge();
		$fragments['span.cart-count'] = ob_get_clean();
		return $fragments;
	}
);

/* ---------- Product card ---------- */
// Card markup lives in woocommerce/content-product.php. Stock line under price:
function itbenz_stock_label( WC_Product $product ): string {
	if ( ! $product->is_in_stock() ) {
		return '<span class="stock out">' . esc_html__( 'Rupture de stock', 'itbenz' ) . '</span>';
	}
	if ( $product->is_on_backorder() ) {
		return '<span class="stock backorder">' . esc_html__( 'Sur commande', 'itbenz' ) . '</span>';
	}
	$qty = $product->get_stock_quantity();
	if ( $product->managing_stock() && null !== $qty && $qty <= (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ) ) {
		/* translators: %d: stock quantity */
		return '<span class="stock low">' . esc_html( sprintf( __( 'Plus que %d en stock', 'itbenz' ), $qty ) ) . '</span>';
	}
	return '<span class="stock in">' . esc_html__( 'En stock', 'itbenz' ) . '</span>';
}

add_filter(
	'woocommerce_sale_flash',
	static function ( $html, $post, $product ) {
		$pct = 0;
		if ( $product instanceof WC_Product && $product->is_type( 'simple' ) && (float) $product->get_regular_price() > 0 ) {
			$pct = (int) round( 100 - ( (float) $product->get_sale_price() / (float) $product->get_regular_price() * 100 ) );
		}
		return '<span class="onsale">' . ( $pct > 0 ? esc_html( '-' . $pct . '%' ) : esc_html__( 'Promo', 'itbenz' ) ) . '</span>';
	},
	10,
	3
);

add_filter( 'woocommerce_product_add_to_cart_text', static function ( $text, $product ) {
	if ( $product->is_type( 'simple' ) ) {
		return $product->is_purchasable() && $product->is_in_stock() ? __( 'Ajouter au panier', 'itbenz' ) : __( 'Voir le produit', 'itbenz' );
	}
	return $product->is_type( 'variable' ) ? __( 'Choisir les options', 'itbenz' ) : $text;
}, 10, 2 );

/* ---------- Single product ---------- */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

// Brand above the title.
add_action( 'woocommerce_single_product_summary', static function (): void {
	$brands = get_the_terms( get_the_ID(), 'product_brand' );
	if ( $brands && ! is_wp_error( $brands ) ) {
		printf( '<p class="eyebrow"><a href="%s">%s</a></p>', esc_url( get_term_link( $brands[0] ) ), esc_html( $brands[0]->name ) );
	}
}, 4 );

// Key facts + stock under the price.
add_action( 'woocommerce_single_product_summary', static function (): void {
	global $product;
	echo '<div class="pdp-facts">' . itbenz_stock_label( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
	if ( $product->get_sku() ) {
		echo '<span class="muted">' . esc_html__( 'SKU', 'itbenz' ) . ' : ' . esc_html( $product->get_sku() ) . '</span>';
	}
	if ( $model = $product->get_meta( '_itbenz_model' ) ) {
		echo '<span class="muted">' . esc_html__( 'Modèle', 'itbenz' ) . ' : ' . esc_html( $model ) . '</span>';
	}
	echo '</div>';
}, 15 );

// Service box under add-to-cart: COD + real policies only.
add_action( 'woocommerce_single_product_summary', static function (): void {
	global $product;
	$rows = array( array( 'cash', __( 'Paiement à la livraison', 'itbenz' ) ) );
	if ( $w = $product->get_meta( '_itbenz_warranty' ) ) {
		$rows[] = array( 'shield', __( 'Garantie', 'itbenz' ) . ' : ' . $w );
	}
	if ( $d = itbenz_opt( 'delivery' ) ) {
		$rows[] = array( 'truck', $d );
	}
	echo '<ul class="pdp-service">';
	foreach ( $rows as [ $icon, $text ] ) {
		echo '<li>' . itbenz_icon( $icon ) . '<span>' . esc_html( $text ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
	$wa = itbenz_wa( sprintf( /* translators: 1: product, 2: url */ __( 'Bonjour, je suis intéressé(e) par : %1$s — %2$s', 'itbenz' ), $product->get_name(), get_permalink() ) );
	if ( $wa ) {
		printf( '<a class="pill pill--wa pdp-wa" href="%s" target="_blank" rel="noopener">%s%s</a>', esc_url( $wa ), itbenz_icon( 'whatsapp' ), esc_html__( 'Une question ? WhatsApp', 'itbenz' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 35 );

// Tabs: Description + "Caractéristiques techniques" (spec table) instead of "Informations complémentaires".
add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		unset( $tabs['additional_information'] );
		global $product;
		if ( function_exists( 'itbenz_product_specs' ) && $product && itbenz_product_specs( $product ) ) {
			$tabs['itbenz_specs'] = array(
				'title'    => __( 'Caractéristiques techniques', 'itbenz' ),
				'priority' => 5,
				'callback' => 'itbenz_render_specs',
			);
		}
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['title'] = __( 'Description', 'itbenz' );
		}
		return $tabs;
	},
	20
);

function itbenz_render_specs(): void {
	global $product;
	echo '<table class="spec-table"><tbody>';
	foreach ( itbenz_product_specs( $product ) as $label => $value ) {
		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
	}
	echo '</tbody></table>';
}

add_filter( 'woocommerce_output_related_products_args', static fn( $a ) => array_merge( $a, array( 'posts_per_page' => 4, 'columns' => 4 ) ) );
add_filter( 'woocommerce_product_related_products_heading', static fn() => __( 'Produits similaires', 'itbenz' ) );

/* ---------- Cart & checkout ---------- */
// Reassurance above the payment button.
add_action( 'woocommerce_review_order_before_submit', static function (): void {
	echo '<p class="checkout-note">' . itbenz_icon( 'cash' ) . esc_html__( 'Vous payez en espèces à la livraison. Nous pouvons vous appeler pour confirmer la commande.', 'itbenz' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
} );
