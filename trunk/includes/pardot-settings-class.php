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
	 * @var string Admin page on Pardot's website linked to an authenticated user's account.
	 */
	const ACCOUNT_URL = 'https://pi.pardot.com/account/user';

    /**
     * @var string Link to App Manager on Ligntning where users can create their connected app
     */
    const APP_MANAGER_URL = 'https://login.salesforce.com/lightning/setup/NavigationMenus/home';

    /**
     * @var string Link to the settings page on Lightning where users can find their business unit id
     */
    const BUSINESS_UNIT_ID_URL = 'https://login.salesforce.com/lightning/setup/PardotAccountSetup/home';

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

	private static $CODE_VERIFIER = 'pardot-code-verifier';

	/**
	 * @var string Key for the Options Page and for the Settings API page.
	 * Used as a const, defined as a var so it can be private.
	 */
	private static $PAGE = 'pardot';

	/**
	 * @var array Contain array of the fields for the Settings API.
	 * Used as a const, defined as a var so it can be private.
	 */
	private static $FIELDS = array(
        'auth_status'       => '',
        'auth_type'         => '',
        'email'             => '',
        'password'          => '',
        'user_key'          => '',
        'client_id'         => '',
        'client_secret'     => '',
        'business_unit_id'  => '',
        'campaign'          => '',
        'version'           => '',
        'https'             => '',
        'submit'            => '',
    );

	/**
	 * @var Pardot_Plugin Capture $this so that other can remove_action() if needed.
	 */
	private static $self;

	/**
	 * @var null|string String containing the menu page's "hook_suffix" in case others need to access it.
	 * @see http://codex.wordpress.org/Function_Reference/add_options_page
	 */
	private static $admin_page = null;

	/**
	 * @var Pardot_API Capture an $api instance since we will often use it several times in a page load.
	 */
	private static $api;

	/**
	 * @var boolean A flag to help combat a bug where settings-page admin notices show twice per submission.
	 * @see https://core.trac.wordpress.org/ticket/21989
	 */
	private static $showed_auth_notice = false;

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
		 * This class is designed to be instantiated only once.
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


        /**
         * Because a crypto key is *REQUIRED* we're going to check to see if a pardot crypto key exists
         * in the settings table, and if it doesn't we're going to create one for use. We only ever want to do this
         * one time so it's not going to be continuing logic and it won't be stored with other pardot settings
         */
        if (get_option('pardot_crypto_key', NULL) === NULL) {
            $crypto = new PardotCrypto();
            $crypto->set_key();
        }


        /**
         * And we're going to check here to see if the setting for pardot already exists in the database for their password...
         * if it does then we need to determine if the password setting is correctly encrypted or needs to be re-encrypted
         */
        $optstring = get_option('pardot_settings', NULL);
        if (isset($optstring['password']) && $optstring['password'] != NULL)
        {
            if ((substr($optstring['password'], 0, 6) !== "NACL::") and
                (substr($optstring['password'], 0, 6) !== "OGCM::") and
                (substr($optstring['password'], 0, 6) !== "OETM::"))
            {
                self::upgrade_old_password($optstring['password']);
            }
        }
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

		if (self::is_authenticated() && self::get_setting('auth_type') == 'pardot') {
            $msg = __( 'Pardot authentication is being discontinued in February 2021.  To update your authentication to Salesforce SSO, go to your %s.', 'pardot' );
            $msg = sprintf( $msg, self::get_admin_page_link( array( 'link_text' => __( 'Pardot plugin settings', 'pardot' ) ) ) );
            echo "<div class=\"updated\" style=\"border-left-color: #ffb900\"><p>{$msg}</p></div>";
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
        $code_challenge = self::base64url_encode(pack('H*', hash('sha256', get_option(self::$CODE_VERIFIER))));

        $html =<<<HTML
<style type="text/css">
<!--
#settings_page_pardot h2.pardot-title{padding:40px 10px; margin-bottom: 10px}
#settings_page_pardot h2.pardot-title img{margin:-20px 10px 0 0;}
#settings_page_pardot .success{color:green;}
#settings_page_pardot .failure{color:red;}
#settings_page_pardot .instructions{font-style:italic;}
#settings_page_pardot .hidden{display: none;}
#settings_page_pardot #sso-sign-in{width: 200px; margin-left: 20px}
-->
</style>
<script>
jQuery(document).ready(function(){jQuery("#campaign").chosen();});

jQuery(document).ready(function($){
    $('#auth-type').change(function() {      
        if (this.value === 'pardot') {        
            $('#email-wrap').parents().eq(1).show();
            $('#password-wrap').parents().eq(1).show();  
            $('#user-key-wrap').parents().eq(1).show();
            $('#client-id-wrap').parents().eq(1).hide();
            $('#client-secret-wrap').parents().eq(1).hide(); 
            $('#business-unit-id-wrap').parents().eq(1).hide();
            $('#sso-sign-in').hide();
            
        } else if (this.value === 'sso') {        
            $('#email-wrap').parents().eq(1).hide();    
            $('#password-wrap').parents().eq(1).hide();
            $('#user-key-wrap').parents().eq(1).hide();
            $('#client-id-wrap').parents().eq(1).show();
            $('#client-secret-wrap').parents().eq(1).show(); 
            $('#business-unit-id-wrap').parents().eq(1).show();
            $('#sso-sign-in').show();
        }
    });

});

// Source: https://stackoverflow.com/a/27747377
// dec2hex :: Integer -> String
// i.e. 0-255 -> '00'-'ff'
function dec2hex(dec) {
  return dec < 10
    ? '0' + String(dec)
    : dec.toString(16);
}

// generateId :: Integer -> String
function generateNonce(len) {
  let arr = new Uint8Array((len || 40) / 2);
  window.crypto.getRandomValues(arr);
  return Array.from(arr, dec2hex).join('');
}

let nonce = false;

function clickSubmit() {
    nonce = generateNonce();
    
    let authSelect = document.getElementById("auth-type");
    let authValue = authSelect.options[authSelect.selectedIndex].value;
    let client_id = document.getElementById("client-id").value;
    let sign_in_sso = document.getElementById("sso-sign-in");
    if (authValue == 'sso') {
        if (client_id) {
            let url = "https://login.salesforce.com/services/oauth2/authorize?client_id=" + client_id + "&redirect_uri=" +
                window.location.href.split('?')[0] + '?page=pardot' + "&response_type=code" + "&display=popup" + "&scope=refresh_token%20pardot_api" + 
                "&state=" + nonce + "&code_challenge=" + '{$code_challenge}';
            window.open(url, "Sign In with Salesforce", "height=800, width=400, left=" + sign_in_sso.getBoundingClientRect().right);
        }
        else {
            alert("Please type in a valid Consumer Key.");
        }
    }
}

window.loginCallback = function(urlString) {
    let url = new URL(urlString);
    let returnedState = url.searchParams.get('state');
    if (returnedState === nonce) {
        url.searchParams.append('status', 'success');
        window.location.replace(url);
    }
    else {
        alert("Invalid state parameter returned.");
    }
};

const urlParams = new URLSearchParams(window.location.search);
const codeParam = urlParams.get('code');
const statusParam = urlParams.get('status');

if (codeParam && codeParam.length > 1 && !statusParam) {
    
    window.opener.loginCallback(window.location.href);
    window.close();
}

</script>

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
     * Encodes plain text into base64 for URLs
     * @param $plainText
     * @return string
     *
     * @since 1.5.0
     */
    function base64url_encode($plainText)
    {
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        $base64url = strtr($base64, '+/', '-_');
        return ($base64url);
    }

    /**
     * Saves a new code_verifier to WP Options
     *
     * @since 1.5.0
     */
    function create_code_verifier() {
        $random = wp_create_nonce();
        $verifier = self::base64url_encode(pack('H*', $random));
        update_option(self::$CODE_VERIFIER, $verifier);
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
		if ( ! self::is_admin_page() ) {
            return;
        }

		/**
         * Checks if authorization token request failed
         */
        if ( $error_description = isset($_GET['error_description']) ) {
            add_settings_error(self::$OPTION_GROUP, 'update_settings', 'Failed to authenticate.  Please check your credentials again. (' . $error_description . ')', 'error');
            settings_errors('update_settings');
        }

        /**
         * Does not create new code verifier when 'code' query string present
         * First needs to verify the code challenge passed during the authorization code process
         */
		if ( ! isset($_GET['code']) ) {
            $this->create_code_verifier();
        }
		
		

		if (isset($_GET['code']) && isset($_GET['status']) && $_GET['status'] == 'success' && ! self::is_authenticated()) {
            $url = 'https://login.salesforce.com/services/oauth2/token';
            $body = array(
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'client_id' => self::get_setting('client_id'),
                'client_secret' => self::decrypt_or_original(self::get_setting('client_secret')),
                'redirect_uri' => ( function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type() ) ? admin_url( 'options-general.php?page=pardot') : admin_url( 'options-general.php?page=pardot', 'https' ),
                'code_verifier' => get_option(self::$CODE_VERIFIER),
            );

            $args = array(
                'body'        => $body,
                'timeout'     => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array("Content-type: application/json"),
                'cookies'     => array(),
            );

            $response = wp_remote_post( $url, $args );

            $response = json_decode(wp_remote_retrieve_body($response));

            if (isset($response->{'error'})) {
                add_settings_error(self::$OPTION_GROUP, 'update_settings', 'Failed to authenticate!  Please check your credentials again. (' . $response->{'error'} . ':' . $response->{'error_description'} . ')', 'error');
                settings_errors('update_settings');
            }

            if ( isset($response->{'access_token'}) ) {
                self::set_setting('api_key', $response->{'access_token'});
            }

            if ( isset($response->{'refresh_token'}) ) {
                self::set_setting('refresh_token', $response->{'refresh_token'});
            }

            // Error message to remind user that they should have enabled refresh_token scope for auto-reauth
            else if ( $body['grant_type'] != 'refresh_token' && !isset($response->{'error'}) && !isset($response->{'refresh_token'}) ) {
                add_settings_error( self::$OPTION_GROUP, 'update_settings', 'Make sure you enable the refresh_token scope if you want to be want to be reauthenticated automatically.', 'error' );
                settings_errors('update_settings');
            }

            // After using the code_verifier is used, delete it
            delete_option(self::$CODE_VERIFIER);
        }

		/**
		 * Add CSS to the header.  Called with a priority of zero (0) this can be
		 * overridden by other CSS.
		 */
		add_action( 'admin_head', array( $this, 'admin_head' ), 0 );

        /**
         * If the user is already authenticated with an an email (a.k.a. Pardot auth), set auth_type to pardot
         * If user doesn't have any credentials save, default auth_type to sso
         * This ensures seamless compatibility with upgrading users while encouraging new users to use sso
         */
		if (self::get_setting( 'auth_type' ) != 'sso' && self::get_setting( 'auth_type' ) != 'pardot') {
		    if (self::get_setting('email')) {
                self::set_setting('auth_type', 'pardot');
            }
		    else {
                self::set_setting('auth_type', 'sso');
            }
        }

        if ( self::is_authenticated() ) {
            self::get_api(array())->get_account();

            $api_error = $this->retrieve_api_error();

            if ( ! empty( $api_error ) ) {
                $msg = sprintf( esc_html_x( 'Error: %s', 'pardot' ), "<i>$api_error</i>" );
                add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );
            }
        }

		/**
		 * Add Chosen to Campaign Selector
		 */
		wp_enqueue_script(  'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js', array( 'jquery' ), '1.0' );
		wp_enqueue_style( 'chosen', '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css' );

		/**
		 * Define fields and their labels
		 */
		self::$FIELDS = array(
		    'auth_status'=> [__( 'Authentication Status', 'pardot' ), ''],
            'auth_type' => [__( 'Authentication Type', 'pardot' ), ''],
			'email'     => [__( 'Email', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'sso' ? array( 'class' => 'hidden' ) : array() )],
			'password'  => [__( 'Password', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'sso' ? array( 'class' => 'hidden' ) : array() )],
			'user_key'  => [__( 'User Key', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'sso' ? array( 'class' => 'hidden' ) : array() )],
            'client_id'  => [__( 'Consumer Key', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'pardot' ? array( 'class' => 'hidden' ) : array() )],
            'client_secret'  => [__( 'Consumer Secret', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'pardot' ? array( 'class' => 'hidden' ) : array() )],
            'business_unit_id'  => [__( 'Business Unit ID', 'pardot' ), ( self::get_setting( 'auth_type' ) === 'pardot' ? array( 'class' => 'hidden' ) : array() )],
			'campaign'  => [__( 'Campaign (for Tracking Code)', 'pardot' ), ''],
			'version'   => [__( 'API Version', 'pardot' ), array( 'class' => 'hidden' )],
			'https'     => [__( 'Use HTTPS?', 'pardot' ), ''],
			'submit'    => '',
		);

		/**
		 * Register the settings page required by WordPress Settings API
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
		foreach( self::$FIELDS as $name => $arr ) {
            $title = null;
            if (isset($arr[0])) {
                $title = $arr[0];
            }
		    $class = null;
		    if (isset($arr[1])) {
		        $class = $arr[1];
            }

			add_settings_field( $name, $title, array( $this, "{$name}_field" ), self::$PAGE, self::$OPTION_GROUP , $class);
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
			$empty_settings['api_key'] = '';
			$empty_settings['refresh_token'] = '';
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
            self::reset_settings();
			wp_safe_redirect( admin_url( 'options-general.php?page=pardot' )  );
			exit;
		}

		if ( isset( $_POST['clear'] ) ) {

			Pardot_Plugin::clear_cache();

			add_settings_error( self::$OPTION_GROUP, 'reset_settings', __( 'The cache has been cleared!', 'pardot' ), 'updated' );
		}

        /**
         * Use existing password if the setting has not been changed
         */
        if (empty($dirty['password'])) {
            $dirty['password'] = self::get_setting( 'password' );
        }

        /**
         * Use existing client_secret if the setting has not been changed
         */
        if (empty($dirty['client_secret'])) {
            $dirty['client_secret'] = self::get_setting( 'client_secret' );
        }

        /**
         * Use existing api_key if the setting has not been changed
         */
        if (empty($dirty['api_key'])) {
            $dirty['api_key'] = self::get_setting( 'api_key' );
        }

        /**
         * Use existing refresh_token if the setting has not been changed
         */
        if (empty($dirty['refresh_token'])) {
            $dirty['refresh_token'] = self::get_setting( 'refresh_token' );
        }

        /**
		 * Sanitize each of the fields values
		 */
		foreach( $clean as $name => $value ) {
			if ( isset( $dirty[$name] ) && $name !== 'password' ) {
				$clean[$name] = trim( esc_attr( $dirty[$name] ) );
			} elseif ( isset( $dirty[$name] ) && $name === 'password' ) {
				$clean[$name] = trim( $dirty[$name] );
			}
		}

		$clean['password'] = self::decrypt_or_original( $clean['password'] );

		/**
		 * Call the Pardot API to attempt to authenticate
		 */
		if ( $clean['auth_type'] == 'pardot' && ! $this->authenticate( $clean ) ) {

			if ( ! self::$showed_auth_notice ) {
				$msg = __( 'Cannot authenticate. Please check the fields below and click "Save Settings" again.', 'pardot' );

				$api_error = $this->retrieve_api_error();

				if ( ! empty( $api_error ) ) {
					$msg = sprintf( esc_html_x( 'Error: %s', 'pardot' ), "<i>$api_error</i>" ) . '<br><br>' . $msg;
				}

				add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );

				self::$showed_auth_notice = true;
			}
		} elseif ($clean['auth_type'] == 'sso') {
		    if (!$clean['client_id']) {
                $msg = __( 'Please check the Consumer Key field below and click "Save Settings" again.', 'pardot' );
                add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );
            }
		    else if (!$clean['client_secret']) {
                $msg = __( 'Please check the Consumer Secret field below and click "Save Settings" again.', 'pardot' );
                add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );
            }
		    else if (!$clean['business_unit_id']) {
                $msg = __( 'Please check the Business Unit ID field below and click "Save Settings" again.', 'pardot' );
                add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'error' );
            }

		    self::get_api( $clean );

        } else {

			if ( ! self::$showed_auth_notice ) {
				$msg = __( 'Authentication successful. Settings saved.', 'pardot' );
				add_settings_error( self::$OPTION_GROUP, 'update_settings', $msg, 'updated' );

				/**
				 * Capture the api_key so we can save to the wp_options table.
				 */
				$clean['api_key'] = $this->get_api_key();

				self::$showed_auth_notice = true;
			}
		}

		/**
		 * Add a filter to encrypt credentials
		 */
		add_filter( 'pre_update_option_pardot_settings', array( $this, 'pre_update_option_pardot_settings' ), 10, 2 );

		return $clean;
	}

	/**
	 * Returns the error message provided in the last API response or null if
	 * an error was not supplied.
	 *
	 * @since 1.4.8
	 *
	 * @return string|null
	 */
	private function retrieve_api_error() {
		$api_error = null;

		if ( ! empty( self::$api->error ) ) {
			// Get the raw error text from the (SimpleXMLElement) error object
			$api_error = esc_html( trim( (string) self::$api->error ) );

			// Convert any URLs contained within into actual links
			$api_error = make_clickable( $api_error );
		}

		return empty( $api_error ) ? null : $api_error;
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
		if ( ! self::$api instanceof Pardot_API ) {
			/**
			 * Get one, either from arg passed in $auth, or by instantiating a new Pardot_API
			 */
			self::$api = isset( $auth['api'] ) && $auth['api'] instanceof Pardot_API ? $auth['api'] : new Pardot_API();
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
				$auth = Pardot_Settings::get_settings();
			/**
			 * Extract just the auth values. $auth can be passed as part of an
			 * array of criteria, so make sure all the other values don't accidently confuse.
			 */
			$auth = self::extract_auth_args( $auth );

			/**
			 * If $auth array contains at least one value, set auth.
			 * If not empty, it likely has credentials, maybe api_key
			 */
			if ( count( $auth ) )
				self::$api->set_auth( $auth );
		}
 		return self::$api;
	}

	/**
	 * Extract the auth args from the passed array.
	 *
	 * @param array $auth Values 'auth_type', 'email', 'password', 'user_key', 'client_id', 'client_secret', 'business_unit_id', 'refresh_token', and 'api_key' supported.
	 * @return array Contains 'auth_type', 'email', 'password', 'user_key', 'client_id', 'client_secret', 'business_unit_id','refresh_token' and 'api_key' if they existing as keys in $auth.
	 */
	static function extract_auth_args( $auth = array() ) {
		return array_intersect_key( $auth, array_flip( array( 'auth_type', 'email', 'password', 'user_key', 'api_key', 'client_id', 'client_secret', 'business_unit_id', 'refresh_token') ) );
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
	 * @param array $auth Values 'auth_type', 'email', 'password', 'user_key', 'client_id', 'client_secret', 'business_unit_id', 'refresh_token', and 'api_key' supported.
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
	 * @since 1.0.0
	 */
	function pre_update_option_pardot_settings( $new_options, $old_options ) {

		/**
		 * We don't need to call this filter again on this page load.
		 */
		remove_filter( 'pre_update_option_pardot_settings', array( $this, 'pre_update_option_pardot_settings' ) );

		/**
		 * Trim whitespace
		 */
		$new_options['email']    = trim( $new_options['email'] );
		$new_options['password'] = trim( $new_options['password'] );
		$new_options['user_key'] = trim( $new_options['user_key'] );
        $new_options['client_id'] = trim( $new_options['client_id'] );
        $new_options['client_secret'] = trim( $new_options['client_secret'] );
        $new_options['business_unit_id'] = trim( $new_options['business_unit_id'] );

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
     * Displays authentication status
     *
     * @since 1.5.0
     */
	function auth_status_field() {
	    if (self::is_authenticated() && self::get_api(array())->get_account()) {
	        if ( self::get_setting('auth_type') == 'sso' ) {
                $message = __('Authenticated with Salesforce SSO', 'pardot');
                $buttonValue = __( 'Re-authenticate with Salesforce', 'pardot' );
                $html =<<<HTML
<div id="auth-status-wrap" class="success">
{$message}
<input id="sso-sign-in" class="button-primary" name="sso-sign-in" style="width: 217px" value="{$buttonValue}" onclick="clickSubmit()"/>
</div>
HTML;
            }
	        else {
                $message = __('Authenticated with Pardot', 'pardot');
                $html =<<<HTML
<div id="auth-status-wrap" class="success">
{$message}
</div>
HTML;
            }
        }
	    else {
            $message = __('Not Authenticated', 'pardot');
            if ( self::get_setting('auth_type') == 'sso' && self::get_setting('client_id')
                    && self::get_setting('client_secret') && self::get_setting('business_unit_id' )) {
                $buttonValue = __( 'Authenticate with Salesforce', 'pardot' );
                $html =<<<HTML
<div id="auth-status-wrap" class="failure">
{$message}
<input id="sso-sign-in" class="button-primary" name="sso-sign-in" value="{$buttonValue}" onclick="clickSubmit()"/>
</div>
HTML;
            }
            else {
                $html =<<<HTML
<div id="auth-status-wrap" class="failure">{$message}</div>
HTML;
            }
        }
        echo $html;
    }

    /**
     * Displays the API Type (Pardot or Salesforce) drop-down field for the Settings API
     *
     * @since 1.5.0
     */
    function auth_type_field() {
        $auth_type = self::get_setting( 'auth_type' );
        $html_name = $this->_get_html_name( 'auth_type' );
        $html = '<div id="auth-type-wrap"><select id="auth-type" name="' . $html_name . '">';
        $html .= '<option';
        if ( $auth_type === 'pardot' ) {
            $html .= ' selected="selected"';
        }
        $html .= ' value="pardot">Pardot</option>';
        $html .= '<option';
        if ( $auth_type === 'sso' ) {
            $html .= ' selected="selected"';
        }
        $html .= ' value="sso">Salesforce SSO</option>';
        $html .= '</select></div>';
        echo $html;
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
     * Displays the Consumer Key field for the Settings API
     *
     * @since 1.5.0
     */
    function client_id_field() {
        $client_id = self::get_setting( 'client_id' );
        $html_name = $this->_get_html_name( 'client_id' );
        $msg = __( 'Consumer Key and Consumer Secret are obtained after creating a connected app in <a href="%s" target="_blank">App Manager</a>.', 'pardot' );
        $msg = sprintf( $msg, self::APP_MANAGER_URL );

        $html =<<<HTML
<div id="client-id-wrap">
	<input type="text" size="30" id="client-id" name="{$html_name}" value="{$client_id}" />
	<p>{$msg}</p>
</div>
HTML;
        echo $html;
    }

    /**
     * Displays the Consumer Secret field for the Settings API
     *
     * @since 1.5.0
     */
    function client_secret_field() {
        /**
         * Grab the length of the real password and turn it into a placeholder string that looks like it is filled
         * in whenever a password is set.
         */
        $secretLength = strlen(self::get_setting( 'client_secret' ));

        /**
         * Set password length to some arbitrary amount iff there is a set password already so that it shows that the
         * password is set already without disclosing the exact number of characters in the password
         */
        $secretLength = $secretLength > 0 ? 64 : 0;
        $secretPlaceholder = str_repeat("&#8226;", $secretLength);

        $html_name = $this->_get_html_name( 'client_secret' );
        $html =<<<HTML
<div id="client-secret-wrap">
	<input type="password" size="30" id="client-secret" name="{$html_name}" placeholder="{$secretPlaceholder}" />
</div>
HTML;
        echo $html;
    }

    /**
     * Displays the Business Unit ID Secret field for the Settings API
     *
     * @since 1.5.0
     */
    function business_unit_id_field() {
        $business_unit_id = self::get_setting( 'business_unit_id' );
        $html_name = $this->_get_html_name( 'business_unit_id' );
        $msg = __( 'Find your Pardot Business Unit ID in <a href="%s" target="_blank">Pardot Account Setup</a>.', 'pardot' );
		$msg = sprintf( $msg, self::BUSINESS_UNIT_ID_URL );

        $html =<<<HTML
<div id="business-unit-id-wrap">
	<input type="text" size="30" id="business-unit-id" name="{$html_name}" value="{$business_unit_id}" />
	<p>{$msg}</p>
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
        /**
         * Grab the length of the real password and turn it into a placeholder string that looks like it is filled
         * in whenever a password is set.
         */
        $passwordLength = strlen(self::get_setting( 'password' ));

        /**
         * Set password length to some arbitrary amount iff there is a set password already so that it shows that the
         * password is set already without disclosing the exact number of characters in the password
         */
        $passwordLength = $passwordLength > 0 ? 11 : 0;
        $passwordPlaceholder = str_repeat("&#8226;", $passwordLength);

		$html_name = $this->_get_html_name( 'password' );
$html =<<<HTML
<div id="password-wrap">
	<input type="password" size="30" id="password" name="{$html_name}" placeholder="{$passwordPlaceholder}" />
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
		$msg = __( 'Find your <em>"User Key"</em> in the <em>"My Profile"</em> section of your <a href="%s" target="_blank">Pardot Account Settings</a>.', 'pardot' );
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
	 * Displays the Campaign drop-down field for the Settings API
	 *
	 * @since 1.0.0
	 */
	function campaign_field() {

	    $campaigns = null;

        if ( ! self::get_setting('api_key') ) {
            $campaigns = false;
        }
        else {
            $campaigns = Pardot_Plugin::get_campaigns();
        }

		if ( ! $campaigns ) {
			$msg = __( 'These will show up once you\'re connected.', 'pardot' );
			echo "<p>{$msg}</p>";
		} else {
			$label     = __( 'Select Campaign', 'pardot' );
			$html_name = $this->_get_html_name( 'campaign' );
			$html      = array();
			$html[]    = <<<HTML
<div id="campaign-wrap">
<select id="campaign" name="{$html_name}">
<option selected="selected" value="">{$label}</option>
HTML;

			$selected_value = self::get_setting( 'campaign' );

			foreach ( $campaigns as $campaign => $data ) {

				$campaign_id = esc_attr( $campaign );
				$selected    = selected( $selected_value, $campaign_id, false );

				// A fallback in the rare case of a malformed/empty stdClass of campaign data.
				$campaign_name = sprintf( __( 'Campaign ID: %s', 'pardot' ), $campaign_id );

				if ( isset( $data->name ) && is_string( $data->name ) ) {
					$campaign_name = esc_html( $data->name );
				}

				$html[] = "<option {$selected} value=\"{$campaign_id}\">{$campaign_name}</option>";
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
		$html .= '<option';
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
        $value      = __( 'Save Settings', 'pardot' );
        $valuecache = __( 'Clear Cache', 'pardot' );
        $valuereset = __( 'Reset All Settings', 'pardot' );
        $msgResetConfirm = __( 'This will remove all your Pardot account information from the database. Click OK to proceed.', 'pardot' );
        $msgResetTrue    = __( 'Your Pardot settings have been reset.', 'pardot' );
        $html =<<<HTML
<script>
function resetSettingsClick() {
    if (confirm('{$msgResetConfirm}')) {
        alert("{$msgResetTrue}");
        document.getElementById("resetSettings").click();
    }
}
</script>

<input type="submit" class="button-primary" name="save" value="{$value}" /> 
<input type="submit" class="button-secondary" name="clear" value="{$valuecache}" style="margin-left: 50px;" /> 
<div onclick="resetSettingsClick()" class="button-secondary">{$valuereset}</div>
<input type="submit" name="reset" style="display: none" id="resetSettings"/>

HTML;
        echo $html;
    }

	/**
	 * Encrypts with a bit more complexity
	 * returns false if the string could not be encrypted (cases where encryption fails, or Sodium or OpenSSL are not present in PHP).
	 * @since 1.1.2
	 */
	public static function pardot_encrypt( $input_string, $set_flag = false ) {
        $crypto = new PardotCrypto();
        return $crypto->encrypt( $input_string );
	}


    /**
     * Decrypts with a bit more complexity.
     *
     * In situations where the string could not be decrypted boolean false will
     * be returned. This could include scenarios where the string has already
     * been decrypted.
     *
     * @return string|bool
     * @throws Exception
     * @since 1.1.2
     *
     */
	public static function pardot_decrypt( $encrypted_input_string ) {
	    $crypto = new PardotCrypto();
	    return $crypto->decrypt( $encrypted_input_string );
	}


	/**
	 * Returns the decrypted form of the input string or if decryption fails it
	 * will pass back the input string unmodified.
	 *
	 * @since 1.4.6
	 * @see   self::pardot_decrypt()
	 *
	 * @param string $input_string
	 * @param string string $key
	 *
	 * @return string
	 */
	public static function decrypt_or_original( $input_string ) {
		$decrypted_pass = self::pardot_decrypt( $input_string );

		if (
			! empty( $decrypted_pass )
			&& $decrypted_pass !== $input_string
			&& ctype_print( $decrypted_pass )
		) {
			return $decrypted_pass;
		}

		return $input_string;
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

		} elseif ( isset( $settings['password'] ) && ! empty( $settings['password'] ) ) {

			$decrypted_pass = self::pardot_decrypt( $settings['password'], 'pardot_key' );

			if ( $decrypted_pass !== $settings['password'] && ctype_print($decrypted_pass) ) {
				$settings['password'] = $decrypted_pass;
			}
        }

        if ( isset( $settings['client_secret'] ) && ! empty( $settings['client_secret'] ) ) {

            $decrypted_token= self::pardot_decrypt( $settings['client_secret'], 'pardot_key' );

            if ( $decrypted_token !== $settings['client_secret'] && ctype_print($decrypted_token) ) {
                $settings['client_secret'] = $decrypted_token;
            }
        }

        if ( isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) ) {

            $decrypted_token= self::pardot_decrypt( $settings['api_key'], 'pardot_key' );

            if ( $decrypted_token !== $settings['api_key'] && ctype_print($decrypted_token) ) {
                $settings['api_key'] = $decrypted_token;
            }
        }

        if ( isset( $settings['refresh_token'] ) && ! empty( $settings['refresh_token'] ) ) {

            $decrypted_token= self::pardot_decrypt( $settings['refresh_token'], 'pardot_key' );

            if ( $decrypted_token !== $settings['refresh_token'] && ctype_print($decrypted_token) ) {
                $settings['refresh_token'] = $decrypted_token;
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
		$value = null;

		if ( isset( $settings[ $key ] ) ) {
			$value = $settings[ $key ];
		}

		/**
		 * Provides an opportunity to intercept and override Pardot settings.
		 *
		 * @since 1.4.6
		 *
		 * @param mixed  $value
		 * @param string $key
		 */
		return apply_filters( 'pardot_get_setting', $value, $key );
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
		$settings[ $key ] = $value;

        if ($settings['password'] != NULL) {
            $settings['password'] = self::pardot_encrypt($settings['password'], true);
        }

        if ($settings['client_secret'] != NULL) {
            $settings['client_secret'] = self::pardot_encrypt($settings['client_secret'], true);
        }

        if ($settings['api_key'] != NULL) {
            $settings['api_key'] = self::pardot_encrypt($settings['api_key'], true);
        }

        if ($settings['refresh_token'] != NULL) {
            $settings['refresh_token'] = self::pardot_encrypt($settings['refresh_token'], true);
        }

		/**
		 * Now update all the settings as a serialized array
		 */
		update_option( self::$OPTION_GROUP, (array) $settings, false );
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
			'onclick'   => false,
			'target'    => false,
			'link_text' => false,
		));

		$onclick   = $args['onclick'] ? " onclick=\"{$args['onclick']}\"" : '';
		$target    = $args['target'] ? " target=\"{$args['target']}\"" : '';
		$link_text = $args['link_text'] ?  $args['link_text'] :  __( 'Settings', 'pardot' );

		return "<a{$target}{$onclick} href=\"" . self::get_admin_page_url() . "\">{$link_text}</a>";
	}

	/**
	 * Deletes the main Pardot plugin options (and some persistent transients) from the database.
	 *
	 * @since 1.4.6
	 */
	public static function reset_settings() {

		$main_options = array(
			self::$OPTION_GROUP,
			self::$CODE_VERIFIER,
			'_pardot_cache_keys',
			'_pardot_transient_keys',
			'widget_pardot-dynamic-content',
			'widget_pardot-forms',
		);

		$main_transients = array(
			'_transient_pardot_campaigns',
			'_transient_pardot_dynamicContent',
			'_transient_timeout_pardot_campaigns',
			'_transient_timeout_pardot_dynamicContent',
		);

		foreach( $main_options as $option_name ) {
			delete_option( $option_name );
		}

		foreach( $main_transients as $option_name ) {
			delete_option( $option_name );
		}
	}



    /**
     * If it's an upgrade, then use the old crypto routines to retrieve the password
     * in plaintext and then re-encrypt it using our routines instead.
     */
    private function upgrade_old_password($pwd) {

        /* Get the password from wp_settings and decrypt it using the _old_ method for decrypting... */
        $plaintext = Pardot_Settings::old_decrypt_or_original($pwd, 'pardot_key');

        /* And stick it back in wp_settings (it is encrypted when the setting is set) */
        Pardot_Settings::set_setting('password', $plaintext);
    }



    /**
     * These are the old crypto functions which are no longer supported but still need to be here for upgrade
     * functions
     */
    public static function old_decrypt_or_original( $input_string, $key = 'pardot_key' ) {
        $decrypted_pass = self::old_pardot_decrypt( $input_string, $key );

        if (
            ! empty( $decrypted_pass )
            && $decrypted_pass !== $input_string
            && ctype_print( $decrypted_pass )
        ) {
            return $decrypted_pass;
        }

        return $input_string;
    }


    public static function old_pardot_decrypt( $encrypted_input_string, $key = 'pardot_key' ) {

        // Use simple OpenSSL encryption available in PHP 7.x+
        if ( function_exists( 'openssl_decrypt' ) ) {

            // IV length for AES-256-CBC must be 16 chars.
            $key = wp_salt( 'secure_auth' );
            $iv  = substr( wp_salt( 'auth' ), 0, 16);

            return openssl_decrypt( base64_decode( $encrypted_input_string ), 'AES-256-CBC', $key, true, $iv );
        }

        // Otherwise fall back on mcrypt.
        if ( function_exists( 'mcrypt_encrypt' ) ) {
            $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
            $iv      = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
            $h_key   = hash( 'sha256', $key, TRUE );

            return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $h_key, base64_decode( $encrypted_input_string ), MCRYPT_MODE_ECB, $iv ) );
        }

        // And worst case scenario, fall back on base64_encode.
        return base64_decode( $encrypted_input_string );
    }


}

/**
 * Instantiate this class to ensure the action and shortcode hooks are hooked.
 * This instantiation can only be done once (see it's __construct() to understand why.)
 */
function pardot_settings_instantiate() {
   new Pardot_Settings();
}
add_action( 'plugins_loaded', 'pardot_settings_instantiate' );
