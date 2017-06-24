/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, undefined ) {
	$( function () {
		$( '.resume-manager-add-row' ).on( 'click wpjm-click', function ( e ) {
			setTimeout( function () {
				$( '#submit-resume-form' ).find( '.resume-manager-data-row .jmfe-date-picker:not(.hasDatepicker)' ).each( function ( index, input ) {
					$( input ).datepicker( jmfe_date_field );
				} );
			}, 100 );
		} );

		$( '#submit-resume-form' ).find( '.fieldset-candidate_education, .fieldset-candidate_experience' ).on( 'change', '.jmfe-date-picker', function ( e ) {
			var $entry_row = $( e.currentTarget ).closest( '.resume-manager-data-row' );
			$entry_row.find( '.fieldset-date input' ).val(
				$entry_row.find( '.fieldset-start_date input' ).val()
				+ ' / ' +
				$entry_row.find( '.fieldset-end_date input' ).val()
			);
		} );
	} );
})( window, jQuery );