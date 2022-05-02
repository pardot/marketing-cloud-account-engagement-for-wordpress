<?php

/**
 * Manages the general functionality for the Pardot Plugin that doesn't fit elsewhere.
 *
 * Includes:
 *  - Automatic Javascript in the theme footer
 * 	- [pardot-form] Shortcode
 *  - [pardot-dynamic-content] Shortcode
 *  - Caching support
 *  - Call and retrieve values from the Pardot API
 *  - Hooks to support the Pardot Forms Shortcode Insert button for TinyMCE
 *  - AJAX support for the Pardot Forms Shortcode Insert popup
 *  - Adds 'Settings' link to the plugins admin page for this plugin.
 *  - admin_init to ensure hooks are added for admin pages.
 *
 * @todo Refresh the API cache on a cron task before the cache times out (default = 180 seconds) which will ensure
 * always fast performance on Widgets page on with Shortcode Insert Popup.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 *
 * @since 1.0.0
 */
class Pardot_Plugin
{
	/**
	 * @var Pardot_Plugin Capture $this so that other can remove_action() if needed.
	 */
	private static $self;

	/**
	 * @var Pardot_API Capture an $api instance since we will often use it several times in a page load.
	 */
	private static $api;

	/**
	 * @var int Set cache timeout for the API (probably 180 seconds) to cache API responses other than authenticate.
	 * Set based on a constant which can be changed in /wp-config.php
	 */
	public static $cache_timeout = PARDOT_API_CACHE_TIMEOUT;

	/**
	 * @var The name of the wp_option where cache keys will be stored.
	 *
	 * @since 1.4.6
	 */
	public static $saved_cache_keys = '_pardot_cache_keys';

	/**
	 * @var The name of the wp_option where transient keys will be stored.
	 *
	 * @since 1.4.6
	 */
	public static $saved_transient_keys = '_pardot_transient_keys';

	/**
	 * Create singleton instance of the Pardot Plugin object.
	 *
	 * @since 1.0.0
	 */
	function __construct()
	{

		/**
		 * This class is designed to be instansiated only once.
		 * We instantiate once at end of this class definition, throw an error if someone tries a second time.
		 */
		if (isset(self::$self))
			wp_die(__('Pardot_Plugin should not be created more than once.', 'pardot'));

		/**
		 * Set self::$self so that a user can remove access to one of these actions or shortcodes if they need to.
		 */
		self::$self = $this;

		/**
		 * Hook the 'init' action where we add other actions and the shortcode.
		 */
		add_action('init', [$this, 'init']);

	}

	/**
	 * Return the singleton instance of this class.
	 *
	 * To be use in case someone needs to remove one of the actions or shortcodes.
	 *
	 * @static
	 * @return Pardot_Plugin
	 *
	 * @since 1.0.0
	 */
	static function self()
	{
		return self::$self;
	}

	/**
	 * Hook the 'init' action where we add other actions and the shortcode.
	 *
	 * @since 1.0.0
	 */
	function init()
	{
		/**
		 * Load the pardot text domain for language translations
		 */
		add_action('plugins_loaded', [$this, 'plugins_loaded']);

		/**
		 * Add the Pardot Javascript to the form.
		 */
		add_action('wp_footer', [$this, 'wp_footer']);

		/**
		 *    Create the shortcode [pardot-form]
		 */
		add_shortcode('pardot-form', [$this, 'form_shortcode']);

		/**
		 *    Create the shortcode [pardot-dynamic-content]
		 */
		add_shortcode('pardot-dynamic-content', [$this, 'dynamic_content_shortcode']);

		/**
		 * Add 'Settings' link on plugin list page and add TinyMCE button for post editor.
		 */
		add_action('admin_init', [$this, 'admin_init']);

		/**
		 * Listen for AJAX post back for the form's shortcode selector.
		 */
		add_action('wp_ajax_get_pardot_forms_shortcode_select_html', [$this, 'wp_ajax_get_pardot_forms_shortcode_select_html']);

		/**
		 * Listen for AJAX post back for the dynamic content's shortcode selector.
		 */
		add_action('wp_ajax_get_pardot_dynamicContent_shortcode_select_html', [$this, 'wp_ajax_get_pardot_dynamicContent_shortcode_select_html']);

		/**
		 * Listen for AJAX post back for the reload button.
		 */
		add_action('wp_ajax_popup_reset_cache', [$this, 'wp_ajax_popup_reset_cache']);

		/**
		 * Listen for AJAX post back for deleting cached HTML of assets
		 */
		add_action('wp_ajax_delete_asset_html_transient', [$this, 'wp_ajax_delete_asset_html_transient']);
	}

