<?php
/**
 * Boutons du mini-panier : texte et adresse personnalisés de « Voir le
 * panier » et « Commander » (mini-panier classique ici, mini-panier en
 * blocs dans assets/js/mini-cart.js).
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Texte et adresse d'un bouton, personnalisés ou par défaut.
 *
 * @param string $button 'cart' ou 'checkout'.
 * @return array text (vide = texte de WooCommerce), url.
 */
function saito_mc_button( $button ) {
	$url = trim( (string) saito_mc_text( $button . '_btn_url', '' ) );
	return array(
		'text' => trim( (string) saito_mc_text( $button . '_btn_text', '' ) ),
		'url'  => '' !== $url ? $url : ( 'cart' === $button ? wc_get_cart_url() : wc_get_checkout_url() ),
	);
}

add_action( 'init', 'saito_mc_replace_classic_buttons' );

/**
 * Remplace les boutons du mini-panier classique quand un texte ou une
 * adresse est personnalisé.
 */
function saito_mc_replace_classic_buttons() {
	foreach ( array(
		'cart'     => 'woocommerce_widget_shopping_cart_button_view_cart',
		'checkout' => 'woocommerce_widget_shopping_cart_proceed_to_checkout',
	) as $button => $callback ) {
		if ( '' === trim( (string) saito_mc_setting( $button . '_btn_text' ) ) && '' === trim( (string) saito_mc_setting( $button . '_btn_url' ) ) ) {
			continue;
		}
		$priority = 'cart' === $button ? 10 : 20;
		if ( remove_action( 'woocommerce_widget_shopping_cart_buttons', $callback, $priority ) ) {
			add_action( 'woocommerce_widget_shopping_cart_buttons', 'saito_mc_classic_button_' . $button, $priority );
		}
	}
}

/**
 * Bouton « Voir le panier » du mini-panier classique.
 */
function saito_mc_classic_button_cart() {
	$button = saito_mc_button( 'cart' );
	$text   = '' !== $button['text'] ? $button['text'] : __( 'Voir le panier', 'saito-mini-cart' );
	echo '<a href="' . esc_url( $button['url'] ) . '" class="button wc-forward' . esc_attr( function_exists( 'wc_wp_theme_get_element_class_name' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '">' . esc_html( $text ) . '</a>';
}

/**
 * Bouton « Commander » du mini-panier classique.
 */
function saito_mc_classic_button_checkout() {
	$button = saito_mc_button( 'checkout' );
	$text   = '' !== $button['text'] ? $button['text'] : __( 'Commander', 'saito-mini-cart' );
	echo '<a href="' . esc_url( $button['url'] ) . '" class="button checkout wc-forward' . esc_attr( function_exists( 'wc_wp_theme_get_element_class_name' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '">' . esc_html( $text ) . '</a>';
}
