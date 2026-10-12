# 4. Guide du gestionnaire de boutique (IT-BENZ)

Connexion : `https://it-benz.dz/wp-admin/` avec votre compte **Gestionnaire de boutique**.
Le tableau de bord affiche le bloc **« IT-BENZ — Accès rapide »** : nombre de commandes par statut et boutons vers chaque tâche.

---

## Avant l'ouverture (une seule fois)

1. **IT-BENZ → Infos boutique** : téléphone, WhatsApp (format `213XXXXXXXXX`), e-mail, adresse, horaires, textes livraison / garantie / retours / service client, liens réseaux sociaux. *Un champ vide n'apparaît pas sur le site. N'écrivez que des informations vraies.*
2. **WooCommerce → Livraison IT-BENZ** : saisissez les **vrais tarifs** de votre transporteur pour chaque wilaya (domicile et/ou stop desk). Une wilaya sans tarif ne peut pas commander en ligne.
3. **Pages** (administrateur) : rédigez puis publiez *Livraison*, *Garantie*, *Retours & échanges*, *Conditions générales de vente*, *Contact*. Elles apparaissent dans le pied de page dès qu'elles sont publiées. Ensuite, dans **WooCommerce → Réglages → Avancé → Page des conditions**, choisissez « Conditions générales de vente » pour afficher la case à cocher au moment de la commande.
4. **Supprimez les exemples** (staging uniquement) : tous les produits, bannières et images marqués **EXEMPLE**.

## Produits

### Ajouter un produit
**Produits → Ajouter**
* **Nom** exact (marque + modèle).
* **Description** : texte du fabricant, vérifié.
* **Données produit → Général** : *Tarif régulier* (DA) et, si promotion, *Tarif promo* (le prix barré et le badge « -X% » s'affichent tout seuls).
* **Inventaire** : *UGS/SKU*, *Référence / modèle constructeur*, *Garantie* (laisser vide si non confirmée), cocher *Suivre la quantité en stock* et saisir le stock.
* **Attributs** : choisissez les caractéristiques (Taille d'écran, Résolution, Taux de rafraîchissement, Type de dalle, Connectique, Connexion, Disposition clavier, Type de switch, Compatibilité, Capacité, DPI…) puis cochez *Visible sur la page produit*. Elles forment le tableau **« Caractéristiques techniques »**.
* **Marques** (colonne de droite) : choisissez ou créez la marque.
* **Catégories**, **Image produit** (fond blanc de préférence, carré, min. 1000 px) et **Galerie**.
* **Mise en avant** : l'étoile ☆ dans la liste des produits = produit mis en avant.

### Importer / modifier en masse (CSV)
1. Ouvrez `data/produits-modele.csv` dans Excel ou Google Sheets. Une ligne = un produit. Gardez la ligne d'en-tête telle quelle.
2. `Published` : `-1` = brouillon (conseillé pour vérifier), `1` = publié.
3. Plusieurs valeurs dans une cellule : séparées par des virgules (`"Windows, macOS"`).
4. **Produits → Importer** → choisissez le fichier → *Exécuter l'importateur*. Si une colonne n'est pas reconnue automatiquement, choisissez-la dans la liste.
5. Pour **modifier les prix ou stocks en masse** : **Produits → Exporter** → modifiez le fichier → **Importer** en cochant *Mettre à jour les produits existants* (repère : le SKU).

## Commandes

**WooCommerce → Commandes**. La colonne « Wilaya / téléphone » montre la destination et le numéro.

| Statut | Signification | Action |
|---|---|---|
| **Nouvelle** | commande reçue (paiement à la livraison) | appeler ou WhatsApp le client (bouton **« Confirmer par WhatsApp »** dans la commande) |
| **Confirmée** | client joint, commande validée | préparer |
| **En préparation** | colis en cours | remettre au transporteur |
| **Expédiée** | colis chez le transporteur | noter le n° de suivi dans une *note de commande* |
| **Livrée** | payé et livré | — |
| **Annulée** | client injoignable / annulation | le stock est réintégré automatiquement |
| **Retournée** | colis retourné | le stock est réintégré automatiquement |

* Changer un statut : ouvrir la commande → *Statut* → *Mettre à jour*. Pour plusieurs commandes : cocher → *Actions groupées* → « Passer en … ».
* Rechercher : par nom, téléphone ou numéro `ITB-1234`.
* Vous recevez un e-mail **« [IT-BENZ] Nouvelle commande ITB-… »** à chaque commande. Le destinataire se règle dans *WooCommerce → Réglages → E-mails → Nouvelle commande*.

## Page d'accueil

* **Bannières** : l'ordre (*Attributs → Ordre*) décide de leur place :
  * **n° 1** = grande bannière du haut (grand titre + photo)
  * **n° 2** = carte « inspiration » sous les catégories
  * **n° 3 et suivantes** = carte colorée + photo à la fin de chaque collection
  * Pour chaque bannière : titre, sur-titre, sous-titre, bouton, lien, couleur de fond, photo. Mettre en *Brouillon* pour masquer.
* **Produits → Catégories → (modifier)** :
  * *Miniature* = petite image de la tuile catégorie.
  * *Afficher sur l'accueil* = la catégorie devient une **collection** (5 max).
  * *Photo de collection* = grande photo.
  * *Texte mis en avant* = phrase dans la carte colorée.
  * *Ordre* : glisser-déposer les catégories dans la liste.
* Sections automatiques :
  * *Promotions* = produits avec un tarif promo.
  * *Les plus demandés* = ventes.
  * *Nouveaux arrivages* = derniers produits.

## Promotions et codes promo

* **Prix barré** : *Tarif promo* sur le produit (avec dates facultatives).
* **Code promo** : **Marketing → Codes promo → Ajouter** : montant fixe ou %, date d'expiration, minimum de commande, limite d'utilisation.

## Livraison gratuite

**WooCommerce → Livraison IT-BENZ** → *Livraison gratuite à partir de* (DA). `0` = jamais.

## Statistiques et exports

* **Analytique → Vue d'ensemble / Commandes / Produits** : chiffre d'affaires, commandes, produits les plus vendus, par période.
* **Exporter les commandes** : *Analytique → Commandes* → choisir la période → **Télécharger** (CSV).

## Bonnes pratiques

* Ne partagez jamais votre mot de passe. Chaque personne a son propre compte.
* Ne publiez que des prix, stocks, délais et garanties vérifiés.
* En cas de doute ou d'erreur, ne supprimez rien : mettez en *Brouillon* et prévenez l'administrateur.