	/**
	 * AJAX function used to return the list of Pardot forms for the current accounts selected campaign.
	 *
	 * @since 1.0.0
	 */
	function wp_ajax_get_pardot_forms_shortcode_select_html()
	{
		/**
		 * Use the API or the cache to retrieve an array of Pardot Forms
		 */
		$forms = self::get_forms();

		/**
		 * Do we have Pardot Forms?
		 */
		if (!empty($forms)) {

			/**
			 * YES, we have Pardot Forms! :-)
			 *
			 * Grab the HTML that contains a <select> which lets the user select a Pardot Form
			 * for which it insert a shortcode for that form into the TinyMCE editing space.
			 */
			$html = $this->get_forms_shortcode_select_html('formshortcode', $forms);

		} else {

			/**
			 * No, we have no Pardot Forms today. :-(
			 *
			 * Grab the URL where users can define forms on Pardot's website and
			 * put into a variable that can be embedded in a string.
			 */
			$forms_url = Pardot_Settings::FORMS_URL;

			/**
			 * Grab link text so it can be translated seperately.
			 */
			$link_text = __('create one', 'pardot');

			/**
			 * Assemble the link for where Pardot Forms can be defined.
			 */
			$page_link = "<a target=\"_blank\" href=\"{$forms_url}\">{$link_text}</a>";

			/**
			 * Assemble the link for where Pardot Forms can be defined.
			 */
			$error_msg = __('It looks like you don\'t have any forms set up yet. Please %s.', 'pardot');

			/**
			 * Insert the link into the error message.
			 */
			$html = sprintf($error_msg, $page_link);
		}

		/**
		 * Output either <select> with Pardot Forms as <options>, or an error message with instructions to add Pardot Forms.
		 */
		echo $html;

		/**
		 * And we're done.  Don't fall through and let WordPress echo a '0'.
		 */
		die();
	}

	/**
	 * AJAX function used to return the list of Pardot dynamic content for the current accounts selected campaign.
	 *
	 * @since 1.1.0
	 */
	function wp_ajax_get_pardot_dynamicContent_shortcode_select_html()
	{
		/**
		 * Use the API or the cache to retrieve an array of Pardot dynamicContents
		 */
		$dynamicContents = self::get_dynamicContent();

		/**
		 * Do we have Pardot dynamicContents?
		 */
		if (!empty($dynamicContents)) {

			/**
			 * YES, we have Pardot dynamicContents! :-)
			 *
			 * Grab the HTML that contains a <select> which lets the user select a Pardot dynamicContent
			 * for which it insert a shortcode for that dynamicContent into the TinyMCE editing space.
			 */
			$lmth = $this->get_dynamicContents_shortcode_select_html('dcshortcode', $dynamicContents);

		} else {

			/**
			 * No, we have no Pardot dynamicContents today. :-(
			 *
			 * Grab the URL where users can define dynamicContents on Pardot's website and
			 * put into a variable that can be embedded in a string.
			 */
			$dynamicContents_url = Pardot_Settings::DYNAMIC_CONTENT_URL;

			/**
			 * Grab link text so it can be translated seperately.
			 */
			$link_text = __('create some', 'pardot');

			/**
			 * Assemble the link for where Pardot dynamicContents can be defined.
			 */
			$page_link = "<a target=\"_blank\" href=\"{$dynamicContents_url}\">{$link_text}</a>";

			/**
			 * Assemble the link for where Pardot dynamicContents can be defined.
			 */
			$error_msg = __('<br />It looks like you don\'t have any Dynamic Content set up yet. Please %s.', 'pardot');

			/**
			 * Insert the link into the error message.
			 */
			$lmth = sprintf($error_msg, $page_link);
		}

		/**
		 * Output either <select> with Pardot dynamicContents as <options>, or an error message with instructions to add Pardot dynamicContents.
		 */
		echo $lmth;

		/**
		 * And we're done.  Don't fall through and let WordPress echo a '0'.
		 */
		die();
	}


	/**
	 * AJAX function used to delete HTML cache of dynamic content & forms
	 *
	 * @since 1.5.7
	 */
	public function wp_ajax_delete_asset_html_transient()
	{
		$assetType = $_REQUEST['asset_type'];
		$assetId = $_REQUEST['asset_id'];

		if ($assetType === 'form') {
			self::delete_form_html_transient($assetId);
		} elseif ($assetType === 'dc') {
			self::delete_dc_html_transient($assetId);
		}

		die();
	}

	public static function delete_form_html_transient(int $assetId)
	{
		delete_transient('pardot_form_html_' . $assetId);
	}

	public static function delete_dc_html_transient(int $assetId)
	{
		delete_transient('pardot_dynamicContent_html_' . $assetId);
	}

	/**
	 * AJAX function used to clear the cache in the popups.
	 *
	 * @since 1.1.5
	 */

	function wp_ajax_popup_reset_cache()
	{

		delete_transient('pardot_forms');
		delete_transient('pardot_dynamicContent');

		die();
	}

