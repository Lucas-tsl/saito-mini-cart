<?php
/**
 * Administration : page Saito > Mini-panier, avec trois onglets
 * (Livraison gratuite, Produits recommandés, Boutons).
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'saito_mc_admin_menu' );
add_action( 'admin_init', 'saito_mc_register_settings' );
add_action( 'admin_enqueue_scripts', 'saito_mc_admin_assets' );
add_action( 'admin_notices', 'saito_mc_admin_notices' );
add_filter( 'plugin_action_links_' . plugin_basename( SAITO_MC_FILE ), 'saito_mc_action_links' );

/**
 * Sous-menu du menu « Saito ».
 */
function saito_mc_admin_menu() {
	add_submenu_page( saito_hub_menu_slug(), __( 'Mini-panier', 'saito-mini-cart' ), __( 'Mini-panier', 'saito-mini-cart' ), SAITO_MC_CAP, 'saito-mini-cart', 'saito_mc_render_admin_page' );
}

/**
 * Lien « Réglages » dans la liste des extensions.
 *
 * @param array $links Liens.
 * @return array
 */
function saito_mc_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=saito-mini-cart' ) ) . '">' . esc_html__( 'Réglages', 'saito-mini-cart' ) . '</a>' );
	return $links;
}

/**
 * Option enregistrée via l'API Settings (nonce et droits vérifiés par options.php).
 */
function saito_mc_register_settings() {
	register_setting(
		'saito_mc',
		SAITO_MC_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'saito_mc_sanitize_settings',
			'default'           => saito_mc_default_settings(),
		)
	);
}

/**
 * Sélecteur de produits de WooCommerce (onglet Produits recommandés).
 *
 * @param string $hook Page courante.
 */
function saito_mc_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'saito-mini-cart' ) ) {
		return;
	}
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_script( 'wc-enhanced-select' );
}

/**
 * Onglet affiché.
 *
 * @return string
 */
function saito_mc_current_tab() {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'progress'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- simple choix d'onglet.
	return in_array( $tab, array( 'progress', 'reco', 'buttons' ), true ) ? $tab : 'progress';
}

/**
 * Avis : réglages repris de Saito Dev Toolkit, toolkit encore actif.
 */
function saito_mc_admin_notices() {
	if ( get_transient( 'saito_mc_imported' ) ) {
		delete_transient( 'saito_mc_imported' );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Saito Mini Cart a repris les réglages de Saito Dev Toolkit (barre de livraison gratuite, boutons du mini-panier).', 'saito-mini-cart' )
		);
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	// Saito Dev Toolkit actif : ses fonctions de barre ou de boutons sont chargées.
	$toolkit_active = function_exists( 'saito_progressive_bar_lsg_render' ) || function_exists( 'saito_redirect_minicart_register_settings' );
	if ( $screen && false !== strpos( (string) $screen->id, 'saito-mini-cart' ) && $toolkit_active ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Saito Dev Toolkit est encore actif : désactivez ses modules « Barre de livraison » et « Redirection mini-panier » pour ne pas afficher deux barres ou deux jeux de boutons.', 'saito-mini-cart' )
		);
	}
}

/**
 * Page Saito > Mini-panier.
 */
