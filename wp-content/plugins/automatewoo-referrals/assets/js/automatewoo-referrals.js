/* global automatewooReferralsLocalizeScript, Cookies */
jQuery( document ).ready( function ( $ ) {
	const options = automatewooReferralsLocalizeScript;

	function openBox( url ) {
		window.open(
			url,
			'automatewoo_social_share',
			'titlebar=no,toolbar=no,height=300,width=550,resizable=yes,status=no'
		);
	}

	$( 'a.js-automatewoo-open-share-box' ).on( 'click', function ( e ) {
		e.preventDefault();
		openBox( $( this ).attr( 'href' ) );
	} );

	if ( options.is_link_based ) {
		// save advocate key from url as cookie

		function getQueryVars() {
			const vars = [];
			const hashes = window.location.href
				.slice( window.location.href.indexOf( '?' ) + 1 )
				.split( '&' );
			for ( let i = 0; i < hashes.length; i++ ) {
				const hash = hashes[ i ].split( '=' );
				vars.push( hash[ 0 ] );

				const key = typeof hash[ 1 ] === 'undefined' ? 0 : 1;

				// Remove fragment identifiers
				const n = hash[ key ].indexOf( '#' );
				hash[ key ] = hash[ key ].substring(
					0,
					n !== -1 ? n : hash[ key ].length
				);
				vars[ hash[ 0 ] ] = hash[ key ];
			}
			return vars;
		}

		const ref = getQueryVars()[ options.link_param ];

		if ( typeof ref !== 'undefined' ) {
			Cookies.set( 'aw_referral_key', ref, {
				expires: parseInt( options.cookie_expires, 10 ),
				path: '/',
			} );
		}
	}
} );
