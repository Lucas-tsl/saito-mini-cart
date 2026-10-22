# Saito Mini Cart

Plugin WordPress / WooCommerce de la famille Saito : améliore le mini-panier (tiroir du panier ouvert depuis l'en-tête), pour les thèmes en blocs (bloc Mini-Cart) comme pour les thèmes classiques (widget panier).

- **Barre de livraison gratuite** : « Plus que 12,50 € pour profiter de la livraison gratuite », dans le mini-panier et au-dessus de la page panier. Seuil lu dans la méthode « Livraison gratuite » de WooCommerce (zone du client) ou saisi à la main. Montant calculé côté serveur comme WooCommerce. Shortcode `[saito_mini_cart_progress]`.
- **Produits recommandés** : carrousel en bas du mini-panier (ventes croisées, produits suggérés, sélection manuelle, meilleures ventes), ajout en un clic pour les produits simples.
- **Boutons** : texte et adresse de « Voir le panier » et « Commander ».

Réglages : **Saito → Mini-panier**. Reprend automatiquement, à l'activation, les réglages des modules « Barre de livraison » et « Redirection mini-panier » de Saito Dev Toolkit.

## Développement

```
composer install && vendor/bin/phpcs      # WordPress Coding Standards
for t in tests/test-*.php; do php "$t"; done
npm install && npm run lint                 # ESLint + Stylelint
./scripts/build-wporg-release.sh            # zip WordPress.org dans build/
```

Le menu « Saito » commun vient de [saito-hub](https://github.com/Lucas-tsl/saito-hub) (`includes/saito-hub/`, ne pas modifier ici).

## Structure

```
saito-mini-cart.php          Point d'entrée, déclaration au menu Saito
includes/settings.php        Réglages, WPML, reprise de Saito Dev Toolkit
includes/progress.php        Barre de livraison gratuite (calcul + rendu + emplacements)
includes/recommendations.php Produits recommandés
includes/buttons.php         Boutons du mini-panier classique
includes/frontend.php        Assets et route REST saito-mini-cart/v1/fragments
includes/admin.php           Page Saito > Mini-panier
assets/js/mini-cart.js       Mini-panier en blocs, carrousel, ajout au panier
```

## Licence

GPLv2 ou ultérieure.
