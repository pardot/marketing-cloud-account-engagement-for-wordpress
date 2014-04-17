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
	var $chosen_js_url;
	var $chosen_css_url;
	var $js;
	var $css;
	var $body_inner_html;

	/**
	 * Initialize the values used in the popup's HTML page.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		$this->title = __( 'Pardot', 'pardot' );
		$this->jquery_url  = site_url( '/wp-includes/js/jquery/jquery.js' );
		$this->tiny_mce_popup_url = site_url( '/wp-includes/js/tinymce/tiny_mce_popup.js' );
		$this->chosen_js_url = '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js';
		$this->chosen_css_url = '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css';
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
#pardot-forms-shortcode-popup p {margin:5px 0;clear:both;}
#pardot-forms-shortcode-popup p.well {padding:5px;background:#ccc;}
#pardot-forms-shortcode-popup .mceActionPanel {text-align:center;margin:10px 0;}
#pardot-forms-shortcode-select .spinner {vertical-align:-3px;}
#pardot-forms-shortcode-select #formshortcode, #pardot-dc-shortcode-select #dcshortcode {font-size:1em;max-width:100%;float:left;}
#shortcode-dc-input {width:70%;padding:3px 0;}
.mceActionPanel {width:100%;float:left;}
.mceActionPanel #insert {float:left;}
.mceActionPanel #cancel {float:right;}
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
var PardotShortcodePopup = {
	init:function() {},
	insert:function() {
		if ( ( jQuery('#formshortcode').length != 0 ) && ( jQuery('#formshortcode').val() != '0' ) ) {
			var formval = jQuery('#formshortcode').val();
			var formheight = jQuery('#formh').val();
			if ( formheight ) {
				formval = formval.replace('pardot-form', 'pardot-form height="'+formheight+'"')
			}
			var formwidth = jQuery('#formw').val();
			if ( formwidth ) {
				formval = formval.replace('pardot-form', 'pardot-form width="'+formwidth+'"')
			}
			var formclass = jQuery('#formc').val();
			if ( formclass ) {
				formval = formval.replace('pardot-form', 'pardot-form class="'+formclass+'"')
			}
			tinyMCEPopup.editor.execCommand('mceInsertContent',false,formval);
		}
		if ( ( jQuery('#dcshortcode').length != 0 ) && ( jQuery('#dcshortcode').val() != '0' ) ) {
		    var dcval = jQuery('#dcshortcode').val();
			var dcheight = jQuery('#dch').val();
			if ( dcheight ) {
				dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content height="'+dcheight+'"')
			}
			var dcwidth = jQuery('#dcw').val();
			if ( dcwidth ) {
				dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content width="'+dcwidth+'"')
			}
			var dcclass = jQuery('#dcc').val();
			if ( dcclass ) {
				dcval = dcval.replace('pardot-dynamic-content', 'pardot-dynamic-content class="'+dcclass+'"')
			}
			tinyMCEPopup.editor.execCommand('mceInsertContent',false,dcval);
		}
		tinyMCEPopup.close();
	}
};
tinyMCEPopup.onInit.add(PardotShortcodePopup.init,PardotShortcodePopup);
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
		 * Get the Settings Page url.
		 */
		$pardot_settings_url = admin_url( '/options-general.php?page=pardot' );
		/**
		 * Allow label to be translated into other written languages.
		 */
		$formsec = __( 'Forms', 'pardot' );
		$labelform = __( 'Select a form to insert', 'pardot' );
		$formcust = __( 'Optional iframe Parameters', 'pardot' );
        $dccust = __( 'Optional iframe Parameters', 'pardot' );
		$labelformh = __( 'Height', 'pardot' );
		$labelformw = __( 'Width', 'pardot' );
		$labelformc = __( 'Class', 'pardot' );
        $labeldch = __( 'Height', 'pardot' );
        $labeldcw = __( 'Width', 'pardot' );
        $labeldcc = __( 'Class', 'pardot' );
		$dcsec = __( 'Dynamic Content', 'pardot' );
		$labeldc = __( 'Select dynamic content to insert', 'pardot' );
		//$labeldcalt = __( 'Default content to show JS-disabled users', 'pardot' );
		$cache_text = __( '<strong>Not seeing something you added recently in Pardot?</strong> Please click the Clear Cache button on the %s.', 'pardot' );
		$cache_link = sprintf( '<a href="%s" target="_parent">%s</a>', $pardot_settings_url, 'Pardot Settings Page' );
		$cache_text = sprintf( $cache_text, $cache_link );
        $formparam_text = __( 'Height and width should be in digits only (i.e. 250).', 'pardot' );
        $dcparam_text = __( 'Height and width should be in px or % (i.e. 250px or 90%).', 'pardot' );
		/**
		 * Use HEREDOC to make the form's HTML much more easy to understand.
		 *
		 * After form loads use AJAX to call back to WordPress to get the list of forms for user selection.
		 */
		$html =<<<HTML
