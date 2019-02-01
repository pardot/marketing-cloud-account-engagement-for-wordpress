(function( $ ) {
	// We need to wait for window.load because we're manipulating Thickbox elements.
	$( window ).on( 'load', function() {

		/**
		 * Ensure our customized Thickbox window is centered on load.
		 *
		 * @since 1.4.4
		 */
		$( 'body' ).on( 'pardot-open-thickbox', function() {

			$( 'body.pardot-modal-open #TB_window' ).css( 'marginLeft', '-220px' );

			/**
			 * Remove our "namespace" class from the body.
			 *
			 * @since 1.4.4
			 */
			$( '#TB_closeWindowButton' ).add( '#TB_overlay' ).click( function() {
				// Thickbox will close itself, but using .hide() here prevents a UI flicker when body class is removed.
				$( '#TB_window, #TB_overlay' ).hide();
				$( 'body' ).removeClass( 'pardot-modal-open' );
			});
		});

		/**
		 * Ensure our customized Thickbox window remains centered.
		 *
		 * @since 1.4.4
		 */
		$(window).on( 'resize', function() {
			$( 'body.pardot-modal-open #TB_window' ).css( 'marginLeft', '-220px' );
		});

		/**
		 * Populates the values of the "Forms" select field in the popup.
		 *
		 * @since 1.0.0
		 */
		$.ajax({
			type     : 'post',
			dataType : 'html',
			url      : PardotShortcodePopup.ajaxurl,
			data     : {
				action : 'get_pardot_forms_shortcode_select_html'
			},
			success: function( response ) {
			 	$( document.getElementById( 'pardot-forms-shortcode-select' ) ).html( response );
			 	$( document.getElementById( 'formshortcode' ) ).chosen({ width: '100%' });
		 	}
		});

		/**
		 * Populates the values of the "Dynamic Content" select field in the popup.
		 *
		 * @since 1.0.0
		 */
		$.ajax({
			type     : 'post',
			dataType : 'html',
			url      : PardotShortcodePopup.ajaxurl,
			data     : {
				action : 'get_pardot_dynamicContent_shortcode_select_html'
			},
			success: function( response ) {
			 	$( document.getElementById( 'pardot-dc-shortcode-select' ) ).html( response );
			 	$( document.getElementById( 'dcshortcode' ) ).chosen({ width: '100%' });
			}
		});

		/**
		 * Currently-unused function to clear Pardot API cache and get "fresh" data for form's Chosen fields.
		 *
		 * @since 1.0.0
		 */
		function refresh_cache() {

			$.ajax({
				type : 'post',
				url  : PardotShortcodePopup.ajaxurl,
				data : {
					action : 'popup_reset_cache'
				}
			});

			$.ajax({
				type     : 'post',
				dataType : 'html',
				url      : PardotShortcodePopup.ajaxurl,
				data     : {
					action : 'get_pardot_forms_shortcode_select_html'
				},
				success: function( response ) {
				 	$( document.getElementById( 'pardot-forms-shortcode-select' ) ).html( response );
			 	}
			});

			$.ajax({
				type     : 'post',
				dataType : 'html',
				url      : PardotShortcodePopup.ajaxurl,
				data     : {
					action : 'get_pardot_dynamicContent_shortcode_select_html'
				},
				success: function( response ) {
				 	$( document.getElementById( 'pardot-dc-shortcode-select' ) ).html( response );
			 	}
			});
		}

		/**
		 * Take the form choices and use them to create a Pardot shortcode in the editor.
		 *
		 * @since 1.0.0
		 */
		$( document.getElementById( 'pardot-forms-modal-insert' ) ).on( 'click', function( e ) {
			e.preventDefault();

			if ( ( $( '#formshortcode' ).length != 0 ) && ( $( '#formshortcode' ).val() != '0' ) ) {
				var formval    = $( '#formshortcode' ).val();
				var formheight = $( '#formh' ).val();

				if ( formheight ) {
					formval = formval.replace( 'pardot-form', 'pardot-form height="' + formheight + '"' );
				}

				var formwidth = $( '#formw' ).val();

				if ( formwidth ) {
					formval = formval.replace( 'pardot-form', 'pardot-form width="' + formwidth + '"' );
				}

				var formclass = $( '#formc' ).val();

				if ( formclass ) {
					formval = formval.replace( 'pardot-form', 'pardot-form class="' + formclass + '"' );
				}

				window.send_to_editor( formval );
			}

			if ( ( $( '#dcshortcode' ).length != 0 ) && ( $( '#dcshortcode' ).val() != '0' ) ) {
				var dcval    = $( '#dcshortcode' ).val();
				var dcheight = $( '#dch' ).val();

				if ( dcheight ) {
					dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content height="' + dcheight + '"' );
				}

				var dcwidth = $( '#dcw' ).val();

				if ( dcwidth ) {
					dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content width="' + dcwidth + '"' );
				}

				var dcclass = $( '#dcc' ).val();

				if ( dcclass ) {
					dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content class="' + dcclass + '"' );
				}

				window.send_to_editor( dcval );
			}

		});

		/**
		 * Cancels form submission and closes the Pardot shortcode builder modal.
		 *
		 * @since 1.4.4
		 */
		$( document.getElementById( 'pardot-forms-modal-cancel' ) ).on( 'click', function( e ) {
			e.preventDefault();
			tb_remove();

			// Thickbox will close itself, but using .hide() here prevents a UI flicker when body class is removed.
			$( '#TB_window, #TB_overlay' ).hide();
			$( 'body' ).removeClass( 'pardot-modal-open' );
		});

		/**
		 * Body class and jQuery event that let us customize the Pardot shortcode-builder without
		 * altering other Thickbox modals that might be on the page.
		 *
		 * @since 1.4.6
		 */
		$( document ).on( 'click', '.mce-pardot-tinymce-button', function() {
			$( 'body' ).addClass( 'pardot-modal-open' ).trigger( 'pardot-open-thickbox' );
		});

	});

})( jQuery );