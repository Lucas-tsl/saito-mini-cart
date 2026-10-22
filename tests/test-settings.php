<?php
/**
 * Réglages : identifiants de produits et reprise des options de Saito Dev Toolkit.
 * Exécuté par la CI via `php tests/test-settings.php`.
 */

require __DIR__ . '/bootstrap-stubs.php';
require __DIR__ . '/../includes/settings.php';

saito_mc_assert( 'identifiants : texte avec espaces et doublons', array( 12, 34, 5 ), saito_mc_parse_ids( '12, 34,,34 5' ) );
saito_mc_assert( 'identifiants : tableau (sélecteur de produits)', array( 7, 8 ), saito_mc_parse_ids( array( '7', '8', '0' ) ) );
saito_mc_assert( 'identifiants : vide', array(), saito_mc_parse_ids( '' ) );

$map = saito_mc_map_toolkit_settings(
	array(
		'saito_progressive_bar_lsg_limit'   => '40',
		'saito_progressive_bar_lsg_enabled' => '1',
		'saito_cart_btn_text'               => 'Mon panier',
		'saito_cart_btn_url'                => 'https://exemple.fr/panier/',
		'saito_checkout_btn_text'           => '',
	)
);
saito_mc_assert( 'toolkit : seuil manuel repris', 'manual', $map['progress_threshold'] );
saito_mc_assert( 'toolkit : montant repris', 40.0, $map['progress_amount'] );
saito_mc_assert( 'toolkit : barre activée', 1, $map['progress_enabled'] );
saito_mc_assert( 'toolkit : texte du bouton panier', 'Mon panier', $map['cart_btn_text'] );
saito_mc_assert( 'toolkit : adresse du bouton panier', 'https://exemple.fr/panier/', $map['cart_btn_url'] );
saito_mc_assert( 'toolkit : texte vide non repris', false, isset( $map['checkout_btn_text'] ) );

saito_mc_assert( 'toolkit absent : rien à reprendre', array(), saito_mc_map_toolkit_settings( array() ) );

$map = saito_mc_map_toolkit_settings( array( 'saito_progressive_bar_lsg_enabled' => '0' ) );
saito_mc_assert( 'toolkit : barre désactivée reprise', 0, $map['progress_enabled'] );
saito_mc_assert( 'toolkit : sans montant, seuil automatique gardé', false, isset( $map['progress_threshold'] ) );

saito_mc_tests_done();
