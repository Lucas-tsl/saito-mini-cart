<?php
/**
 * Réglages : une seule option (saito_mc_settings), valeurs par défaut,
 * nettoyage et reprise des réglages de l'ancien plugin Saito Dev Toolkit.
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valeurs par défaut.
 *
 * Textes vides = texte par défaut traduit (voir saito_mc_text()).
 *
 * @return array
 */
function saito_mc_default_settings() {
	return array(
		// Barre de livraison gratuite.
		'progress_enabled'     => 1,
		'progress_threshold'   => 'auto', // 'auto' (méthode « Livraison gratuite » de WooCommerce) ou 'manual'.
		'progress_amount'      => 50,
		'progress_in_minicart' => 1,
		'progress_in_cart'     => 1,
		'progress_text'        => '',
		'progress_done_text'   => '',
		// Produits recommandés.
		'reco_enabled'         => 1,
		'reco_in_minicart'     => 1,
		'reco_title'           => '',
		'reco_limit'           => 6,
		'reco_products'        => '', // Identifiants séparés par des virgules (repli).
		'reco_bestsellers'     => 1,
		// Boutons du mini-panier.
		'cart_btn_text'        => '',
		'cart_btn_url'         => '',
		'checkout_btn_text'    => '',
		'checkout_btn_url'     => '',
	);
}

/**
 * Réglages enregistrés, complétés par les valeurs par défaut.
 *
 * @return array
 */
function saito_mc_get_settings() {
	$saved = get_option( SAITO_MC_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), saito_mc_default_settings() );
}

/**
 * Un réglage.
 *
 * @param string $key Clé.
 * @return mixed
 */
function saito_mc_setting( $key ) {
	$settings = saito_mc_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
}

/**
 * Nettoie les réglages envoyés par le formulaire. Seules les clés connues
 * sont gardées ; une case non cochée n'est pas envoyée et vaut donc 0.
 *
 * @param mixed $input Valeurs envoyées.
 * @return array
 */
