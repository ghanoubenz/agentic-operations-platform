<?php
/**
 * Outgoing e-mail.
 *
 * Staging: SMTP_HOST=mail (Mailpit) catches every message.
 * Production: set SMTP credentials as constants in wp-config.php (never in the database):
 *   ITBENZ_SMTP_HOST, ITBENZ_SMTP_PORT, ITBENZ_SMTP_USER, ITBENZ_SMTP_PASS, ITBENZ_SMTP_SECURE ('tls'|'ssl'|'')
 * Without ITBENZ_SMTP_HOST, WordPress uses the host's default mail().
 *
 * Merchant notification: WooCommerce "Nouvelle commande" e-mail (recipient set in
 * WooCommerce → Réglages → E-mails). Customer confirmation is sent when the customer
 * gives an e-mail address (optional at checkout).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'phpmailer_init',
	static function ( $mailer ): void {
		$host = defined( 'ITBENZ_SMTP_HOST' ) ? (string) ITBENZ_SMTP_HOST : '';
		if ( '' === $host ) {
			return;
		}
		$mailer->isSMTP();
		$mailer->Host = $host;
		$mailer->Port = defined( 'ITBENZ_SMTP_PORT' ) && ITBENZ_SMTP_PORT ? (int) ITBENZ_SMTP_PORT : 587;
		if ( defined( 'ITBENZ_SMTP_USER' ) && ITBENZ_SMTP_USER ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = (string) ITBENZ_SMTP_USER;
			$mailer->Password = defined( 'ITBENZ_SMTP_PASS' ) ? (string) ITBENZ_SMTP_PASS : '';
		}
		$mailer->SMTPSecure  = defined( 'ITBENZ_SMTP_SECURE' ) ? (string) ITBENZ_SMTP_SECURE : '';
		$mailer->SMTPAutoTLS = '' !== $mailer->SMTPSecure;
	}
);

// Add the customer phone + wilaya to the merchant's "new order" e-mail subject for quick triage.
add_filter(
	'woocommerce_email_subject_new_order',
	static function ( $subject, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $subject;
		}
		$states = WC()->countries->get_states( 'DZ' );
		return sprintf(
			/* translators: 1: order number, 2: wilaya, 3: total */
			__( '[IT-BENZ] Nouvelle commande %1$s — %2$s — %3$s', 'itbenz' ),
			$order->get_order_number(),
			$states[ $order->get_billing_state() ] ?? '',
			wp_strip_all_tags( wc_price( $order->get_total() ) )
		);
	},
	10,
	2
);
