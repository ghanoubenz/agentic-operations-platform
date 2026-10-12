#!/usr/bin/env bash
# IT-BENZ store provisioning (idempotent). Runs anywhere WP-CLI can reach the site:
#   staging (docker):  docker compose --profile tools run --rm cli bash /scripts/setup.sh [--demo]
#   production (SSH):  cd ~/public_html && SITE_URL=https://it-benz.dz ADMIN_EMAIL=... bash setup.sh
#
#   --demo  adds clearly-labelled EXAMPLE products, banners and TEST delivery prices.
#           Never use --demo on production.
set -euo pipefail

DEMO=0; [[ "${1:-}" == "--demo" ]] && DEMO=1
SITE_URL="${SITE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-itbenz-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
WC_ZIP="${WC_ZIP:-/vendor-cache/woocommerce.zip}"
ENV_TYPE="${WP_ENVIRONMENT_TYPE:-staging}"

if [[ $DEMO == 1 && "$ENV_TYPE" == "production" ]]; then
  echo "Refusing --demo on production." >&2; exit 1
fi

echo "==> Waiting for WordPress files and database"
for i in $(seq 1 60); do
  if [[ -f wp-config.php ]] && wp db check --quiet >/dev/null 2>&1; then break; fi
  [[ $i == 60 ]] && { echo "WordPress is not ready after 3 minutes. Is 'docker compose up -d' running? Check: docker compose logs wordpress" >&2; exit 1; }
  sleep 3
done

echo "==> WordPress core"
if ! wp core is-installed 2>/dev/null; then
  [[ -z "$ADMIN_PASSWORD" ]] && ADMIN_PASSWORD="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)" && echo "Generated admin password (shown once): $ADMIN_PASSWORD"
  wp core install --url="$SITE_URL" --title="IT-BENZ" --admin_user="$ADMIN_USER" --admin_email="$ADMIN_EMAIL" --admin_password="$ADMIN_PASSWORD" --skip-email
fi
wp option update blogdescription "Matériel informatique et accessoires tech en Algérie"
wp option update timezone_string "Africa/Algiers"
wp option update date_format "d/m/Y"
wp option update time_format "H:i"
wp option update start_of_week 0
wp option update default_comment_status closed
wp option update users_can_register 0
# Staging must not be indexed; production flips this on at go-live.
[[ "$ENV_TYPE" == "production" ]] && wp option update blog_public 1 || wp option update blog_public 0

echo "==> French language (needs access to wordpress.org; skipped if unreachable)"
wp language core install fr_FR --activate 2>/dev/null || echo "   (fr_FR core pack unavailable here — install it on the server)"

echo "==> Plugins & theme"
if ! wp plugin is-installed woocommerce; then
  if [[ -f "$WC_ZIP" ]]; then wp plugin install "$WC_ZIP"; else wp plugin install woocommerce; fi
fi
wp plugin activate woocommerce
wp plugin activate itbenz-commerce
wp theme activate itbenz
wp language plugin install woocommerce fr_FR 2>/dev/null || true
for p in akismet hello; do wp plugin delete "$p" 2>/dev/null || true; done
for t in twentytwentythree twentytwentyfour; do wp theme delete "$t" 2>/dev/null || true; done

echo "==> Permalinks (French slugs)"
wp rewrite structure '/%postname%/' --hard
wp option update woocommerce_permalinks '{"product_base":"produit","category_base":"categorie","tag_base":"etiquette","attribute_base":"","use_verbose_page_rules":false}' --format=json