<div id="pardot-forms-shortcode-insert-dialog">
<form method="post"	action="#">
	<div class="fields">
		<h2>{$formsec}</h2>
		<label for="shortcode">{$labelform}</label>:
		<br clear="all" />
		<span id="pardot-forms-shortcode-select">
			<input type="hidden" id="shortcode">
			<img class="spinner" src="{$spinner_url}" height="16" weight="16" alt="Time waits for no man.">
		</span>
		<br clear="all" />
		<h4>{$formcust}</h4>
		<p><small>{$formparam_text}</small></p>
		<label for="formh">{$labelformh}</label>:
		<input type="text" size="6" id="formh" name="formh" />
		<label for="formw">{$labelformw}</label>:
		<input type="text" size="6" id="formw" name="formw" />
		<label for="formc">{$labelformc}</label>:
		<input type="text" id="formc" name="formc"/>
		<br clear="all" />
		<br />
		<h2>{$dcsec}</h2>
		<label for="shortcode-dc">{$labeldc}</label>:
		<span id="pardot-dc-shortcode-select">
			<input type="hidden" id="shortcodedc">
			<img class="spinner" src="{$spinner_url}" height="16" weight="16" alt="Time waits for no man.">
		</span>
		<br clear="all" />
		<h4>{$dccust}</h4>
	    <p><small>{$dcparam_text}</small></p>
		<label for="dch">{$labeldch}</label>:
		<input type="text" size="6" id="dch" name="dch" />
		<label for="dcw">{$labeldcw}</label>:
		<input type="text" size="6" id="dcw" name="dcw" />
		<label for="dcc">{$labeldcc}</label>:
		<input type="text" id="dcc" name="dcc"/>
	</div>
	<div class="mceActionPanel">
		<span class="insert-button">
			<input type="submit" id="insert" name="insert" value="{#insert}" class="button-primary" onclick="PardotShortcodePopup.insert();" />
		</span>
		<!-- If you're reading this, you're getting a sneak peek of a future relase. Well done! -->
		<!--<span class="reload-button">
			<input type="submit" id="reload" name="reload" value="Reload" class="updateButton" onclick="return refresh_cache();" />
		</span>-->
		<span class="cancel-button">
			<input type="submit" id="cancel" name="cancel" value="{#cancel}" class="button-secondary" onclick="tinyMCEPopup.close();" />
		</span>
	</div>
	<p class="well"><small>{$cache_text}</small></p>
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
		 	$('#formshortcode').chosen();
	 	}
	});
	$.ajax({
		type:"post",
		dataType:"html",
		url:"{$ajax_url}",
		data:{action:"get_pardot_dynamicContent_shortcode_select_html"},
		success: function(lmth) {
		 	$("#pardot-dc-shortcode-select").html(lmth);
		 	$('#dcshortcode').chosen();
	 	}
	});
});
function refresh_cache() {
	tinyMCEPopup.resizeToInnerSize();
	jQuery.ajax({
		type:"post",
		url:"{$ajax_url}",
		data:{action:"popup_reset_cache"},
	});
	jQuery.ajax({
		type:"post",
		dataType:"html",
		url:"{$ajax_url}",
		data:{action:"get_pardot_forms_shortcode_select_html"},
		success: function(html) {
		 	jQuery("#pardot-forms-shortcode-select").html(html);
	 	}
	});
	jQuery.ajax({
		type:"post",
		dataType:"html",
		url:"{$ajax_url}",
		data:{action:"get_pardot_dynamicContent_shortcode_select_html"},
		success: function(lmth) {
		 	jQuery("#pardot-dc-shortcode-select").html(lmth);
	 	}
	});
}
</script>
HTML;
		return $html;
	}
}
