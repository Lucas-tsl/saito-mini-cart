<?php
/**
 * Shortcodes du modèle du mini-panier (saito_mc_mini_cart_shortcodes()) : le
 * paragraphe qui les entoure doit disparaître en entier, sinon le bloc est mal
 * formé et le tiroir s'affiche ouvert au chargement de chaque page.
 * Exécuté par la CI via `php tests/test-mini-cart-shortcodes.php`.
 */

require __DIR__ . '/bootstrap-stubs.php';

function saito_mc_progress_shortcode() {
	return '<div data-saito-mc="progress-shortcode"></div>';
}
function saito_mc_recommendations_shortcode() {
	return '<div data-saito-mc="reco-shortcode"></div>';
}

require __DIR__ . '/../includes/frontend.php';

saito_mc_assert(
	'paragraphe avec classe (WordPress 7) retiré en entier',
	"<div>\n<div data-saito-mc=\"reco-shortcode\"></div>\n</div>",
	saito_mc_mini_cart_shortcodes( "<div>\n<p class=\"wp-block-paragraph\">[saito_mini_cart_recommendations]</p>\n</div>" )
);
saito_mc_assert(
	'paragraphe simple retiré',
	'<div data-saito-mc="progress-shortcode"></div>',
	saito_mc_mini_cart_shortcodes( '<p>[saito_mini_cart_progress]</p>' )
);
saito_mc_assert(
	'bloc Code court (sans paragraphe)',
	"x\n<div data-saito-mc=\"progress-shortcode\"></div>\ny",
	saito_mc_mini_cart_shortcodes( "x\n[saito_mini_cart_progress]\ny" )
);
saito_mc_assert(
	'paragraphe avec autre texte : seul le shortcode est remplacé',
	'<p>Bonjour <div data-saito-mc="progress-shortcode"></div></p>',
	saito_mc_mini_cart_shortcodes( '<p>Bonjour [saito_mini_cart_progress]</p>' )
);
saito_mc_assert( 'sans shortcode : inchangé', '<p>[autre]</p>', saito_mc_mini_cart_shortcodes( '<p>[autre]</p>' ) );

saito_mc_tests_done();
