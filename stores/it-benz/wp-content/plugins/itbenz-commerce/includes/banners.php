<?php
/**
 * Homepage promotional banners ("Bannières"), editable by the shop manager.
 *
 * Each banner = title (headline) + featured image + eyebrow / subtitle / button / link /
 * background colour. "Ordre" (menu_order) sets the slide order; drafts are hidden.
 */

defined( 'ABSPATH' ) || exit;

const ITBENZ_BANNER_COLORS = array(
	'onyx'   => '#202124',
	'royal'  => '#113979',
	'forest' => '#073618',
	'crimson' => '#980b0b',
	'powder' => '#d2e3fc',
	'mint'   => '#a8dab5',
	'butter' => '#ffedb8',
);

add_action(
	'init',
	static function (): void {
		register_post_type(
			'itbenz_banner',
			array(
				'labels'          => array(
					'name'          => __( 'Bannières', 'itbenz' ),
					'singular_name' => __( 'Bannière', 'itbenz' ),
					'add_new_item'  => __( 'Ajouter une bannière', 'itbenz' ),
					'edit_item'     => __( 'Modifier la bannière', 'itbenz' ),
					'featured_image'     => __( 'Image de la bannière (min. 1600 px de large)', 'itbenz' ),
					'set_featured_image' => __( 'Choisir l’image', 'itbenz' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 57,
				'menu_icon'       => 'dashicons-images-alt2',
				'supports'        => array( 'title', 'thumbnail', 'page-attributes' ),
				'capability_type' => 'product',
				'map_meta_cap'    => true,
			)
		);
	}
);

function itbenz_banner_fields(): array {
	return array(
		'eyebrow'  => __( 'Sur-titre (ex. OFFRE DE LA SEMAINE)', 'itbenz' ),
		'subtitle' => __( 'Sous-titre', 'itbenz' ),
		'cta'      => __( 'Texte du bouton', 'itbenz' ),
		'url'      => __( 'Lien du bouton (produit, catégorie…)', 'itbenz' ),
	);
}

add_action(
	'add_meta_boxes_itbenz_banner',
	static function (): void {
		add_meta_box(
			'itbenz_banner_meta',
			__( 'Contenu de la bannière', 'itbenz' ),
			static function ( WP_Post $post ): void {
				wp_nonce_field( 'itbenz_banner', 'itbenz_banner_nonce' );
				foreach ( itbenz_banner_fields() as $key => $label ) {
					printf(
						'<p><label><strong>%1$s</strong><br><input type="%4$s" class="widefat" name="itbenz_banner[%2$s]" value="%3$s"></label></p>',
						esc_html( $label ),
						esc_attr( $key ),
						esc_attr( (string) get_post_meta( $post->ID, '_itbenz_' . $key, true ) ),
						'url' === $key ? 'url' : 'text'
					);
				}
				$full  = (bool) get_post_meta( $post->ID, '_itbenz_full', true );
				$place = (string) get_post_meta( $post->ID, '_itbenz_place', true ) ?: 'hero';
				printf(
					'<p><label><input type="checkbox" name="itbenz_banner[full]" value="1" %s> <strong>%s</strong></label><br><span class="description">%s</span></p>',
					checked( $full, true, false ),
					esc_html__( 'Visuel complet', 'itbenz' ),
					esc_html__( 'Cochez si le titre et le bouton sont déjà dessinés dans l’image (format 16:9, ex. 1672 × 941 px). L’image est alors affichée entière, sans texte par-dessus.', 'itbenz' )
				);
				echo '<p><label><strong>' . esc_html__( 'Emplacement sur l’accueil', 'itbenz' ) . '</strong><br><select name="itbenz_banner[place]">';
				printf( '<option value="hero" %s>%s</option>', selected( $place, 'hero', false ), esc_html__( 'Carrousel du haut', 'itbenz' ) );
				printf( '<option value="inspire" %s>%s</option>', selected( $place, 'inspire', false ), esc_html__( 'Carte « inspiration » sous les catégories', 'itbenz' ) );
				$cats = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0 ) ) : array();
				foreach ( is_wp_error( $cats ) ? array() : $cats as $t ) {
					/* translators: %s: category */
					printf( '<option value="%1$d" %2$s>%3$s</option>', (int) $t->term_id, selected( $place, (string) $t->term_id, false ), esc_html( sprintf( __( 'Après la collection « %s »', 'itbenz' ), $t->name ) ) );
				}
				echo '</select></label></p>';
				$current = get_post_meta( $post->ID, '_itbenz_color', true ) ?: 'onyx';
				echo '<p><strong>' . esc_html__( 'Couleur de fond', 'itbenz' ) . '</strong><br>';
				foreach ( ITBENZ_BANNER_COLORS as $key => $hex ) {
					printf(
						'<label style="display:inline-flex;align-items:center;gap:4px;margin-right:12px"><input type="radio" name="itbenz_banner[color]" value="%1$s" %2$s><span style="width:18px;height:18px;border-radius:4px;background:%3$s;border:1px solid #ccc"></span></label>',
						esc_attr( $key ),
						checked( $current, $key, false ),
						esc_attr( $hex )
					);
				}
				echo '</p><p class="description">' . esc_html__( 'Le titre de la bannière est le grand titre affiché. L’ordre se règle dans « Attributs → Ordre ». Les images d’exemple doivent être remplacées par vos propres visuels.', 'itbenz' ) . '</p>';
			},
			'itbenz_banner',
			'normal',
			'high'
		);
	}
);

