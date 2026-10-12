<?php
/**
 * Classic checkout tailored for Algerian cash-on-delivery orders.
 *
 * Fields: full name, mobile phone, optional e-mail, wilaya, commune, address, note.
 * Everything entered here is re-validated server-side; totals are always computed by
 * WooCommerce on the server (shipping comes from ITBENZ_Shipping_Wilaya).
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_checkout_fields',
	static function ( array $fields ): array {
		$b = &$fields['billing'];

		unset( $b['billing_last_name'], $b['billing_company'], $b['billing_address_2'], $b['billing_postcode'] );

		$b['billing_first_name'] = array_merge(
			$b['billing_first_name'],
			array(
				'label'        => __( 'Nom complet', 'itbenz' ),
				'placeholder'  => __( 'Prénom et nom', 'itbenz' ),
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'name',
				'priority'     => 10,
			)
		);
		$b['billing_phone'] = array_merge(
			$b['billing_phone'],
			array(
				'label'             => __( 'Téléphone mobile', 'itbenz' ),
				'placeholder'       => '05XX XX XX XX',
				'required'          => true,
				'class'             => array( 'form-row-wide' ),
				'priority'          => 20,
				'custom_attributes' => array(
					'inputmode' => 'tel',
					'pattern'   => '[0-9 +.\-]{9,16}',
				),
			)
		);
		$b['billing_email'] = array_merge(
			$b['billing_email'],
			array(
				'label'    => __( 'E-mail (pour recevoir la confirmation)', 'itbenz' ),
				'required' => false,
				'class'    => array( 'form-row-wide' ),
				'priority' => 30,
			)
		);
		$b['billing_country']['priority'] = 40;
		$b['billing_state']               = array_merge(
			$b['billing_state'],
			array(
				'label'    => __( 'Wilaya', 'itbenz' ),
				'required' => true,
				'class'    => array( 'form-row-first' ),
				'priority' => 50,
			)
		);
		$b['billing_city'] = array_merge(
			$b['billing_city'],
			array(
				'label'       => __( 'Commune', 'itbenz' ),
				'placeholder' => __( 'Choisissez d’abord la wilaya', 'itbenz' ),
				'required'    => true,
				'class'       => array( 'form-row-last' ),
				'priority'    => 60,
			)
		);
		$b['billing_address_1'] = array_merge(
			$b['billing_address_1'],
			array(
				'label'       => __( 'Adresse de livraison', 'itbenz' ),
				'placeholder' => __( 'Quartier, rue, n° de bâtiment…', 'itbenz' ),
				'class'       => array( 'form-row-wide' ),
				'priority'    => 70,
			)
		);

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( 'Remarque', 'itbenz' );
			$fields['order']['order_comments']['placeholder'] = __( 'Repère, horaire préféré…', 'itbenz' );
		}
		return $fields;
	},
	20
);

// Country locale: WooCommerce re-applies labels/requirements per country in JS, keep them aligned.
add_filter(
	'woocommerce_get_country_locale',
	static function ( array $locale ): array {
		$locale['DZ'] = array(
			'phone'     => array( 'label' => __( 'Téléphone mobile', 'itbenz' ), 'required' => true, 'priority' => 20 ),
			'state'     => array( 'label' => __( 'Wilaya', 'itbenz' ), 'required' => true, 'priority' => 50 ),
			'city'      => array( 'label' => __( 'Commune', 'itbenz' ), 'required' => true, 'priority' => 60 ),
			'address_1' => array( 'label' => __( 'Adresse de livraison', 'itbenz' ), 'priority' => 70 ),
			'postcode'  => array( 'required' => false, 'hidden' => true ),
			'last_name' => array( 'required' => false, 'hidden' => true ),
		);
		return $locale;
	}
);

add_filter( 'woocommerce_default_address_fields', static function ( array $f ): array {
	$f['last_name']['required'] = false;
	$f['postcode']['required']  = false;
	return $f;
} );

add_filter( 'default_checkout_billing_country', static fn() => 'DZ' );

/**
 * Server-side validation & normalization.
 */
