<?php
/*
Plugin Name: Pardot Marketing Automation
Description: Connect your WordPress installation with Pardot for campaign tracking and quick form access..
Version: 1.0
Author: PARDOT.com
Author URI: http://wordpress.org/extend/plugins/pardot/
Plugin URI: http://pardot.com
*/

require_once('pardot-api.php');

register_activation_hook(__FILE__, array('PARDOT', 'activation'));
register_deactivation_hook(__FILE__, array('PARDOT', 'deactivation'));

PARDOT::init();
/**
 * PARDOT
 *
 * DONE
 * =====
 *
 *
 * @since      2012-04-21
 */

class PARDOT {
    const version         = '0.1';
	const domain          = 'pardot';
    const settings_account_key = 'pardot_account';
    const settings_configuration_key = 'pardot_configuration';
    const settings_status_key = 'pardot_status';

	static $settings_page = null;
	static $pluginUrl = '';
    static $settings_tabs = array();

	/**
	 * Hook into WordPress.
	 *
	 * @since      2012-03-31
	 */
	static function init() {
		self::$pluginUrl	= WP_PLUGIN_URL . '/' . basename(dirname(__FILE__));

		/* Admin Hooks. */
		add_action( 'plugins_loaded',       array( __CLASS__, 'load_textdomain' ) );
		add_action( 'admin_menu',           array( __CLASS__, 'settings_menu' ),    10 );
		add_action( 'admin_menu',           array( __CLASS__, 'settings_enqueue' ), 11 );
        add_action( 'admin_init',           array( __CLASS__, 'admin_init' ) );
        add_action( 'admin_init',           array( __CLASS__, 'register_settings_account' ) );

        add_filter( 'plugin_action_links',  array( __CLASS__, 'add_settings_link' ), 10, 2 );

        /* Front End Hooks */
        add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		add_action( 'wp_footer', array( __CLASS__, 'wp_footer' ));			

        //	create the shortcode [pardot-form]
        add_shortcode( 'pardot-form', array(__CLASS__, 'pardot_form'));

	}
	
    /**
     * Plugin Activation.
     *
     * @since      2012-04-21
     */
    function activation(){
        add_option(self::settings_account_key);
        add_option(self::settings_configuration_key);
        add_option(self::settings_status_key);
		
    }

    /**
     * Admin Notice.
     *
     * @since      2012-07-22
     */
    function admin_notice() {
		global $pagenow;
		if ( current_user_can( 'install_plugins' ) && current_user_can( 'manage_options' ) && ( $pagenow != 'options-general.php' ) && !get_pardot_api_key()) {
            $settings_link = '<a href="'.admin_url('options-general.php?page=' . self::domain ) .'">'.__('configuration', self::domain).'</a>';
			echo '<div class="updated"><p><b>Pardot plugin is activate</b>. It requires ' . $settings_link . ' before operating correctly.</p></div>';
		}
    }
	
	
    /**
	 * Plugin Deactivation.
	 *
	 * @since      2012-04-21
	 */
	function deactivation(){
        delete_option(self::settings_account_key);
        delete_option(self::settings_configuration_key);
        delete_option(self::settings_status_key);
	}
	
    /**
     * Admin Init.
     *
     * @since      2012-04-22
     */
    function admin_init() {
        if ( current_user_can('edit_posts') && current_user_can('edit_pages') && get_user_option('rich_editing') == 'true') {
            add_filter("mce_external_plugins", array(__CLASS__, "mce_external_plugins"));
            add_filter('mce_buttons', array(__CLASS__, 'mce_buttons'));
        }
		
		//WPRackTest plugin integration. Adding Plugin name to the racktest plugins list
		if ( class_exists( 'WPRackTest' ) ){
			add_filter('get_racktest_plugins', array(__CLASS__, "get_racktest_plugins"));
		}	

		add_action('admin_notices',  array( __CLASS__, 'admin_notice' ));	
		
    }

	//Target of WPRackTest plugin filter. Provides the plugin
	function get_racktest_plugins( $plugins ) {
		return array_merge($plugins, array('pardot/pardot.php' => 'pardot/pardot.php'));
	}
	
    /**
     * MCE External Plugins.
     * Load the TinyMCE plugin : editor_plugin.js (wp2.5)
     * @since      2012-04-22
     */
    function mce_external_plugins($plugin_array) {
        $plugin_array['pardotbn'] = self::$pluginUrl . '/js/tinymce/editor_plugin.js';
        return $plugin_array;
    }

