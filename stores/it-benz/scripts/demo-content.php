<?php
/**
 * SAMPLE CATALOGUE (staging / presentation only).
 *
 * Fills the store with realistic-looking products so the design can be judged:
 * photos come from the owner's IT-BENZ banners (data/sample/), prices/stock are
 * SAMPLE values chosen by the owner's request — not real IT-BENZ prices.
 * Everything created here carries the meta "_itbenz_demo" and can be removed with:
 *   wp post delete $(wp post list --post_type=product,itbenz_banner --meta_key=_itbenz_demo --format=ids) --force
 *
 * Idempotent: previous sample products and banners are replaced on each run.
 */

if ( 'production' === wp_get_environment_type() ) {
	WP_CLI::error( 'Sample content is not allowed on production.' );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

const ITBENZ_SAMPLE_DIR = '/data/sample/';

/** Import a local file into the media library once (matched by file name). */
function itbenz_sample_media( string $rel, string $alt ): int {
	$existing = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_itbenz_sample_src', 'meta_value' => $rel, 'numberposts' => 1, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $existing ) {
		return (int) $existing[0];
	}
	$tmp = wp_tempnam( basename( $rel ) );
	copy( ITBENZ_SAMPLE_DIR . $rel, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $rel ), 'tmp_name' => $tmp ), 0, $alt );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $rel . ': ' . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_itbenz_sample_src', $rel );
	update_post_meta( $id, '_itbenz_demo', 1 );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

