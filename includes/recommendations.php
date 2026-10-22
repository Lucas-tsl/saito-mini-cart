<?php
/**
 * Produits recommandés : carrousel en bas du mini-panier, avec un bouton
 * d'ajout direct pour les produits simples.
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ordre de priorité des sources et dédoublonnage (sans WordPress : testé
 * par tests/test-recommendations.php).
 *
 * @param int[][] $sources  Listes d'identifiants, par ordre de priorité.
 * @param int[]   $excluded Identifiants à exclure (déjà dans le panier).
 * @param int     $limit    Nombre maximum.
 * @return int[]
 */
function saito_mc_merge_recommendations( $sources, $excluded, $limit ) {
	$excluded = array_map( 'intval', (array) $excluded );
	$result   = array();
	foreach ( $sources as $ids ) {
		foreach ( (array) $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $excluded, true ) && ! in_array( $id, $result, true ) ) {
				$result[] = $id;
				if ( count( $result ) >= $limit ) {
					return $result;
				}
			}
		}
	}
	return $result;
}

/**
 * Produits à recommander pour le panier en cours : ventes croisées des
 * produits du panier, puis produits suggérés (montée en gamme), puis la
 * liste des réglages, puis les meilleures ventes. Produits du panier,
 * indisponibles ou non visibles exclus.
 *
 * @return WC_Product[]
 */
function saito_mc_recommended_products() {
	if ( ! saito_mc_setting( 'reco_enabled' ) || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return array();
	}
	$limit      = (int) saito_mc_setting( 'reco_limit' );
	$in_cart    = array();
	$cross_sell = array();
	$upsell     = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$in_cart[] = (int) $item['product_id'];
		if ( ! empty( $item['variation_id'] ) ) {
			$in_cart[] = (int) $item['variation_id'];
		}
		$product = wc_get_product( $item['product_id'] );
		if ( $product ) {
			$cross_sell = array_merge( $cross_sell, $product->get_cross_sell_ids() );
			$upsell     = array_merge( $upsell, $product->get_upsell_ids() );
		}
	}
	$sources = array( $cross_sell, $upsell, saito_mc_parse_ids( saito_mc_setting( 'reco_products' ) ) );
	if ( saito_mc_setting( 'reco_bestsellers' ) ) {
		$sources[] = wc_get_products(
			array(
				'status'       => 'publish',
				'visibility'   => 'catalog',
				'stock_status' => 'instock',
				'limit'        => $limit + count( $in_cart ),
				'orderby'      => 'meta_value_num',
				'meta_key'     => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- tri des meilleures ventes, requête limitée.
				'order'        => 'DESC',
				'return'       => 'ids',
			)
		);
	}
	$candidates = saito_mc_merge_recommendations( $sources, $in_cart, $limit * 2 );

	$products = array();
	foreach ( $candidates as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_visible() && $product->is_in_stock() && $product->is_purchasable() ) {
			$products[] = $product;
			if ( count( $products ) >= $limit ) {
				break;
			}
		}
	}
	return (array) apply_filters( 'saito_mc_recommended_products', $products );
}

/**
 * Balisage du carrousel. Vide s'il n'y a rien à recommander.
 *
 * @return string
 */
function saito_mc_recommendations_html() {
	$products = saito_mc_recommended_products();
	if ( ! $products ) {
		return '';
	}
	$title = saito_mc_text( 'reco_title', __( 'Vous aimerez aussi', 'saito-mini-cart' ) );
	$html  = '<section class="saito-mc-reco" aria-label="' . esc_attr( $title ) . '">';
	$html .= '<div class="saito-mc-reco__head"><p class="saito-mc-reco__title">' . esc_html( $title ) . '</p>';
	$html .= '<div class="saito-mc-reco__nav">';
	$html .= '<button type="button" class="saito-mc-reco__prev" aria-label="' . esc_attr__( 'Produits précédents', 'saito-mini-cart' ) . '">‹</button>';
	$html .= '<button type="button" class="saito-mc-reco__next" aria-label="' . esc_attr__( 'Produits suivants', 'saito-mini-cart' ) . '">›</button>';
	$html .= '</div></div><ul class="saito-mc-reco__list">';
	foreach ( $products as $product ) {
		$url   = $product->get_permalink();
		$image = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'saito-mc-reco__img', 'loading' => 'lazy' ) );
		$html .= '<li class="saito-mc-reco__item">';
		$html .= '<a class="saito-mc-reco__link" href="' . esc_url( $url ) . '">' . $image . '<span class="saito-mc-reco__name">' . esc_html( $product->get_name() ) . '</span></a>';
		$html .= '<span class="saito-mc-reco__price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
			/* translators: %s: nom du produit. */
			$label = sprintf( __( 'Ajouter « %s » au panier', 'saito-mini-cart' ), $product->get_name() );
			$html .= '<button type="button" class="saito-mc-reco__add" data-product-id="' . esc_attr( $product->get_id() ) . '" aria-label="' . esc_attr( $label ) . '">' . esc_html__( 'Ajouter', 'saito-mini-cart' ) . '</button>';
		} else {
			$html .= '<a class="saito-mc-reco__add saito-mc-reco__add--link" href="' . esc_url( $url ) . '">' . esc_html__( 'Choisir', 'saito-mini-cart' ) . '</a>';
		}
		$html .= '</li>';
	}
	$html .= '</ul></section>';
	return $html;
}

add_shortcode( 'saito_mini_cart_recommendations', 'saito_mc_recommendations_shortcode' );

/**
 * Shortcode [saito_mini_cart_recommendations] : le carrousel où l'on veut,
 * par exemple dans le modèle du mini-panier (Éditeur de site), à la place
 * de l'insertion automatique. Mis à jour par assets/js/mini-cart.js.
 *
 * @return string
 */
function saito_mc_recommendations_shortcode() {
	saito_mc_enqueue_assets();
	return '<div class="saito-mc-slot" data-saito-mc="reco-shortcode">' . saito_mc_recommendations_html() . '</div>';
}

// Mini-panier classique : sous les boutons, rafraîchi par les fragments.
add_action( 'woocommerce_after_mini_cart', 'saito_mc_classic_minicart_recommendations' );

/**
 * Carrousel en bas du mini-panier classique.
 */
function saito_mc_classic_minicart_recommendations() {
	if ( ! saito_mc_setting( 'reco_in_minicart' ) ) {
		return;
	}
	echo saito_mc_recommendations_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans saito_mc_recommendations_html().
}