	/**
	 * Assemble <select> element for selected Pardot Forms.
	 *
	 * The <option> value will be the WordPress [pardot-form] shortcode to insert into TinyMCE
	 *
	 * @param string $select_name The HTML name for the <select> element
	 * @param array $forms The array for Pardot Forms returned form the Pardot API.
	 * @return string The HTML string to display
	 *
	 * @since 1.0.0
	 */
	function get_forms_shortcode_select_html($select_name, $forms)
	{

		/**
		 * Create an array to capture the HTML output into.
		 */
		$html = [];

		/**
		 * Use dashes for HTML IDs instead of underscores.
		 */
		$select_id = str_replace('-', '_', $select_name);

		/**
		 * Assemble the opening <select> tag.
		 */
		$html[] = "<select id=\"{$select_id}\" name=\"{$select_name}\">";

		$html[] = "<option value=\"0\">Select</option>";

		/**
		 * For each Pardot Form
		 */
		foreach ($forms as $form) {
			/**
			 * Assemble an option where the value is the WordPress [pardot-form] shortcode to insert into TinyMCE
			 */
			if (isset($form->id)) {
				$html[] = "<option value=\"[pardot-form id=&quot;{$form->id}&quot; title=&quot;{$form->name}&quot;]\">{$form->name}</option>";
			}
		}
		$html[] = '</select>';

		/**
		 * Compact the array of HTML into a string of HTML and return it.
		 */
		return implode('', $html);
	}

	/**
	 * Assemble <select> element for selected Pardot dynamicContents.
	 *
	 * The <option> value will be the WordPress [pardot-dynamic-content] shortcode to insert into TinyMCE
	 *
	 * @param string $select_name The HTML name for the <select> element
	 * @param array $dynamicContents The array for Pardot dynamicContents returned dynamicContent the Pardot API.
	 * @return string The HTML string to display
	 *
	 * @since 1.1.0
	 */
	function get_dynamicContents_shortcode_select_html($select_name, $dynamicContents)
	{

		/**
		 * Create an array to capture the HTML output into.
		 */
		$lmth = [];

		/**
		 * Use dashes for HTML IDs instead of underscores.
		 */
		$select_id = str_replace('-', '_', $select_name);

		/**
		 * Assemble the opening <select> tag.
		 */
		$lmth[] = "<select id=\"{$select_id}\" name=\"{$select_name}\">";

		$lmth[] = "<option value=\"0\">Select</option>";

		/**
		 * For each Pardot dynamicContent
		 */
		foreach ($dynamicContents as $dynamicContent) {
			/**
			 * Assemble an option where the value is the WordPress [pardot-dynamic-content] shortcode to insert into TinyMCE
			 */
			$defaultContent = urlencode($dynamicContent->baseContent);
			$lmth[] = "<option value=\"[pardot-dynamic-content id=&quot;{$dynamicContent->id}&quot; default=&quot;{$defaultContent}&quot;]\">{$dynamicContent->name}</option>";
		}
		$lmth[] = '</select>';

		/**
		 * Compact the array of HTML into a string of HTML and return it.
		 */
		return implode('', $lmth);
	}