function itbenz_cat_id( string $slug, string $name = '' ): int {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name ?: $slug, 'product_cat', array( 'slug' => $slug ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

// ---- Clean previous sample content -------------------------------------------------
foreach ( get_posts( array( 'post_type' => array( 'product', 'itbenz_banner' ), 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_itbenz_demo', 'fields' => 'ids' ) ) as $old ) { // phpcs:ignore WordPress.DB.SlowDBQuery
	wp_delete_post( $old, true );
}

foreach ( get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'meta_key' => '_itbenz_demo', 'fields' => 'ids' ) ) as $att ) { // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! get_post_meta( $att, '_itbenz_sample_src', true ) ) {
		wp_delete_attachment( $att, true ); // generated grey placeholders from the first demo
	}
}

// Extra categories used by the sample catalogue.
itbenz_cat_id( 'bureautique', 'Bureautique & impression' );
itbenz_cat_id( 'accessoires', 'Accessoires & mobilité' );
itbenz_cat_id( 'coffrets-cadeaux', 'Coffrets cadeaux' );

$brand = term_exists( 'IT-BENZ', 'product_brand' ) ?: wp_insert_term( 'IT-BENZ', 'product_brand' );

// ---- Products ------------------------------------------------------------------------
// [ name, photo, categories, regular DA, sale DA, stock, featured, short description, attributes ]
$products = array(
	array( 'Disque SSD externe 1 To USB-C', 'ssd-externe', array( 'stockage' ), 15900, 13900, 14, true, 'SSD portable compact, coque texturée, transferts rapides en USB-C.', array( 'capacite' => '1 To', 'interface' => 'USB-C 3.2', 'compatibilite' => 'Windows, macOS, Android' ) ),
	array( 'Carte SD 256 Go V30', 'carte-sd-256', array( 'stockage' ), 4900, 0, 30, false, 'Carte SDXC pour appareils photo, drones et consoles.', array( 'capacite' => '256 Go', 'interface' => 'SDXC UHS-I U3 V30' ) ),
	array( 'Clé USB métal 64 Go', 'cle-usb-metal', array( 'stockage' ), 1800, 1500, 60, false, 'Clé USB en aluminium avec anneau porte-clés.', array( 'capacite' => '64 Go', 'interface' => 'USB 3.0' ) ),
	array( 'Hub USB-C 7-en-1 HDMI 4K', 'hub-usb-c-7en1', array( 'cables-adaptateurs' ), 6500, 5400, 18, true, 'HDMI 4K, 3 × USB, USB-C PD et Ethernet dans un seul adaptateur.', array( 'connectique' => 'HDMI, USB-A, USB-C, RJ45', 'compatibilite' => 'PC portables USB-C, MacBook' ) ),
	array( 'Câble USB-C tressé 1 m', 'cable-usb-c-tresse', array( 'cables-adaptateurs' ), 1200, 0, 80, false, 'Câble USB-C vers USB-C gainé nylon, charge rapide.', array( 'connectique' => 'USB-C / USB-C' ) ),
	array( 'Point d’accès Wi-Fi 6 mesh', 'point-acces-wifi', array( 'reseau' ), 9800, 0, 7, false, 'Étend la couverture Wi-Fi de la maison ou du bureau.', array( 'connexion' => 'Wi-Fi 6', 'connectique' => 'RJ45' ) ),
	array( 'Routeur Wi-Fi 6 bi-bande', 'routeur-wifi', array( 'reseau' ), 12500, 10900, 9, true, 'Routeur 4 antennes pour connexion fibre ou ADSL.', array( 'connexion' => 'Wi-Fi 6 bi-bande', 'connectique' => 'RJ45' ) ),
	array( 'Imprimante multifonction Wi-Fi', 'imprimante', array( 'bureautique' ), 32000, 29500, 5, true, 'Impression, copie et scan, connexion Wi-Fi et USB.', array( 'connexion' => 'Wi-Fi, USB' ) ),
	array( 'Ramette papier A4 70 g (500 feuilles)', 'ramette-a4', array( 'bureautique' ), 1100, 0, 120, false, 'Papier blanc A4 pour imprimantes laser et jet d’encre.', array() ),
	array( 'Cartouches d’encre — multipack 4 couleurs', 'cartouches', array( 'bureautique' ), 4200, 0, 25, false, 'Noir, cyan, magenta et jaune.', array() ),
	array( 'Webcam Full HD 1080p', 'webcam', array( 'bureautique', 'accessoires' ), 5900, 4900, 16, false, 'Webcam avec micro intégré pour visioconférence.', array( 'resolution' => '1920 × 1080', 'connexion' => 'USB' ) ),
	array( 'Sac à dos PC portable 15,6"', 'sac-a-dos', array( 'accessoires' ), 6900, 5900, 20, true, 'Compartiment rembourré, tissu déperlant, nombreuses poches.', array( 'taille-ecran' => '15,6"', 'couleur' => 'Noir' ) ),
	array( 'PC portable 15,6" Core i5 — 16 Go / 512 Go SSD', 'pc-portable', array( 'pc-portables-bureau' ), 119000, 0, 4, true, 'Ordinateur portable fin pour le travail et les études.', array( 'processeur' => 'Intel Core i5', 'memoire' => '16 Go', 'stockage' => '512 Go SSD', 'taille-ecran' => '15,6"' ) ),
	array( 'Souris sans fil silencieuse', 'souris-sans-fil', array( 'souris-claviers' ), 2200, 0, 45, false, 'Clics silencieux, récepteur USB nano.', array( 'connexion' => 'Sans fil 2,4 GHz', 'dpi' => '1600' ) ),
	array( 'Chargeur USB-C 65 W', 'chargeur-usb-c', array( 'accessoires' ), 4500, 0, 22, false, 'Charge un PC portable, une tablette ou un smartphone.', array( 'connectique' => 'USB-C, USB-A' ) ),
	array( 'Casque gaming 7.1 RGB', 'casque-gaming', array( 'gaming' ), 8900, 7500, 12, true, 'Son surround 7.1, micro antibruit, éclairage RGB.', array( 'connexion' => 'USB', 'compatibilite' => 'PC, PS5, Xbox' ) ),
	array( 'Clavier mécanique gaming RGB AZERTY', 'clavier-rgb', array( 'gaming', 'souris-claviers' ), 11900, 0, 10, true, 'Rétroéclairage RGB par touche, switches mécaniques.', array( 'disposition' => 'AZERTY (FR)', 'switch' => 'Linéaire (rouge)', 'connexion' => 'USB' ) ),
	array( 'Souris gaming RGB 12 000 DPI', 'souris-gaming', array( 'gaming', 'souris-claviers' ), 4900, 3900, 3, false, 'Capteur optique précis, 6 boutons programmables.', array( 'dpi' => '12 000', 'connexion' => 'USB' ) ),
	array( 'Manette sans fil pour PC', 'manette', array( 'gaming' ), 7900, 0, 0, false, 'Manette ergonomique, vibrations, connexion sans fil.', array( 'connexion' => 'Sans fil, USB-C', 'compatibilite' => 'PC, Android' ) ),
	array( 'Clavier sans fil slim AZERTY', 'clavier-sans-fil', array( 'souris-claviers' ), 5200, 4400, 15, false, 'Clavier fin avec pavé numérique, touches silencieuses.', array( 'disposition' => 'AZERTY (FR)', 'connexion' => 'Sans fil 2,4 GHz' ) ),
	array( 'Batterie externe 20 000 mAh', 'powerbank', array( 'accessoires' ), 5500, 0, 28, false, 'Charge rapide USB-C, deux appareils à la fois.', array( 'capacite' => '20 000 mAh', 'connectique' => 'USB-C, USB-A' ) ),
	array( 'Chargeur double port 30 W', 'chargeur-blanc', array( 'accessoires' ), 2900, 2400, 35, false, 'Un port USB-C et un port USB-A, format compact.', array( 'connectique' => 'USB-C, USB-A' ) ),
	array( 'Souris de bureau sans fil', 'souris-bureau', array( 'souris-claviers' ), 1900, 0, 50, false, 'Souris ergonomique pour le bureau, autonomie longue durée.', array( 'connexion' => 'Sans fil 2,4 GHz' ) ),
	// Owner's product photos (data/sample/real/).
	array( 'Coffret cadeau montre, 2 stylos et porte-clés', '../real/coffret-montre', array( 'coffrets-cadeaux' ), 8500, 7200, 10, true, 'Coffret prestige : montre chronographe, stylo plume, stylo bille et porte-clés métal.', array( 'couleur' => 'Argent' ) ),
	array( 'Coffret cadeau carnet, gourde isotherme, stylo et porte-clés', '../real/coffret-bleu', array( 'coffrets-cadeaux' ), 5900, 0, 14, true, 'Carnet en simili-cuir, bouteille isotherme, stylo et porte-clés assortis.', array( 'couleur' => 'Bleu' ) ),
	array( 'Coffret cadeau mug isotherme, carnet et stylo', '../real/coffret-mug', array( 'coffrets-cadeaux' ), 3900, 3400, 18, true, 'Mug de voyage en inox, carnet et stylo dans une boîte cadeau.', array( 'couleur' => 'Rouge' ) ),
	array( 'Sac à dos urbain bleu marine', '../real/sac-a-dos-bleu', array( 'accessoires' ), 5400, 0, 12, true, 'Sac à dos léger avec poche zippée frontale et poches latérales.', array( 'couleur' => 'Bleu marine' ) ),
	array( 'Cahier A4 couverture rigide', '../real/cahier-rouge', array( 'bureautique' ), 650, 0, 70, false, 'Cahier grand format, couverture rigide rouge.', array( 'couleur' => 'Rouge' ) ),
);

foreach ( $products as $i => [ $name, $photo, $cats, $regular, $sale, $stock, $featured, $short, $attrs ] ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_status( 'publish' );
	$p->set_regular_price( (string) $regular );
	if ( $sale ) {
		$p->set_sale_price( (string) $sale );
	}
	$p->set_manage_stock( true );
	$p->set_stock_quantity( $stock );
	$p->set_featured( $featured );
	$p->set_sku( 'ITB-' . str_pad( (string) ( $i + 1 ), 3, '0', STR_PAD_LEFT ) );
	$p->set_short_description( $short );
	$p->set_description( '<p>' . esc_html( $short ) . '</p><p>Fiche de démonstration : description, prix et stock sont des exemples.</p>' );
	$p->set_category_ids( array_filter( array_map( 'itbenz_cat_id', $cats ) ) );
	$p->set_menu_order( $i );
	$wc_attrs = array();
	foreach ( $attrs as $slug => $value ) {
		$tax = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'product' );
		}
		$t = term_exists( $value, $tax ) ?: wp_insert_term( $value, $tax );
		if ( is_wp_error( $t ) ) {
			continue;
		}
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
		$a->set_name( $tax );
		$a->set_options( array( (int) $t['term_id'] ) );
		$a->set_visible( true );
		$wc_attrs[] = $a;
	}
	$p->set_attributes( $wc_attrs );
	$p->update_meta_data( '_itbenz_model', 'ITB-' . strtoupper( substr( md5( $name ), 0, 6 ) ) );
	$p->update_meta_data( '_itbenz_demo', 1 );
	$p->set_image_id( itbenz_sample_media( 'products/' . $photo . '.jpg', $name ) );
	$id = $p->save();
	if ( ! is_wp_error( $brand ) ) {
		wp_set_object_terms( $id, (int) $brand['term_id'], 'product_brand' );
	}
	// Some sales history so "Les plus demandés" has content.
	update_post_meta( $id, 'total_sales', $featured ? max( 3, 30 - $i ) : max( 0, 8 - $i % 9 ) );
	WP_CLI::log( "  #$id $name" );
}

