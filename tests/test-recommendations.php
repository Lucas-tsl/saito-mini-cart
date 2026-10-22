<?php
/**
 * Produits recommandés : ordre des sources, dédoublonnage, exclusions, limite.
 * Exécuté par la CI via `php tests/test-recommendations.php`.
 */

require __DIR__ . '/bootstrap-stubs.php';
require __DIR__ . '/../includes/recommendations.php';

saito_mc_assert(
	'sources dans l\'ordre, sans doublon',
	array( 3, 4, 5, 6 ),
	saito_mc_merge_recommendations( array( array( 3, 4 ), array( 4, 5 ), array( 6 ) ), array(), 10 )
);
saito_mc_assert(
	'produits du panier exclus',
	array( 4, 6 ),
	saito_mc_merge_recommendations( array( array( 3, 4 ), array( 6 ) ), array( 3 ), 10 )
);
saito_mc_assert(
	'limite respectée',
	array( 1, 2 ),
	saito_mc_merge_recommendations( array( array( 1, 2, 3 ) ), array(), 2 )
);
saito_mc_assert(
	'identifiants invalides ignorés',
	array( 9 ),
	saito_mc_merge_recommendations( array( array( 0, -1, '9' ) ), array(), 5 )
);
saito_mc_assert( 'aucune source', array(), saito_mc_merge_recommendations( array(), array(), 5 ) );

saito_mc_tests_done();