add_action(
	'woocommerce_after_checkout_validation',
	static function ( array $data, WP_Error $errors ): void {
		if ( 'DZ' !== ( $data['billing_country'] ?? '' ) ) {
			$errors->add( 'country', __( 'La livraison est disponible uniquement en Algérie.', 'itbenz' ) );
		}
		$name = trim( (string) ( $data['billing_first_name'] ?? '' ) );
		if ( mb_strlen( $name ) < 3 || ! str_contains( $name, ' ' ) ) {
			$errors->add( 'name', __( 'Veuillez saisir votre nom et prénom.', 'itbenz' ) );
		}
		if ( '' === ITBENZ_Geo::normalize_phone( (string) ( $data['billing_phone'] ?? '' ) ) ) {
			$errors->add( 'phone', __( 'Numéro de téléphone invalide : utilisez un mobile algérien (05, 06 ou 07 + 8 chiffres).', 'itbenz' ) );
		}
		$state = (string) ( $data['billing_state'] ?? '' );
		if ( ! array_key_exists( $state, ITBENZ_Geo::wilayas() ) ) {
			$errors->add( 'state', __( 'Veuillez choisir une wilaya valide.', 'itbenz' ) );
		} elseif ( ! ITBENZ_Geo::is_valid_commune( $state, (string) ( $data['billing_city'] ?? '' ) ) ) {
			$errors->add( 'city', __( 'Veuillez choisir une commune de la wilaya sélectionnée.', 'itbenz' ) );
		}
		if ( mb_strlen( trim( (string) ( $data['billing_address_1'] ?? '' ) ) ) < 5 ) {
			$errors->add( 'address', __( 'Veuillez préciser votre adresse de livraison.', 'itbenz' ) );
		}
	},
	10,
	2
);

// Store the phone in a single canonical format (useful for WhatsApp / courier export).
add_action(
	'woocommerce_checkout_create_order',
	static function ( WC_Order $order ): void {
		$phone = ITBENZ_Geo::normalize_phone( $order->get_billing_phone() );
		if ( $phone ) {
			$order->set_billing_phone( $phone );
		}
		$order->set_shipping_first_name( $order->get_billing_first_name() );
		$order->set_shipping_phone( $phone );
	}
);

/**
 * Duplicate-order & abuse protection.
 *  - Same phone + same cart within 3 minutes  => rejected (double click, refresh, back button).
 *  - More than N orders per IP per hour        => rejected (bot / spam).
 */
function itbenz_checkout_fingerprint(): string {
	$phone = ITBENZ_Geo::normalize_phone( wc_clean( wp_unslash( $_POST['billing_phone'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification -- WC verified the nonce.
	return 'itbenz_dup_' . md5( $phone . '|' . WC()->cart->get_cart_hash() );
}

function itbenz_client_ip(): string {
	return (string) WC_Geolocation::get_ip_address();
}

add_action(
	'woocommerce_checkout_process',
	static function (): void {
		if ( get_transient( itbenz_checkout_fingerprint() ) ) {
			wc_add_notice( __( 'Cette commande a déjà été enregistrée. Si vous souhaitez commander à nouveau, patientez quelques minutes ou contactez-nous.', 'itbenz' ), 'error' );
			return;
		}
		$limit = (int) apply_filters( 'itbenz_orders_per_ip_per_hour', 5 );
		$key   = 'itbenz_rate_' . md5( itbenz_client_ip() );
		if ( (int) get_transient( $key ) >= $limit ) {
			wc_add_notice( __( 'Trop de commandes depuis cette connexion. Merci de nous contacter par téléphone ou WhatsApp.', 'itbenz' ), 'error' );
		}
	}
);

add_action(
	'woocommerce_checkout_order_processed',
	static function (): void {
		set_transient( itbenz_checkout_fingerprint(), 1, 3 * MINUTE_IN_SECONDS );
		$key = 'itbenz_rate_' . md5( itbenz_client_ip() );
		set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	}
);

// Human-friendly order numbers: ITB-1234.
add_filter( 'woocommerce_order_number', static fn( $number ) => 'ITB-' . $number );

// Search orders by "ITB-1234" in admin.
add_filter(
	'woocommerce_shop_order_search_results',
	static function ( $ids, $term ) {
		if ( preg_match( '/^ITB-(\d+)$/i', trim( $term ), $m ) ) {
			$ids[] = (int) $m[1];
		}
		return $ids;
	},
	10,
	2
);

/**
 * Commune dropdown data — only on the checkout / account address pages.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! ( is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) ) {
			return;
		}
		wp_enqueue_script( 'itbenz-checkout', ITBENZ_URL . 'assets/checkout.js', array( 'jquery' ), ITBENZ_VERSION, true );
		$communes = array();
		foreach ( array_keys( ITBENZ_Geo::wilayas() ) as $state ) {
			$communes[ $state ] = ITBENZ_Geo::communes( $state );
		}
		wp_localize_script(
			'itbenz-checkout',
			'itbenzGeo',
			array(
				'communes'    => $communes,
				'placeholder' => __( 'Choisir la commune', 'itbenz' ),
				'pickWilaya'  => __( 'Choisissez d’abord la wilaya', 'itbenz' ),
			)
		);
	}
);

// Classic shortcode checkout keeps full control of fields; make sure the COD button label is clear.
add_filter( 'woocommerce_order_button_text', static fn() => __( 'Confirmer la commande', 'itbenz' ) );

// French storefront: "DA" (dinar algérien) instead of the Arabic symbol; Arabic locale keeps "د.ج".
add_filter(
	'woocommerce_currency_symbol',
	static fn( $symbol, $currency ) => ( 'DZD' === $currency && ! str_starts_with( determine_locale(), 'ar' ) ) ? 'DA' : $symbol,
	10,
	2
);