// ---- Category tiles & homepage collections -----------------------------------------
$tiles = array(
	'souris-claviers' => 'souris-sans-fil', 'ecrans' => 'webcam', 'pc-portables-bureau' => 'pc-portable', 'casques-audio' => 'casque-gaming',
	'gaming' => 'manette', 'stockage' => 'ssd-externe', 'cables-adaptateurs' => 'hub-usb-c-7en1', 'reseau' => 'routeur-wifi',
	'composants-pc' => 'carte-sd-256', 'packs' => 'clavier-sans-fil', 'bureautique' => 'imprimante', 'accessoires' => 'sac-a-dos', 'coffrets-cadeaux' => '../real/coffret-bleu',
);
foreach ( $tiles as $slug => $photo ) {
	$tid = itbenz_cat_id( $slug );
	update_term_meta( $tid, 'thumbnail_id', itbenz_sample_media( 'products/' . $photo . '.jpg', $photo ) );
	update_term_meta( $tid, 'itbenz_home', 0 );
}
$order = 0;
foreach ( array( 'souris-claviers', 'gaming', 'coffrets-cadeaux', 'stockage', 'bureautique', 'accessoires', 'pc-portables-bureau', 'cables-adaptateurs', 'reseau', 'casques-audio', 'ecrans', 'composants-pc', 'packs' ) as $slug ) {
	update_term_meta( itbenz_cat_id( $slug ), 'order', $order++ );
}
$collections = array(
	'gaming'      => 'Clavier, souris et casque : tout le setup gaming au même endroit.',
	'stockage'    => 'SSD, cartes SD et clés USB pour garder vos fichiers partout.',
	'bureautique' => 'Imprimante, papier et encre pour le bureau à la maison.',
	'accessoires' => 'Sacs, chargeurs et batteries pour le travail et les études.',
	'coffrets-cadeaux' => 'Des coffrets prêts à offrir : montre, stylos, carnet, mug.',
);
foreach ( $collections as $slug => $text ) {
	$tid = itbenz_cat_id( $slug );
	update_term_meta( $tid, 'itbenz_home', 1 );
	update_term_meta( $tid, 'itbenz_highlight', $text );
	$cover = 'coffrets-cadeaux' === $slug ? 'real/coffret-montre.jpg' : 'covers/' . $slug . '.jpg';
	update_term_meta( $tid, 'itbenz_cover_id', itbenz_sample_media( $cover, 'Collection ' . $slug ) );
}

