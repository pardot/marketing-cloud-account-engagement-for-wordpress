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
	var $jquery_url;
	var $tiny_mce_popup_url;
	var $js;
	var $css;
	var $body_inner_html;

	/**
	 * Initialize the values used in the popup's HTML page.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		$this->title = __( 'Insert a Pardot Form Shortcode', 'pardot' );
		$this->jquery_url  = site_url( '/wp-includes/js/jquery/jquery.js' );
		$this->tiny_mce_popup_url = site_url( '/wp-includes/js/tinymce/tiny_mce_popup.js' );
		$this->js = $this->get_js();
		$this->css = $this->get_css();
		$this->body_inner_html = $this->get_body_inner_html();
	}
	/**
	 * Return CSS to embed into popup's HTML page.
	 *
	 * @return string The CSS to embed sans <style> tag.
	 *
	 * @since 1.0.0
	 */
	function get_css() {
		$css =<<<CSS
#pardot-tinymce-popup { min-width:400px;width:400px;margin:0;padding:10px 0 0 10px;overflow:hidden;}
#pardot-forms-shortcode-popup {padding:10px 20px 0 10px;width:376px;}
#pardot-forms-shortcode-popup	#pardot-forms-shortcode-insert-dialog {font-size:1.15em;}
#pardot-forms-shortcode-popup h1 {font-size:1.5em;margin-bottom:0.5em;}
#pardot-forms-shortcode-popup .mceActionPanel {text-align:center;margin-top:20px;}
#pardot-forms-shortcode-select .spinner {vertical-align:-3px;}
#pardot-forms-shortcode-select #shortcode {font-size:1em;}
CSS;
		return $css;
	}
	/**
	 * Return Javacript to embed into popup's HTML page.
	 *
	 * This Javascript creates and initializes a tinyMCEPopup object and implements an insert into TinyMCE command.
	 *
	 * @see: http://www.tinymce.com/wiki.php/API3:class.tinyMCEPopup
	 * @see: http://tinymce.moxiecode.com/js/tinymce/docs/api/index.html#class_tinyMCEPopup.html
	 * @see: http://www.tinymce.com/wiki.php/How-to_implement_a_custom_file_browser (search for "File Browser Dialogue Initialization in TinyMCE3.x")
	 *
	 * @return string The Javascript to embed sans <script> tags.
	 *
	 * @since 1.0.0
	 */
	function get_js() {
		$js =<<<JS
var PardotFormsShortcodePopup = {
	init:function() {},
	insert:function() {
		tinyMCEPopup.editor.execCommand('mceInsertContent',false,jQuery('#shortcode').val());
		tinyMCEPopup.close();
	}
};
tinyMCEPopup.onInit.add(PardotFormsShortcodePopup.init,PardotFormsShortcodePopup);
JS;
		return $js;
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
		if ( get_pardot_api_key() ) {
			$html = $this->get_dialog_html();
		} else {
			$page_link = Pardot_Settings::get_admin_page_link( array(
				'target' => '_blank',
				'onclick' => 'tinyMCEPopup.close();',
			));
			$error_msg = __( 'It looks like your account isn\'t connected yet. Please configure your account credentials at your %s page.', 'pardot' );
			$error_msg = sprintf( $error_msg, $page_link );
			$html =<<<HTML
<p>{$error_msg}</p>
<div class="mceActionPanel">
	<div class="cancel-button">
		<input type="button" id="cancel" name="cancel" value="{#cancel}">
	</div>
</div>
HTML;
		}
		return <<<HTML
<div id="pardot-forms-shortcode-popup">
<h1>{$this->title}</h1>
{$html}
</div>
HTML;
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
		/**
		 * AJAX url needed to load forms after popup is displayed.
		 * We do this since the forms may not be cached and will
		 * thus may take a few seconds to lookup via API. Not using
		 * AJAX results in a pause for the user where they can easily
		 * get confused and assume nothing is happening.
		 *
		 * Note that 'get_pardot_forms_shortcode_select_html' is defined in Pardot_Plugin class.
		 */
		$ajax_url = admin_url( 'admin-ajax.php' );
		/**
		 * Get the URL of a nice little indicator that something is happening.
		 */
		$spinner_url = admin_url( '/images/wpspin_light.gif' );
		/**
		 * Allow label to be translated into other written languages.
		 */
		$label = __( 'Select which Form to Insert', 'pardot' );
		/**
		 * Use HEREDOC to make the form's HTML much more easy to understand.
		 *
		 * After form loads use AJAX to call back to WordPress to get the list of forms for user selection.
		 */
		$html =<<<HTML
<div id="pardot-forms-shortcode-insert-dialog">
<form method="post"	action="#">
	<div class="fields">
		<label for="shortcode">{$label}</label>:
		<span id="pardot-forms-shortcode-select">
			<input type="hidden" id="shortcode">
			<img class="spinner" src="{$spinner_url}" height="16" weight="16" alt="Time waits for no man.">
		</span>
	</div>
	<div class="mceActionPanel">
		<span class="cancel-button">
			<input type="submit" id="cancel" name="cancel" value="{#cancel}" class="button-secondary" onclick="tinyMCEPopup.close();" />
		</span>
		<span class="insert-button">
			<input type="submit" id="insert" name="insert" value="{#insert}" class="button-primary" onclick="PardotFormsShortcodePopup.insert();" />
		</span>
	</div>
</form>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$.ajax({
		type:"post",
		dataType:"html",
		url:"{$ajax_url}",
		data:{action:"get_pardot_forms_shortcode_select_html"},
		success: function(html) {
		 	$("#pardot-forms-shortcode-select").html(html);
	 	}
	});
});
</script>
HTML;
		return $html;
	}
}

