/* Bionexa Core front-end helpers. */
( function () {
	'use strict';

	// WooCommerce's add-to-cart form is not multipart by default; the SDS form needs file uploads.
	document.querySelectorAll( 'form.cart' ).forEach( function ( form ) {
		if ( form.querySelector( 'input[type="file"]' ) ) {
			form.setAttribute( 'enctype', 'multipart/form-data' );
		}
	} );

	// SDS form: required checkbox groups (e.g. countries) need at least one tick.
	document.querySelectorAll( '.bionexa-sds-form' ).forEach( function ( box ) {
		var form = box.closest( 'form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener( 'submit', function ( event ) {
			var groups = box.querySelectorAll( '.bionexa-checkgrid[data-required="1"]' );
			for ( var i = 0; i < groups.length; i++ ) {
				if ( ! groups[ i ].querySelector( 'input:checked' ) ) {
					event.preventDefault();
					groups[ i ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
					window.alert( 'Please choose at least one option for: ' + groups[ i ].getAttribute( 'data-label' ) );
					return;
				}
			}
		} );
	} );
} )();
