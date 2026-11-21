/* Bionexa Core front-end helpers. */
( function () {
	'use strict';

	// WooCommerce's add-to-cart form is not multipart by default; the SDS form needs file uploads.
	document.querySelectorAll( 'form.cart' ).forEach( function ( form ) {
		if ( form.querySelector( 'input[type="file"]' ) ) {
			form.setAttribute( 'enctype', 'multipart/form-data' );
		}
	} );

	// SDS form: at least one region must be ticked before submitting.
	document.querySelectorAll( '.bionexa-sds-form' ).forEach( function ( fieldset ) {
		var form = fieldset.closest( 'form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener( 'submit', function ( event ) {
			if ( ! fieldset.querySelector( 'input[name="bionexa_sds_regions[]"]:checked' ) ) {
				event.preventDefault();
				var first = fieldset.querySelector( 'input[name="bionexa_sds_regions[]"]' );
				if ( first ) {
					first.focus();
				}
				window.alert( 'Please choose at least one country / regulation for your SDS.' );
			}
		} );
	} );
} )();
