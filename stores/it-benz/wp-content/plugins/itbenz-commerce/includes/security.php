<?php
/**
 * Lightweight hardening that does not need a security plugin.
 * Server-level protections (firewall, fail2ban / host WAF, backups) are documented in docs/.
 */

defined( 'ABSPATH' ) || exit;

// XML-RPC is a common brute-force vector and is not used by the store.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', static function ( $h ) {
	unset( $h['X-Pingback'] );
	return $h;
} );

// Hide WordPress version.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Block user enumeration: ?author=N and /author/<login>/ archives (the store has no author pages).
// Runs before WordPress' canonical redirect, which would otherwise reveal the login name.
add_action( 'template_redirect', static function (): void {
	if ( isset( $_GET['author'] ) || is_author() ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 0 );
// The users sitemap would list author slugs (= login names).
add_filter( 'wp_sitemaps_add_provider', static fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );
add_filter( 'rest_endpoints', static function ( array $endpoints ): array {
	if ( ! current_user_can( 'list_users' ) ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );

// Security headers (HSTS is set at the web-server level once HTTPS is confirmed).
add_action( 'send_headers', static function (): void {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)' );
} );

// Generic login error (does not reveal whether the username exists).
add_filter( 'login_errors', static fn() => itbenz_login_locked() ? __( 'Trop de tentatives. Réessayez dans 15 minutes.', 'itbenz' ) : __( 'Identifiants incorrects.', 'itbenz' ) );

/**
 * Login throttling: 5 failures per IP / 15 minutes, then locked for 15 minutes.
 */
function itbenz_login_key(): string {
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	return 'itbenz_login_' . md5( $ip );
}

function itbenz_login_locked(): bool {
	return (int) get_transient( itbenz_login_key() ) >= (int) apply_filters( 'itbenz_login_max_attempts', 5 );
}

// Priority 99: runs after WordPress' own username/password check so the lock cannot be bypassed.
add_filter(
	'authenticate',
	static function ( $user ) {
		if ( itbenz_login_locked() ) {
			return new WP_Error( 'itbenz_locked', __( 'Trop de tentatives. Réessayez dans 15 minutes.', 'itbenz' ) );
		}
		return $user;
	},
	99
);
add_action( 'wp_login_failed', static function (): void {
	$key = itbenz_login_key();
	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
} );
add_action( 'wp_login', static fn() => delete_transient( itbenz_login_key() ) );

// Front-end performance: drop emoji scripts and oEmbed discovery.
add_action( 'init', static function (): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
} );
