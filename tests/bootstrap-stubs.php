<?php
/**
 * Fonctions WordPress minimales pour exécuter les tests sans WordPress
 * (les fichiers testés ne déclarent que des fonctions et des hooks).
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function absint( $value ) {
	return abs( (int) $value );
}

$GLOBALS['saito_mc_failures'] = 0;

function saito_mc_assert( $label, $expected, $actual ) {
	if ( $expected !== $actual ) {
		$GLOBALS['saito_mc_failures']++;
		fwrite( STDERR, sprintf( "FAIL: %s (attendu %s, obtenu %s)\n", $label, var_export( $expected, true ), var_export( $actual, true ) ) );
		return;
	}
	echo "OK: {$label}\n";
}

function saito_mc_tests_done() {
	if ( $GLOBALS['saito_mc_failures'] > 0 ) {
		fwrite( STDERR, "\n{$GLOBALS['saito_mc_failures']} échec(s).\n" );
		exit( 1 );
	}
	echo "\nTous les tests sont passés.\n";
}
