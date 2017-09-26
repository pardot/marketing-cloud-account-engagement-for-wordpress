<?php
/**
 * Manage the Settings page and access to settings for the Pardot Plugin
 *
 * We are using private static variables in UPPERCASE to simulate private constants
 * because constants can't be private and we don't want to expose these as they are
 * only for convenience at this point and may need to change as the plugin evolves.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @since 1.0.0
 */
class Pardot_Settings {
	/**
	 * @var string Help page on Pardot's website discussing this plugin.
	 */
	const HELP_URL = 'http://www.pardot.com/help/faqs/add-ons/wordpress-plugin';

	/**
	 * @var string Admin page on Pardot's website linked to an authenticated user's account.
	 */
	const ACCOUNT_URL = 'https://pi.pardot.com/account';

	/**
	 * @var string Admin page on Pardot's website that allows authenticated users to add forms to a campaign
	 */
	const FORMS_URL = 'https://pi.pardot.com/form';

	/**
	 * @var string Admin page on Pardot's website that allows authenticated users to add forms to a campaign
	 */
	const DYNAMIC_CONTENT_URL = 'https://pi.pardot.com/content';

	/**
	 * @var string The root URL used to <iframe> a Pardot Forms. Used to add inline forms support.
	 */
	const POST_ROOT_URL = 'http://go.pardot.com';

	/**
	 * @var string Key for the Settings API option group AND for the settings stored in wp_options
	 * Used as a const, defined as a var so it can be private.
	 */
	private static $OPTION_GROUP = 'pardot_settings';

	/**
	 * @var string Key for the Options Page and for the Settings API page.
	 * Used as a const, defined as a var so it can be private.
	 */
	private static $PAGE = 'pardot';

	/**
	 * @var array Contain array of the fields for the Settings API.
	 * Used as a const, defined as a var so it can be private.
	 */
	private static $FIELDS = array();

/**
	 * @var Pardot_Plugin Capture $this so that other can remove_action() if needed.
	 */
	private static $self;

	/**
	 * @var null|string String containing the menu page's "hook_suffix" in case others need to access it.
	 * @see: http://codex.wordpress.org/Function_Reference/add_options_page
	 */
	private static $admin_page = null;

	/**
	 * @var Pardot_API Capture an $api instance since we will often use it several times in a page load.
	 */
	private static $api;

	/**
	 * Return the singleton instance of this class.
	 *
	 * To be use in case someone needs to remove one of the actions or shortcodes.
	 *
	 * @static
	 * @return Pardot_Settings A reference to the only instance of this object.
	 *
	 * @since 1.0.0
	 */
	static function self() {
		return self::$self;
	}
	/**
	 * Adds action and filter hooks when this singleton object is instantiated.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		/**
		 * This class is designed to be instansiated only once.
		 * We instantiate once at end of this class definition, throw an error if someone tries a second time.
		 */
		if ( isset( self::$self ) )
			wp_die( __( 'Pardot_Settings should not be created more than once.', 'pardot' ) );

		/**
		 * Set self::$self so that a user can remove access to one of these actions or shortcodes if they need to.
		 */
		self::$self = $this;

