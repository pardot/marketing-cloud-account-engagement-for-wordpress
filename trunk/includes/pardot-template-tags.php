<?php
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
			$html =<<<HTML
<script type="text/javascript">
<!--
{$tracking_code_template}
-->
</script>
HTML;
		}
	}
	return $html;
}
