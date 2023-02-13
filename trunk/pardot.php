<?php
/*
 * Plugin Name: Pardot
 * Description: Connect your WordPress to Pardot with shortcode and widgets for campaign tracking, quick form access, and dynamic content.
 * Author: Salesforce
 * Author URI: https://www.salesforce.com/products/marketing-cloud/marketing-automation/
 * Plugin URI: http://wordpress.org/extend/plugins/pardot/
 * Developer: Salesforce
 * Developer URI: https://www.salesforce.com/products/marketing-cloud/marketing-automation/
 * Version: 2.0.0
 * License: GPLv2
 *
 * Copyright 2022 Salesforce, Inc.
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
define( 'PARDOT_PLUGIN_VER', '2.0.0' );

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

function pardot_init() {
    $dir = dirname( __FILE__ );

    $script_asset_path = "$dir/build/index.asset.php";
    if ( ! file_exists( $script_asset_path ) ) {
        throw new Error(
            'You need to run `npm start` or `npm run build` for the "create-block/pardot" block first.'
        );
    }
    $index_js = 'build/index.js';
    $script_asset = require( $script_asset_path );
    array_push($script_asset['dependencies'], 'wp-api');
    wp_register_script(
        'pardot-editor',
        plugins_url( $index_js, __FILE__ ),
        $script_asset['dependencies'],
        $script_asset['version']
    );

    $editor_css = 'build/index.css';
    wp_register_style(
        'pardot-editor',
        plugins_url( $editor_css, __FILE__ ),
        array(),
        filemtime( "$dir/$editor_css" )
    );

    $style_css = 'build/style-index.css';
    wp_register_style(
        'pardot',
        plugins_url( $style_css, __FILE__ ),
        array(),
        filemtime( "$dir/$style_css" )
    );

    wp_localize_script( 'build/index.js', 'ajaxurl', admin_url( 'includes/admin-ajax.php' ));

    register_block_type( 'pardot/form', array(
        'editor_script'   => 'pardot-editor',
        'editor_style'    => 'pardot-editor',
        'style'           => 'pardot',
        'render_callback' => 'pardot_form_block_callback',
        'attributes'  => array(
            'form_id'  => array(
                'type'  => 'string',
                'default' => '',
            ),
            'height'    => array(
                'type'  => 'string',
                'default'   => '',
            ),
            'width'    => array(
                'type'  => 'string',
                'default'   => '',
            ),
            'className'    => array(
                'type'  => 'string',
                'default'   => '',
            ),
            'title'    => array(
                'type'  => 'string',
                'default'   => '',
            ),
        ),
    ) );

    register_block_type( 'pardot/dynamic-content', array(
        'editor_script'   => 'pardot-editor',
        'editor_style'    => 'pardot-editor',
        'style'           => 'pardot',
        'render_callback' => 'pardot_dynamic_content_block_callback',
        'attributes' => array(
            'dynamicContent_id' => array(
                'type' => 'string',
                'default' => '',
            ),
            'dynamicContent_default' => array(
                'type' => 'string',
                'default' => '',
            ),
            'height' => array(
                'type'  => 'string',
                'default' => '',
            ),
            'width' => array(
                'type'  => 'string',
                'default' => '',
            ),
            'className' => array(
                'type'  => 'string',
                'default' => '',
            ),
        ),
    ) );
}


add_action( 'init', 'pardot_init' );

function pardot_form_block_callback($attributes) {
    if (isset($attributes['form_id'])) {
        $attributes['class'] = $attributes['className'];
        unset($attributes['className']);
        return Pardot_Plugin::get_form_body($attributes);
    }
    return '';
}

function pardot_dynamic_content_block_callback($attributes) {
    if (isset($attributes['dynamicContent_id'])) {
        $attributes['class'] = $attributes['className'];
        unset($attributes['className']);
        return Pardot_Plugin::get_dynamic_content_body($attributes);
    }
    return '';
}