		/**
		 * Configure the settings page for the Pardot Plugin if we are currently on the settings page.
		 */
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		/**
		 * Configure the admin menu that points to this settings page for the Pardot Plugin.
		 */
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		/**
		 * Present an admin message telling the user to configure the plugin if there are not yet any credentials.
		 * This gets displayed at the top of an admin page.
		 */
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		/**
		 * Enqueue JS for Chosen on Widgets Screen
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'pardot_chosen_enqueue' ) );

	}
	/**
	 * Use to determine if we are in the Pardot Settings admin page.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	static function is_admin_page() {
		static $is_admin_page;
		if ( ! isset( $is_admin_page ) ) {
			global $pagenow;
			/**
			 * Are we on the plugin's settings page?
			 */
			$is_admin_page = 'options-general.php' == $pagenow && isset( $_GET['page'] ) && self::$PAGE == $_GET['page'];
			if ( ! $is_admin_page ) {
				/**
				 * Maybe we are trying to update the settings?
				 */
				$is_admin_page = 'options.php' == $pagenow &&
					isset( $_POST['action'] ) && 'update' == $_POST['action'] &&
					isset( $_POST['option_page'] ) && self::$OPTION_GROUP == $_POST['option_page'];
			}
		}
		return $is_admin_page;
	}
	/**
	 * Dynamic hooks for Settings page.
	 *
	 * Assigns the menu page's "hook_suffix" to self::$admin_page in case others need to access it.
	 * @see: http://codex.wordpress.org/Function_Reference/add_options_page
	 *
	 * @since 1.0.0
	 */
	function admin_menu() {
		$title = __( 'Pardot', 'pardot' );
		self::$admin_page = add_options_page( $title, $title, 'manage_options', self::$PAGE , array( $this, 'settings_page' ) );
	}

	/**
	 * Display admin notices if applicable.
	 *
	 * @since 1.0.0
	 */
	function admin_notices() {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'manage_options' ) ) {
			/**
			 * If the user can't install plugins or manage options, then of course we should bail!
			 * No message for you!
			 */
			return;
		}

		if ( self::is_admin_page() ) {
			/**
			 * No need to ask them to visit the settings page if they are already here
			 */
			return;
		}

		if ( self::is_authenticated() ) {
			/**
			 * No need to ask them to configure of they have already configured.
			 */
			return;
		}

		/**
		 * The Pardot plugin has been activated but it can't be authenticated yet because it has no credentials.
		 * Give the user a message so they know where to go to make it work.
		 */
		$msg = __( '<strong>The Pardot plugin is activated (yay!)</strong>, but it needs some quick %s to start working correctly.', 'pardot' );
		$msg = sprintf( $msg, self::get_admin_page_link( array( 'link_text' => __( 'configuration', 'pardot' ) ) ) );
		echo "<div class=\"updated\"><p>{$msg}</p></div>";

	}

	/**
	 * Insert CSS for Pardot Setting page into the seeting page's HTML <head>.
	 *
	 * Called with a priority of zero (0) this can be overridden with other CSS.
	 * We chose to incldue this in the head rather than in a CSS file to mininize
	 * the performance impact of the plugin; loading extra files via HTTP is one
	 * of the biggest performance drains there is.
	 *
	 * @since 1.0.0
	 */
	function admin_head() {
		$html =<<<HTML
<style type="text/css">
<!--
#settings_page_pardot h2.pardot-title{padding:40px 10px;}
#settings_page_pardot h2.pardot-title img{margin:-20px 10px 0 0;}
#settings_page_pardot .success{color:green;}
#settings_page_pardot .failure{color:red;}
#settings_page_pardot .instructions{font-style:italic;}
-->
</style>
<script>jQuery(document).ready(function(){jQuery("#campaign").chosen();});</script>
HTML;
		echo $html;
	}
	/**
	 * Check to see if the user has authenticated with the Pardot API.
	 *
	 * @static
	 * @param array $auth Authentication arguments.
	 * @return bool True if there is an API key.
	 *
	 * @since 1.0.0
	 */
	static function is_authenticated( $auth = array() ) {
		return self::get_api( $auth )->is_authenticated();
	}


	/**
	 * Configure the settings page for the Pardot Plugin if we are currently on the settings page.
	 *
	 * @since 1.0.0
	 */
	function admin_init() {

		/**
		 * If we are not on the admin page for this plugin, bail.
		 */
		if ( ! self::is_admin_page() )
			return;

		/**
		 * Add CSS to the header.  Called with a priority of zero (0) this can be
		 * overridden by other CSS.
		 */
		add_action( 'admin_head', array( $this, 'admin_head' ), 0 );

		/**
		 * Add Chosen to Campaign Selector
		 */
		wp_enqueue_script(  'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js', array( 'jquery' ), '1.0' );
		wp_enqueue_style( 'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css' );

		/**
		 * Define fields and their labels
		 */
		self::$FIELDS = array(
			'email' 	=> __( 'Email', 'pardot' ),
			'password'  => __( 'Password', 'pardot' ),
			'user_key' 	=> __( 'User Key', 'pardot' ),
			'campaign' 	=> __( 'Campaign (for Tracking Code)', 'pardot' ),
			'version' 	=> __( 'API Version', 'pardot' ),
			'https' 	=> __( 'Use HTTPS?', 'pardot' ),
			'submit'	=> '',
			'clearcache'=> '',
			'reset'	 	=> '',
			'api_key' 	=> '',
		);

		/**
		 * Register the settings page  required by WordPress Settings API
		 *
		 * Include the fields we'll use and the field sanitization callback function.
		 */
		register_setting( self::$OPTION_GROUP, self::$OPTION_GROUP, array( $this, 'sanitize_fields' ) );

		/**
		 * Add the settings sections required by WordPress Settings API.
		 */
		add_settings_section( self::$OPTION_GROUP, __( 'User Account Settings', 'pardot' ), array( $this, 'user_account_section' ),	self::$PAGE );

		/**
		 * Add the setting fields required by WordPress Settings API.
		 */
		foreach( self::$FIELDS as $name => $label ) {
			add_settings_field( $name, $label, array( $this, "{$name}_field" ), self::$PAGE, self::$OPTION_GROUP );
		}

	}

	/**
	 * Get an array with all the settings fields with all empty values.
	 *
	 * Get the setting field names and then set each array key to an
	 * empty string ('') so we will have initialized all potential elements.
	 *
	 * @return array
	 */
	static function get_empty_settings() {
		static $empty_settings;
		if ( ! isset( $empty_settings ) ) {
			/**
			 * First time in, create an array with all expected keys and with empty string values.
			 */
			$empty_settings = array_fill_keys( array_keys( self::$FIELDS ), '' );
		}
		return $empty_settings;
	}
	/**
	 * Sanitize Settings Account.
	 *
	 * @param array $dirty List of values that may be settings.
	 * @return array Sanitized array of all recognized settings.
	 *
	 * @since 1.0.0
	 */
	function sanitize_fields( $dirty ) {

		/**
		 * Nothing passed? Then nothing to sanitize.
		 */
		if ( empty( $dirty ) )  {
			return false;
		}

		/**
		 * Get the setting field names and add 'action' and 'status',
		 * then set each array key to an empty string ('') so we
		 * will have initialized all potential array elements.
		 */
		$clean = self::get_empty_settings();

		if ( isset( $_POST['reset'] ) ) {
			global $wpdb;
			$collecttrans = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_pardot%';" );

			foreach ( $collecttrans as $collecttran ) {
				delete_transient(str_replace('_transient_', '', $collecttran));
			}

			add_settings_error( self::$OPTION_GROUP, 'reset_settings', __( 'Settings have been reset!', 'pardot' ), 'updated' );
			return $clean;
		}

		if ( isset( $_POST['clear'] ) ) {
			global $wpdb;
			$collecttrans = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_pardot%';" );

			foreach ( $collecttrans as $collecttran ) {
				delete_transient(str_replace('_transient_', '', $collecttran));
			}
			add_settings_error( self::$OPTION_GROUP, 'reset_settings', __( 'The cache has been cleared!', 'pardot' ), 'updated' );
		}

		/**
		 * Sanitize each of the fields values
		 */
		foreach( $clean as $name => $value )
			if ( isset( $dirty[$name] ) && $name !== 'password' ) {
				$clean[$name] = trim( esc_attr( $dirty[$name] ) );
			} elseif ( isset( $dirty[$name] ) && $name === 'password' ) {
				$clean[$name] = trim( $dirty[$name] );
			}

		/**
		 * Call the Pardot API to attempt to authenticate
		 */
		if ( ! $this->authenticate( $clean ) ) {
			$msg = __( 'Cannot authenticate. Please check the fields below and click "Save Settings" again.', 'pardot' );
			add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );
		} else {
			$msg = __( 'Authentication successful. Settings saved.', 'pardot' );
			add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'updated' );
			/**
			 * Capture the api_key so we can save to the wp_options table.
			 */
			$clean['api_key'] = $this->get_api_key();
		}

		/**
		 * Add a filter to remove the values of submit, reset buttons and to obscure the password from prying eyes.
		 */
		add_filter( 'pre_update_option_pardot_settings', array( $this, 'pre_update_option_pardot_settings' ), 10, 2 );

		return $clean;
	}

	/**
	 * Get an instance of Pardot_API
	 *
	 * @static
	 * @param null|array|bool $auth If false, don't initialize. If empty array, initialize w/defaults then $auth array get set.
	 * @return Pardot_API
	 */
	static function get_api( $auth = array() ) {
		/**
		 * If self::$api not already a new Pardot_API
		 */
		if ( ! is_a( self::$api, 'Pardot_API' ) ) {
			/**
			 * Get one, either from arg passed in $auth, or by instantiating a new Pardot_API
			 */
			self::$api = isset( $auth['api'] ) && is_a( $auth['api'], 'Pardot_API' ) ? $auth['api'] : new Pardot_API();
		}

		/**
		 * If $auth not passed as false
		 */
		if ( is_array( $auth ) ) {
			/**
			 * If an empty array was passed,
			 */
			if ( 0 == count( $auth ) )
				/**
				 * Get the setings from wp_options
				 */
				$auth =  Pardot_Settings::get_settings();
			/**
			 * Extract just the auth values. $auth can be passed as part of an
			 * array of criteria, so make sure all the other values don't accidently confuse.
			 */
			$auth = self::extract_auth_args( $auth );

			/**
			 * If $auth array contains at least one value, set auth.
			 * If not empty, it's likely to have email and password and user_key, maybe api_key.
			 */
			if ( count( $auth ) )
				self::$api->set_auth( $auth );
		}
 		return self::$api;
	}

	/**
	 * Extract the auth args from the passed array.
	 *
	 * @param array $auth Values 'email', 'password', 'user_key' and 'api_key' supported.
	 * @return array Contains just 'email', 'password', 'user_key', 'api_key' if they existing as keys in $auth.
	 */
	static function extract_auth_args( $auth = array() ) {
		return array_intersect_key( $auth, array_flip( array( 'email', 'password', 'user_key', 'api_key' ) ) );
	}

	/**
	 * Get the api_key
	 *
	 * If the object already have an api_key then use it, otherwise get defaults.
	 *
	 * @return string API key returned by the Pardot API.
	 */
	function get_api_key() {
		return isset( self::$api->api_key ) ? self::$api->api_key : self::get_api()->api_key;
	}

	/**
	 * Call the Pardot API to authenticate based on credentials provided by the user.
	 *
	 * @param array $auth Values 'email', 'password', 'user_key' and 'api_key' supported.
	 * @return bool|string API Key if authenticated, false if not.
	 *
	 * @since 1.0.0
	 */
	function authenticate( $auth ) {
		return self::get_api( false )->authenticate( $auth );
	}

	/**
	 * Clean the Pardot settings before saving to wp_options
	 *
	 * @param array $new_options The settings as they user edited them.
	 * @param array $old_options The settings as they were previously in the database.
	 * @return mixed The settings after we removed 'submit', 'reset' and encoded 'password'
	 *
	 * @todo Do better than 'prying eyes' encryption.
	 *
	 * @since 1.0.0
	 */
	function pre_update_option_pardot_settings( $new_options, $old_options ) {

		/**
		 * We don't need to call this filter again on this page load.
		 */
		remove_filter( 'pre_update_option_pardot_settings', array( $this, 'pre_update_option_pardot_settings' ) );

		/**
		 * Don't store the values of the submit and reset buttons into wp_options.
		 */
		unset( $new_options['submit'] );
		unset( $new_options['reset'] );

		/**
		 * Trim whitespace
		 */
		$new_options['email'] = trim( $new_options['email'] );
		$new_options['password'] = trim( $new_options['password'] );
		$new_options['user_key'] = trim( $new_options['user_key'] );

		/**
		 * Add 'prying eyes' encryption for passsword.
		 * Base64 won't stop a hacker if they get access to the database but will keep
		 * endusers from being able to see a valid password.
		 */
		$new_options['password'] = self::pardot_encrypt( $new_options['password'], 'pardot_key' );

		return $new_options;
	}

	/**
	 * Displays Section text for the Settings API
	 *
	 * @since 1.0.0
	 */
	function user_account_section() {
		$msg = __( 'Use your Pardot login information to securely connect (you\'ll only need to do this once).', 'pardot' );
		echo "<span id=\"instructions\">{$msg}</span>";
	}

	/**
	 * Displays the Settings Page using the Settings API
	 *
	 * @since 1.0.0
	 */
	function settings_page() {
		/**
		 * Use the $admin_page for HTML id
		 */
		$admin_page = esc_attr( self::$admin_page );

		/**
		 * Grab the URL for the logo
		 */
		$logo_url = plugins_url( '/images/pardot-logo.png', dirname( __FILE__ ) );

		/**
		 * Grab alt text the logo
		 */
		$alt_text = __( 'Pardot, a Salesforce Company', 'pardot' );

		/**
		 * Grab title to be displayed behind the logo
		 */
		$title = esc_html__( 'Settings', 'pardot' );

		/**
		 * Grab the URL for the options page within the admin
		 */
		$options_url = admin_url( 'options.php' );

		/**
		 * Assemble the prefix HTML, all including the <form> tag.
		 */
		$html =<<<HTML
<div class="wrap" id="{$admin_page}">
	<h2 class="pardot-title"><img src="{$logo_url}" alt="{$alt_text}" width="181" height="71" class="alignleft" /></h2>
	<form action="{$options_url}" method="post">
HTML;
		echo $html;
		/**
		 * Call the Settings API for this option group
		 */
		settings_fields( self::$OPTION_GROUP );

		/**
		 * Display the fields for the only section we defined
		 */
		do_settings_sections( self::$PAGE );

		/**
		 * Out put the closing form tag.
		 */
		echo "\n</form>\n</div>";
	}

	/**
	 * Returns HTML input name for a raw field name
	 *
	 * The Settings API want HTML input names in the form "pardot_settings[email]" instead of just "email".
	 *
	 * @param string $field_name
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function _get_html_name( $field_name ) {
		return self::$OPTION_GROUP  . "[{$field_name}]";
	}

	/**
	 * Displays the Email field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function email_field() {
		$email = self::get_setting( 'email' );
		$html_name = $this->_get_html_name( 'email' );
$html =<<<HTML
<div id="email-wrap">
	<input type="text" size="30" id="email" name="{$html_name}" value="{$email}" />
</div>
HTML;
		echo $html;
	}

	/**
	 * Displays the Password field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function password_field() {
		$password = self::get_setting( 'password' );
		$html_name = $this->_get_html_name( 'password' );
$html =<<<HTML
<div id="password-wrap">
	<input type="password" size="30" id="password" name="{$html_name}" value="{$password}" />
</div>
HTML;
		echo $html;
	}
	/**
	 * Displays the User Key field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function user_key_field() {
		$user_key = self::get_setting( 'user_key' );
		$html_name = $this->_get_html_name( 'user_key' );
		$msg = __( 'Find your <em>"User Key"</em> in the <em>"My User Information"</em> section of your <a href="%s" target="_blank">Pardot Account Settings</a>.', 'pardot' );
		$msg = sprintf( $msg, self::ACCOUNT_URL );
$html =<<<HTML
<div id="user-key-wrap">
	<input type="text" size="30" id="user-key" name="{$html_name}" value="{$user_key}" />
	<p>{$msg}</p>
</div>
HTML;
		echo $html;
	}
	/**
	 * Displays the hidden API Key field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function api_key_field() {
		$api_key = self::get_setting( 'api_key' );
		$html_name = $this->_get_html_name( 'api_key' );
$html =<<<HTML
<input type="hidden" id="api-key" name="{$html_name}" value="{$api_key}" />
HTML;
		echo $html;
	}

	/**
	 * Displays the Campaign drop-down field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function campaign_field() {
		$campaigns = Pardot_Plugin::get_campaigns();
		if ( ! $campaigns ) {
			$msg = __( 'These will show up once you\'re connected.', 'pardot' );
			echo "<p>{$msg}</p>";
		} else {
			$label = __( 'Select Campaign', 'pardot' );
			$html_name = $this->_get_html_name( 'campaign' );
			$html = array();
			$html[] = <<<HTML
<div id="campaign-wrap">
<select id="campaign" name="{$html_name}">
<option selected="selected" value="">{$label}</option>
HTML;
			$selected_value = self::get_setting( 'campaign' );
			foreach ( $campaigns as $campaign => $data ) {
				$value = esc_attr( $campaign );
				$selected = selected( $selected_value, $value, false );
				if ( isset($data->name) ) {
					$campaign_name = esc_html( $data->name );
				}
				$html[] = "<option {$selected} value=\"{$value}\">{$campaign_name}</option>";
			}
			$html[] = '</select></div>';
			echo implode( '', $html );
		}
	}

	/**
	 * Displays the API Version drop-down field for the Settings API
	 *
	 * @since 1.4.1
	 */
	function version_field() {
		$version = self::get_setting( 'version' );
		$html_name = $this->_get_html_name( 'version' );
		$html = '<div id="version-wrap"><select id="version" name="' . $html_name . '">';
		$html .= '<option';
		if ( $version === '3' ) {
			$html .= ' selected="selected"';
		}
		$html .= ' value="3">3</option>';
		$html .= '<option';
		if ( $version === '4' ) {
			$html .= ' selected="selected"';
		}
		$html .= ' value="4">4</option>';
		$html .= '</select></div>';
		echo $html;
	}

	/**
	 * Displays the HTTPS-only checkbox for the Settings API
	 *
	 * @since 1.4
	 */
	function https_field() {
		$https = self::get_setting( 'https' );
		if ( $https ) {
			$https = "checked";
		}
		$html_name = $this->_get_html_name( 'https' );
		$html =<<<HTML
<input type="checkbox" id="https" name="{$html_name}" {$https} />
HTML;
		echo $html;
	}

	/**
	 * Displays the Submit button for the Settings API
	 *
	 * @since 1.0.0
	 */
	function submit_field() {
		$value = __( 'Save Settings', 'pardot' );
		$valuecache = __( 'Clear Cache', 'pardot' );
		$valuereset = __( 'Reset All Settings', 'pardot' );
		$msgreset = __( 'This will remove all your Pardot account information from the database. Click OK to proceed', 'pardot' );
		$html =<<<HTML
<input type="submit" class="button-primary" name="save" value="{$value}" /> <input type="submit" class="button-secondary" name="clear" value="{$valuecache}" style="margin-left: 50px;" /> <input onclick="return confirm('{$msgreset}.');" type="submit" class="button-secondary" name="reset" value="{$valuereset}" />
HTML;
		echo $html;
	}

	/**
	 * Displays the Reset button for the Settings API
	 *
	 * @since 1.0.0
	 */
	function reset_field() {
		$value = __( 'Reset All Settings', 'pardot' );
		$msg = __( 'This will remove all your Pardot account information from the database. Click OK to proceed', 'pardot' );
		$html =<<<HTML
<input onclick="return confirm('{$msg}.');" type="submit" class="button-secondary" name="reset" value="{$value}" />
HTML;
		//echo $html;
	}

	/**
	 * Displays the Clear Cache button for the Settings API
	 *
	 * @since 1.1.0
	 */
	function clearcache_field() {
		$value = __( 'Clear Cache', 'pardot' );
		$valuetwo = __( 'Reset All Settings', 'pardot' );
		$msg = __( 'This will remove all your Pardot account information from the database. Click OK to proceed', 'pardot' );
		$html =<<<HTML
<input type="submit" class="button-secondary" name="clear" value="{$value}" /> <input onclick="return confirm('{$msg}.');" type="submit" class="button-secondary" name="reset" value="{$valuetwo}" />
HTML;
		//echo $html;
	}

	/**
	 * Encrypts with a bit more complexity
	 *
	 * @since 1.1.2
	 */
	public static function pardot_encrypt($input_string, $key='pardot_key'){
		if ( function_exists('mcrypt_encrypt') ) {
			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
			$h_key = hash('sha256', $key, TRUE);
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
		} else {
			return base64_encode($input_string);
		}
	}

	/**
	 * Decrypts with a bit more complexity
	 *
	 * @since 1.1.2
	 */
	public static function pardot_decrypt($encrypted_input_string, $key='pardot_key'){
		if ( function_exists('mcrypt_encrypt') ) {
		    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		    $h_key = hash('sha256', $key, TRUE);
		    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
	    } else {
		    return base64_decode($encrypted_input_string);
	    }
	}


	/**
	 * Enqueue Chosen on Widgets Screen
	 *
	 * @public
	 *
	 * @since 1.3.8
	 */
	public function pardot_chosen_enqueue($hook) {
		if( 'widgets.php' != $hook )
			return;
		wp_enqueue_script(  'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js', array( 'jquery' ), '1.0' );
		wp_enqueue_style( 'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css' );
		add_action( 'in_admin_footer', array( $this, 'pardot_chosen_init' ) );
	}

	/**
	 * Initiate Chosen on Widgets Screen (on load and after save)
	 *
	 * @public
	 *
	 * @since 1.3.8
	 */
	public function pardot_chosen_init() {
		echo '
	<script>
		jQuery(document).ready(function(){
			jQuery(".widgets-holder-wrap:not(#available-widgets) .js-chosen").chosen({width: "100%"});
		});
		jQuery(document).ajaxSuccess(function(e, xhr, settings) {
			var widget_id_base = "pardot";

			if(settings.data.search("action=save-widget") != -1 && settings.data.search("id_base=" + widget_id_base) != -1) {
				jQuery(".widgets-holder-wrap:not(#available-widgets) .js-chosen").chosen({width: "100%"});
			}
		});
	</script>';
	}

	/**
	 * Return list of Pardot plugin settings
	 *
	 * @static
	 * @return array List of settings
	 *
	 * @todo Improve the 'prying eyes' security over base64 encoding.
	 *
	 * @since 1.0.0
	 */
	static function get_settings() {
		/**
		 * Grab the (expected) array of settings
		 */
		$settings = get_option( self::$OPTION_GROUP );

		if ( empty( $settings ) ) {

			/**
			 * If it's empty, make sure it's an empty array.
			 */
			$settings = array();

		} elseif ( isset( $settings['password'] ) && !empty( $settings['password'] ) ) {

			$decrypted_pass = self::pardot_decrypt( $settings['password'], 'pardot_key' );

			if ( $decrypted_pass !== $settings['password'] && ctype_print($decrypted_pass) ) {
				$settings['password'] = $decrypted_pass;
			}

        }

		/**
		 * Merge in the empty settings to make sure all expected setting keys are in returned array.
		 */
		return array_merge( self::get_empty_settings(), $settings );
	}

	/**
	 * Return an individual Pardot plugin settings
	 *
	 * @static
	 * @param string $key Identifies a setting
	 * @return mixed|null Value of the setting
	 *
	 * @since 1.0.0
	 */
	static function get_setting( $key ) {
		$settings = self::get_settings();
		if ( isset( $settings[$key] ) ) {
			return $settings[$key];
		}
		return null;
	}

	/**
	 * Save a Pardot plugin settings
	 *
	 * @static
	 * @param string $key Identifies a setting
	 * @param mixed $value Value of the setting to save
	 *
	 * @since 1.0.0
	 */
	static function set_setting( $key, $value ) {
		/**
		 * Gran the array of settings
		 */
		$settings = self::get_settings();

		/**
		 * Assign the setting for this key its value
		 */
		$settings[$key] = $value;

		/**
		 * Now update all the settings as a serialized array
		 */
		update_option( self::$OPTION_GROUP, (array)$settings );
	}

	/**
	 * Clear an individual Pardot plugin settings
	 *
	 * @static
	 * @param string $key
	 *
	 * @since 1.0.0
	 */
	static function clear_setting( $key ) {
		self::set_setting( $key, '' );
	}

	/**
	 * Get the URL for the Pardot plugin settings page in the admin.
	 *
	 * @static
	 * @return string URL for the settings page.
	 *
	 * @since 1.0.0
	 */
	static function get_admin_page_url() {
		return admin_url( 'options-general.php?page=' . self::$PAGE );
	}
	/**
	 * Simple function to return an HTML link to the admin URL for Settings
	 *
	 * @static
	 * @param array $args Options for changing the link: onclick, target, and/or link_text.
	 * @return string HTML <a> link to the admin page
	 *
	 * @since 1.0.0
	 */
	static function get_admin_page_link( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'onclick' => false,
			'target' => false,
			'link_text' => false,
		));
		$onclick = $args['onclick'] ? " onclick=\"{$args['onclick']}\"" : '';
		$target = $args['target'] ? " target=\"{$args['target']}\"" : '';
		$link_text = $args['link_text'] ?  $args['link_text'] :  __( 'Settings', 'pardot' );
		return "<a{$target}{$onclick} href=\"" . self::get_admin_page_url() . "\">{$link_text}</a>";
	}

}

/**
 * Instantiate this class to ensure the action and shortcode hooks are hooked.
 * This instantiation can only be done once (see it's __construct() to understand why.)
 */
new Pardot_Settings();
