/*
TinyMCE Button for Pardot WordPress Plugin
See: http://www.tinymce.com/wiki.php/Creating_a_plugin
Author: Mike Schinkel <mike@newclarity.net>
*/
(function () {
	tinymce.create('tinymce.plugins.PardotFormsShortcodeInsert', {
		init:function(ed,url) {
			ed.addCommand('mcePardotFormsShortcodeInsert',function() {
				ed.windowManager.open({
					file:url + '/pardot-forms-shortcode-popup.php',
					width:420 + parseInt(ed.getLang('pardotformsshortcodeinsert.delta_width', 0)),
					height:570 + parseInt(ed.getLang('pardotformsshortcodeinsert.delta_height', 0)),
					inline:1
				},{
					plugin_url:url
				})
			});
			ed.addButton('pardotformsshortcodeinsert',{
				title:'Pardot',
				cmd:'mcePardotFormsShortcodeInsert',
				image:url+"/img/pardot-button.png"}
			);
		},
		createControl:function(n,cm) {
			return null
		},
		getInfo:function() {
			return {
				longname:"Pardot Form or Dynamic Content Shortcode Insert Button",
				author:"Pardot",
				authorurl:"http://www.pardot.com",
				infourl:"http://wordpress.org/extend/plugins/pardot",
				version:"1.0"
			}
		}});
	tinymce.PluginManager.add('pardotformsshortcodeinsert',tinymce.plugins.PardotFormsShortcodeInsert)
})();
