/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, doc, undefined ) {
	$( function () {
		var re       = /([^&=]+)=?([^&]*)/g;
		var decodeRE = /\+/g;  // Regex for replacing addition symbol with a space
		var decode   = function ( str ) {
			return decodeURIComponent( str.replace( decodeRE, " " ) );
		};

		var parse_params = function ( query ) {
			var params = {}, e;
			while ( e = re.exec( query ) ) {
				var k = decode( e[ 1 ] ), v = decode( e[ 2 ] );
				if ( k.substring( k.length - 2 ) === '[]' ) {
					k = k.substring( 0, k.length - 2 );
					(params[ k ] || (params[ k ] = [])).push( v );
				}
				else params[ k ] = v;
			}
			return params;
		};

		var $doc = $( doc );

		$doc.ajaxSuccess( function ( e, request, settings, response_body ) {
			if ( 'string' === typeof settings.data ) {
				var data = parse_params( settings.data );
				if ( data.hasOwnProperty( 'register' ) && data.hasOwnProperty( 'reg_role' ) ) {
					if ( $( response_body ).find( '.woocommerce-error' ).length < 1 ) {
						w.location.href = wpjm_top_resume.post_resume_url;
					}
				}
			}
		} );

		$doc.on( 'submit', '.modal form.register', function ( e ) {
			e.preventDefault();
		} );
	} );
})( window, jQuery, document );