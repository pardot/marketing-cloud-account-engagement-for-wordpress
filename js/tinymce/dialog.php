<?php
require_once("../../../../../wp-config.php");
require_once('../../pardot-api.php');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Embed Pardot Forms</title>
    <script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>   	
	<script type="text/javascript" src="js/tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/forms.js"></script>
    <style type="text/css">
	h2 {
		font-size: 12px;
		color: #000000;
		padding:10px 0;
	}
	.mceActionPanel {
		margin-top:20px;
	}
	.checkout_page{
		margin:5px 5px 0 10px;
	}
    </style>
    
</head>
<body>
<?php

if (get_pardot_api_key()) {

	$forms = get_pardot_forms();

	if ($forms) {
?>    
	<form onsubmit="pardotbnDialog.insert();return false;" action="#">
		<table width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td>Select Form:</td>
				<td>
					<select id="shortcode" name="shortcode">
<?php
	foreach ($forms as $form) {
		echo ('<option value="[pardot-form id=\'' . $form->id . '\' title=\'' . $form->name . '\']">' . $form->name . '</option>');
	}
?>					
					</select>
				</td>
			</tr>
		</table>
		<div class="mceActionPanel">
			<div style="float: left">
				<input type="button" id="insert" name="insert" value="{#insert}" onclick="pardotbnDialog.insert();" />
			</div>
			<div style="float: right">
				<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
			</div>
		</div>
	</form>
<?php
	} else {
?>
		<p>It looks like you don't have any forms set up yet. <br/>
		Visit the <a onclick="self.parent.location.href = 'https://pi.pardot.com/form';tinyMCEPopup.close();" href="#">Forms sections</a> in Pardot to create one!</p>
		<div class="mceActionPanel">
			<div style="float: right">
				<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
			</div>
		</div>
<?php	
	}
} else {
?>
		<p>It looks like your account isn't connected: <br/>
		Please check and save your information on the <a onclick="self.parent.location.href = '/wp-admin/options-general.php?page=pardot';tinyMCEPopup.close();" href="#">Settings page</a>.</p>
		<div class="mceActionPanel">
			<div style="float: right">
				<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
			</div>
		</div>
<?php
}
?>
</body>
</html>
<?php
?>