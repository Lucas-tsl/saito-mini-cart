/**
 * Saito Mini Cart : barre de livraison gratuite, produits recommandés et
 * boutons personnalisés dans le mini-panier en blocs de WooCommerce (rendu
 * dans le navigateur), dans la page panier et le shortcode. Le contenu
 * vient du serveur (route REST saito-mini-cart/v1/fragments) : rien n'est
 * déduit du texte affiché. Le mini-panier classique, lui, est déjà rendu
 * côté serveur et rafraîchi par les fragments WooCommerce.
 */
( function () {
	'use strict';

	var config = window.saitoMiniCart;
	if ( ! config ) {
		return;
	}

	var last = null;
	var timer = null;
	var pending = false;

	/**
	 * Place (ou retrouve) un emplacement dans le mini-panier en blocs. Le
	 * tiroir est rendu par React : un nouveau rendu peut retirer nos
	 * éléments, d'où cette fonction appelée à chaque modification.
	 */
	function ensureSlot( container, name, reference, position ) {
		var slot = container.querySelector( '[data-saito-mc="' + name + '"]' );
		if ( slot ) {
			return slot;
		}
		if ( ! reference ) {
			return null;
		}
		slot = document.createElement( 'div' );
		slot.className = 'saito-mc-slot';
		slot.setAttribute( 'data-saito-mc', name );
		if ( 'prepend' === position ) {
			reference.insertBefore( slot, reference.firstChild );
		} else {
			reference.parentNode.insertBefore( slot, 'before' === position ? reference : reference.nextSibling );
		}
		if ( last ) {
			fill( slot, name );
		}
		return slot;
	}

	function fill( slot, name ) {
		var html = '';
		if ( last ) {
			if ( 'progress-minicart' === name ) {
				html = last.progressMiniCart;
			} else if ( 'progress-cart' === name ) {
				html = last.progressCart;
			} else if ( 'progress-shortcode' === name ) {
				html = last.progressShortcode;
			} else if ( 'reco-minicart' === name || 'reco-shortcode' === name ) {
				html = last.reco;
			}
		}
		if ( slot.getAttribute( 'data-saito-mc-html' ) !== html ) {
			slot.innerHTML = html || '';
			slot.setAttribute( 'data-saito-mc-html', html || '' );
		}
	}

	function drawerContents() {
		return document.querySelector( '.wc-block-components-drawer .wp-block-woocommerce-filled-mini-cart-contents-block' );
	}

	// Le tiroir du mini-panier en blocs est piloté par l'Interactivity API de
	// WordPress : le modifier avant qu'il ait été ouvert une première fois
	// (pendant son hydratation, au chargement de chaque page) le fait
	// réafficher dans son état de départ, ouvert, puis se refermer — un
	// clignotement à chaque page. On n'y touche donc qu'une fois ouvert.
	var drawerReady = false;
	function drawerIsOpen() {
		var overlay = document.querySelector( '.wc-block-components-drawer__screen-overlay' );
		var drawer = document.querySelector( '.wc-block-components-drawer' );
		return !! overlay && !! drawer && ! overlay.classList.contains( 'wc-block-components-drawer__screen-overlay--is-hidden' ) && 'true' !== drawer.getAttribute( 'aria-hidden' );
	}
	function canTouch( slot ) {
		if ( ! slot.closest( '.wc-block-components-drawer' ) ) {
			return true;
		}
		if ( ! drawerReady && drawerIsOpen() ) {
			drawerReady = true;
		}
		return drawerReady;
	}

	function placeInDrawer() {
		var contents = drawerContents();
		if ( ! contents || ! canTouch( contents ) ) {
			return;
		}
		// Dans la liste des produits, et non juste avant : le titre du
		// tiroir la chevauche (marge négative, fond opaque) et masquerait
		// la barre ; la liste prévoit cet espace.
		if ( config.progressMiniCart ) {
			ensureSlot( contents, 'progress-minicart', contents.querySelector( '.wc-block-mini-cart__items' ), 'prepend' );
		}
		if ( config.reco ) {
			ensureSlot( contents, 'reco-minicart', contents.querySelector( '.wc-block-mini-cart__footer' ), 'before' );
		}
		applyButtons( contents );
	}

	function applyButton( link, text, url ) {
		if ( ! link ) {
			return;
		}
		if ( url && link.getAttribute( 'href' ) !== url ) {
			link.setAttribute( 'href', url );
		}
		if ( text ) {
			var label = link.querySelector( '.wc-block-components-button__text' ) || link;
			if ( label.textContent !== text ) {
				label.textContent = text;
			}
		}
	}

	function applyButtons( root ) {
		var buttons = config.buttons || {};
		applyButton( root.querySelector( '.wc-block-mini-cart__footer-cart' ), buttons.cartText, buttons.cartUrl );
		applyButton( root.querySelector( '.wc-block-mini-cart__footer-checkout' ), buttons.checkoutText, buttons.checkoutUrl );
	}

	function render() {
		placeInDrawer();
		var slots = document.querySelectorAll( '[data-saito-mc]' );
		for ( var i = 0; i < slots.length; i++ ) {
			if ( canTouch( slots[ i ] ) ) {
				fill( slots[ i ], slots[ i ].getAttribute( 'data-saito-mc' ) );
			}
		}
	}

	function refresh() {
		if ( pending ) {
			return;
		}
		pending = true;
		fetch( config.restUrl, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': config.restNonce },
		} )
			.then( function ( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function ( data ) {
				pending = false;
				if ( data ) {
					last = data;
					render();
				}
			} )
			.catch( function () {
				pending = false;
			} );
	}

	function scheduleRefresh( delay ) {
		clearTimeout( timer );
		timer = setTimeout( refresh, typeof delay === 'number' ? delay : 250 );
	}

	// Tiroir ouvert, ou sous-total qui change (quantité modifiée, produit
	// retiré dans le tiroir) : nouvelles données du serveur. Le texte du
	// sous-total sert seulement de signal, jamais de valeur.
	var lastSubtotal = null;
	var ticking = false;
	var observer = new MutationObserver( function () {
		// Regroupe les nombreuses modifications d'un rendu React en un seul
		// passage par image.
		if ( ticking ) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame( onMutations );
	} );
	function onMutations() {
		ticking = false;
		placeInDrawer();
		var subtotal = document.querySelector( '.wc-block-components-drawer .wc-block-mini-cart__footer-subtotal .wc-block-components-totals-item__value, .wp-block-woocommerce-cart .wc-block-components-totals-item__value' );
		var value = subtotal ? subtotal.textContent : null;
		if ( value !== lastSubtotal ) {
			lastSubtotal = value;
			scheduleRefresh();
		}
	}
	observer.observe( document.body, { childList: true, subtree: true, characterData: true } );

	[ 'wc-blocks_added_to_cart', 'wc-blocks_removed_from_cart' ].forEach( function ( name ) {
		document.body.addEventListener( name, function () {
			scheduleRefresh();
		} );
	} );
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart removed_from_cart updated_cart_totals wc_fragments_refreshed', function () {
			scheduleRefresh();
		} );
	}

	function replaceFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}
		Object.keys( fragments ).forEach( function ( selector ) {
			var nodes = document.querySelectorAll( selector );
			for ( var i = 0; i < nodes.length; i++ ) {
				nodes[ i ].outerHTML = fragments[ selector ];
			}
		} );
	}

	// Carrousel : boutons précédent / suivant.
	document.addEventListener( 'click', function ( event ) {
		var nav = event.target.closest( '.saito-mc-reco__prev, .saito-mc-reco__next' );
		if ( nav ) {
			var list = nav.closest( '.saito-mc-reco' ).querySelector( '.saito-mc-reco__list' );
			var step = list.clientWidth * 0.8 * ( nav.classList.contains( 'saito-mc-reco__prev' ) ? -1 : 1 );
			list.scrollBy( { left: step, behavior: 'smooth' } );
			return;
		}

		// Ajout au panier depuis le carrousel (produits simples).
		var add = event.target.closest( 'button.saito-mc-reco__add[data-product-id]' );
		if ( ! add || add.disabled ) {
			return;
		}
		event.preventDefault();
		var label = add.textContent;
		add.disabled = true;
		add.classList.add( 'is-loading' );
		var body = new URLSearchParams();
		body.append( 'product_id', add.getAttribute( 'data-product-id' ) );
		body.append( 'quantity', '1' );
		fetch( config.addToCartUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				add.classList.remove( 'is-loading' );
				if ( ! data || data.error ) {
					add.disabled = false;
					add.textContent = config.i18n.error;
					return;
				}
				add.textContent = config.i18n.added;
				// Mini-panier classique : on remplace nous-mêmes les fragments
				// renvoyés (le script de WooCommerce qui le fait n'est pas
				// chargé sur toutes les pages), comme cart-fragments.js.
				replaceFragments( data.fragments );
				// Mini-panier en blocs et autres scripts : évènements standard.
				if ( window.jQuery ) {
					window.jQuery( document.body ).trigger( 'added_to_cart', [ data.fragments, data.cart_hash, window.jQuery( add ) ] );
				}
				document.body.dispatchEvent( new CustomEvent( 'wc-blocks_added_to_cart', { bubbles: true, cancelable: true } ) );
				scheduleRefresh( 400 );
				setTimeout( function () {
					add.disabled = false;
					add.textContent = label;
				}, 1500 );
			} )
			.catch( function () {
				add.classList.remove( 'is-loading' );
				add.disabled = false;
				add.textContent = config.i18n.error;
			} );
	} );

	render();
	// Shortcodes présents dans la page (ou dans le modèle du mini-panier) :
	// données à jour dès le chargement.
	if ( document.querySelector( '[data-saito-mc]' ) ) {
		scheduleRefresh( 0 );
	}
}() );
