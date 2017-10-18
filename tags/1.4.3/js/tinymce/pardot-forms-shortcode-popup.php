<?php
/**
 * Provides the full HTML for the Pardot Forms Shortcode Popup page.
 *
 * This HTML page is tied to the Pardot Forms Shortcode Insert button and
 * enables inserting 'pardot-form ...]' shortcodes into the TinyMCE editor.
 *
 * @author Mike Schinkel
 * @since 1.0.0
 *
 */

/**
 * Load /wp-load.php into global namespace as long as its in an ancestor directory.
 */
require( dirname( __FILE__ ) . '/../../includes/pardot-wp-loader.php' );

/**
 * Load definition of _Pardot_Forms_Shortcode_Popup() class.
 */
require(dirname( __FILE__ ) . '/../../includes/pardot-forms-shortcode-popup-class.php');

/**
 * Create a $popup object as a simple convenience. Consider this class internal-use only, it will probably change.
 */
$popup = new _Pardot_Forms_Shortcode_Popup();

/**
 * Use a HEREDOC to assemble the HTML for this page really clean and easy to read.
 * Go with HTML5 doctype, why not?
 */
$html =<<<HTML
<!DOCTYPE html>
<html>
<head>
<title>{$popup->title}</title>
<script type="text/javascript" src="{$popup->jquery_url}"></script>
<script type="text/javascript" src="{$popup->tiny_mce_popup_url}"></script>
<script type="text/javascript" src="{$popup->chosen_js_url}"></script>
<link rel="stylesheet" type="text/css" href= "{$popup->chosen_css_url}">
<script type="text/javascript">{$popup->js}</script>
<style type="text/css">{$popup->css}</style>
</head>
<body id="pardot-tinymce-popup">
{$popup->body_inner_html}
</body>
</html>
HTML;

/**
 * Send HTML off to the browser.
 */
echo $html;

