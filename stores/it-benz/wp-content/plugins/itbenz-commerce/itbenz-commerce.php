<?php
/**
 * Plugin Name:       IT-BENZ Commerce (Algérie)
 * Description:       Logique e-commerce Algérie pour WooCommerce : 69 wilayas + communes, validation téléphone, livraison par wilaya (domicile / stop desk), statuts de commande, anti-doublon, bannières, réglages boutique, tableau de bord simplifié et durcissement sécurité.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            IT-BENZ
 * License:           GPL-2.0-or-later
 * Text Domain:       itbenz
 *
 * Business logic lives here; presentation lives in the "itbenz" theme.
 * Keeping them apart lets the theme be swapped (white-label) without losing orders logic.
 */

defined( 'ABSPATH' ) || exit;

define( 'ITBENZ_VERSION', '1.0.0' );
define( 'ITBENZ_FILE', __FILE__ );
define( 'ITBENZ_DIR', plugin_dir_path( __FILE__ ) );
define( 'ITBENZ_URL', plugin_dir_url( __FILE__ ) );

require_once ITBENZ_DIR . 'includes/settings.php';
require_once ITBENZ_DIR . 'includes/security.php';
require_once ITBENZ_DIR . 'includes/banners.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once ITBENZ_DIR . 'includes/geo.php';
		require_once ITBENZ_DIR . 'includes/checkout.php';
		require_once ITBENZ_DIR . 'includes/shipping.php';
		require_once ITBENZ_DIR . 'includes/order-statuses.php';
		require_once ITBENZ_DIR . 'includes/product-fields.php';
		require_once ITBENZ_DIR . 'includes/admin.php';
		require_once ITBENZ_DIR . 'includes/mail.php';
		require_once ITBENZ_DIR . 'includes/collections.php';
	}
);

// Classic checkout + HPOS compatible.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ITBENZ_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ITBENZ_FILE, false );
		}
	}
);
