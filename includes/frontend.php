<?php
/**
 * Site : feuille de style, script et route REST qui renvoie la barre et le
 * carrousel à jour pour le panier en cours. Le mini-panier en blocs de
 * WooCommerce est rendu dans le navigateur : assets/js/mini-cart.js y
 * place ces éléments et les rafraîchit à chaque modification du panier.
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'saito_mc_maybe_enqueue_assets' );
add_filter( 'render_block_woocommerce/mini-cart-contents', 'saito_mc_mini_cart_shortcodes' );

/**
 * WooCommerce n'interprète pas les shortcodes dans le modèle du mini-panier
 * (Éditeur de site > Compositions > Mini-panier) : on y remplace nous-mêmes
 * les deux shortcodes du plugin, qu'ils soient dans un bloc Code court ou
 * dans un paragraphe (balises <p> autour retirées, une <div> n'a pas sa
 * place dans un paragraphe). Les autres shortcodes ne sont pas touchés.
 *
 * @param string $content Rendu du bloc Mini-Cart Contents.
 * @return string
 */
function saito_mc_mini_cart_shortcodes( $content ) {
	if ( false === strpos( $content, '[saito_mini_cart_' ) ) {
		return $content;
	}
	// Le paragraphe qui entoure le shortcode est retiré en entier, balise
	// d'ouverture comprise (WordPress lui ajoute une classe) : un <p> laissé
	// ouvert rend le bloc mal formé, et WordPress renonce alors à poser côté
	// serveur l'état initial du tiroir (fermé) — il s'affichait ouvert à
	// chaque chargement de page, puis se refermait.
	$result = preg_replace_callback(
		'#<p\b[^>]*>\s*\[saito_mini_cart_(progress|recommendations)\s*/?\]\s*</p>|\[saito_mini_cart_(progress|recommendations)\s*/?\]#',
		function ( $matches ) {
			$name = '' !== $matches[1] ? $matches[1] : $matches[2];
			return 'progress' === $name ? saito_mc_progress_shortcode() : saito_mc_recommendations_shortcode();
		},
		$content
	);
	return null === $result ? $content : $result;
}
add_action( 'rest_api_init', 'saito_mc_register_rest_route' );

/**
 * Le mini-panier est présent sur tout le site (en-tête) : assets chargés
 * partout dès qu'une fonction est active.
 */
function saito_mc_maybe_enqueue_assets() {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}
	$settings = saito_mc_get_settings();
	$buttons  = '' !== $settings['cart_btn_text'] . $settings['cart_btn_url'] . $settings['checkout_btn_text'] . $settings['checkout_btn_url'];
	if ( $settings['progress_enabled'] || $settings['reco_enabled'] || $buttons ) {
		saito_mc_enqueue_assets();
	}
}

/**
 * Feuille de style et script (une seule fois par page).
 */
function saito_mc_enqueue_assets() {
	if ( wp_script_is( 'saito-mini-cart', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style( 'saito-mini-cart', SAITO_MC_URL . 'assets/css/mini-cart.css', array(), SAITO_MC_VERSION );
	wp_enqueue_script( 'saito-mini-cart', SAITO_MC_URL . 'assets/js/mini-cart.js', array(), SAITO_MC_VERSION, true );

	$settings = saito_mc_get_settings();
	$cart     = saito_mc_button( 'cart' );
	$checkout = saito_mc_button( 'checkout' );
	wp_localize_script(
		'saito-mini-cart',
		'saitoMiniCart',
		array(
			'restUrl'          => esc_url_raw( rest_url( 'saito-mini-cart/v1/fragments' ) ),
			'restNonce'        => wp_create_nonce( 'wp_rest' ),
			'addToCartUrl'     => esc_url_raw( WC_AJAX::get_endpoint( 'add_to_cart' ) ),
			'progressMiniCart' => (bool) ( $settings['progress_enabled'] && $settings['progress_in_minicart'] ),
			'reco'             => (bool) ( $settings['reco_enabled'] && $settings['reco_in_minicart'] ),
			'buttons'          => array(
				'cartText'     => $cart['text'],
				'cartUrl'      => '' !== trim( (string) $settings['cart_btn_url'] ) ? $cart['url'] : '',
				'checkoutText' => $checkout['text'],
				'checkoutUrl'  => '' !== trim( (string) $settings['checkout_btn_url'] ) ? $checkout['url'] : '',
			),
			'i18n'             => array(
				'added' => __( 'Ajouté ✓', 'saito-mini-cart' ),
				'error' => __( 'Réessayer', 'saito-mini-cart' ),
			),
		)
	);
}

/**
 * GET /wp-json/saito-mini-cart/v1/fragments : barre et carrousel pour le
 * panier du visiteur. Lecture seule, publique (le panier est celui de la
 * session du visiteur).
 */
function saito_mc_register_rest_route() {
	register_rest_route(
		'saito-mini-cart/v1',
		'/fragments',
		array(
			'methods'             => 'GET',
			'callback'            => 'saito_mc_rest_fragments',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Réponse de la route : HTML de la barre (mini-panier, page panier,
 * shortcode) et du carrousel.
 *
 * @return WP_REST_Response
 */
function saito_mc_rest_fragments() {
	if ( function_exists( 'wc_load_cart' ) && ( ! WC()->cart || ! WC()->session ) ) {
		wc_load_cart();
	}
	if ( WC()->cart ) {
		WC()->cart->calculate_totals();
	}
	$response = rest_ensure_response(
		array(
			'progressMiniCart' => saito_mc_setting( 'progress_in_minicart' ) ? saito_mc_progress_html( 'minicart' ) : '',
			'progressCart'     => saito_mc_setting( 'progress_in_cart' ) ? saito_mc_progress_html( 'cart' ) : '',
			'progressShortcode' => saito_mc_progress_html( 'shortcode' ),
			'reco'             => saito_mc_recommendations_html(),
		)
	);
	// Propre au panier du visiteur : jamais mis en cache.
	$response->header( 'Cache-Control', 'no-store, private' );
	return $response;
}
