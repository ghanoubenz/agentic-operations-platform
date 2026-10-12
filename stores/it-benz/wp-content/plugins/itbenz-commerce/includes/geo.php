<?php
/**
 * Algerian administrative data: 69 wilayas (Law 26-06, 4 April 2026) and their communes.
 *
 * WooCommerce core still ships the 58-wilaya list, so we replace it. Keys keep the
 * WooCommerce "DZ-NN" format so existing orders and zones stay compatible.
 */

defined( 'ABSPATH' ) || exit;

final class ITBENZ_Geo {

	private static ?array $data = null;

	public static function data(): array {
		if ( null === self::$data ) {
			$json       = file_get_contents( ITBENZ_DIR . 'data/communes-dz.json' );
			self::$data = json_decode( (string) $json, true ) ?: array( 'wilayas' => array(), 'communes' => array() );
		}
		return self::$data;
	}

	/** @return array<string,string> "DZ-16" => "16 - Alger" */
	public static function wilayas(): array {
		$out = array();
		foreach ( self::data()['wilayas'] as $code => $names ) {
			$out[ 'DZ-' . $code ] = $code . ' - ' . self::name( $names );
		}
		return $out;
	}

	/** @return string[] commune names for a "DZ-NN" state code. */
	public static function communes( string $state ): array {
		$code = substr( $state, 3 );
		$list = self::data()['communes'][ $code ] ?? array();
		return array_map( array( self::class, 'name' ), $list );
	}

	public static function is_valid_commune( string $state, string $commune ): bool {
		$needle = self::normalize( $commune );
		foreach ( self::data()['communes'][ substr( $state, 3 ) ] ?? array() as $names ) {
			if ( self::normalize( $names['fr'] ) === $needle || self::normalize( $names['ar'] ) === $needle ) {
				return true;
			}
		}
		return false;
	}

	private static function name( array $names ): string {
		return str_starts_with( determine_locale(), 'ar' ) ? $names['ar'] : $names['fr'];
	}

	private static function normalize( string $s ): string {
		return mb_strtolower( trim( remove_accents( $s ) ) );
	}

	/**
	 * Normalize an Algerian mobile number to 0XXXXXXXXX. Returns '' when invalid.
	 * Accepts 05/06/07 mobiles written with spaces, dots, dashes, +213 or 00213.
	 */
	public static function normalize_phone( string $raw ): string {
		$digits = preg_replace( '/\D+/', '', $raw );
		if ( str_starts_with( $digits, '00213' ) ) {
			$digits = '0' . substr( $digits, 5 );
		} elseif ( str_starts_with( $digits, '213' ) && strlen( $digits ) === 12 ) {
			$digits = '0' . substr( $digits, 3 );
		}
		return preg_match( '/^0[567]\d{8}$/', $digits ) ? $digits : '';
	}
}

add_filter(
	'woocommerce_states',
	static function ( $states ) {
		$states['DZ'] = ITBENZ_Geo::wilayas();
		return $states;
	}
);
