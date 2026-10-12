<?php
/**
 * Extra technical-product fields: model number and warranty.
 *
 * Brand uses WooCommerce's built-in "Marques" taxonomy (product_brand).
 * Technical specifications use global product attributes (pa_*) created by
 * scripts/setup.sh so they are filterable and CSV-importable.
 * CSV import columns: "Meta: _itbenz_model", "Meta: _itbenz_warranty".
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'woocommerce_product_options_sku',
	static function (): void {
		woocommerce_wp_text_input(
			array(
				'id'          => '_itbenz_model',
				'label'       => __( 'Référence / modèle constructeur', 'itbenz' ),
				'desc_tip'    => true,
				'description' => __( 'Ex. référence exacte du fabricant. Affichée sur la fiche produit.', 'itbenz' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => '_itbenz_warranty',
				'label'       => __( 'Garantie', 'itbenz' ),
				'desc_tip'    => true,
				'description' => __( 'Laissez vide si aucune garantie n’est confirmée. Ex. « 12 mois ».', 'itbenz' ),
			)
		);
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	static function ( WC_Product $product ): void {
		foreach ( array( '_itbenz_model', '_itbenz_warranty' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WC verifies the product nonce.
				$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}
);

/** Public helper used by the theme. */
function itbenz_product_specs( WC_Product $product ): array {
	$specs = array();
	$brands = wp_get_post_terms( $product->get_id(), 'product_brand', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $brands ) && $brands ) {
		$specs[ __( 'Marque', 'itbenz' ) ] = implode( ', ', $brands );
	}
	if ( $model = $product->get_meta( '_itbenz_model' ) ) {
		$specs[ __( 'Modèle', 'itbenz' ) ] = $model;
	}
	if ( $sku = $product->get_sku() ) {
		$specs[ __( 'SKU', 'itbenz' ) ] = $sku;
	}
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() ) {
			continue;
		}
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
			: $attribute->get_options();
		if ( $values ) {
			$specs[ wc_attribute_label( $attribute->get_name(), $product ) ] = implode( ', ', $values );
		}
	}
	if ( $warranty = $product->get_meta( '_itbenz_warranty' ) ) {
		$specs[ __( 'Garantie', 'itbenz' ) ] = $warranty;
	}
	return $specs;
}
