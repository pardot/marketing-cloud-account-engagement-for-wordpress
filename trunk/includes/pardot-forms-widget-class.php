<?php
/**
 * WordPress Pardot Forms Widget
 *
 * @author Mike Schinkel <mike@newclarity.net>
 *
 * @see: http://codex.wordpress.org/Widgets_API
 *
 * @since 1.0.0
 *
 */
class Pardot_Forms_Widget extends WP_Widget {
	/**
	 * @var int Timeout value for front-end widget form that can reset in a hook, if need be. Initially 30 days.
	 *
	 * @since 1.0.0
	 */
	static $cache_timeout = PARDOT_WIDGET_FORM_CACHE_TIMEOUT;

	/**
	 * Add the hooks needed by this Widget.
	 *
	 * This method is called once immediately at the end of the class definition.
	 *
	 * @static
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	static function on_load() {
		/**
		 * Use 'widgets_init' to register this widget
		 */
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
		/*
				 * Do the following for inline forms only; not needed for iframed forms.
				 */
		if ( 'inline' == PARDOT_FORM_INCLUDE_TYPE ) {
			/**
			 * Use the wp_head action to insert CSS into the header.
			 * Use priority == 0 so it will be added very early and thus will allow themer's to override CSS if required.
			 */
			add_action( 'wp_head', array( __CLASS__, 'wp_head' ), 0 );
			/**
			 * Use the plugins_loaded hook to check for the /pardot-form-submit/ path
			 * which is the path used for a postback from this widget.
			 */
			add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
		}

	}

	/**
	 * Register this widget with WordPress using the name of this PHP class.
	 *
	 * @static
	 *
	 * @return void
	 *
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.0.0
	 */
	static function widgets_init() {
		register_widget( __CLASS__ );
	}

	/**
	 * Use the wp_head action to insert CSS into the header.
	 *
	 * This will be called with priority == 0 to allow themer's to override CSS if required.
	 * This will only be called for inline forms; not needed for iframed forms.
	 *
	 * @static
	 *
	 * @since 1.0.0
	 */
	static function wp_head() {
		$css = <<<CSS
.pardot-forms-body .field-label { text-align: left; width:auto;}
.pardot-forms-body form.form input.text { width:95%;}
CSS;
		echo "\n<style type=\"text/css\">\n{$css}\n</style>\n";
	}

	/**
	 * Use the plugins_loaded action to test for /pardot-form-submit/ path.
	 *
	 * The  /pardot-form-submit/ path is the postback URL for inline forms.
	 * This will only be called for inline forms; not needed for iframed forms.
	 *
	 * @static
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	static function plugins_loaded() {
		/**
		 * Check for /pardot-form-submit/ path.
		 */
		if ( preg_match( '#^/pardot-form-submit/\?(.*)$#', $_SERVER['REQUEST_URI'] ) ) {
			/**
			 * Check to make sure that referrer is the current, i.e. that the HTTP_REFERER begins with the site_url().
			 */
			$regex = '#^' . str_replace( '.', '\.', site_url() ) . '#';
			$url = preg_match( $regex, $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : site_url();

			/**
			 * Connvert the POST_ROOT_URL into a regex (escape the '.'s)
			 */
			$post_root_url_regex = str_replace( '.', '\.', Pardot_Settings::POST_ROOT_URL );

			/**
			 * Check to make the "url" parameter matches the post's root URL
			 */
			if ( isset( $_GET['url'] ) && preg_match( "#^{$post_root_url_regex}/#", $_GET['url'] ) ) {
				/**
				 * Post back to Pardot's website using the WordPress HTTP function.
				 *
				 * $_POST will contain the prior $_POST's body, just pass it along.
				 *
				 */
				$response = wp_remote_post(
					$_GET['url'], array(
						'body' => $_POST,
						'user-agent' => 'Pardot WordPress Plugin',
					)
				);

				/**
				 * Inspect response for Status 200=ok, 'success' for 200, 'errors' otherwise
				 *
				 * $_POST will contain the prior $_POST's body, just pass it along.
				 *
				 */
				$result = wp_remote_retrieve_response_code( $response ) == 200 ? 'success' : 'errors';

				/**
				 * Add a URL parameter named 'pardot-contact-request' for success or failure.
				 *
				 */
				$url = add_query_arg( array( 'pardot-contact-request' => $result ), $url );
			}
			/**
			 * Redirect back to this page or the root of the site if referrer is not this page and with success or error
			 * message if $_GET['url'] starts with Pardot_Settings::POST_ROOT_URL.
			 */
			wp_safe_redirect( $url );
			/**
			 * Terminate the page.
			 */
			exit;
		}
	}

	/**
	 * PHP object constructor calls parent to enable setting Widget classname and description.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		/**
		 * @var array Set Widget's objects, i.e. classname and description.
		 */
		$widget_ops = array(
			'classname' => 'pardot-forms',
			'description' => __( 'Embed a Pardot form in your sidebar.', 'pardot' )
		);
		/**
		 * @var array Empty array lists to document that parameters for the WP_Widget parent constructor.
		 *
		 */
		$control_ops = array();

		/**
		 * Call the WP_Widget parent constructor.
		 */
		parent::__construct( 'pardot-forms', __( 'Pardot Forms', 'pardot' ), $widget_ops, $control_ops );
	}