    /**
     * ME Buttons.
     *
     * @since      2012-04-22
     */
    function mce_buttons($buttons) {
        array_push($buttons, "separator", "pardotbn");
        return $buttons;
    }

    /**
	 * Add settings link to plugin listing on plugins page.
	 *
	 * @since      2012-04-21
	 */
    static function add_settings_link( $links, $file ){
        if( $file == plugin_basename(__FILE__) ){
            $settings_link = '<a href="'.admin_url('options-general.php?page=' . self::domain ) .'">'.__('Settings', self::domain).'</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

	/**
	 * Load Text Domain.
	 *
	 * @since      2012-04-21
	 */
	static function load_textdomain() {
		load_plugin_textdomain( self::domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Dynamic hooks for Settings page.
	 *
	 * @since      2012-04-21
	 */
	static function settings_enqueue() {
		add_action( 'admin_print_styles-' . self::$settings_page, array( __CLASS__, 'style_settings_page' ) );
	}

	/**
	 * Settings Page Styles.
	 *
	 * @since      2012-03-31
	 */
	static function style_settings_page() {
		wp_enqueue_style(
			self::domain . '_settings',
			plugin_dir_url( __FILE__ ) . 'style.css',
			array(),
			self::version,
			'screen'
		);
	}
	
	/**
	 * Add Link to Admin Menu.
	 *
	 * @since      2012-04-21
	 */
	static function settings_menu() {
		self::$settings_page = add_options_page(
			__( 'Pardot Settings', self::domain ),
			__( 'Pardot  Settings', self::domain ),
			'manage_options',
			self::domain,
			array( __CLASS__, 'settings_page' )
			);
	}

    /**
     * Register Account Settings.
     *
     * @since      2012-04-25
     */
    static function register_settings_account() {
        self::$settings_tabs[self::settings_account_key] = 'Account';
        register_setting(
            self::settings_account_key,
            self::settings_account_key,
            array( __CLASS__, 'settings_sanitize_account' )
        );
        add_settings_section(
            self::settings_account_key . '_message_account',
            __( '', self::domain ),
            array( __CLASS__, 'message_account' ),
            self::settings_account_key
        );
        add_settings_section(
            self::settings_account_key . '_account',
            __( 'User Account', self::domain ),
            array( __CLASS__, 'section_account' ),
            self::settings_account_key
        );
        add_settings_field(
            self::settings_account_key . '_email',
            __( 'Email', self::domain ),
            array( __CLASS__, 'control_email' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_password',
            __( 'Password', self::domain ),
            array( __CLASS__, 'control_password' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_user_key',
            __( 'User Key<br><i>You can find your User Key in the "My User Information" table on the <a href="https://pi.pardot.com/account">Settings page</a> for your account.</i>', self::domain ),
            array( __CLASS__, 'control_user_key' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_campaign',
            __( 'Campaign', self::domain ),
            array( __CLASS__, 'control_campaign' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_racktest',
            __( '', self::domain ),
            array( __CLASS__, 'control_racktest' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_account_submit',
            false,
            array( __CLASS__, 'control_account_submit' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
        add_settings_field(
            self::settings_account_key . '_reset_settings',
            false,
            array( __CLASS__, 'control_reset_settings' ),
            self::settings_account_key,
            self::settings_account_key . '_account'
        );
		
    }

    /**
     * Sanitize Settings Account.
     *
     * @param      array     $dirty List of values that may be settings.
     * @return     array     Sanitized array of all recognized settings.
     *
     * @since      2012-04-15
     */
    static function settings_sanitize_account($dirty) {

        $clean = array(
            'action'            => '',
            'status'            => '',
            'email' => '',
            'password' => '',
            'user_key'      => '',
            'api_key'      => '',
            'campaign'      => '',
        );

        if (isset($_POST['reset-settings'])) {
            add_settings_error('general', 'reset_settings', __('Settings Reset.'), 'updated');
            return $clean;
        }

        if (isset($_POST['status'])) {
            $clean['status'] = $_POST['status'];
        }

        if( isset( $dirty['email'] ) ){
            $clean['email'] = esc_attr( $dirty['email'] );
        }

        if( isset( $dirty['password'] ) ){
            $clean['password'] = esc_attr( $dirty['password'] );
        }

        if( isset( $dirty['user_key'] ) ){
            $clean['user_key'] = esc_attr( $dirty['user_key'] );
        }

        if( isset( $dirty['campaign'] ) ){
            $clean['campaign'] = esc_attr( $dirty['campaign'] );
        }
		
        if (isset($_POST['login-account'])) {
            $clean['action'] = 'login-account';
            $clean['status'] = 'logged';
            add_settings_error('general', 'account_logged', __('Account logged.'), 'updated');
        }

        if (isset($_POST['logout-account'])) {
            $clean['action'] = 'logout-account';
            add_settings_error('general', 'account_logouted', __('Account logouted.'), 'updated');
        }
		
		if (!get_pardot_api_key($clean['email'], $clean['password'], $clean['user_key'])) {
            add_settings_error('general', 'update_settings', __('**Darn!** Would you review the errors below and click \'Save Settings\' again?'), 'error');
		}
        return $clean;
    }

    /**
     * Message for Account section of settings page.
     *
     * @since      2012-04-25
     */
    static function message_account() {
        if (self::get_setting(self::settings_account_key, 'action') == 'login-account') {
            $key = 'login_account';
            $id = self::settings_account_key . '_' . $key;
            echo '<div id="' . $id . '_wrap">';
            if( self::get_setting(self::settings_account_key, 'email') && self::get_setting(self::settings_account_key, 'password')  && self::get_setting(self::settings_account_key, 'user_key')){
                $result = get_pardot_api_key();
                if( $result ){
                    echo '<p style="color:green;">' . __('The Account Logged.', self::domain ) . '</p>';
                    $_POST['status'] = 'logged';
                    self::set_setting(self::settings_status_key, 'status',  'logged');
                    self::set_setting(self::settings_status_key, 'api_key',  $result);
					
                    $_POST['api_key'] =  $result;
                } else{
                    echo '<p style="color:red;">' . __('There was a problem logging your account. Please try again.', self::domain ) . '</p>';
                }
            }
            echo '</div>';
            self::set_setting(self::settings_account_key, 'action', '');
        }
        if (self::get_setting(self::settings_account_key, 'action') == 'logout-account') {
            $key = 'logout_account';
            $id = self::settings_account_key . '_' . $key;
            echo '<div id="' . $id . '_wrap">';
            echo '<p style="color:green;">' . __('The Account Logouted.', self::domain ) . '</p>';
            echo '</div>';
            $_POST['status'] = '';
            self::set_setting(self::settings_status_key, 'status',  '');
            self::set_setting(self::settings_account_key, 'action', '');
            self::set_setting(self::settings_status_key, 'api_key',  '');
            $_POST['api_key'] =  '';
        }
    }

    /**
     * Section Account of settings page.
     *
     * @since      2012-04-25
     */
    static function section_account() {
		echo("<i>Use your Pardot login information to securely connect (you\'ll only need to do this once).</i>");
/*
		echo '<p>';
        esc_html_e( 'Account Information', self::domain );
        echo '</p>';
        echo(self::get_setting(self::settings_status_key, 'api_key'));
*/
    }

    /**
     * Control Email UI
     *
     * @since      2012-04-25
     */
    static function control_email(){
        $key = 'email';
        $id = self::settings_account_key . '_' . $key;
        $saved = self::get_setting( self::settings_account_key, $key );
        echo '<div id="' . $id . '_wrap">';
        echo '<input type="text" id="' . $id . '" class="' . $id . '" name="' . self::settings_account_key . '[' . $key . ']' . '" value="' . esc_attr($saved) . '" />';
        echo '</div>';
    }

    /**
     * Control Password UI
     *
     * @since      2012-04-25
     */
    static function control_password(){
        $key = 'password';
        $id = self::settings_account_key . '_' . $key;
        $saved = self::get_setting( self::settings_account_key, $key );
        echo '<div id="' . $id . '_wrap">';
        echo '<input type="password" id="' . $id . '" class="' . $id . '" name="' . self::settings_account_key . '[' . $key . ']' . '" value="' . esc_attr($saved) . '" />';
        echo '</div>';
    }

    /**
     * Control User Key UI
     *
     * @since      2012-04-25
     */
    static function control_user_key(){
        $key = 'user_key';
        $id = self::settings_account_key . '_' . $key;
        $saved = self::get_setting( self::settings_account_key, $key );
        echo '<div id="' . $id . '_wrap">';
        echo '<input type="text" id="' . $id . '" class="' . $id . '" name="' . self::settings_account_key . '[' . $key . ']' . '" value="' . esc_attr($saved) . '" />';
        echo '</div>';
    }

    /**
     * Control Campaign UI
     *
     * @since      2012-05-26
     */
    static function control_campaign(){
		$campaigns = get_pardot_campaign();
		if ($campaigns) {
			$key = 'campaign';
			$id = self::settings_account_key . '_' . $key;
			$saved = self::get_setting( self::settings_account_key, $key );
			echo '<div id="' . $id . '_wrap">';
			echo '<select id="' . $id . '" class="' . $id . '" name="' . self::settings_account_key . '[' . $key . ']">';
				print "\n" . '<option selected="selected" value="" />Select Campaign</option>';
			foreach ( $campaigns as $campaign => $data ) {
				$id = self::settings_account_key . '_' . $key . '_' . $campaign;
				print "\n" . '<option ' . selected( $saved, $campaign, false ) . ' value="' . esc_attr( $campaign ) . '" /> ' . esc_html( $data->name ) . '</option>';
			}
			echo '</select></div>';
		}
    }

    /**
     * Control Rack Test UI
     *
     * @since      2012-06-05
     */
    static function control_racktest(){
		if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] && (self::get_setting(self::settings_account_key, 'user_key') != '')) {
			$key = 'racktest';
			$id = self::settings_account_key . '_' . $key;
			echo '<div id="' . $id . '_wrap">';
			self::racktest_run();
			echo '</div>';
		}
    }
	
    /**
     * Control Account Submit.
     *
     * @since      2012-04-25
     */
    static function control_account_submit() {
		echo '<input type="submit" class="button-primary" name="save-account" value="' . __( '&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;Settings&nbsp;&nbsp;', self::domain ) . '" />';
    }

    /**
     * Reset Options UI
     *
     * @since      2012-04-01
     */
    static function control_reset_settings(){
        echo '<input onclick="return confirm(\'This will remove all account information from the database\');" type="submit" class="button-secondary" name="reset-settings" value="' . __( 'Reset All Settings', self::domain ) . '" />';
    }

    /**
     * Default Values.
     *
     * @return     array     Default settings.
     *
     * @since      2012-04-25
     */
    static function get_defaults($group) {
        switch ($group) {
            case self::settings_account_key:
                return array();
                break;
            case self::settings_configuration_key:
                return array();
                break;
                break;
            case self::settings_status_key:
                return array();
                break;
        }
    }

    /**
     * Get Settings.
     *
     * @since      2012-04-25
     */
    static function get_settings($group) {
        return wp_parse_args( (array) get_option( $group ), self::get_defaults( $group ) );
    }

    /**
     * Get Setting.
     *
     * Gets an individual key stored in the custom settings array.
     * In the event that an unrecognized key is asked for, boolean
     * false will be returned.
     *
     * @param      string       $key Get a single unsanitized setting.
     * @return     mixed
     *
     * @since      2012-04-25
     */
    static function get_setting( $group, $key ) {
        $settings = self::get_settings($group);
        if ( isset( $settings[$key] ) ) {
            return $settings[$key];
        }
        return false;
    }

    /**
     * Set Setting.
     *
     * Sets an individual key stored in the custom settings array.
     * In the event that an unrecognized key is asked for, boolean
     * false will be returned.
     *
     * @param      string       $key Set a single unsanitized setting.
     * @return     mixed
     *
     * @since      2012-04-25
     */
    static function set_setting( $group, $key, $value ) {
        $settings = self::get_settings($group);
        $settings[$key] = $value;
        update_option( $group,  (array) $settings);
    }

    /**
     * Settings Page Template.
     *
     * @since      2012-03-31
     */
    static function settings_page() {
        print "\n" . '<div class="wrap" id="' . esc_attr( self::$settings_page ) . '">';
        screen_icon('pardot');
        print "\n" . '<h2 style="padding: 40px;">' . esc_html__( 'Settings', self::domain ) . '</h2>';

        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::settings_account_key;
        print "\n" . '<form action="options.php" method="post">';
        settings_fields( $tab );
        do_settings_sections( $tab );
        print "\n" . '</form>';
        print "\n" . '</div>';
    }

    /**
     * Settings Tabs UI
     *
     * @since      2012-04-15
     */
    static function settings_tabs() {
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::settings_account_key;
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( self::$settings_tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . self::domain  . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
        echo '</h2>';
    }



    /**
     * Get The Pardot HTML
     *
     * @since       2012-04-21
     */
    static function html(){
        return html_entity_decode( esc_html( self::get_setting( 'field' ) ) );
    }

    /**
     * Register pardot widget by passing the class name to WordPress
     *
     * @since       2012-04-21
     */
    static function register_widget(){
        register_widget('PARDOT_Widget');
    }

    /**
     * Register pardot shortcode by passing the class name to WordPress
     *
     * @since       2012-04-21
     */
    static function pardot_form($atts){
		$forms = get_pardot_forms();
		if ($forms) {
			$id = $atts['id'];
			if (isset($forms[$id])) {
				$form = $forms[$id];
				$content = $form->embedCode;
				if (isset($atts['height']) && ($atts['height'] != '')) {
					$height = $atts['height'];
					if (preg_match('/height=\"\w+\"/', $content, $matches)) {
						$content = str_replace($matches[0], 'height="' . $height . '"', $content);
					} else {
						$content = str_replace('iframe', 'iframe height="' . $height . '"', $content);
					}
				}
				echo($content);
			}
		}
    }
	
    /**
     * The Pardot Get Footer method
     *
     * @since      2012-05-26
     */
	function wp_footer() { 
		$campaign = self::get_setting( self::settings_account_key, 'campaign' );
		if ($campaign) {
			$account = get_pardot_account();
			if ($account) {
				// *The value to substitute should be the ID of the campaign plus 1000*.
				$campaign += 1000;
				$tracking_code_template = $account->tracking_code_template;
				// $tracking_code_template = str_replace( '."', '', $tracking_code_template); // fix 
				$tracking_code_template = str_replace( '%%CAMPAIGN_ID%%', $campaign, $tracking_code_template);
				print '<script type="text/javascript">' . $tracking_code_template . '</script>';
			}
		}
	}
	
    static function racktest_run() {
		global $WPRackTest;
		if ( isset($WPRackTest) ){
			 $WPRackTest->wp_racktest_run('pardot');
		}	
		
/*
        $url = self::$pluginUrl . '/phprack.php';
        $response = wp_remote_request( $url, array(
            'timeout' 		=> '30',
            'redirection' 	=> '5',
            'method' => 'POST',
            'headers' => array('content-type' => 'application/json; charset=utf-8'),
            'blocking'		=> true,
            'compress'		=> false,
            'decompress'	=> true,
            'sslverify' => false,
        ));

        if( wp_remote_retrieve_response_code( $response ) == 200 ){
            $body = wp_remote_retrieve_body( $response );
            echo ( $body );
        } else {
            $body = wp_remote_retrieve_body( $response );
            echo ( $body );
        }
*/		
    }
	
	
}

class PARDOT_Widget extends WP_Widget {

    function PARDOT_Widget(){
        $this->WP_Widget(false,
            __('WP PARDOT Widget'),
            array('classname' => 'PARDOT_widget',
                'description' => 'Widget boosts your sales with Pardot.'
            )
        );
    }

    function widget( $args, $instance ){
		extract( $args );
		$id = $instance['id'];
        // Begin widget wrapper
        echo $before_widget;
        // Display widget content to user
		$forms = get_pardot_forms();
		if ($forms) {
			if (isset($forms[$id])) {
				$form = $forms[$id];
				echo($form->embedCode);
			}
		}
        // End widget wrapper
        echo $after_widget;
    }
	
	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		/* Strip tags (if needed) and update the widget settings. */
		$instance['id'] = $new_instance['id'];
		return $instance;	
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		/* Set up some default widget settings. */
		$forms = get_pardot_forms();
		if ($forms) {
		?>    
		<p>
			<label for="<?php echo $this->get_field_id( 'id' ); ?>"><?php _e('Select Form:'); ?></label>
			<select id="<?php echo $this->get_field_id( 'id' ); ?>" name="<?php echo $this->get_field_name( 'id' ); ?>">
		<?php
		foreach ($forms as $form) {
			echo ('<option value="' . $form->id . '" ' . selected( $instance['id'],  $form->id  ) . ' >' . $form->name . '</option>');
		}
		?>					
			</select>
		</p>
		<?php
		} else {
		?>
			<p>No forms</p>
		<?php	
		}
		?>		
		<?php 
	}
	
}