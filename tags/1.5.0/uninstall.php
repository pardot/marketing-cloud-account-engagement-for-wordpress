<?php
/**
 * Remove the 'pardot_settings' entry from wp-options when plugin is
 *
 * @author Mike Schinkel
 * @since 1.0.0
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

/**
 * Delete Pardot Settings when plugin is uninstalled
 */
delete_option( 'pardot_settings' );
delete_option( 'pardot_crypto_key' );
