<?php
/*
 * Plugin Name: Pardot
 * Description: Connect your WordPress to Pardot with shortcode and widgets for campaign tracking, quick form access, and dynamic content.
 * Author: Pardot
 * Author URI: http://www.pardot.com
 * Plugin URI: http://wordpress.org/extend/plugins/pardot/
 * Developer: Pardot
 * Developer URI: http://pardot.com
 * Version: 1.5.5
 * License: GPLv2
 *
 * Copyright 2021 Pardot LLC
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
 *
 */

define( 'PARDOT_PLUGIN_FILE', __FILE__ );
define( 'PARDOT_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'PARDOT_PLUGIN_VER', '1.5.5' );

if ( ! defined( 'PARDOT_FORM_INCLUDE_TYPE' ) ) {
	define( 'PARDOT_FORM_INCLUDE_TYPE', 'iframe' );	// iframe or inline
}

if ( ! defined( 'PARDOT_API_CACHE_TIMEOUT' ) ) {
	define( 'PARDOT_API_CACHE_TIMEOUT', MONTH_IN_SECONDS );
}

if ( ! defined( 'PARDOT_WIDGET_FORM_CACHE_TIMEOUT' ) ) {
	define( 'PARDOT_WIDGET_FORM_CACHE_TIMEOUT', MONTH_IN_SECONDS );
}

if ( ! defined( 'PARDOT_JS_CACHE_TIMEOUT' ) ) {
	define( 'PARDOT_JS_CACHE_TIMEOUT', MONTH_IN_SECONDS );
}


require( PARDOT_PLUGIN_DIR . '/includes/pardot-api-class.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-api-functions.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-forms-shortcode-popup-class.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-plugin-class.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-crypto.php');
require( PARDOT_PLUGIN_DIR . '/includes/pardot-settings-class.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-forms-widget-class.php' );
require( PARDOT_PLUGIN_DIR . '/includes/pardot-template-tags.php' );
