tinyMCEPopup.requireLangPack();

var pardotbnDialog = {
	init: function() {},
	insert : function() {
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, jQuery('#shortcode').val());
		tinyMCEPopup.close();
	}
};
tinyMCEPopup.onInit.add(pardotbnDialog.init, pardotbnDialog);
