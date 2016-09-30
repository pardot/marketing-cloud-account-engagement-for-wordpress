<?php

if ( !function_exists('has_shortcode') ) {
    function has_shortcode( $content, $tag ) {
        if ( shortcode_exists( $tag ) ) {
            preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
            if ( empty( $matches ) )
                return false;

            foreach ( $matches as $shortcode ) {
                if ( $tag === $shortcode[2] )
                    return true;
            }
        }
        return false;
    }
}

if ( !function_exists('shortcode_exists') ) {
    function shortcode_exists( $tag ) {
        global $shortcode_tags;
        return array_key_exists( $tag, $shortcode_tags );
    }
}

/**
 * Template Tag for Pardot Javascript Tracking Code.
 *
 * This function is a WordPress template tag that can be used in a theme
 * to add the Javascript needed to Pardot enable a website. If called before
 * the 'wp_footer' it won't be done twice. If you want to use after the
 * 'wp_footer' hook you need to call remove_pardot_wp_footer() before
 * the 'wp_footer' hook is called, such as in an 'init' hook.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @since 1.0.0
 * @return void
 *
 */
function the_pardot_tracking_js() {
	static $done = false;
	if ( ! $done ) {
		/**
		 * Only do this once.
		 */
		echo get_pardot_tracking_js();
	}
	$done = true;
}

function pardot_dc_async_script() {
    static $done = false;
    if ( ! $done && get_post() && has_shortcode(get_the_content(get_the_ID()),'pardot-dynamic-content') ) {
        wp_register_script( 'pddc', plugins_url( 'js/asyncdc.min.js' , dirname(__FILE__) ), array('jquery'), false, true);
        wp_enqueue_script( 'pddc' );
    }
    $done = true;
}

/**
 * Used to remove the 'wp_footer' hook that automatically adds the Pardot Javascript.
 *
 * This could be done in an 'init' action or anytime prior to 'wp_footer' firing at
 * priority 10.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @since 1.0.0
 */
function remove_pardot_wp_footer() {
	remove_action( 'wp_footer', array( Pardot_Plugin::self(), 'wp_footer' ) );
}
/**
 *
 * @return string Javascript that uses Pardot to track a website.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @since 1.0.0
 */
function get_pardot_tracking_js() {
	$html = false;
	$campaign = Pardot_Settings::get_setting( 'campaign' );
	if ( $campaign ) {
		$tracking_code_template = get_transient( 'pardot_tracking_code_template' );
		if ( ! $tracking_code_template ) {
			$account = get_pardot_account();
			if ( isset( $account->tracking_code_template ) ) {
				$tracking_code_template = $account->tracking_code_template;
				set_transient( 'pardot_tracking_code_template', $tracking_code_template, PARDOT_JS_CACHE_TIMEOUT );
			}
		}
		/**
		 * The value to substitute should be the ID of the campaign plus 1000.
		 */
		$tracking_code_template = str_replace( '%%CAMPAIGN_ID%%', $campaign+1000, $tracking_code_template );
		if ( $tracking_code_template ) {
            $campaign = $campaign + 1000;
			$html =<<<HTML
<script type="text/javascript">
<!--
piCId = '{$campaign}';
{$tracking_code_template}
-->
</script>
HTML;
		}
	}
	return $html;
}