// ---- Banners (owner-supplied visuals, text drawn in the image) -----------------------
$banners = array(
	// title (alt text), file, placement, link
	array( 'Gaming Setup Essentials', 'gaming-setup', 'hero', 'gaming' ),
	array( 'Storage & Connectivity', 'stockage-connectivite', 'hero', 'stockage' ),
	array( 'Smart Deals on Accessories', 'offres-accessoires', 'hero', 'promo' ),
	array( 'Home Office Ready', 'bureau-maison', 'bureautique', 'bureautique' ),
	array( 'Back-to-School Tech', 'rentree-tech', 'accessoires', 'accessoires' ),
);
foreach ( $banners as $i => [ $title, $file, $place, $link ] ) {
	$id = wp_insert_post( array( 'post_type' => 'itbenz_banner', 'post_status' => 'publish', 'post_title' => $title, 'menu_order' => $i ) );
	$url = 'promo' === $link ? add_query_arg( 'promo', 1, wc_get_page_permalink( 'shop' ) ) : get_term_link( itbenz_cat_id( $link ), 'product_cat' );
	update_post_meta( $id, '_itbenz_url', is_wp_error( $url ) ? '' : $url );
	update_post_meta( $id, '_itbenz_full', 1 );
	update_post_meta( $id, '_itbenz_place', 'hero' === $place ? 'hero' : (string) itbenz_cat_id( $place ) );
	update_post_meta( $id, '_itbenz_color', 'onyx' );
	update_post_meta( $id, '_itbenz_demo', 1 );
	set_post_thumbnail( $id, itbenz_sample_media( 'banners/' . $file . '.jpg', $title ) );
}
delete_transient( 'itbenz_banners' );

// ---- Delivery TEST prices & shop info -------------------------------------------------
if ( ! get_option( ITBENZ_Delivery_Rates::OPTION ) ) {
	$rates = array();
	foreach ( array_keys( ITBENZ_Geo::wilayas() ) as $state ) {
		$rates[ $state ] = array( 'home' => '600', 'desk' => '400' );
	}
	$rates['DZ-33'] = array( 'home' => '', 'desk' => '' ); // Illizi left empty: tests the "not available" path.
	update_option( ITBENZ_Delivery_Rates::OPTION, $rates, false );
}
$settings                 = (array) get_option( 'itbenz_settings', array() );
$settings['announcement'] = 'Paiement à la livraison · Commandez en ligne ou sur WhatsApp';
$settings['delivery']     = 'À domicile ou en point relais (stop desk), tarif selon votre wilaya.';
$settings['whatsapp']     = $settings['whatsapp'] ?? '213000000000';
unset( $settings['phone'] );
update_option( 'itbenz_settings', $settings );

wc_delete_product_transients();
WP_CLI::success( count( $products ) . ' sample products, ' . count( $banners ) . ' banners.' );