echo "==> Store settings (Algeria, DZD, COD)"
wp option update woocommerce_default_country "DZ:DZ-16"
wp option update woocommerce_allowed_countries specific
wp option update woocommerce_specific_allowed_countries '["DZ"]' --format=json
wp option update woocommerce_ship_to_countries specific
wp option update woocommerce_specific_ship_to_countries '["DZ"]' --format=json
wp option update woocommerce_ship_to_destination billing_only
wp option update woocommerce_currency DZD
wp option update woocommerce_currency_pos right_space
wp option update woocommerce_price_thousand_sep " "
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_num_decimals 0
wp option update woocommerce_weight_unit kg
wp option update woocommerce_dimension_unit cm
wp option update woocommerce_calc_taxes no
wp option update woocommerce_enable_coupons yes
wp option update woocommerce_checkout_phone_field required
wp option update woocommerce_checkout_company_field hidden
wp option update woocommerce_checkout_address_2_field hidden
wp option update woocommerce_enable_guest_checkout yes
wp option update woocommerce_enable_checkout_login_reminder yes
wp option update woocommerce_enable_signup_and_login_from_checkout yes
wp option update woocommerce_enable_myaccount_registration no
wp option update woocommerce_registration_generate_password yes
wp option update woocommerce_enable_shipping_calc no
wp option update woocommerce_shipping_cost_requires_address yes
wp option update woocommerce_manage_stock yes
wp option update woocommerce_notify_low_stock_amount 2
wp option update woocommerce_hold_stock_minutes 60
wp option update woocommerce_enable_reviews no
wp option update woocommerce_coming_soon no
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json
wp option update woocommerce_task_list_hidden yes 2>/dev/null || true
wp option update woocommerce_show_marketplace_suggestions no
wp option update woocommerce_allow_tracking no
wp option update woocommerce_email_from_name "IT-BENZ"
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Paiement à la livraison","description":"Payez en espèces à la réception de votre commande.","instructions":"Votre commande est enregistrée. Nous vous contacterons pour la confirmer avant l’expédition. Paiement en espèces à la livraison.","enable_for_methods":[],"enable_for_virtual":"no"}' --format=json
wp option update woocommerce_bacs_settings '{"enabled":"no"}' --format=json
wp option update woocommerce_cheque_settings '{"enabled":"no"}' --format=json
wp option update woocommerce_new_order_settings "{\"enabled\":\"yes\",\"recipient\":\"$ADMIN_EMAIL\"}" --format=json

# Localized endpoints.
wp option update woocommerce_checkout_order_received_endpoint commande-recue
wp option update woocommerce_checkout_pay_endpoint payer
wp option update woocommerce_myaccount_orders_endpoint commandes
wp option update woocommerce_myaccount_view_order_endpoint voir-commande
wp option update woocommerce_myaccount_edit_address_endpoint adresses
wp option update woocommerce_myaccount_edit_account_endpoint compte
wp option update woocommerce_myaccount_lost_password_endpoint mot-de-passe-perdu
wp option update woocommerce_logout_endpoint deconnexion
wp option update woocommerce_myaccount_downloads_endpoint telechargements

echo "==> Pages"
page() { # slug title content [status]
  local id; id=$(wp post list --post_type=page --name="$1" --post_status=any --field=ID --posts_per_page=1)
  if [[ -z "$id" ]]; then id=$(wp post create --post_type=page --post_name="$1" --post_title="$2" --post_content="$3" --post_status="${4:-publish}" --porcelain); fi
  echo "$id"
}
HOME_ID=$(page accueil "Accueil" "")
SHOP_ID=$(page boutique "Boutique" "")
CART_ID=$(page panier "Panier" "[woocommerce_cart]")
CHECKOUT_ID=$(page commande "Commande" "[woocommerce_checkout]")
ACCOUNT_ID=$(page mon-compte "Mon compte" "[woocommerce_my_account]")
wp post update "$CART_ID" --post_content="[woocommerce_cart]" >/dev/null
wp post update "$CHECKOUT_ID" --post_content="[woocommerce_checkout]" >/dev/null
wp option update woocommerce_shop_page_id "$SHOP_ID"
wp option update woocommerce_cart_page_id "$CART_ID"
wp option update woocommerce_checkout_page_id "$CHECKOUT_ID"
wp option update woocommerce_myaccount_page_id "$ACCOUNT_ID"
wp option update show_on_front page
wp option update page_on_front "$HOME_ID"
# Policy pages are created as DRAFTS: the merchant writes the real policy, then publishes.
TODO="<p><strong>À COMPLÉTER PAR IT-BENZ avant publication.</strong> N’indiquez que des informations vérifiées (délais, tarifs, durée de garantie, conditions).</p>"
LIV=$(page livraison "Livraison" "$TODO" draft)
GAR=$(page garantie "Garantie" "$TODO" draft)
RET=$(page retours-echanges "Retours & échanges" "$TODO" draft)
CGV=$(page conditions-generales-de-vente "Conditions générales de vente" "$TODO" draft)
CTC=$(page contact "Contact" "$TODO" draft)
PRIV=$(wp option get wp_page_for_privacy_policy 2>/dev/null || echo 0)
[[ "$PRIV" != "0" ]] && wp post update "$PRIV" --post_title="Politique de confidentialité" --post_name=politique-de-confidentialite >/dev/null || true
# Terms checkbox: set woocommerce_terms_page_id only AFTER the CGV page is written and published (docs/04-merchant-guide.md).
wp post delete 1 --force 2>/dev/null || true  # "Hello world"
wp post delete 2 --force 2>/dev/null || true  # "Sample page"