add_action(
	'save_post_itbenz_banner',
	static function ( int $post_id ): void {
		if ( ! isset( $_POST['itbenz_banner_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['itbenz_banner_nonce'] ), 'itbenz_banner' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = (array) wp_unslash( $_POST['itbenz_banner'] ?? array() );
		foreach ( array_keys( itbenz_banner_fields() ) as $key ) {
			$v = (string) ( $in[ $key ] ?? '' );
			update_post_meta( $post_id, '_itbenz_' . $key, 'url' === $key ? esc_url_raw( $v ) : sanitize_text_field( $v ) );
		}
		update_post_meta( $post_id, '_itbenz_full', empty( $in['full'] ) ? 0 : 1 );
		$place = sanitize_key( $in['place'] ?? 'hero' );
		update_post_meta( $post_id, '_itbenz_place', in_array( $place, array( 'hero', 'inspire' ), true ) || ctype_digit( $place ) ? $place : 'hero' );
		$color = sanitize_key( $in['color'] ?? 'onyx' );
		update_post_meta( $post_id, '_itbenz_color', isset( ITBENZ_BANNER_COLORS[ $color ] ) ? $color : 'onyx' );
		delete_transient( 'itbenz_banners' );
	}
);
add_action( 'deleted_post', static fn() => delete_transient( 'itbenz_banners' ) );
add_action( 'transition_post_status', static function ( $new, $old, $post ) {
	if ( 'itbenz_banner' === $post->post_type ) {
		delete_transient( 'itbenz_banners' );
	}
}, 10, 3 );

/** Published banners, ordered — used by the theme. */
function itbenz_get_banners( int $limit = 10 ): array {
	$cached = get_transient( 'itbenz_banners' );
	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'itbenz_banner', 'post_status' => 'publish', 'numberposts' => 20, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) ) as $p ) {
		$color = get_post_meta( $p->ID, '_itbenz_color', true ) ?: 'onyx';
		$out[] = array(
			'id'       => $p->ID,
			'title'    => get_the_title( $p ),
			'image_id' => (int) get_post_thumbnail_id( $p ),
			'eyebrow'  => (string) get_post_meta( $p->ID, '_itbenz_eyebrow', true ),
			'subtitle' => (string) get_post_meta( $p->ID, '_itbenz_subtitle', true ),
			'cta'      => (string) get_post_meta( $p->ID, '_itbenz_cta', true ),
			'url'      => (string) get_post_meta( $p->ID, '_itbenz_url', true ),
			'color'    => $color,
			'hex'      => ITBENZ_BANNER_COLORS[ $color ] ?? '#202124',
			'full'     => (bool) get_post_meta( $p->ID, '_itbenz_full', true ),
			'place'    => (string) get_post_meta( $p->ID, '_itbenz_place', true ) ?: 'hero',
		);
	}
	set_transient( 'itbenz_banners', $out, DAY_IN_SECONDS );
	return array_slice( $out, 0, $limit );
}

/** Banners for one homepage slot: 'hero', 'inspire' or a product_cat term ID. */
function itbenz_banners_for( string $place ): array {
	return array_values( array_filter( itbenz_get_banners( 20 ), static fn( $b ) => $b['place'] === $place ) );
}