/*

*/
	/**
	 * Displays Pardot forms for Widget HTML on front end of site.
	 *
	 * Echos form defined in Pardot account and specified for this widget.  Can display as IFrame or as inline HTML the
	 * latter of which is experimental.
	 *
	 * @param array $args Arguments passed by the sidebar. $args can be one of:
	 *
	 *   - 'name'
     *   - 'id'
	 *   - 'description'
	 *   - 'class'
	 *   - 'before_widget'
	 *   - 'after_widget'
	 *   - 'before_title'
	 *   - 'after_title'
	 *   - 'widet_id'
	 *   - 'widget_name'
	 *
	 * @param array $instance Contains 'form_id' value set in $this->form() and $this->update().
	 * @return void
	 *
	 * @see WP_Widget::widget()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.0.0
	 */
	function widget( $args, $instance ) {
		/**
		 * @var string $title Allow the widget title to be modified by the 'widget_title' hook.
		 */
		$title = apply_filters(
			'widget_title', ! empty( $instance['title'] ) ? $instance['title'] : false, $instance, $this->id_base
		);
		/**
		 * If no title specified by hook, give is a default value.
		 */
		if ( empty( $title ) )
			$title = __( '', 'pardot' );

		/**
		 * Wrap a non-empty title with before and after content.
		 */
		$title_html = ! empty( $title ) ? "{$args['before_title']}{$title}{$args['after_title']}" : false;

		/**
		 * Grab form_id from the instance that we set in $this->update() and use it to grab the HTML for this Pardot Form.
		 */
		$body_html = '<h4>Please select a Pardot form.</h4>';
		if ( isset($instance['form_id']) ) {
            $body_html = Pardot_Plugin::get_form_body( $instance );
        }

		/**
		 * After all that if the $body_html is not empty, we can use it as a form.
		 */
		if ( $body_html ) {
			/**
			 * Allow others to modify the form HTML if needed.
			 */
			$body_html = apply_filters( 'pardot_widget_body_html', $body_html, $instance, $this, $args );
			/**
			 * Use a HEREDOC to assemble the HTML for the form.
			 */
			$html = <<<HTML
{$args['before_widget']}
<div class="pardot-forms-widget">
	{$title_html}
	<div class="pardot-forms-body">
		{$body_html}
	</div>
</div>
{$args['after_widget']}
HTML;
			/**
			 * Lastly let other modify the HTML if needed.
			 */
			echo apply_filters( 'widget_html', $html, $instance, $this, $args );
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 *
	 * @see WP_Widget::update()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 *
	 * @since 1.0.0
	 */
	function update( $new_instance, $old_instance ) {
		/**
		 * Default the 'form_id' to a non-selected form ID, i.e. '0'.
		 */
		$instance = array( 'form_id' => 0 );

		/**
		 * If the new instance has 'form_id' then capture it's value before returning.
		 */
		if ( isset( $new_instance['form_id'] ) )
			$instance['form_id'] = $new_instance['form_id'];
			
		$instance['title'] = strip_tags( $new_instance['title'] );	
		$instance['height'] = strip_tags( $new_instance['height'] );	
		$instance['width'] = strip_tags( $new_instance['width'] );	
		$instance['class'] = strip_tags( $new_instance['class'] );	

		return $instance;
	}

	/**
	 * Display the widget configuration form in the Widgets section of the Admin.
	 *
	 * @param array $instance Contains 'form_id' value set in $this->update() and used in $this->widget().
	 * @return void
	 *
	 * @see WP_Widget::form()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.0.0
	 */
	function form( $instance ) {
		/**
		 * Check the cache and/or call the API to get the array of forms.
		 */
		$forms = get_pardot_forms();
		if ( ! $forms ) {
			/**
			 * We have no forms!
			 * Create link text for the help link
			 */
			$help_link_text = __( 'Settings', 'pardot' );

			/**
			 * Merge the link and help text link into a help link.
			 */
			$help_link = sprintf( '<a href="%s" target="_blank">%s</a>', Pardot_Settings::get_admin_page_url(), $help_link_text );

			/**
			 * Create a variable for help text to be used in the HEREDOC
			 * Add the link into the help text.
			 */
			$help_text = __( 'You have no forms yet, or there is a connection issue. Please check your %s.', 'pardot' );
			$help_text = sprintf( $help_text, $help_link );

			/**
			 * Finally create the HTML containing the help message about no forms.
			 */
			$html = "<p>{$help_text}</p>";

		} else {
			/**
			 * We DO have forms!
			 *
			 * If the instance hasn't been initialized via $this->update(), give it's 'form_id' element
			 * a value indicating no Pardot form has yet to be selected by an admin user.
			 */
			if ( ! isset( $instance['form_id'] ) )
				$instance['form_id'] = 0;

			/**
			 * Create an array to capture the HTML output into.
			 */
			$options = array();

			/**
			 * Give the zero (0) value meaning no form yet selected a default value.
			 */
			$label_option = (object) array( 'id' => 0, 'name' => __( 'Please select a Form', 'pardot' ) );

			/**
			 * Insert the 'no form yet selected' value to the beginning of the list of options.
			 */
			array_unshift( $forms, $label_option );

			/**
			 * For each Pardot form that the current account has configured
			 */
			foreach ( $forms as $form ) {
				/**
				 * For the selected value, assign $selected to be ' selected="selected"' for use in the <option> tag.
				 */
				if ( isset($form->id) ) {
					$selected = selected( $instance['form_id'], $form->id, false );
					
					/**
					 * Add an array element containing the HTML for each option
					 */
						$options[] = "<option value=\"{$form->id}\"{$selected}>{$form->name}</option>";
				}
			}

			/**
			 * Convert array of HTML options to a string of HTML options
			 */
			$options = implode( '', $options );

			/**
			 * Get the Form ID into a variable for HTML id that can be used in the HEREDOC
			 * This will leave dashes.
			 */
			$html_id = $this->get_field_id( 'form_id' );

			/**
			 * Get the Form ID into a variable for HTML name that can be used in the HEREDOC
			 * This will convert dashes to underscores.
			 */
			$html_name = $this->get_field_name( 'form_id' );

			/**
			 * Create a variable for prompting the user to be used in the HEREDOC
			 */
			$prompt = __( 'Select Form:', 'pardot' );

            /**
             * Create a variable for parameters helper text.
             */
            $param_text = __( 'Height and width should be in digits only (i.e. 250).', 'pardot' );

			/**
			 * Create link to Settings Page
			 */
			$pardot_settings_url = admin_url( '/options-general.php?page=pardot' );
			$cache_text = __( '<strong>Not seeing something you added recently in Pardot?</strong> Please click the Clear Cache button on the %s.', 'pardot' );
			$cache_link = sprintf( '<a href="%s" target="_parent">%s</a>', $pardot_settings_url, 'Pardot Settings Page' );
			$cache_text = sprintf( $cache_text, $cache_link );

			/**
			 * Create the HTML for displaying the select of Pardot forms
			 */
			$html = <<<HTML
<p><label for="{$html_id}">{$prompt}</label><select id="{$html_id}" name="{$html_name}" style="max-width:100%" class="js-chosen">{$options}</select></p>
HTML;
		}
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( '', 'text_domain' );
		}
		
		$html .= '<p><label for="' . $this->get_field_id( "title" ) . '">' . __( 'Title:' ) . '</label><input class="widefat" id="' . $this->get_field_id( "title" ) . '" name="' . $this->get_field_name( "title" ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';
		
		$html .= '<p><strong>Optional Parameters</strong><br/><small>' . $param_text . '</small></p>';

		if ( isset( $instance[ 'height' ] ) ) {
			$height = $instance[ 'height' ];
		} else {
			$height = __( '', 'text_domain' );
		}
		
		$html .= '<p><label for="' . $this->get_field_id( "height" ) . '">' . __( 'Height:' ) . '</label><input id="' . $this->get_field_id( "height" ) . '" name="' . $this->get_field_name( "height" ) . '" type="text" value="' . esc_attr( $height ) . '" size="6" />';
		
		if ( isset( $instance[ 'width' ] ) ) {
			$width = $instance[ 'width' ];
		} else {
			$width = __( '', 'text_domain' );
		}
		
		$html .= '<label for="' . $this->get_field_id( "width" ) . '">' . __( 'Width:' ) . '</label><input id="' . $this->get_field_id( "width" ) . '" name="' . $this->get_field_name( "width" ) . '" type="text" value="' . esc_attr( $width ) . '" size="6" /></p>';

		if ( isset( $instance[ 'class' ] ) ) {
			$class = $instance[ 'class' ];
		} else {
			$class = __( '', 'text_domain' );
		}
		
		$html .= '<p><label for="' . $this->get_field_id( "class" ) . '">' . __( 'Class:' ) . '</label><input class="widefat" id="' . $this->get_field_id( "class" ) . '" name="' . $this->get_field_name( "class" ) . '" type="text" value="' . esc_attr( $class ) . '" /></p>';
		
		
		
		$html .= <<<HTML
<p><small>{$cache_text}</small></p>
HTML;
		
		/**
		 * Display whatever HTML is appropriate; error message help or list of forms.
		 */
		echo $html;
	}

}
/**
 * Calls startup method that add actions and filters to hook WordPress for this widget.
 */
Pardot_Forms_Widget::on_load();

/**
 * WordPress Pardot Dynamic Content Widget
 *
 * @author Cliff Seal <cliff.seal@pardot.com>
 *
 * @see: http://codex.wordpress.org/Widgets_API
 *
 * @since 1.1.0
 *
 */
class Pardot_Dynamic_Content_Widget extends WP_Widget {
	/**
	 * @var int Timeout value for front-end widget form that can reset in a hook, if need be. Initially 30 days.
	 *
	 * @since 1.1.0
	 */
	static $cache_timeout = PARDOT_WIDGET_FORM_CACHE_TIMEOUT;

	/**
	 * Add the hooks needed by this Widget.
	 *
	 * This method is called once immediately at the end of the class definition.
	 *
	 * @static
	 *
	 * @return void
	 *
	 * @since 1.1.0
	 */
	static function on_load() {
		/**
		 * Use 'widgets_init' to register this widget
		 */
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}

	/**
	 * Register this widget with WordPress using the name of this PHP class.
	 *
	 * @static
	 *
	 * @return void
	 *
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.1.0
	 */
	static function widgets_init() {
		register_widget( __CLASS__ );
	}

	/**
	 * PHP object constructor calls parent to enable setting Widget classname and description.
	 *
	 * @since 1.1.0
	 */
	function __construct() {
		/**
		 * @var array Set Widget's objects, i.e. classname and description.
		 */
		$widget_ops = array(
			'classname' => 'pardot-dynamic-content',
			'description' => __( 'Use Pardot Dynamic Content in your sidebar.', 'pardot' )
		);
		/**
		 * @var array Empty array lists to document that parameters for the WP_Widget parent constructor.
		 *
		 */
		$control_ops = array();

		/**
		 * Call the WP_Widget parent constructor.
		 */
		parent::__construct( 'pardot-dynamic-content', __( 'Pardot Dynamic Content', 'pardot' ), $widget_ops, $control_ops );
	}
/*

*/
	/**
	 * Displays Pardot dynamic content for Widget HTML on front end of site.
	 *
	 * Echos dynamic content defined in Pardot account and specified for this widget. 
	 *
	 * @param array $args Arguments passed by the sidebar. $args can be one of:
	 *
	 *   - 'name'
     *   - 'id'
	 *   - 'description'
	 *   - 'class'
	 *   - 'before_widget'
	 *   - 'after_widget'
	 *   - 'before_title'
	 *   - 'after_title'
	 *   - 'widet_id'
	 *   - 'widget_name'
	 *
	 * @param array $instance Contains 'form_id' value set in $this->form() and $this->update().
	 * @return void
	 *
	 * @see WP_Widget::widget()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.1.0
	 */
	function widget( $args, $instance ) {
		/**
		 * @var string $title Allow the widget title to be modified by the 'widget_title' hook.
		 */
		$title = apply_filters(
			'widget_title', ! empty( $instance['title'] ) ? $instance['title'] : false, $instance, $this->id_base
		);
		/**
		 * If no title specified by hook, give is a default value.
		 */
		if ( empty( $title ) )
			$title = __( '', 'pardot' );

		/**
		 * Wrap a non-empty title with before and after content.
		 */
		$title_html = ! empty( $title ) ? "{$args['before_title']}{$title}{$args['after_title']}" : false;

		/**
		 * Grab form_id from the instance that we set in $this->update() and use it to grab the HTML for this Pardot Form.
		 */
		$body_html = '<h4>Please select Pardot dynamic content.</h4>';
		if ( isset($instance['dynamicContent_id']) ) {
            $body_html = Pardot_Plugin::get_dynamic_content_body( $instance );
        }

        wp_register_script( 'pddc', plugins_url( 'js/asyncdc.min.js' , dirname(__FILE__) ), array('jquery'), false, true);
        wp_enqueue_script( 'pddc' );

		/**
		 * After all that if the $body_html is not empty, we can use it as a form.
		 */
		if ( $body_html ) {
			/**
			 * Allow others to modify the form HTML if needed.
			 */
			$body_html = apply_filters( 'pardot_widget_body_html', $body_html, $instance, $this, $args );
			/**
			 * Use a HEREDOC to assemble the HTML for the form.
			 */
			$html = <<<HTML
{$args['before_widget']}
<div class="pardot-dynamic-content-widget">
	{$title_html}
	<div class="pardot-dynamic-content-body">
		{$body_html}
	</div>
</div>
{$args['after_widget']}
HTML;
			/**
			 * Lastly let other modify the HTML if needed.
			 */
			echo apply_filters( 'widget_html', $html, $instance, $this, $args );
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 *
	 * @see WP_Widget::update()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 *
	 * @since 1.1.0
	 */
	function update( $new_instance, $old_instance ) {
		/**
		 * Default the 'form_id' to a non-selected form ID, i.e. '0'.
		 */
		$instance = array( 'dynamicContent' => 0 );

		/**
		 * If the new instance has 'form_id' then capture it's value before returning.
		 */
		if ( isset( $new_instance['dynamicContent_id'] ) )
			$instance['dynamicContent_id'] = $new_instance['dynamicContent_id'];
		
		$instance['title'] = strip_tags( $new_instance['title'] );
        $instance['height'] = strip_tags( $new_instance['height'] );
        $instance['width'] = strip_tags( $new_instance['width'] );
        $instance['class'] = strip_tags( $new_instance['class'] );
		
		return $instance;
	}

	/**
	 * Display the widget configuration form in the Widgets section of the Admin.
	 *
	 * @param array $instance Contains 'form_id' value set in $this->update() and used in $this->widget().
	 * @return void
	 *
	 * @see WP_Widget::form()
	 * @see: http://codex.wordpress.org/Widgets_API
	 *
	 * @since 1.1.0
	 */
	function form( $instance ) {
		/**
		 * Check the cache and/or call the API to get the array of forms.
		 */
		$dynamicContents = get_pardot_dynamic_content();
		if ( ! $dynamicContents ) {
			/**
			 * We have no forms!
			 * Create link text for the help link
			 */
			$help_link_text = __( 'Settings', 'pardot' );

			/**
			 * Merge the link and help text link into a help link.
			 */
			$help_link = sprintf( '<a href="%s" target="_blank">%s</a>', Pardot_Settings::get_admin_page_url(), $help_link_text );

			/**
			 * Create a variable for help text to be used in the HEREDOC
			 * Add the link into the help text.
			 */
			$help_text = __( 'You have no dynamic content yet, or there is a connection issue. Please check your %s.', 'pardot' );
			$help_text = sprintf( $help_text, $help_link );

			/**
			 * Finally create the HTML containing the help message about no forms.
			 */
			$html = "<p>{$help_text}</p>";

		} else {
			/**
			 * We DO have forms!
			 *
			 * If the instance hasn't been initialized via $this->update(), give it's 'form_id' element
			 * a value indicating no Pardot form has yet to be selected by an admin user.
			 */
			if ( ! isset( $instance['dynamicContent_id'] ) )
				$instance['dynamicContent_id'] = 0;

			/**
			 * Create an array to capture the HTML output into.
			 */
			$options = array();

			/**
			 * Give the zero (0) value meaning no form yet selected a default value.
			 */
			$label_option = (object) array( 'id' => 0, 'name' => __( ' Please Select Dynamic Content', 'pardot' ) );

			/**
			 * Insert the 'no form yet selected' value to the beginning of the list of options.
			 */
			array_unshift( $dynamicContents, $label_option );

			/**
			 * For each Pardot form that the current account has configured
			 */
			foreach ( $dynamicContents as $dynamicContent ) {
				/**
				 * For the selected value, assign $selected to be ' selected="selected"' for use in the <option> tag.
				 */
				$selected = selected( $instance['dynamicContent_id'], $dynamicContent->id, false );
				/**
				 * Add an array element containing the HTML for each option
				 */
				$options[] = "<option value=\"{$dynamicContent->id}\"{$selected}>{$dynamicContent->name}</option>";
			}

			/**
			 * Convert array of HTML options to a string of HTML options
			 */
			$options = implode( '', $options );

			/**
			 * Get the Form ID into a variable for HTML id that can be used in the HEREDOC
			 * This will leave dashes.
			 */
			$html_id = $this->get_field_id( 'dynamicContent_id' );

			/**
			 * Get the Form ID into a variable for HTML name that can be used in the HEREDOC
			 * This will convert dashes to underscores.
			 */
			$html_name = $this->get_field_name( 'dynamicContent_id' );

			/**
			 * Create a variable for prompting the user to be used in the HEREDOC
			 */
			$prompt = __( 'Select Dynamic Content:', 'pardot' );
						
			/**
			 * Create link to Settings Page
			 */
			$pardot_settings_url = admin_url( '/options-general.php?page=pardot' );
			$cache_text = __( '<strong>Not seeing something you added recently in Pardot?</strong> Please click the Clear Cache button on the %s.', 'pardot' );
			$cache_link = sprintf( '<a href="%s" target="_parent">%s</a>', $pardot_settings_url, 'Pardot Settings Page' );
			$cache_text = sprintf( $cache_text, $cache_link );

            /**
             * Create a variable for parameters helper text.
             */
            $param_text = __( 'Height and width should be in px or % (i.e. 250px or 90%).', 'pardot' );

			/**
			 * Create the HTML for displaying the select of Pardot forms
			 */
			$html = <<<HTML
<p><label for="{$html_id}">{$prompt}</label><select id="{$html_id}" name="{$html_name}" style="max-width:100%" class="js-chosen">{$options}</select></p>
HTML;
		}
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'New title', 'text_domain' );
		}
		
		$html .= '<p><label for="' . $this->get_field_id( "title" ) . '">' . __( 'Title:' ) . '</label><input class="widefat" id="' . $this->get_field_id( "title" ) . '" name="' . $this->get_field_name( "title" ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';

        if ( isset($param_text) ) {
            $html .= '<p><strong>Optional Parameters</strong><br/><small>' . $param_text . '</small></p>';
        }

        if ( isset( $instance[ 'height' ] ) ) {
            $height = $instance[ 'height' ];
        } else {
            $height = __( '', 'text_domain' );
        }

        $html .= '<p><label for="' . $this->get_field_id( "height" ) . '">' . __( 'Height:' ) . '</label><input id="' . $this->get_field_id( "height" ) . '" name="' . $this->get_field_name( "height" ) . '" type="text" value="' . esc_attr( $height ) . '" size="6" />';

        if ( isset( $instance[ 'width' ] ) ) {
            $width = $instance[ 'width' ];
        } else {
            $width = __( '', 'text_domain' );
        }

        $html .= '<label for="' . $this->get_field_id( "width" ) . '">' . __( 'Width:' ) . '</label><input id="' . $this->get_field_id( "width" ) . '" name="' . $this->get_field_name( "width" ) . '" type="text" value="' . esc_attr( $width ) . '" size="6" /></p>';

        if ( isset( $instance[ 'class' ] ) ) {
            $class = $instance[ 'class' ];
        } else {
            $class = __( '', 'text_domain' );
        }

        $html .= '<p><label for="' . $this->get_field_id( "class" ) . '">' . __( 'Class:' ) . '</label><input class="widefat" id="' . $this->get_field_id( "class" ) . '" name="' . $this->get_field_name( "class" ) . '" type="text" value="' . esc_attr( $class ) . '" /></p>';

        if ( isset($cache_text) ) {
			$html .= '<p><small>' . $cache_text . '</small></p>';
        }

		/**
		 * Display whatever HTML is appropriate; error message help or list of forms.
		 */
		echo $html;
	}

}
/**
 * Calls startup method that add actions and filters to hook WordPress for this widget.
 */
Pardot_Dynamic_Content_Widget::on_load();
