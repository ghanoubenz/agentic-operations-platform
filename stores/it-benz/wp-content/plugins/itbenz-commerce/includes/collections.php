<?php
/**
 * Homepage "collections" — editorial blocks built from product categories.
 *
 * Products → Catégories → (edit a category):
 *   - "Afficher sur l'accueil"  : include this category as a collection block
 *   - "Photo de collection"     : large lifestyle photo shown next to the coloured card
 *   - "Texte mis en avant"      : sentence shown in the coloured card (only verified claims)
 * The category "thumbnail" (WooCommerce core field) stays the small tile image.
 */

defined( 'ABSPATH' ) || exit;

function itbenz_collection_fields_html( ?WP_Term $term ): void {
	$home      = $term ? (bool) get_term_meta( $term->term_id, 'itbenz_home', true ) : false;
	$cover     = $term ? (int) get_term_meta( $term->term_id, 'itbenz_cover_id', true ) : 0;
	$highlight = $term ? (string) get_term_meta( $term->term_id, 'itbenz_highlight', true ) : '';
	wp_nonce_field( 'itbenz_collection', 'itbenz_collection_nonce' );
	?>
	<tr class="form-field"><th scope="row"><?php esc_html_e( 'Accueil', 'itbenz' ); ?></th><td>
		<label><input type="checkbox" name="itbenz_home" value="1" <?php checked( $home ); ?>> <?php esc_html_e( 'Afficher cette catégorie comme collection sur la page d’accueil', 'itbenz' ); ?></label>
	</td></tr>
	<tr class="form-field"><th scope="row"><?php esc_html_e( 'Photo de collection', 'itbenz' ); ?></th><td>
		<div class="itbenz-media" data-target="itbenz_cover_id">
			<div class="itbenz-media__preview"><?php echo $cover ? wp_get_attachment_image( $cover, 'medium' ) : ''; ?></div>
			<input type="hidden" name="itbenz_cover_id" id="itbenz_cover_id" value="<?php echo esc_attr( (string) $cover ); ?>">
			<button type="button" class="button itbenz-media__pick"><?php esc_html_e( 'Choisir une photo', 'itbenz' ); ?></button>
			<button type="button" class="button-link itbenz-media__clear"><?php esc_html_e( 'Retirer', 'itbenz' ); ?></button>
		</div>
		<p class="description"><?php esc_html_e( 'Grande photo d’ambiance (format paysage ou carré, min. 1200 px).', 'itbenz' ); ?></p>
	</td></tr>
	<tr class="form-field"><th scope="row"><label for="itbenz_highlight"><?php esc_html_e( 'Texte mis en avant', 'itbenz' ); ?></label></th><td>
		<textarea name="itbenz_highlight" id="itbenz_highlight" rows="2"><?php echo esc_textarea( $highlight ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Une phrase courte affichée dans la carte colorée. N’indiquez que des informations vérifiées.', 'itbenz' ); ?></p>
	</td></tr>
	<?php
}

add_action( 'product_cat_edit_form_fields', static fn( $term ) => itbenz_collection_fields_html( $term ) );
add_action(
	'product_cat_add_form_fields',
	static function (): void {
		echo '<table class="form-table">';
		itbenz_collection_fields_html( null );
		echo '</table>';
	}
);

$itbenz_save_collection = static function ( int $term_id ): void {
	if ( ! isset( $_POST['itbenz_collection_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['itbenz_collection_nonce'] ), 'itbenz_collection' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	update_term_meta( $term_id, 'itbenz_home', empty( $_POST['itbenz_home'] ) ? 0 : 1 );
	update_term_meta( $term_id, 'itbenz_cover_id', absint( $_POST['itbenz_cover_id'] ?? 0 ) );
	update_term_meta( $term_id, 'itbenz_highlight', sanitize_textarea_field( wp_unslash( $_POST['itbenz_highlight'] ?? '' ) ) );
};
add_action( 'created_product_cat', $itbenz_save_collection );
add_action( 'edited_product_cat', $itbenz_save_collection );

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) || 'product_cat' !== ( $_GET['taxonomy'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			"jQuery(function($){
				$(document).on('click','.itbenz-media__pick',function(e){
					e.preventDefault();
					var box=$(this).closest('.itbenz-media');
					var frame=wp.media({title:'Photo de collection',library:{type:'image'},multiple:false});
					frame.on('select',function(){
						var a=frame.state().get('selection').first().toJSON();
						box.find('input').val(a.id);
						box.find('.itbenz-media__preview').html('<img src=\"'+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+'\" style=\"max-width:240px;border-radius:8px\">');
					});
					frame.open();
				});
				$(document).on('click','.itbenz-media__clear',function(e){
					e.preventDefault();
					var box=$(this).closest('.itbenz-media');
					box.find('input').val('');box.find('.itbenz-media__preview').empty();
				});
			});"
		);
	}
);

/**
 * Categories to show as homepage collections.
 * Uses the categories ticked "Afficher sur l'accueil"; if none are ticked yet,
 * falls back to the first top-level categories that contain products.
 *
 * @return WP_Term[]
 */
function itbenz_home_collections( int $limit = 4 ): array {
	$args  = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => $limit,
		'orderby'    => 'meta_value_num',
		'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	);
	$terms = get_terms( $args + array( 'meta_query' => array( array( 'key' => 'itbenz_home', 'value' => '1' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( is_wp_error( $terms ) || ! $terms ) {
		$terms = get_terms( $args + array( 'parent' => 0 ) );
	}
	return is_wp_error( $terms ) ? array() : $terms;
}
