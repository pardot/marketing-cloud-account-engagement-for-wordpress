<?php
/**
 * This is a simple convenience class to make it easier to manage the code for the Shortcode Select Popup easier.
 * Consider it internal-use only.
 *
 * @author Mike Schinkel
 * @since 1.0.0
 */
class _Pardot_Forms_Shortcode_Popup {
	var $title;
	var $tiny_mce_popup_url;
	var $body_inner_html;

	/**
	 * Initialize the values used in the popup's HTML page.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
		add_action( 'admin_footer', array( $this, 'get_body_inner_html' ) );
	}

	/**
	 * Load plugin CSS needed for the Shortcode builder modal.
	 *
	 * @since 1.4.4
	 */
	public function load_css() {
		wp_enqueue_style( 'pardot-chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css' );
		wp_enqueue_style( 'pardot-popup', plugins_url( 'css/popup.css', PARDOT_PLUGIN_FILE ), array( 'pardot-chosen' ) );
	}

	/**
	 * Load plugin JS needed for the Shortcode builder modal.
	 *
	 * @since 1.4.4
	 */
	public function load_js() {
		wp_enqueue_script( 'pardot-chosen-js', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.2/chosen.jquery.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'pardot-popup-js', plugins_url( 'js/popup.js', PARDOT_PLUGIN_FILE ), array( 'pardot-chosen-js', 'jquery' ) );
		wp_localize_script( 'pardot-popup-js', 'PardotShortcodePopup', array(
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'tinymce_button_url' => plugins_url( 'images/pardot-button.png', PARDOT_PLUGIN_FILE )
		) );
	}

	/**
	 * Returns the HTML to be used inside the <body> element for popup's HTML page.
	 *
	 * Mostly delegates to $this->get_dialog_html() but it presents an error message if an
	 * API key cannot be retrieved indicating the account has not yet been configured.
	 *
	 * @return string The HTML to display inside of <body> tag.
	 *
	 * @since 1.0.0
	 */
	function get_body_inner_html() {

		$html = '<div id="pardot-forms-shortcode-popup" style="display:none;">';

		if ( get_pardot_api_key() ) {

			$html .= $this->get_dialog_html();

		} else {

			$page_link = Pardot_Settings::get_admin_page_link( array( 'target'  => '_blank' ) );
			$error_msg = __( 'It looks like your account isn\'t connected yet. Please configure your account credentials at your %s page.', 'pardot' );
			$error_msg = sprintf( $error_msg, $page_link );
			$close = __( 'Close', 'pardot');

			ob_start();


            $html .= <<<HTML
				<p>{$error_msg}</p>
				<div class="mceActionPanel">
					<div class="cancel-button">
						<input type="button" id="close" name="close" value={$close} onclick="self.parent.tb_remove()">
					</div>
				</div> 
HTML;
			$html .= ob_get_clean();
		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Returns the HTML for the  HTML popup dialog that allows selection of Pardot Forms
	 *
	 * Mostly delegates to $this->get_dialog_html() but it presents an error message if an
	 * API key cannot be retrieved indicating the account has not yet been configured.
	 *
	 * @note Uses WordPress AJAX to retrieve list of Pardot forms to avoid painful pause in initial dialog display.
	 *
	 * @return string The HTML to containing the <form> tag with it's <div> wrapper.
	 *
	 * @since 1.0.0
	 */
	function get_dialog_html() {

		$spinner_url         = admin_url( '/images/wpspin_light.gif' );
		$pardot_settings_url = admin_url( '/options-general.php?page=pardot' );

		ob_start();

		include PARDOT_PLUGIN_DIR . '/includes/popup.php';

		$html = ob_get_clean();

		return $html;
	}
}