	/**
	 * Load the text domain for language translation after the plugin is loaded.
	 *
	 * As of 1.0.0 no language translations are yet included.
	 *
	 * @since 1.0.0
	 */
	function plugins_loaded()
	{
		/**
		 * Load the 'pardot' text domain for language translation using the /languages/ subdirectory.
		 */
		load_plugin_textdomain('pardot', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Add the 'Settings' link on the plugin list page and add the TinyMCE button for the post editor.
	 *
	 * @since 1.0.0
	 */
	function admin_init()
	{
		global $pagenow, $typenow;

		/**
		 * Add a 'Settings' link to the "Installed Plugins" page
		 * Add a "Developer's site' link under the plugin description.
		 */
		if ('plugins.php' == $pagenow) {
			add_filter('plugin_action_links_pardot/pardot.php', [$this, 'plugin_action_links']);
			add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

		}

		/**
		 * Test to see if we should add the TinyMCE support for Pardot
		 *
		 * Check to see if we are adding or editing a post or page.
		 */
		if (!preg_match('#^(post-new.php|post.php)$#', $pagenow))
			return;

		/**
		 * If we are adding or editing a 'post', see if this user can edit posts.
		 */
		if ('post' == $typenow && !current_user_can('edit_posts'))
			return;

		/**
		 * If we are adding or editing a 'page', see if this user can edit pages.
		 */
		if ('page' == $typenow && !current_user_can('edit_pages'))
			return;

		/**
		 * Lastly see if this user has access to rich editing.
		 */
		if (get_user_option('rich_editing') == 'true') {
			/**
			 * All clear, add the hooks that will add the Pardot button to TinyMCE.
			 */
			add_filter('mce_external_plugins', [$this, 'mce_external_plugins']);
			add_filter('mce_buttons', [$this, 'mce_buttons']);

			new _Pardot_Forms_Shortcode_Popup();
		}
	}


	/**
	 * Filter hook to add a "Settings" link for the plugin on the plugins admin page.
	 *
	 * @param array $actions HTML links for the plugin page for this plugin.
	 * @return array Filtered HTML links for the plugin page for this plugin.
	 *
	 * @since 1.0.0
	 */
	function plugin_action_links($actions)
	{
		/**
		 * Add a 'Settings' link to the available actions for this plugin on the plugin page.
		 * Add to the beginning of the array of actions.
		 */
		array_unshift($actions, Pardot_Settings::get_admin_page_link());
		return $actions;
	}

	/**
	 * Filter hook to add a new link under the plugin description.
	 *
	 * @param array $plugin_meta List of HTML links.
	 * @param string $plugin_file slug for the plugin
	 * @return array Filtered HTML links for the plugin page for this plugin.
	 *
	 * @since 1.0.0
	 */
	function plugin_row_meta($plugin_meta, $plugin_file)
	{
		if (false !== strpos($plugin_file, '/pardot.php')) {
			$link_text = __("Visit developer's site", 'pardot');
			$plugin_meta[] = "<a href=\"http://about.me/mikeschinkel\" target=\"_blank\">{$link_text}</a>";
		}
		return $plugin_meta;
	}

	/**
	 * Filter hook to add a TinyMCE plugin for Pardot Forms.
	 *
	 * @param array $plugin_array List of TinyMCE buttons where key is string identifying the button and value is
	 * the path to the javascript file implementing the buttons functionality.
	 *
	 * @return array Filtered list of TinyMCE buttons.
	 *
	 * @see: http://www.tinymce.com/wiki.php/Creating_a_plugin
	 *
	 * @since 1.0.0
	 */
	function mce_external_plugins($plugin_array)
	{
		/**
		 * 'pardotformsshortcodeinsert' identifies the Pardot Forms button to TinyMCE.
		 * '.../editor_plugin.js' implements the button's functionality.
		 */
		$plugin_array['pardotformsshortcodeinsert'] = plugins_url('/js/tinymce.js', PARDOT_PLUGIN_FILE);
		return $plugin_array;
	}

	/**
	 * Filter hook to add a TinyMCE button to launch the Pardot Forms Shortcode Insert Popup
	 *
	 * @param array $buttons String indicators like 'bold', 'italic' and 'pardotformsshortcodeinsert'
	 * @return array Filtered array of string indicators for buttons.
	 *
	 * @see: http://www.tinymce.com/wiki.php/Buttons/controls
	 *
	 * @since 1.0.0
	 */
	function mce_buttons($buttons)
	{
		/**
		 * 'pardotformsshortcodeinsert' identifies the Pardot Forms button to TinyMCE prefixed with a separator.
		 */
		array_push($buttons, 'separator', 'pardotformsshortcodeinsert');
		return $buttons;
	}

	/**
	 * Adds the Pardot Javascript Tracking Code to the end of any theme that calls wp_footer().
	 *
	 * If you want to use after the template tag the_pardot_tracking_js() elsewhere
	 * then call remove_pardot_wp_footer() before the 'wp_footer' hook is called,
	 * such as in an 'init' hook or if you call that function before this hook files
	 * the javascript will only be generated once.
	 *
	 * @since 1.0.0
	 */
	function wp_footer()
	{
		pardot_dc_async_script();
		the_pardot_tracking_js();
	}

	/**
	 * Register the shortcode [pardot-form ...]
	 *
	 * @param array $atts Contains shortcode attributes provided by the user. Expect 'id' for Form ID.
	 *
	 * @since 1.0.0
	 */
	function form_shortcode($atts)
	{
		/**
		 * Translate from 'id' to 'form_id' which is what $this->get_form_body() uses.
		 */
		$atts['form_id'] = isset($atts['id']) ? $atts['id'] : 0;

		/**
		 * Output the Pardot form
		 */
		return self::get_form_body($atts);
	}

	/**
	 * Register the shortcode [pardot-dynamic-content ...]
	 *
	 * @param array $atts Contains shortcode attributes provided by the user. Expect 'id' for Dyanamic Content ID.
	 *
	 * @since 1.1.0
	 */
	function dynamic_content_shortcode($atts)
	{
		/**
		 * Translate from 'id' to 'dynamicContent_id' which is what $this->get_dynamic_content_body() uses.
		 */
		$atts['dynamicContent_id'] = isset($atts['id']) ? $atts['id'] : 0;

		/**
		 * Give a default to wrap in <noscript> for accesibility.
		 */
		$atts['dynamicContent_default'] = isset($atts['default']) ? $atts['default'] : '';

		/**
		 * Output the Pardot form
		 */
		return self::get_dynamic_content_body($atts);
	}

	/**
	 * Grab the HTML for the Pardot Form to be displayed via a widget or via a shortcode.
	 *
	 * @static
	 * @param array $args Contains 'form_id' and maybe 'height'
	 * @return bool|string
	 *
	 * @since 1.0.0
	 */
	static function get_form_body($args = [])
	{
		$body_html = false;
		/**
		 * If this is a postback when using the inline form time, provide a nice status message.
		 * The URL parameter will be 'pardot-contact-request' and will contain 'success' or 'errors'.
		 */
		if (isset($_GET['pardot-contact-request'])) {
			$request_status = 'success' == $_GET['pardot-contact-request'] ? 'success' : 'error';
			if ('success' == $request_status) {
				$body_html = __('Thank you for requesting more information. We will be back in touch soon.', 'pardot');
			} else {
				$body_html = __(
					'An error occurred with your request. Unfortunately we have no more information about why. Please contact or email us.',
					'pardot'
				);
			}
			$body_html = '<div id="pardot-' . $request_status . '-msg">' . $body_html . '</div>';
		} else {
			/**
			 * If this is not the special case of a postback, figure out what to display.
			 */
			$form_html = true;

			/**
			 * Form include type can be 'iframe' or 'inline'
			 */
			$is_iframe = 'iframe' == PARDOT_FORM_INCLUDE_TYPE;

			/**
			 * See if we've got the form cached as a transient
			 */
			$form_id = $args['form_id'];
			$form_html = get_transient('pardot_form_html_' . $form_id);
			if ($form_html) {
				/**
				 * We add either 'IFRAME:' or 'INLINE:' in front of form in transient so we can determine if we need
				 * to invalidate the transient in the case someone (re-)defines PARDOT_FORM_INCLUDE_TYPE
				 */
				preg_match('#^(IFRAME|INLINE):(.*)$#s', $form_html, $matches);
				/**
				 * If the transient doesn't start with 'IFRAME' or 'INLINE' or if transient contain the wrong type based on
				 * the current value of PARDOT_FORM_INCLUDE_TYPE then clear the cached value.
				 */
				if (3 != count($matches) || ($is_iframe && 'INLINE' == $matches[1]) || (!$is_iframe && 'IFRAME' == $matches[1])) {
					$form_html = false;
				} else {
					/**
					 * If transient DOES start with 'IFRAME' or 'INLINE' and that agreed with PARDOT_FORM_INCLUDE_TYPE
					 * then grab the cached part which comes after "IFRAME:" or "INLINE:".
					 */
					$form_html = $matches[2];

					/**
					 * Filter the embed code for HTTPS
					 */
					$form_html = self::convert_embed_code_https($form_html);
				}
			}
			/**
			 * If we don't have forms_html in cache then call API to get the forms.
			 */
			if (!$form_html && $forms = get_pardot_forms()) {
				/**
				 * Grab the form_id from the args passed.
				 */
				$form_id = $args['form_id'];

				if (isset($forms[$form_id])) {
					/**
					 * Use the form_id to find the right form
					 */
					$form = $forms[$form_id];
					/**
					 * Use that value is an object with 'embedCode' property...
					 */
					if (isset($form->embedCode)) {
						/**
						 * And if it's an IFRAME value then it's simple; just concat the embed code.
						 */
						if ($is_iframe) {
							$form_html = 'IFRAME:' . $form->embedCode;
							/**
							 * If height is passed as a shortcode argument
							 */
							if (!empty($atts['height'])) {
								/**
								 * If height is passed as a shortcode argument
								 */
								$height = $atts['height'];
								/**
								 * Find it in the embedCode HTML returned by Pardot's API
								 */
								if (preg_match('#height="[^"]+"#', $form_html, $matches)) {
									/**
									 * And replace with height passed
									 */
									$form_html = str_replace($matches[0], "height=\"{$height}\"", $form_html);
								} else {
									/**
									 * Or just add to the iframe.
									 */
									$form_html = str_replace('iframe', "iframe height=\"{$height}\"", $form_html);
								}
							}
							/**
							 * If width is passed as a shortcode argument
							 */
							if (!empty($atts['width'])) {
								/**
								 * If width is passed as a shortcode argument
								 */
								$width = $atts['width'];
								/**
								 * Find it in the embedCode HTML returned by Pardot's API
								 */
								if (preg_match('#width="[^"]+"#', $form_html, $matches)) {
									/**
									 * And replace with width passed
									 */
									$form_html = str_replace($matches[0], "width=\"{$width}\"", $form_html);
								} else {
									/**
									 * Or just add to the iframe.
									 */
									$form_html = str_replace('iframe', "iframe width=\"{$width}\"", $form_html);
								}
							}

							/**
							 * If class is passed as a shortcode argument
							 */
							if (!empty($atts['class'])) {
								/**
								 * If width is passed as a shortcode argument
								 */
								$class = $atts['class'];
								/**
								 * Add it.
								 */
								$form_html = str_replace('<iframe', "<iframe class=\"pardotform {$class}\"", $form_html);
							} else {
								$form_html = str_replace('<iframe', "<iframe class=\"pardotform\"", $form_html);
							}

							/**
							 * If title is passed as a shortcode argument
							 */
							if (!empty($atts['title'])) {
								/**
								 * If title is passed as a shortcode argument
								 */
								$title = $atts['title'];
								/**
								 * Add it.
								 */
								$form_html = str_replace('<iframe', "<iframe title=\"{$title}\"", $form_html);
							}

							/**
							 * Filter the embed code for HTTPS
							 */
							$form_html = self::convert_embed_code_https($form_html);
						} else {
							/**
							 * But if it's INLINE then we need to do some work; extract URL from embed code
							 */
							$url = preg_replace('#^<iframe src="([^"]+)".*$#', '$1', $form->embedCode);

							/**
							 * Now call the URL to get the HTML page that Pardot expects to be inlined
							 */
							$response = (object)wp_remote_get($url);

							/**
							 * If the response body isn't empty we expect it will have a string representing the HTML form
							 */
							if (!empty($response->body)) {
								/**
								 * Craft a Regex that gets everything after the <title> in head and everything within the <body>
								 */
								$regex = '#</title>\s*(.*?)\s*</head>\s*<body>\s*(.*?)\s*</body>#s';
								/**
								 * If that regex matches
								 */
								if (preg_match($regex, $response->body, $matches)) {
									/**
									 * If that regex matches then let's grab the action URL from the form, it's the postback
									 */
									preg_match('#<form[^>]+action="([^"]+)"#', $matches[2], $form_action);

									/**
									 * Craft an action that posts back to this site at /pardot-form-submit/ URL path with the
									 * read URL at Pardot as an url parameter named 'url'.
									 */
									$local_form_action = site_url('/pardot-form-submit/?url=' . urlencode($form_action[1]));

									/**
									 * Now use our URL as the new action URL for the form.
									 */
									$matches[2] = str_replace(
										"action=\"{$form_action[1]}\"", "action=\"{$local_form_action}\"", $matches[2]
									);

									/**
									 * Finally prepend 'INLINE' to the form content we extracted from the form
									 * that Pardot expected to be used in an <iframe> tag.
									 */
									$form_html = "INLINE:<div class=\"pardot-inline-form\">{$matches[1]}{$matches[2]}</div>"; //
								}
							}
						}
					}
					/**
					 * Finally, save what we found.
					 */
					if (set_transient('pardot_form_html_' . $form_id, $form_html, self::$cache_timeout)) {
						Pardot_Plugin::save_transient_key('pardot_form_html_' . $form_id);
					}
				}
			}
			/**
			 * Now take whatever we found (cached as a transient or retrieve from API) and remove
			 * the 'IFRAME:' or the 'INLINE:' prefix so we can use it.
			 */
			$body_html = preg_match('#^(IFRAME|INLINE):#', $form_html) ? substr($form_html, strlen('INLINE:')) : $form_html;

			if (!empty($args['querystring'])) {
				/**
				 * If "querystring" is passed via shortcode create HTML to insert in form's <div>
				 */
				if ($is_iframe) {
					/**
					 * If 'iframe' add to the <iframe>
					 */
					$body_html = preg_replace('/src="([^"]+)"/', 'src="$1?' . $args['querystring'] . '"', $body_html);
				}
			}

			if (!empty($args['height'])) {
				/**
				 * If "height" is passed via shortcode create HTML to insert in form's <div>
				 */
				if ($is_iframe) {
					/**
					 * If 'iframe' add to the <iframe>
					 */
					$body_html = preg_replace('#( height="[^"]+")#', " height=\"{$args['height']}\"", $body_html);
				} else {
					/**
					 * If 'inline' add to the <div> using style
					 */
					$height_html = " style=\"height:{$args['height']}px\"";
					$body_html = str_replace('<div class="pardot-inline-form">', "<div class=\"pardot-inline-form\"{$height_html}>", $body_html);
				}
			}

			if (!empty($args['width'])) {
				/**
				 * If "width" is passed via shortcode create HTML to insert in form's <div>
				 */
				if ($is_iframe) {
					/**
					 * If 'iframe' add to the <iframe>
					 */
					$body_html = preg_replace('#( width="[^"]+")#', " width=\"{$args['width']}\"", $body_html);
				} else {
					/**
					 * If 'inline' add to the <div> using style
					 */
					$width_html = " style=\"width:{$args['width']}px\"";
					$body_html = str_replace('<div class="pardot-inline-form">', "<div class=\"pardot-inline-form\"{$width_html}>", $body_html);
				}
			}

			if (!empty($args['title'])) {
				/**
				 * If "title" is passed via shortcode create HTML to insert in form's <div>
				 */
				if ($is_iframe) {
					/**
					 * If 'iframe' add to the <iframe>
					 */
					$body_html = str_replace(' class="pardotform"', " title=\"{$args['title']}\" class=\"pardotform\"", $body_html);
				}
			}

			if (!empty($args['class'])) {
				/**
				 * If "width" is passed via shortcode create HTML to insert in form's <div>
				 */
				if ($is_iframe) {
					/**
					 * If 'iframe' add to the <iframe>
					 */
					$body_html = str_replace(' class="pardotform"', " class=\"pardotform {$args['class']}\"", $body_html);
				}
			}

		}

		return apply_filters('pardot_form_embed_code_' . $args['form_id'], $body_html);
	}

	/**
	 * If HTTPS is desired, override the protocol and domain
	 *
	 * @static
	 * @param string $embed_code Contains HTML for embedding forms, dynamic content, etc.
	 * @return string
	 *
	 * @since 1.4.1
	 */
	static function convert_embed_code_https($embed_code)
	{
		if (Pardot_Settings::get_setting('https')) {
			/**
			 * Look for URLs in the embed code
			 */
			$reg_exUrl = apply_filters("pardot_https_regex", "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,63}(\/\S*)?/");
			preg_match($reg_exUrl, $embed_code, $url);

			// Check if default domain is already HTTPS
			if (strcasecmp(substr($url[0], 0, 8), "https://")) {
				/**
				 * Replace whatever is there with the approved Pardot HTTPS URL
				 */
				$urlpieces = parse_url($url[0]);
				$httpsurl = 'https://go.' . Pardot_Settings::BASE_PARDOT_DOMAIN . $urlpieces['path'];
				$embed_code = preg_replace($reg_exUrl, $httpsurl, $embed_code);
			}
		}

		return $embed_code;
	}

	/**
	 * Grab the HTML for the Pardot Dynamic Content to be displayed via a widget or via a shortcode.
	 *
	 * @static
	 * @param array $args Contains 'dynamicContent_id'
	 * @return bool|string
	 *
	 * @since 1.1.0
	 */
	static function get_dynamic_content_body($args = [])
	{
		$dynamicContent_html = false;
		/**
		 * Grab the dynamicContent_id from the args passed.
		 */
		$dynamicContent_id = $args['dynamicContent_id'];

		if (false === ($dynamicContent_html = get_transient('pardot_dynamicContent_html_' . $dynamicContent_id))) {

			$dynamicContents = get_pardot_dynamic_content();

			if (isset($dynamicContents[$dynamicContent_id])) {
				/**
				 * Use the dynamicContent_id to find the right one
				 */
				$dynamicContent = $dynamicContents[$dynamicContent_id];
				$dynamicContent_html = $dynamicContent->embedCode;
				$dynamicContent_url = $dynamicContent->embedUrl;
				$dynamicContent_default = $dynamicContent->baseContent;
			}

			if ($dynamicContent_url) {
				$dynamicContent_html = "<div data-dc-url='" . $dynamicContent_url . "' style='height:auto;width:auto;' class='pardotdc'>" . $dynamicContent_default . "</div>";
			} else {
				$dynamicContent_html = $dynamicContent_html . "<noscript>" . $dynamicContent_default . "</noscript>";
			}

			if (set_transient('pardot_dynamicContent_html_' . $dynamicContent_id, $dynamicContent_html, self::$cache_timeout)) {
				self::save_transient_key('pardot_dynamicContent_html_' . $dynamicContent_id);
			}

		} else {
			$dynamicContent_html = get_transient('pardot_dynamicContent_html_' . $dynamicContent_id);
		}

		if (!empty($args['height'])) {
			/**
			 * If 'inline' add to the <div> using style
			 */
			$dynamicContent_html = str_replace('height:auto', "height:{$args['height']}", $dynamicContent_html);
		}

		if (!empty($args['width'])) {
			$dynamicContent_html = str_replace('width:auto', "width:{$args['width']}", $dynamicContent_html);
		}

		if (!empty($args['class'])) {
			$dynamicContent_html = str_replace('pardotdc', "pardotdc {$args['class']}", $dynamicContent_html);
		}

		/**
		 * Filter the embed code for HTTPS
		 */
		$dynamicContent_html = self::convert_embed_code_https($dynamicContent_html);

		return $dynamicContent_html;
	}

	/**
	 * Get an instance of Pardot_API
	 *
	 * If API is not instantiated yet, passes $auth array which if empty will retrieve values from self::get_settings().
	 *
	 * @static
	 * @param array|bool $auth If false, don't initialize. If empty array, initialize w/defaults then $auth array get set.
	 * @return Pardot_API
	 *
	 * @since 1.0.0
	 */
	static function get_api($auth = [])
	{
		if (!is_a(self::$api, 'Pardot_API')) {
			self::$api = Pardot_Settings::get_api($auth);
		}

		return self::$api;
	}

	/**
	 * Returns the API key if authenticated.
	 *
	 * @param array|bool $auth If false, don't initialize. If empty array, initialize w/defaults then $auth array get set.
	 * @return string|bool The API key if authenticated, false if not authenticated or API error.
	 *
	 * @since 1.0.0
	 */
	static function get_api_key($auth = [])
	{
		return self::get_api($auth)->api_key;
	}

	/**
	 * Returns array of Campaigns as defined by the Pardot API.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool The array of Campaigns, or false if not authenticated or API error.
	 *
	 * @since 1.0.0
	 */
	static function get_campaigns(array $args = [])
	{
		return self::call_api('campaigns', $args);
	}

	/**
	 * Returns array of Forms as defined by the Pardot API.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool The array of Forms, or false if not authenticated or API error.
	 *
	 * @since 1.0.0
	 */
	static function get_forms(array $args = [])
	{
		return self::call_api('forms', $args);
	}

	/**
	 * Returns array of Dynamic Content as defined by the Pardot API.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool The array of Forms, or false if not authenticated or API error.
	 *
	 * @since 1.1.0
	 */
	static function get_dynamicContent(array $args = [])
	{
		return self::call_api('dynamicContent', $args);
	}

	/**
	 * Returns an Account as defined by the Pardot API.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool The Account, or false if not authenticated or API error.
	 *
	 * @since 1.0.0
	 */
	static function get_account(array $args = [])
	{
		return self::call_api('account', $args);
	}

	/**
	 * Function to streamline calling the Pardot API
	 * @static
	 * @param string $key Name of item to call, i.e. 'forms', 'campaigns', 'account', etc.
	 * @param array $args Combined authorization parameters and query arguments.
	 *
	 * @return mixed Value returned by Pardot API; an array of items like forms or campaigns but representing an account.
	 *
	 * @since 1.0.0
	 */
	static function call_api($key, $args)
	{
		$auth = Pardot_Settings::extract_auth_args($args);
		$value = self::get_api($auth)->{"get_{$key}"}($args);
		return $value;
	}

	/**
	 * Returns cached value based on key, if available.
	 *
	 * Checks the WordPress object cache and if not found then checks WordPress Transients.
	 * If not found in object cache but was found in transients, will set object cache.
	 * Object Cache group will be "pardot", transient key will be "pardot_{$key}"
	 *
	 * @static
	 * @param string $key Cache key
	 *
	 * @return mixed Cached value, or false is not available.
	 *
	 * @since 1.0.0
	 */
	static function get_cache($key)
	{
		$value = wp_cache_get($key, 'pardot');
		if (!$value) {
			$value = get_transient("pardot_{$key}");
			if (false !== $value) {
				self::set_cache($key, $value, false);
			}
		}
		return $value;
	}

	/**
	 * Sets WordPress object cache and WordPress Transient for given key and value.
	 *
	 * Object Cache group will be "pardot", transient key will be "pardot_{$key}"
	 * Bypasses setting transients if 3rd parameter is false.
	 *
	 * @static
	 * @param string $key Cache key
	 * @param mixed $value Value to cache for this cache key
	 * @param bool $set_transient Defaults to true, pass false to bypass setting transient.
	 *
	 * @since 1.0.0
	 */
	public static function set_cache($key, $value, $set_transient = true)
	{

		if (wp_cache_set($key, $value, 'pardot')) {
			self::save_cache_key($key);
		}

		if (!$set_transient) {
			return;
		}

		if (set_transient("pardot_{$key}", $value, self::$cache_timeout)) {
			self::save_transient_key("pardot_{$key}");
		}
	}

	/**
	 * Stores the name of a transient key so that we can reference it later in bulk-deletion.
	 *
	 * @return array
	 * @since 1.4.6
	 *
	 */
	public static function save_transient_key($key)
	{
		$saved_trans = (array)get_option(self::$saved_transient_keys);

		if (empty($saved_trans)) {
			$saved_trans = [$key];
		} else {
			$saved_trans[] = $key;
		}

		// Remove possible empty values.
		$saved_trans = array_filter($saved_trans);

		return update_option(self::$saved_transient_keys, array_values($saved_trans), false);
	}

	/**
	 * Stores the name of a wp_cache key so that we can reference it later in bulk-deletion.
	 *
	 * @return array
	 * @since 1.4.6
	 *
	 */
	public static function save_cache_key($key)
	{
		$saved_keys = (array)get_option(self::$saved_cache_keys);

		if (empty($saved_keys)) {
			$saved_keys = [$key];
		} else {
			$saved_keys[] = $key;
		}

		// Remove possible empty values.
		$saved_keys = array_filter($saved_keys);

		return update_option(self::$saved_cache_keys, array_values($saved_keys), false);
	}

	/**
	 * Get saved transient keys.
	 *
	 * @return array
	 * @since 1.4.6
	 *
	 */
	public static function get_saved_transient_keys()
	{
		$raw_keys = (array)get_option(self::$saved_transient_keys);

		if (empty($raw_keys)) {
			return [];
		}

		$raw_keys = array_unique($raw_keys);

		return array_values($raw_keys);
	}

	/**
	 * Get saved cache keys.
	 *
	 * @return array
	 * @since 1.4.6
	 *
	 */
	public static function get_saved_cache_keys()
	{
		$raw_keys = (array)get_option(self::$saved_cache_keys);

		if (empty($raw_keys)) {
			return [];
		}

		$raw_keys = array_unique($raw_keys);

		return array_values($raw_keys);
	}

	/**
	 * Totally clears out all Pardot transients and wp_cache items.
	 *
	 * @return bool True if the cache was cleared, false otherwise.
	 * @since 1.4.6
	 *
	 */
	public static function clear_cache()
	{
		self::legacy_clear_cache();

		$transient_keys = self::get_saved_transient_keys();
		$cache_keys = self::get_saved_cache_keys();

		foreach ($transient_keys as $i => $transient_key) {
			delete_transient(str_replace('_transient_', '', $transient_key));
		}

		foreach ($cache_keys as $i => $cache_key) {
			wp_cache_delete($cache_key, 'pardot');
		}

		$deleted_trans_keys = delete_option(self::$saved_transient_keys);
		$deleted_cache_keys = delete_option(self::$saved_cache_keys);

		return $deleted_trans_keys && $deleted_cache_keys;
	}

	/**
	 * This method helps clear transients that may have been set on a user's site before updating to
	 * version 1.4.6 of this plugin; and while this method will not work on sites with persistent
	 * object caching, on sites without it it may work better than clear_cache().
	 *
	 * @since 1.4.7
	 */
	public static function legacy_clear_cache()
	{
		global $wpdb;

		$collecttrans = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_pardot%';");

		foreach ($collecttrans as $collecttran) {
			delete_transient(str_replace('_transient_', '', $collecttran));
		}
	}
}

/**
 * Instantiate this class to ensure the action and shortcode hooks are hooked.
 * This instantiation can only be done once (see it's __construct() to understand why.)
 */
new Pardot_Plugin();
