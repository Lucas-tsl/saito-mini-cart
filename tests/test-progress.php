<?php
/**
 * Barre de livraison gratuite : montant restant, pourcentage, seuil atteint.
 * Exécuté par la CI via `php tests/test-progress.php`.
 */

require __DIR__ . '/bootstrap-stubs.php';
require __DIR__ . '/../includes/progress.php';

$state = saito_mc_progress_state( 37.5, 50 );
saito_mc_assert( 'reste 12,50 sur 50', 12.5, $state['remaining'] );
saito_mc_assert( '75 %', 75, $state['percent'] );
saito_mc_assert( 'pas encore atteint', false, $state['reached'] );

$state = saito_mc_progress_state( 50, 50 );
saito_mc_assert( 'seuil exact : atteint', true, $state['reached'] );
saito_mc_assert( 'seuil exact : 100 %', 100, $state['percent'] );
saito_mc_assert( 'seuil exact : reste 0', 0.0, $state['remaining'] );

$state = saito_mc_progress_state( 80, 50 );
saito_mc_assert( 'au-delà du seuil : 100 % maximum', 100, $state['percent'] );
saito_mc_assert( 'au-delà du seuil : reste 0', 0.0, $state['remaining'] );

$state = saito_mc_progress_state( 0, 50 );
saito_mc_assert( 'panier à 0 : 0 %', 0, $state['percent'] );

$state = saito_mc_progress_state( 49.999, 50 );
saito_mc_assert( 'arrondi au centime : reste 0 = atteint', true, $state['reached'] );

$state = saito_mc_progress_state( 10, 0 );
saito_mc_assert( 'seuil nul : considéré comme atteint', true, $state['reached'] );

$state = saito_mc_progress_state( -5, 50 );
saito_mc_assert( 'montant négatif ramené à 0', 50.0, $state['remaining'] );

saito_mc_tests_done();