echo "==> Footer menu"
if ! wp menu list --fields=slug --format=csv | grep -q '^aide$'; then
  wp menu create "Aide" >/dev/null
  for id in "$LIV" "$GAR" "$RET" "$CGV" "$CTC"; do wp menu item add-post aide "$id" >/dev/null; done
  wp menu location assign aide footer
fi

echo "==> Shipping zone + delivery method + attributes + categories"
wp eval-file - <<'PHP'
<?php
// Shipping zone "Algérie" with the per-wilaya method.
$zone_id = 0;
foreach ( WC_Shipping_Zones::get_zones() as $z ) { if ( 'Algérie' === $z['zone_name'] ) { $zone_id = $z['id']; } }
$zone = new WC_Shipping_Zone( $zone_id ?: null );
if ( ! $zone_id ) {
	$zone->set_zone_name( 'Algérie' );
	$zone->add_location( 'DZ', 'country' );
	$zone->save();
}
$has = array_filter( $zone->get_shipping_methods(), fn( $m ) => 'itbenz_wilaya' === $m->id );
if ( ! $has ) { $zone->add_shipping_method( 'itbenz_wilaya' ); }

// Technical attributes (global, filterable, CSV-importable).
$attributes = array(
	'taille-ecran'   => 'Taille d’écran',
	'resolution'     => 'Résolution',
	'frequence'      => 'Taux de rafraîchissement',
	'dalle'          => 'Type de dalle',
	'connectique'    => 'Connectique',
	'connexion'      => 'Connexion',
	'disposition'    => 'Disposition clavier',
	'switch'         => 'Type de switch',
	'compatibilite'  => 'Compatibilité',
	'capacite'       => 'Capacité',
	'interface'      => 'Interface',
	'dpi'            => 'Sensibilité (DPI)',
	'processeur'     => 'Processeur',
	'memoire'        => 'Mémoire (RAM)',
	'stockage'       => 'Stockage',
	'couleur'        => 'Couleur',
);
$existing = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' );
foreach ( $attributes as $slug => $label ) {
	if ( ! in_array( $slug, $existing, true ) ) {
		wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
	}
}

// Proposed categories — confirm against real stock before publishing products.
$cats = array(
	'souris-claviers'      => 'Souris & claviers',
	'ecrans'               => 'Écrans & moniteurs',
	'pc-portables-bureau'  => 'PC portables & bureau',
	'casques-audio'        => 'Casques & audio',
	'gaming'               => 'Accessoires gaming',
	'stockage'             => 'Stockage',
	'cables-adaptateurs'   => 'Câbles & adaptateurs',
	'reseau'               => 'Réseau',
	'composants-pc'        => 'Composants PC',
	'packs'                => 'Packs',
	'bureautique'          => 'Bureautique & impression',
	'accessoires'          => 'Accessoires & mobilité',
	'coffrets-cadeaux'     => 'Coffrets cadeaux',
);
$i = 0;
foreach ( $cats as $slug => $name ) {
	$t = term_exists( $slug, 'product_cat' );
	if ( ! $t ) { $t = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) ); }
	if ( ! is_wp_error( $t ) ) { update_term_meta( (int) $t['term_id'], 'order', $i++ ); }
}
$uncat = (int) get_option( 'default_product_cat' );
if ( $uncat ) { wp_update_term( $uncat, 'product_cat', array( 'name' => 'Non classé', 'slug' => 'non-classe' ) ); }
echo "ok\n";
PHP

if [[ $DEMO == 1 ]]; then
  echo "==> DEMO content (placeholders, staging only)"
  wp eval-file /scripts/demo-content.php
fi

wp rewrite flush --hard
wp cache flush >/dev/null || true
echo "==> Done. Store: $SITE_URL  Admin: $SITE_URL/wp-admin/"
