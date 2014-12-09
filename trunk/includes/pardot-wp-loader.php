<?php
/**
 * Finds the the full filepath for /wp-load.php in a ancestor directory.
 *
 * Walks up the parent directory stack to locate wp-load.php.
 * Used by files in plugins that need require() wp-load.php
 * but that might not be stored exactly where expected.
 *
 * Works as long as the wp-load.php is found by traversing parent directories.
 *
 * @return bool|string
 *
 * @author Mike Schinkel
 * @since 1.0.0
 */
function pardot_get_wp_load_filepath() {
	static $wp_load; // Since this will be called twice, hold onto it.
	if ( ! isset( $wp_load ) ) {
		$wp_load = false;
		$dir = __FILE__;
		while ( '/' != ( $dir = dirname( $dir ) ) ) {
			if ( file_exists( $wp_load = $filepath = "{$dir}/wp-load.php" ) ) {
				break;
			}
		}
		/**
		 * Since the function hasn't exited,
		 * look for the custom load file.
		 */
		if ( !$wp_load || !file_exists( $wp_load ) ) {
			$customfile = dirname( __FILE__ ) . '/pardot-custom-wp-load.php';
			if ( file_exists( $customfile ) ) {
				require($customfile);
				if ( defined('PARDOT_WP_LOAD') ) {
					$wp_load = PARDOT_WP_LOAD;
				}
			}
		}
	}
	return $wp_load;
}

/**
 * Check to see if wp-load.php can be found. Throw an error if not.
 *
 * Call pardot_get_wp_load_filepath() so as not to pollute
 * the global namespace with an accidental global variable.
 *
 */
if ( ! pardot_get_wp_load_filepath() ) {
	echo 'ERROR: /wp-load.php not found.';
	die();
}

/**
 * Require wp-load.php in the global namespace.
 */
require( pardot_get_wp_load_filepath() );