function saito_mc_render_admin_page() {
	if ( ! current_user_can( SAITO_MC_CAP ) ) {
		return;
	}
	$tab  = saito_mc_current_tab();
	$tabs = array(
		'progress' => __( 'Livraison gratuite', 'saito-mini-cart' ),
		'reco'     => __( 'Produits recommandés', 'saito-mini-cart' ),
		'buttons'  => __( 'Boutons', 'saito-mini-cart' ),
	);
	$s    = saito_mc_get_settings();
	$name = SAITO_MC_OPTION;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mini-panier', 'saito-mini-cart' ); ?></h1>
		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Onglets du mini-panier', 'saito-mini-cart' ); ?>">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'saito-mini-cart', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php echo $slug === $tab ? ' nav-tab-active' : ''; ?>"<?php echo $slug === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php settings_errors(); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'saito_mc' ); ?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[_tab]" value="<?php echo esc_attr( $tab ); ?>">
			<?php
			if ( 'reco' === $tab ) {
				saito_mc_render_reco_tab( $s, $name );
			} elseif ( 'buttons' === $tab ) {
				saito_mc_render_buttons_tab( $s, $name );
			} else {
				saito_mc_render_progress_tab( $s, $name );
			}
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Case à cocher.
 *
 * @param string $name  Nom de l'option.
 * @param string $key   Clé.
 * @param array  $s     Réglages.
 * @param string $label Libellé.
 */
function saito_mc_checkbox( $name, $key, $s, $label ) {
	printf(
		'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
		esc_attr( $name ),
		esc_attr( $key ),
		checked( ! empty( $s[ $key ] ), true, false ),
		esc_html( $label )
	);
}

/**
 * Onglet Livraison gratuite.
 *
 * @param array  $s    Réglages.
 * @param string $name Nom de l'option.
 */
function saito_mc_render_progress_tab( $s, $name ) {
	$auto_amount = null;
	if ( function_exists( 'WC' ) && class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( array_merge( WC_Shipping_Zones::get_zones(), array( array( 'zone_id' => 0 ) ) ) as $zone_data ) {
			$zone = WC_Shipping_Zones::get_zone( $zone_data['zone_id'] );
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( 'free_shipping' === $method->id && (float) $method->min_amount > 0 ) {
					$auto_amount = null === $auto_amount ? (float) $method->min_amount : min( $auto_amount, (float) $method->min_amount );
				}
			}
		}
	}
	?>
	<p class="description"><?php esc_html_e( 'Affiche « Plus que 12,50 € pour profiter de la livraison gratuite » avec une barre de progression, dans le mini-panier et la page panier. Le montant est calculé comme le fait WooCommerce pour sa méthode « Livraison gratuite ».', 'saito-mini-cart' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Barre de livraison gratuite', 'saito-mini-cart' ); ?></th>
			<td><?php saito_mc_checkbox( $name, 'progress_enabled', $s, __( 'Afficher la barre', 'saito-mini-cart' ) ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Montant de la livraison gratuite', 'saito-mini-cart' ); ?></th>
			<td>
				<fieldset>
					<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[progress_threshold]" value="auto" <?php checked( $s['progress_threshold'], 'auto' ); ?>> <?php esc_html_e( 'Celui de la méthode « Livraison gratuite » de WooCommerce (zone de livraison du client)', 'saito-mini-cart' ); ?></label><br>
					<p class="description">
						<?php
						if ( null !== $auto_amount ) {
							/* translators: %s: montant minimum. */
							echo esc_html( sprintf( __( 'Méthode détectée : livraison gratuite à partir de %s (zone la plus favorable).', 'saito-mini-cart' ), saito_mc_price_text( $auto_amount ) ) );
						} else {
							esc_html_e( 'Aucune méthode « Livraison gratuite » avec montant minimum n\'est configurée dans WooCommerce > Réglages > Expédition : le montant ci-dessous est utilisé.', 'saito-mini-cart' );
						}
						?>
					</p>
					<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[progress_threshold]" value="manual" <?php checked( $s['progress_threshold'], 'manual' ); ?>> <?php esc_html_e( 'Ce montant :', 'saito-mini-cart' ); ?></label>
					<input type="number" min="0" step="0.01" class="small-text" name="<?php echo esc_attr( $name ); ?>[progress_amount]" value="<?php echo esc_attr( $s['progress_amount'] ); ?>" aria-label="<?php esc_attr_e( 'Montant de la livraison gratuite', 'saito-mini-cart' ); ?>"> <?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Emplacements', 'saito-mini-cart' ); ?></th>
			<td>
				<?php saito_mc_checkbox( $name, 'progress_in_minicart', $s, __( 'En haut du mini-panier', 'saito-mini-cart' ) ); ?><br>
				<?php saito_mc_checkbox( $name, 'progress_in_cart', $s, __( 'Au-dessus du panier (page Panier)', 'saito-mini-cart' ) ); ?>
				<p class="description"><?php echo wp_kses( __( 'Pour la placer vous-même, décochez « En haut du mini-panier » et utilisez le shortcode <code>[saito_mini_cart_progress]</code>, par exemple dans le modèle du mini-panier (Apparence > Éditeur > Compositions > Mini-panier, bloc Code court) ou sur n\'importe quelle page. Il se met à jour à chaque modification du panier.', 'saito-mini-cart' ), array( 'code' => array() ) ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="saito-mc-progress-text"><?php esc_html_e( 'Message', 'saito-mini-cart' ); ?></label></th>
			<td>
				<input type="text" id="saito-mc-progress-text" class="large-text" name="<?php echo esc_attr( $name ); ?>[progress_text]" value="<?php echo esc_attr( $s['progress_text'] ); ?>" placeholder="<?php esc_attr_e( 'Plus que {amount} pour profiter de la livraison gratuite.', 'saito-mini-cart' ); ?>">
				<p class="description"><?php esc_html_e( '{amount} est remplacé par le montant restant. Laisser vide pour le texte par défaut.', 'saito-mini-cart' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="saito-mc-progress-done"><?php esc_html_e( 'Message une fois le montant atteint', 'saito-mini-cart' ); ?></label></th>
			<td><input type="text" id="saito-mc-progress-done" class="large-text" name="<?php echo esc_attr( $name ); ?>[progress_done_text]" value="<?php echo esc_attr( $s['progress_done_text'] ); ?>" placeholder="<?php esc_attr_e( 'Bonne nouvelle : la livraison est offerte !', 'saito-mini-cart' ); ?>"></td>
		</tr>
	</table>
	<?php
}

/**
 * Onglet Produits recommandés.
 *
 * @param array  $s    Réglages.
 * @param string $name Nom de l'option.
 */
function saito_mc_render_reco_tab( $s, $name ) {
	?>
	<p class="description"><?php esc_html_e( 'Carrousel en bas du mini-panier, avec ajout au panier en un clic pour les produits simples. Ordre : ventes croisées des produits du panier, produits suggérés, votre sélection ci-dessous, puis les meilleures ventes. Les produits déjà dans le panier, épuisés ou non visibles ne sont jamais proposés.', 'saito-mini-cart' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Produits recommandés', 'saito-mini-cart' ); ?></th>
			<td>
				<?php saito_mc_checkbox( $name, 'reco_enabled', $s, __( 'Activer les produits recommandés', 'saito-mini-cart' ) ); ?><br>
				<?php saito_mc_checkbox( $name, 'reco_in_minicart', $s, __( 'Les insérer automatiquement en bas du mini-panier', 'saito-mini-cart' ) ); ?>
				<p class="description"><?php echo wp_kses( __( 'Pour les placer vous-même, décochez l\'insertion automatique et utilisez le shortcode <code>[saito_mini_cart_recommendations]</code>, par exemple dans le modèle du mini-panier (Apparence > Éditeur > Compositions > Mini-panier, bloc Code court).', 'saito-mini-cart' ), array( 'code' => array() ) ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="saito-mc-reco-title"><?php esc_html_e( 'Titre', 'saito-mini-cart' ); ?></label></th>
			<td><input type="text" id="saito-mc-reco-title" class="regular-text" name="<?php echo esc_attr( $name ); ?>[reco_title]" value="<?php echo esc_attr( $s['reco_title'] ); ?>" placeholder="<?php esc_attr_e( 'Vous aimerez aussi', 'saito-mini-cart' ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="saito-mc-reco-limit"><?php esc_html_e( 'Nombre de produits', 'saito-mini-cart' ); ?></label></th>
			<td><input type="number" id="saito-mc-reco-limit" min="1" max="12" class="small-text" name="<?php echo esc_attr( $name ); ?>[reco_limit]" value="<?php echo esc_attr( $s['reco_limit'] ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="saito-mc-reco-products"><?php esc_html_e( 'Votre sélection', 'saito-mini-cart' ); ?></label></th>
			<td>
				<select id="saito-mc-reco-products" class="wc-product-search" multiple="multiple" style="width: 50%;" name="<?php echo esc_attr( $name ); ?>[reco_products][]" data-placeholder="<?php esc_attr_e( 'Rechercher un produit…', 'saito-mini-cart' ); ?>" data-action="woocommerce_json_search_products_and_variations">
					<?php
					foreach ( saito_mc_parse_ids( $s['reco_products'] ) as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( $product ) {
							printf( '<option value="%1$d" selected>%2$s</option>', (int) $product_id, esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) );
						}
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Proposés quand les produits du panier n\'ont pas (assez) de ventes croisées ni de produits suggérés.', 'saito-mini-cart' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Compléter avec', 'saito-mini-cart' ); ?></th>
			<td><?php saito_mc_checkbox( $name, 'reco_bestsellers', $s, __( 'Les meilleures ventes de la boutique', 'saito-mini-cart' ) ); ?></td>
		</tr>
	</table>
	<?php
}

/**
 * Onglet Boutons.
 *
 * @param array  $s    Réglages.
 * @param string $name Nom de l'option.
 */
function saito_mc_render_buttons_tab( $s, $name ) {
	$rows = array(
		'cart'     => array( __( 'Bouton « Voir le panier »', 'saito-mini-cart' ), __( 'Voir le panier', 'saito-mini-cart' ), wc_get_cart_url() ),
		'checkout' => array( __( 'Bouton « Commander »', 'saito-mini-cart' ), __( 'Commander', 'saito-mini-cart' ), wc_get_checkout_url() ),
	);
	?>
	<p class="description"><?php esc_html_e( 'Texte et adresse des deux boutons du mini-panier (mini-panier en blocs et classique). Laisser vide pour garder ceux de WooCommerce. Traduisibles avec WPML (String Translation, domaine saito-mini-cart).', 'saito-mini-cart' ); ?></p>
	<table class="form-table" role="presentation">
		<?php foreach ( $rows as $button => $row ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
				<td>
					<p>
						<label for="saito-mc-<?php echo esc_attr( $button ); ?>-text"><?php esc_html_e( 'Texte', 'saito-mini-cart' ); ?></label><br>
						<input type="text" id="saito-mc-<?php echo esc_attr( $button ); ?>-text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $button ); ?>_btn_text]" value="<?php echo esc_attr( $s[ $button . '_btn_text' ] ); ?>" placeholder="<?php echo esc_attr( $row[1] ); ?>">
					</p>
					<p>
						<label for="saito-mc-<?php echo esc_attr( $button ); ?>-url"><?php esc_html_e( 'Adresse', 'saito-mini-cart' ); ?></label><br>
						<input type="url" id="saito-mc-<?php echo esc_attr( $button ); ?>-url" class="regular-text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $button ); ?>_btn_url]" value="<?php echo esc_attr( $s[ $button . '_btn_url' ] ); ?>" placeholder="<?php echo esc_attr( $row[2] ); ?>">
					</p>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
