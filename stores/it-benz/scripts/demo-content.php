<?php
/**
 * STAGING DEMO CONTENT — every item is labelled "EXEMPLE" and must be deleted before launch:
 *   wp post delete $(wp post list --post_type=product,itbenz_banner --meta_key=_itbenz_demo --format=ids) --force
 * Prices, stock and delivery fees below are TEST values, not IT-BENZ prices.
 */

if ( 'production' === wp_get_environment_type() ) {
	WP_CLI::error( 'Demo content is not allowed on production.' );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/** Generate a clearly-marked placeholder image and attach it to the media library. */
function itbenz_demo_image( string $label, string $bg, int $w, int $h, string $fg = '#e8e8e8' ): int {
	$im  = imagecreatetruecolor( $w, $h );
	$rgb = static fn( $hex ) => array_map( 'hexdec', str_split( ltrim( $hex, '#' ), 2 ) );
	[ $r, $g, $b ] = $rgb( $bg );
	imagefill( $im, 0, 0, imagecolorallocate( $im, $r, $g, $b ) );
	[ $r, $g, $b ] = $rgb( $fg );
	$fgc = imagecolorallocate( $im, $r, $g, $b );
	// Simple device silhouette so cards look like products, not empty boxes.
	$cx = intdiv( $w, 2 );
	$cy = intdiv( $h, 2 ) - intdiv( $h, 12 );
	imagesetthickness( $im, max( 2, intdiv( $w, 300 ) ) );
	imagerectangle( $im, $cx - intdiv( $w, 5 ), $cy - intdiv( $h, 7 ), $cx + intdiv( $w, 5 ), $cy + intdiv( $h, 7 ), $fgc );
	imageline( $im, $cx - intdiv( $w, 4 ), $cy + intdiv( $h, 7 ) + 8, $cx + intdiv( $w, 4 ), $cy + intdiv( $h, 7 ) + 8, $fgc );
	$text = strtoupper( 'EXEMPLE - ' . remove_accents( $label ) );
	$font = 5;
	$tw   = imagefontwidth( $font ) * strlen( $text );
	imagestring( $im, $font, max( 4, intdiv( $w - $tw, 2 ) ), $h - 40, $text, $fgc );
	$tmp = wp_tempnam( 'itbenz-demo' ) . '.png';
	imagepng( $im, $tmp );
	imagedestroy( $im );
	$file = array( 'name' => sanitize_file_name( 'exemple-' . $label . '.png' ), 'tmp_name' => $tmp );
	$id   = media_handle_sideload( $file, 0, 'EXEMPLE — ' . $label );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_itbenz_demo', 1 );
	update_post_meta( $id, '_wp_attachment_image_alt', 'Image d’exemple — ' . $label );
	return (int) $id;
}

$products = array(
	// name, category, regular, sale, stock, featured, attributes
	array( 'Souris sans fil', 'souris-claviers', 3500, 2900, 12, true, array( 'connexion' => 'Sans fil 2,4 GHz', 'dpi' => '1600' ) ),
	array( 'Clavier mécanique AZERTY', 'souris-claviers', 9900, 0, 6, true, array( 'disposition' => 'AZERTY (FR)', 'switch' => 'Exemple de switch', 'connexion' => 'USB' ) ),
	array( 'Écran 27 pouces QHD', 'ecrans', 54000, 49000, 3, true, array( 'taille-ecran' => '27"', 'resolution' => '2560 × 1440', 'frequence' => '165 Hz', 'dalle' => 'IPS', 'connectique' => 'HDMI, DisplayPort' ) ),
	array( 'Casque gaming', 'casques-audio', 7800, 0, 0, false, array( 'connexion' => 'Jack 3,5 mm' ) ),
	array( 'SSD NVMe 1 To', 'stockage', 14500, 0, 9, true, array( 'capacite' => '1 To', 'interface' => 'PCIe NVMe' ) ),
	array( 'Câble HDMI 2 m', 'cables-adaptateurs', 900, 0, 40, false, array( 'connectique' => 'HDMI' ) ),
	array( 'Routeur Wi-Fi', 'reseau', 8900, 7900, 2, false, array( 'connexion' => 'Wi-Fi' ) ),
	array( 'Tapis de souris XL', 'gaming', 2200, 0, 25, false, array() ),
	array( 'Pack bureau clavier + souris', 'packs', 5200, 4600, 8, false, array( 'connexion' => 'Sans fil' ) ),
);

$brand_term = term_exists( 'Marque Exemple', 'product_brand' ) ?: wp_insert_term( 'Marque Exemple', 'product_brand' );

foreach ( $products as $i => [ $name, $cat, $regular, $sale, $stock, $featured, $attrs ] ) {
	$title = '[EXEMPLE] ' . $name;
	if ( get_page_by_title( $title, OBJECT, 'product' ) ) { // phpcs:ignore WordPress.WP.DeprecatedFunctions
		continue;
	}
	$p = new WC_Product_Simple();
	$p->set_name( $title );
	$p->set_status( 'publish' );
	$p->set_regular_price( (string) $regular );
	if ( $sale ) {
		$p->set_sale_price( (string) $sale );
	}
	$p->set_manage_stock( true );
	$p->set_stock_quantity( $stock );
	$p->set_featured( $featured );
	$p->set_sku( 'DEMO-' . str_pad( (string) ( $i + 1 ), 3, '0', STR_PAD_LEFT ) );
	$p->set_short_description( 'Produit d’exemple pour tester la boutique. Prix, stock et caractéristiques fictifs — à supprimer avant la mise en ligne.' );
	$p->set_description( '<p>Fiche d’exemple. Remplacez par la description réelle fournie par le fabricant.</p>' );
	$term = get_term_by( 'slug', $cat, 'product_cat' );
	if ( $term ) {
		$p->set_category_ids( array( $term->term_id ) );
	}
	$wc_attrs = array();
	foreach ( $attrs as $slug => $value ) {
		$tax = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'product' );
		}
		$t = term_exists( $value, $tax ) ?: wp_insert_term( $value, $tax );
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
		$a->set_name( $tax );
		$a->set_options( array( (int) $t['term_id'] ) );
		$a->set_visible( true );
		$wc_attrs[] = $a;
	}
	$p->set_attributes( $wc_attrs );
	$p->update_meta_data( '_itbenz_model', 'EX-' . ( 1000 + $i ) );
	$p->update_meta_data( '_itbenz_demo', 1 );
	$p->set_image_id( itbenz_demo_image( $name, '#ffffff', 1000, 1000, '#45474c' ) );
	$id = $p->save();
	if ( ! is_wp_error( $brand_term ) ) {
		wp_set_object_terms( $id, (int) $brand_term['term_id'], 'product_brand' );
	}
	WP_CLI::log( "  product #$id $title" );
}

