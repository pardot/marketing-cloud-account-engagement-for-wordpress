/*
TinyMCE Button for Pardot WordPress Plugin
See: http://www.tinymce.com/wiki.php/Creating_a_plugin
Author: Mike Schinkel <mike@newclarity.net>
*/
(function () {

	tinymce.create( 'tinymce.plugins.PardotFormsShortcodeInsert', {

		init : function( ed, url ) {

			ed.addButton( 'pardotformsshortcodeinsert', {
				title   : 'Pardot',
				image   : PardotShortcodePopup.tinymce_button_url,
				classes : 'pardot-tinymce-button',
				onClick : function() {
					tb_show( 'Pardot', '#TB_inline?width=400&inlineId=pardot-forms-shortcode-popup' );
				}
			});
		},

		createControl : function( n, cm ) {
			return null
		},

		getInfo : function() {
			return {
				longname  : 'Pardot Form or Dynamic Content Shortcode Insert Button',
				author    : 'Pardot',
				authorurl : 'http://www.pardot.com',
				infourl   : 'http://wordpress.org/extend/plugins/pardot',
				version   : '1.0'
			}
		}
	});

	tinymce.PluginManager.add( 'pardotformsshortcodeinsert', tinymce.plugins.PardotFormsShortcodeInsert );
})();