function saito_mc_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$current  = saito_mc_get_settings();
	$defaults = saito_mc_default_settings();
	$out      = array();

	// Chaque onglet n'envoie que ses propres champs : on part des réglages
	// actuels pour ne pas effacer ceux des autres onglets.
	$tab = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';
	$tab_fields = array(
		'progress' => array( 'progress_enabled', 'progress_threshold', 'progress_amount', 'progress_in_minicart', 'progress_in_cart', 'progress_text', 'progress_done_text' ),
		'reco'     => array( 'reco_enabled', 'reco_in_minicart', 'reco_title', 'reco_limit', 'reco_products', 'reco_bestsellers' ),
		'buttons'  => array( 'cart_btn_text', 'cart_btn_url', 'checkout_btn_text', 'checkout_btn_url' ),
	);
	$fields = isset( $tab_fields[ $tab ] ) ? $tab_fields[ $tab ] : array_keys( $defaults );

	foreach ( $defaults as $key => $default ) {
		if ( ! in_array( $key, $fields, true ) ) {
			$out[ $key ] = $current[ $key ];
			continue;
		}
		$value = isset( $input[ $key ] ) ? $input[ $key ] : null;
		switch ( $key ) {
			case 'progress_enabled':
			case 'progress_in_minicart':
			case 'progress_in_cart':
			case 'reco_enabled':
			case 'reco_in_minicart':
			case 'reco_bestsellers':
				$out[ $key ] = empty( $value ) ? 0 : 1;
				break;
			case 'progress_threshold':
				$out[ $key ] = 'manual' === $value ? 'manual' : 'auto';
				break;
			case 'progress_amount':
				$out[ $key ] = max( 0, (float) str_replace( ',', '.', (string) $value ) );
				break;
			case 'reco_limit':
				$out[ $key ] = min( 12, max( 1, (int) $value ) );
				break;
			case 'reco_products':
				$out[ $key ] = implode( ',', saito_mc_parse_ids( $value ) );
				break;
			case 'cart_btn_url':
			case 'checkout_btn_url':
				$out[ $key ] = esc_url_raw( trim( (string) $value ) );
				break;
			default:
				$out[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	saito_mc_register_wpml_strings( $out );
	return $out;
}

/**
 * Liste d'identifiants (texte « 12, 34 » ou tableau) → entiers uniques > 0.
 *
 * @param mixed $value Valeur.
 * @return int[]
 */
function saito_mc_parse_ids( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( ',', $value );
	}
	$ids = array_map( 'absint', preg_split( '/[\s,]+/', (string) $value ) );
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Textes personnalisables traduits par WPML (String Translation), comme
 * dans l'ancien Saito Dev Toolkit.
 *
 * @return string[] Clés des réglages concernés.
 */
function saito_mc_translatable_keys() {
	return array( 'progress_text', 'progress_done_text', 'reco_title', 'cart_btn_text', 'cart_btn_url', 'checkout_btn_text', 'checkout_btn_url' );
}

/**
 * Déclare les textes personnalisés à WPML.
 *
 * @param array $settings Réglages.
 */
function saito_mc_register_wpml_strings( $settings ) {
	foreach ( saito_mc_translatable_keys() as $key ) {
		if ( '' !== (string) $settings[ $key ] ) {
			do_action( 'wpml_register_single_string', 'saito-mini-cart', $key, $settings[ $key ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook de WPML.
		}
	}
}

/**
 * Texte personnalisé (traduit par WPML s'il est actif), ou texte par défaut.
 *
 * @param string $key     Clé du réglage.
 * @param string $default Texte par défaut (déjà traduit).
 * @return string
 */
function saito_mc_text( $key, $default ) {
	$value = trim( (string) saito_mc_setting( $key ) );
	if ( '' === $value ) {
		return $default;
	}
	return (string) apply_filters( 'wpml_translate_single_string', $value, 'saito-mini-cart', $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook de WPML.
}

/**
 * Correspondance entre les options de Saito Dev Toolkit et les réglages
 * de ce plugin (sans WordPress : testée par tests/test-settings.php).
 *
 * @param array $toolkit Options du toolkit (nom => valeur, absentes = non définies).
 * @return array Réglages à reprendre (vide si rien à reprendre).
 */
function saito_mc_map_toolkit_settings( $toolkit ) {
	$map = array();
	if ( isset( $toolkit['saito_progressive_bar_lsg_limit'] ) && '' !== (string) $toolkit['saito_progressive_bar_lsg_limit'] ) {
		$map['progress_threshold'] = 'manual';
		$map['progress_amount']    = max( 0, (float) $toolkit['saito_progressive_bar_lsg_limit'] );
	}
	if ( isset( $toolkit['saito_progressive_bar_lsg_enabled'] ) ) {
		$map['progress_enabled'] = empty( $toolkit['saito_progressive_bar_lsg_enabled'] ) ? 0 : 1;
	}
	$buttons = array(
		'saito_cart_btn_text'     => 'cart_btn_text',
		'saito_cart_btn_url'      => 'cart_btn_url',
		'saito_checkout_btn_text' => 'checkout_btn_text',
		'saito_checkout_btn_url'  => 'checkout_btn_url',
	);
	foreach ( $buttons as $old => $new ) {
		if ( isset( $toolkit[ $old ] ) && '' !== trim( (string) $toolkit[ $old ] ) ) {
			$map[ $new ] = trim( (string) $toolkit[ $old ] );
		}
	}
	return $map;
}

/**
 * Reprend une seule fois, à l'activation, les réglages de Saito Dev
 * Toolkit (barre de livraison gratuite, boutons du mini-panier) s'ils
 * existent et que ce plugin n'a encore jamais été réglé.
 */
function saito_mc_import_toolkit_settings() {
	if ( false !== get_option( SAITO_MC_OPTION, false ) ) {
		return;
	}
	$toolkit = array();
	foreach ( array( 'saito_progressive_bar_lsg_limit', 'saito_progressive_bar_lsg_enabled', 'saito_cart_btn_text', 'saito_cart_btn_url', 'saito_checkout_btn_text', 'saito_checkout_btn_url' ) as $name ) {
		$value = get_option( $name, null );
		if ( null !== $value ) {
			$toolkit[ $name ] = $value;
		}
	}
	$map = saito_mc_map_toolkit_settings( $toolkit );
	if ( $map ) {
		$settings = array_merge( saito_mc_default_settings(), $map );
		$settings['cart_btn_url']     = esc_url_raw( $settings['cart_btn_url'] );
		$settings['checkout_btn_url'] = esc_url_raw( $settings['checkout_btn_url'] );
		add_option( SAITO_MC_OPTION, $settings );
		set_transient( 'saito_mc_imported', 1, DAY_IN_SECONDS );
	}
}
