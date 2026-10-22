<?php
/**
 * Barre de livraison gratuite : « Plus que 12,50 € pour la livraison
 * gratuite ». Montant calculé côté serveur, comme WooCommerce le fait pour
 * sa méthode « Livraison gratuite » (sous-total affiché, remises déduites
 * sauf réglage contraire), plutôt que lu dans le texte affiché.
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

/**
 * État de la barre (sans WordPress : testé par tests/test-progress.php).
 *
 * @param float $amount    Montant du panier retenu.
 * @param float $threshold Seuil de livraison gratuite.
 * @return array remaining (float), percent (0-100), reached (bool).
 */
function saito_mc_progress_state( $amount, $threshold ) {
	$amount    = max( 0.0, (float) $amount );
	$threshold = max( 0.0, (float) $threshold );
	if ( $threshold <= 0 ) {
		return array(
			'remaining' => 0.0,
			'percent'   => 100,
			'reached'   => true,
		);
	}
	$remaining = round( max( 0.0, $threshold - $amount ), 2 );
	return array(
		'remaining' => $remaining,
		'percent'   => (int) floor( min( 100, $amount / $threshold * 100 ) ),
		'reached'   => $remaining <= 0,
	);
}

/**
 * Méthode « Livraison gratuite » avec montant minimum de la zone du client.
 *
 * @return WC_Shipping_Free_Shipping|null
 */
function saito_mc_free_shipping_method() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}
	$packages = WC()->cart->get_shipping_packages();
	$package  = $packages ? reset( $packages ) : array( 'destination' => array( 'country' => WC()->countries->get_base_country() ) );
	$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' === $method->id && in_array( $method->requires, array( 'min_amount', 'either', 'both' ), true ) && (float) $method->min_amount > 0 ) {
			return $method;
		}
	}
	return null;
}

/**
 * Seuil de livraison gratuite : celui de la méthode WooCommerce (mode
 * automatique), sinon le montant saisi dans les réglages. 0 = pas de barre.
 *
 * @return float
 */
function saito_mc_threshold() {
	if ( 'auto' === saito_mc_setting( 'progress_threshold' ) ) {
		$method = saito_mc_free_shipping_method();
		if ( $method ) {
			return (float) $method->min_amount;
		}
	}
	return (float) saito_mc_setting( 'progress_amount' );
}

/**
 * Montant du panier comparé au seuil, calculé comme WooCommerce pour sa
 * méthode « Livraison gratuite » (WC_Shipping_Free_Shipping::is_available()).
 *
 * @return float
 */
function saito_mc_cart_amount() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	$cart   = WC()->cart;
	$total  = (float) $cart->get_displayed_subtotal();
	$method = 'auto' === saito_mc_setting( 'progress_threshold' ) ? saito_mc_free_shipping_method() : null;
	if ( $cart->display_prices_including_tax() ) {
		$total -= (float) $cart->get_discount_tax();
	}
	if ( ! $method || 'yes' !== $method->ignore_discounts ) {
		$total -= (float) $cart->get_discount_total();
	}
	return round( max( 0.0, $total ), wc_get_price_decimals() );
}

/**
 * Montant formaté en texte brut (« 12,50 € »), entités décodées.
 *
 * @param float $amount Montant.
 * @return string
 */
function saito_mc_price_text( $amount ) {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Balisage de la barre pour le panier en cours. Vide si la barre est
 * désactivée, sans seuil ou si le panier est vide.
 *
 * @param string $context 'minicart', 'cart' ou 'shortcode'.
 * @return string
 */
function saito_mc_progress_html( $context = 'minicart' ) {
	if ( ! saito_mc_setting( 'progress_enabled' ) || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return '';
	}
	$threshold = saito_mc_threshold();
	if ( $threshold <= 0 ) {
		return '';
	}
	$state = saito_mc_progress_state( saito_mc_cart_amount(), $threshold );

	if ( $state['reached'] ) {
		$message = esc_html( saito_mc_text( 'progress_done_text', __( 'Bonne nouvelle : la livraison est offerte !', 'saito-mini-cart' ) ) );
	} else {
		/* translators: {amount}: montant restant, ex. 12,50 €. */
		$template = saito_mc_text( 'progress_text', __( 'Plus que {amount} pour profiter de la livraison gratuite.', 'saito-mini-cart' ) );
		$message  = str_replace( '{amount}', '<strong>' . esc_html( saito_mc_price_text( $state['remaining'] ) ) . '</strong>', esc_html( $template ) );
	}

	return sprintf(
		'<div class="saito-mc-progress saito-mc-progress--%1$s%2$s" role="status" aria-live="polite"><p class="saito-mc-progress__text">%3$s</p><div class="saito-mc-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%4$d" aria-label="%5$s"><span class="saito-mc-progress__fill" style="width:%4$d%%"></span></div></div>',
		esc_attr( $context ),
		$state['reached'] ? ' is-reached' : '',
		$message,
		(int) $state['percent'],
		esc_attr__( 'Progression vers la livraison gratuite', 'saito-mini-cart' )
	);
}

add_shortcode( 'saito_mini_cart_progress', 'saito_mc_progress_shortcode' );

/**
 * Shortcode [saito_mini_cart_progress] : la barre n'importe où.
 *
 * @return string
 */
function saito_mc_progress_shortcode() {
	saito_mc_enqueue_assets();
	return '<div class="saito-mc-slot" data-saito-mc="progress-shortcode">' . saito_mc_progress_html( 'shortcode' ) . '</div>';
}

// Mini-panier classique (widget, thèmes non-blocs) : rafraîchi par les
// fragments WooCommerce à chaque modification du panier.
add_action( 'woocommerce_before_mini_cart', 'saito_mc_classic_minicart_progress' );

/**
 * Barre en haut du mini-panier classique.
 */
function saito_mc_classic_minicart_progress() {
	if ( saito_mc_setting( 'progress_in_minicart' ) ) {
		echo saito_mc_progress_html( 'minicart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans saito_mc_progress_html().
	}
}

// Page panier classique.
add_action( 'woocommerce_before_cart', 'saito_mc_classic_cart_progress' );

/**
 * Barre au-dessus du panier (page panier classique).
 */
function saito_mc_classic_cart_progress() {
	if ( saito_mc_setting( 'progress_in_cart' ) ) {
		echo '<div class="saito-mc-slot" data-saito-mc="progress-cart">' . saito_mc_progress_html( 'cart' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans saito_mc_progress_html().
	}
}

// Page panier en blocs : la barre est placée avant le bloc Panier, puis
// tenue à jour par assets/js/mini-cart.js.
add_filter( 'render_block_woocommerce/cart', 'saito_mc_block_cart_progress' );

/**
 * Barre au-dessus du bloc Panier.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function saito_mc_block_cart_progress( $content ) {
	if ( ! saito_mc_setting( 'progress_in_cart' ) || ! saito_mc_setting( 'progress_enabled' ) ) {
		return $content;
	}
	saito_mc_enqueue_assets();
	return '<div class="saito-mc-slot" data-saito-mc="progress-cart">' . saito_mc_progress_html( 'cart' ) . '</div>' . $content;
}
