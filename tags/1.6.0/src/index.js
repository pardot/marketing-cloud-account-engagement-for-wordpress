/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
import { registerBlockType } from '@wordpress/blocks';

import { createElement } from '@wordpress/element';

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import form_edit from './form/form-edit';
import dyn_con_edit from './dynamic-content/dynamic-content-edit';

/**
 * Block Icon
 */
const icon = createElement('svg', {width: 20, height: 20, viewBox: "0 0 1134 794", color: '#00a1e0'},
	createElement('path', {d: "m 390.31413,791.44528 c -57.22116,-8.34611 -108.20263,-40.62332 -140.41938,-88.90184 -5.85555,-8.77486 -16.77138,-29.57415 -20.14041,-38.37606 -1.35759,-3.54684 -2.01243,-4.22024 -3.61063,-3.71299 -1.07375,0.34079 -6.83284,1.26919 -12.79799,2.06309 -51.1195,6.80356 -104.40135,-8.72283 -144.27729,-42.04256 C 1.7126511,564.19345 -18.762584,469.68157 19.142837,390.02279 33.618333,359.60233 56.689021,332.82019 84.548938,314.09462 l 10.048938,-6.75423 -3.870458,-10.30844 C 85.989271,284.41254 81.662189,268.17098 79.286283,254.08802 76.797585,239.3365 76.823603,199.90597 79.331512,185.54295 87.697132,137.63229 107.89776,98.139531 140.99983,64.979793 175.08882,30.831406 217.43841,9.608433 266,2.3374961 c 15.80889,-2.36700334 46.41,-2.34908143 61.96435,0.03629 37.79359,5.7959173 71.16793,19.6391359 100.8803,41.8437629 9.53748,7.127548 32.54486,29.150439 39.36281,37.678482 l 3.51452,4.396031 10.88901,-9.747441 C 524.83103,38.750799 580.04046,20.473371 635.14405,26.047599 673.63101,29.940904 710.7035,45.23528 741,69.718809 c 15.85915,12.816265 34.61263,34.278681 45.46393,52.031191 2.10119,3.4375 4.19467,6.25 4.65218,6.25 0.4575,0 4.10604,-1.29665 8.10786,-2.88145 17.07497,-6.76201 34.30563,-11.45504 54.27603,-14.7829 15.6218,-2.60321 56.27908,-2.61259 72,-0.0166 45.16663,7.45831 82.7689,24.10735 117.5,52.02513 18.9201,15.20851 42.0889,41.7654 54.7873,62.79921 44.3682,73.49205 47.8161,165.79983 9.0295,241.73377 -36.7311,71.90961 -106.2013,120.99434 -186.3168,131.64362 -20.9993,2.79131 -56.5756,1.81412 -76.09723,-2.09021 -2.76191,-0.55238 -3.21416,-0.10813 -8.90104,8.74375 -17.4147,27.10674 -41.21216,48.46761 -70.00173,62.83434 -15.81289,7.89104 -30.07957,12.83919 -46.66621,16.18537 -9.76125,1.96924 -14.38624,2.28648 -33.33379,2.28648 -19.00984,0 -23.55684,-0.31391 -33.4544,-2.30955 -13.07417,-2.63615 -27.61777,-7.08054 -36.93392,-11.28668 C 611.59811,671.29792 608.36212,670 607.9206,670 c -0.44152,0 -3.25356,4.8755 -6.24899,10.83444 -20.32115,40.4259 -53.05396,72.81242 -93.17161,92.18591 -19.87231,9.59668 -39.02405,15.44045 -60.35185,18.41516 -13.75384,1.91833 -44.71303,1.92356 -57.83402,0.01 z"})
);

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
registerBlockType('pardot/form', {
	/**
	 * This is the display title for your block, which can be translated with `i18n` functions.
	 * The block inserter will show this name.
	 */
	title: __('Pardot Form', 'pardot'),

	/**
	 * This is a short description for your block, can be translated with `i18n` functions.
	 * It will be shown in the Block Tab in the Settings Sidebar.
	 */
	description: __(
		'Collect information about people visiting your site or landing page and help you turn anonymous visitors into identified prospects.',
		'pardot'
	),

	/**
	 * Blocks are grouped into categories to help users browse and discover them.
	 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
	 */
	category: 'embed',

	/**
	 * An icon property should be specified to make it easier to identify a block.
	 * These can be any of WordPress’ Dashicons, or a custom svg element.
	 */
	icon: icon,

	/**
	 * Optional block extended support features.
	 */
	supports: {
		// Removes support for an HTML mode.
		html: false,
	},

	attributes: {
		form_id: {
			type: 'string',
			default: '',
		},
		height: {
			type: 'string',
			default: '',
		},
		width:{
			type: 'string',
			default: '',
		},
		className:{
			type: 'string',
			default: '',
		},
		title:{
			type: 'string',
			default: '',
		},
	},

	/**
	 * @see ./edit.js
	 */
	edit: form_edit,

	save: function(props) {
		return null;
	}
});

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
registerBlockType('pardot/dynamic-content', {
	/**
	 * This is the display title for your block, which can be translated with `i18n` functions.
	 * The block inserter will show this name.
	 */
	title: __('Pardot Dynamic Content', 'pardot'),

	/**
	 * This is a short description for your block, can be translated with `i18n` functions.
	 * It will be shown in the Block Tab in the Settings Sidebar.
	 */
	description: __(
		'Delivers targeted messaging to prospects. Content is displayed according to rules defined in Pardot based on the prospect’s data or attributes.',
		'pardot'
	),

	/**
	 * Blocks are grouped into categories to help users browse and discover them.
	 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
	 */
	category: 'embed',

	/**
	 * An icon property should be specified to make it easier to identify a block.
	 * These can be any of WordPress’ Dashicons, or a custom svg element.
	 */
	icon: icon,

	/**
	 * Optional block extended support features.
	 */
	supports: {
		// Removes support for an HTML mode.
		html: false,
	},

	attributes: {
		dynamicContent_id: {
			type: 'string',
			default: '',
		},
		dynamicContent_default: {
			type: 'string',
			default: '',
		},
		height: {
			type: 'string',
			default: '',
		},
		width:{
			type: 'string',
			default: '',
		},
		className:{
			type: 'string',
			default: '',
		},
	},

	/**
	 * @see ./edit.js
	 */
	edit: dyn_con_edit,

	save: function(props) {
		return null;
	}
});

