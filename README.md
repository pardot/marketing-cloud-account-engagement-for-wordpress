# Pardot Plugin for WordPress #
Integrate Pardot with WordPress: easily track visitors, embed forms in pages and posts, or use the forms widget.

## Description ##

Say hello to marketing automation simplicity! With a single login, your self-hosted WordPress installation will be securely connected with Pardot. With the selection of your campaign, you'll be able to track visitors and work with forms without touching a single line of code. You can use the widget to place a form anywhere a sidebar appears, or embed them in a page or post using a shortcode or the Pardot button on the Visual Editor's toolbar.

## Installation ##

1. Upload `pardot-for-wordpress` to your `/wp-content/plugins/` directory or go to Plugins > Add New in your WordPress Admin area and search for Pardot.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings > Pardot Settings to put in your email, password, and user key.
1. Select your campaign (for tracking code usage).

## Frequently Asked Questions ##

### How do I change my campaign? ###

Simply choose another campaign in Settings > Pardot Settings and click 'Save Settings'.

### Some of my form is cut off. What should I do? ###

Since every WordPress theme is different, embedded forms won't always fit. We've written a <a href="http://www.pardot.com/help/faqs/add-ons/wordpress-plugin" target="_blank">Knowledge Base article</a> to help you with this, but you'll want to make a Layout Template specifically for your WordPress theme:

1. Find out the width of the area in which you're embedding the form. You can do this using developer tools in your browser, like <a href="http://getfirebug.com" target="_blank">Firebug</a>.
1. Go to <a href="https://pi.pardot.com/layoutTemplate" target="_blank">Layout Templates</a> in Pardot and click "+Create new layout template".
1. In the Layout tab, add styling in the `<head>` section of your template to suit your WordPress template. Once complete, it would look something like this, where your area is about 100px wide:

```html
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<meta name="description" content="%%description%%"/>
		<title>%%title%%</title>
		<style type="text/css">
			form.form input.text,  form.form textarea.standard {
				width: 100px !important;
			}
			form.form p {
				margin: 3px;
			}
			form.form p.submit input {
				float: left;
				margin: 3px;
			}
			form.form p label {
				text-align: left;
				width: auto !important;
			}
			form.form .submit {
				display: inline;
			}
		</style>
	</head>
	<body>
		%%content%%
	</body>
</html>
```

You might have to add `!important` as above to override some of the CSS.

1. Create your new form or use an existing form, and change the Layout Template (under 'Look and Feel') to your new one. Make sure you save!
1. Add the form and check the styling; tweak as needed.

## Changelog ##

### 1.0.1 ###
Fix bug with form order in content

### 1.0 ###
Initial release.