// Category tile images (placeholders).
foreach ( array( 'souris-claviers', 'ecrans', 'casques-audio', 'stockage' ) as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
		update_term_meta( $term->term_id, 'thumbnail_id', itbenz_demo_image( $term->name, '#ffffff', 600, 600, '#45474c' ) );
	}
}

// Order matters: #1 = hero, #2 = inspiration card, #3+ = card/photo pairs closing each collection.
$banners = array(
	array( 'Setup Gaming', 'EXEMPLE — BANNIÈRE PRINCIPALE', 'Bannière d’exemple : remplacez le titre, le texte et la photo depuis « Bannières ».', 'onyx', '#202124', 'gaming' ),
	array( 'Composez votre setup', 'EXEMPLE — INSPIRATION', 'Besoin d’un conseil pour choisir ? Écrivez-nous, nous vous aidons.', 'royal', '#113979', '' ),
	array( 'Bureau : écrans, claviers et souris pour mieux travailler.', 'EXEMPLE', '', 'forest', '#073618', 'ecrans' ),
	array( 'Les offres de la semaine, dans la limite des stocks.', 'EXEMPLE', '', 'crimson', '#980b0b', '' ),
	array( 'Stockage rapide pour tous vos fichiers.', 'EXEMPLE', '', 'royal', '#113979', 'stockage' ),
);
foreach ( get_posts( array( 'post_type' => 'itbenz_banner', 'numberposts' => -1, 'post_status' => 'any', 'meta_key' => '_itbenz_demo' ) ) as $old ) { // phpcs:ignore WordPress.DB.SlowDBQuery
	wp_delete_post( $old->ID, true );
}
foreach ( $banners as $i => [ $title, $eyebrow, $sub, $color, $hex, $cat ] ) {
	$id = wp_insert_post( array( 'post_type' => 'itbenz_banner', 'post_status' => 'publish', 'post_title' => $title, 'menu_order' => $i ) );
	update_post_meta( $id, '_itbenz_eyebrow', $eyebrow );
	update_post_meta( $id, '_itbenz_subtitle', $sub );
	update_post_meta( $id, '_itbenz_cta', 'Découvrir' );
	update_post_meta( $id, '_itbenz_url', $cat ? get_term_link( $cat, 'product_cat' ) : add_query_arg( 'promo', 1, wc_get_page_permalink( 'shop' ) ) );
	update_post_meta( $id, '_itbenz_color', $color );
	update_post_meta( $id, '_itbenz_demo', 1 );
	set_post_thumbnail( $id, itbenz_demo_image( 'photo ' . ( $i + 1 ), '#dadce0', 1600, 1100, '#5f6368' ) );
}
// Homepage collections (placeholder cover photos).
foreach ( array( 'souris-claviers', 'ecrans', 'stockage' ) as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		update_term_meta( $term->term_id, 'itbenz_home', 1 );
		if ( ! get_term_meta( $term->term_id, 'itbenz_cover_id', true ) ) {
			update_term_meta( $term->term_id, 'itbenz_cover_id', itbenz_demo_image( 'collection ' . $term->name, '#dadce0', 1200, 1200, '#5f6368' ) );
		}
	}
}
delete_transient( 'itbenz_banners' );

// TEST delivery fees (not real carrier prices) so checkout can be exercised end-to-end.
if ( ! get_option( ITBENZ_Delivery_Rates::OPTION ) ) {
	$rates = array();
	foreach ( array_keys( ITBENZ_Geo::wilayas() ) as $state ) {
		$rates[ $state ] = array( 'home' => '600', 'desk' => '400' );
	}
	$rates['DZ-33'] = array( 'home' => '', 'desk' => '' ); // Illizi: left empty to test "not available" path.
	update_option( ITBENZ_Delivery_Rates::OPTION, $rates, false );
}

// Demo shop info so header/footer elements render (example values, clearly fake).
if ( ! get_option( 'itbenz_settings' ) ) {
	update_option( 'itbenz_settings', array(
		'announcement' => 'EXEMPLE — Paiement à la livraison · bandeau modifiable dans IT-BENZ → Infos boutique',
		'whatsapp'     => '213000000000',
		'phone'        => '0X XX XX XX XX (exemple)',
	) );
}
WP_CLI::success( 'Demo content ready (all items marked EXEMPLE).' );
