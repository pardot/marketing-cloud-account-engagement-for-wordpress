(function(){
	tinymce.create('tinymce.plugins.pardotbnPlugin', {
	init: function(ed,url) {
	ed.addCommand('mcepardotbn', function() {
	ed.windowManager.open({
	file:url+'/dialog.php',
	width:420+parseInt(ed.getLang('pardotbn.delta_width',0)),
	height:100+parseInt(ed.getLang('pardotbn.delta_height',0)),
	inline:1
	},{
	plugin_url: url
	})
	});
	ed.addButton('pardotbn',{title:'Add Pardot Shortcode', cmd:'mcepardotbn', image:url+'/img/pardotbn.png'});
	ed.onNodeChange.add(function(ed,cm,n){cm.setActive('prbn',n.nodeName=='IMG')})},
	createControl:function(n,cm){return null},
	getInfo:function(){return{longname:'pardotbn plugin',
	author:'Pardot',
	authorurl:'http://tinymce.moxiecode.com',
	infourl:'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/pardotbn',version:"1.0"}}});
	tinymce.PluginManager.add('pardotbn',tinymce.plugins.pardotbnPlugin)})
();