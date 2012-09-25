<?php
/**
 * Functions to simplify calling the Pardot API.
 *
 * These functions call Pardot's API via Pardot_Plugin class which has a private static api property. These functions
 * will use an instantiated API if already exists for the current page load or it will create one. Also it will grab
 * the user, password, user_key and api_key from 'pardot_setttings' in wp_options or it will use auth if passed via
 * an $args parameter.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @since 1.0.0
 */
/**
 * Returns an API key via the Pardot API as implemented in the Pardot_Plugin class.
 *
 * @param array $args
 * @return Pardot_API
 *
 * @since 1.0.0
 */
function get_pardot_api_key( $args = array() ) {
	return Pardot_Plugin::get_api_key( $args );
}

/**
 * Returns an account object via the Pardot API as implemented in the Pardot_Plugin class.
 *
 * @param array $args
 * @return Pardot_API
 *
 * @since 1.0.0
 */
function get_pardot_account( $args = array() ) {
	return Pardot_Plugin::get_account( $args );
}

/**
 * Returns an array of campaigns via the Pardot API as implemented in the Pardot_Plugin class.
 *
 * @param array $args
 * @return Pardot_API
 *
 * @since 1.0.0
 */
function get_pardot_campaigns( $args = array() ) {
	return Pardot_Plugin::get_campaigns( $args );
}

/**
 * Returns an array of forms via the Pardot API as implemented in the Pardot_Plugin class.
 *
 * @param array $args
 * @return Pardot_API
 *
 * @since 1.0.0
 */
function get_pardot_forms( $args = array() ) {
	return Pardot_Plugin::get_forms( $args );
}

/**
 * Returns an array of dynamic content via the Pardot API as implemented in the Pardot_Plugin class.
 *
 * @param array $args
 * @return Pardot_API
 *
 * @since 1.1.0
 */
function get_pardot_dynamic_content( $args = array() ) {
	return Pardot_Plugin::get_dynamicContent( $args );
}

