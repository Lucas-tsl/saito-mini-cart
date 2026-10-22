<?php
/**
 * Désinstallation : supprime les réglages. Appelé par WordPress uniquement
 * à la suppression du plugin (pas à sa désactivation).
 *
 * @package SaitoMiniCart
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Réglages partagés de Saito > Outils : supprimés seulement avec le dernier plugin Saito.
require __DIR__ . '/includes/saito-hub/uninstall.php';

delete_option( 'saito_mc_settings' );
delete_transient( 'saito_mc_imported' );
