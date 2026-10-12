<?php
/**
 * Service promises. Only "paiement à la livraison" is shown by default (it is how the
 * store works); everything else appears once the merchant fills in the real policy.
 */
defined( 'ABSPATH' ) || exit;
$items = array(
	array( 'cash', __( 'Paiement à la livraison', 'itbenz' ), __( 'Vous payez en espèces à la réception de votre commande.', 'itbenz' ) ),
	array( 'truck', __( 'Livraison', 'itbenz' ), itbenz_opt( 'delivery' ) ),
	array( 'shield', __( 'Garantie', 'itbenz' ), itbenz_opt( 'warranty' ) ),
	array( 'return', __( 'Retours & échanges', 'itbenz' ), itbenz_opt( 'returns' ) ),
	array( 'chat', __( 'Service client', 'itbenz' ), itbenz_opt( 'support' ) ),
);
$items = array_filter( $items, static fn( $i ) => '' !== $i[2] );
?>
<section class="section wrap" aria-label="<?php esc_attr_e( 'Nos services', 'itbenz' ); ?>">
	<ul class="trust">
	<?php foreach ( $items as [ $icon, $title, $text ] ) : ?>
		<li class="trust__item">
			<?php echo itbenz_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3><?php echo esc_html( $title ); ?></h3>
			<p><?php echo esc_html( $text ); ?></p>
		</li>
	<?php endforeach; ?>
	</ul>
</section>
