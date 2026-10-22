<?php
/**
 * Plugin Name: Saito Mini Cart
 * Plugin URI: https://github.com/Lucas-tsl/saito-mini-cart
 * Description: Mini-panier WooCommerce : barre de livraison gratuite, carrousel de produits recommandés et boutons « Voir le panier » / « Commander » personnalisés.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Troteseil Lucas
 * Author URI: https://github.com/Lucas-tsl
 * Text Domain: saito-mini-cart
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SaitoMiniCart
 */

defined( 'ABSPATH' ) || exit;

define( 'SAITO_MC_VERSION', '1.0.0' );
define( 'SAITO_MC_FILE', __FILE__ );
define( 'SAITO_MC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAITO_MC_URL', plugin_dir_url( __FILE__ ) );
define( 'SAITO_MC_OPTION', 'saito_mc_settings' );
define( 'SAITO_MC_CAP', 'manage_woocommerce' );

// Menu « Saito » commun aux plugins Saito (voir includes/saito-hub/hub.php).
require_once SAITO_MC_DIR . 'includes/saito-hub/load.php';
add_filter( 'saito_hub_plugins', 'saito_mc_register_in_saito_hub' );

/**
 * Déclaration auprès du menu Saito (tableau de bord, documentation).
 *
 * @param array $plugins Plugins Saito actifs.
 * @return array
 */
function saito_mc_register_in_saito_hub( $plugins ) {
	$plugins['mini-cart'] = array(
		'version'      => SAITO_MC_VERSION,
		'settings_url' => admin_url( 'admin.php?page=saito-mini-cart' ),
		'menu_slugs'   => array( 'saito-mini-cart' ),
	);
	return $plugins;
}

require_once SAITO_MC_DIR . 'includes/settings.php';
require_once SAITO_MC_DIR . 'includes/progress.php';
require_once SAITO_MC_DIR . 'includes/recommendations.php';
require_once SAITO_MC_DIR . 'includes/buttons.php';
require_once SAITO_MC_DIR . 'includes/frontend.php';

if ( is_admin() ) {
	require_once SAITO_MC_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'saito_mc_import_toolkit_settings' );
