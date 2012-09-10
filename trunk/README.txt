=== Pardot ===
Contributors: NewClarity, MikeSchinkel, cliffseal
Donate link: http://pardot.com
Tags: pardot, marketing automation, forms, tracking, web tracking
Requires at least: 3.4
Tested up to: 3.4.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Pardot with WordPress: easily track visitors, embed forms in pages and posts, or use the forms widget.

== Description ==

Say hello to marketing automation simplicity! With a single login, your self-hosted WordPress installation will be securely connected with Pardot. With the selection of your campaign, you'll be able to track visitors and work with forms without touching a single line of code. You can use the widget to place a form anywhere a sidebar appears, or embed them in a page or post using a shortcode or the Pardot button on the Visual Editor's toolbar.

== Installation ==

1. Upload `pardot-for-wordpress` to your `/wp-content/plugins/` directory or go to Plugins > Add New in your WordPress Admin area and search for Pardot.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings > Pardot Settings to put in your email, password, and user key.
1. Select your campaign (for tracking code usage).

== Frequently Asked Questions ==

= How do I change my campaign? =

Simply choose another campaign in Settings > Pardot Settings and click 'Save Settings'.

= Some of my form is cut off. What should I do? =

Since every WordPress theme is different, embedded forms won't always automatically fit. You'll want to make a Pardot Layout Template specifically for your WordPress theme:

1. Go to <a href="https://pi.pardot.com/form" target="_blank">Forms</a> in Pardot. Find and edit the form that needs updating.
1. Click ahead to the 'Look and Feel' step of the wizard and select the 'Styles' tab.
1. Set 'Label Alignment' to 'Above' and click 'Confirm and Save.'.
1. Click the link to the layout template being used by the form.
1. Edit the layout template and add the following to the <head> section of the template:
`<style type="text/css">
	#pardot-form input.text, #pardot-form textarea {
		width: 150px;
	}
</style>`

A width of 150px is just a starting point. Adjust this value until it fits on your page.

1. Add additional styles as you see fit. For styling help, reference our <a href="http://www.pardot.com/help/faqs/forms/basic-css-for-forms" target="_blank">Basic CSS for Forms</a> page.

== Screenshots ==

1. Settings area
1. Pardot button in the Visual Editor toolbar
1. Choose from any form
1. Use forms in a widget
1. A form widget (with corrected styling)
1. A page can have two forms! Here, one is in the body and one in a widget.

== Changelog ==

= 1.0.1 =
Fix bug with form order in content

= 1.0 =
Initial release.
