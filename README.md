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

### How can I use the shortcodes without the Visual Editor? ###

Two simple shortcodes are available for use.

#### Form Shortcode ####

`[pardot-form id="{Form ID}" title="{Form Name}"]`

Use `[pardot-form]` with at least the `id` parameter. You can also include the `title` parameter that is included when using the toolbar button, but it's not required for display. For instance, `[pardot-form id="1" title="Title"]` renders my Pardot form with an ID of 1.

#### Dynamic Content Shortcode ####

`[pardot-dynamic-content id="{Dynamic Content ID}" default="{Non-JavaScript Content}"]`

Use `[pardot-dynamic-content]` with at least the `id` parameter.

The `default` parameter is used for accessibility. Whatever is placed here is wrapped in `<noscript>` tags and is shown only to users who have JavaScript disabled. By default, it will automatically be your "Default Content" as designated in Pardot. So, 

`[pardot-dynamic-content id="1" default="My default content."]` 

would render something like:

`<script type="text/javascript" src="http://go.pardot.com/dcjs/99999/99/dc.js"></script><noscript>My default content.</noscript>`

...which would show the dynamic content to users with JavaScript enabled, and 'My default content' to users with it disabled. Note that, due to the way the WordPress Visual Editor works, HTML tags for the parameter will be URL encoded to avoid strange formatting.

### How do I change my campaign? ###

Simply choose another campaign in Settings > Pardot Settings and click 'Save Settings'.

### Some of my form is cut off. What should I do? ###

Since every WordPress theme is different, embedded forms won't always automatically fit. You'll want to make a Pardot Layout Template specifically for your WordPress theme:

1. Go to <a href="https://pi.pardot.com/form" target="_blank">Forms</a> in Pardot. Find and edit the form that needs updating.
1. Click ahead to the 'Look and Feel' step of the wizard and select the 'Styles' tab.
1. Set 'Label Alignment' to 'Above' and click 'Confirm and Save.'.
1. Click the link to the layout template being used by the form.
1. Edit the layout template and add the following to the `<head>` section of the template:

```html
<style type="text/css">
	#pardot-form input.text, #pardot-form textarea {
		width: 150px;
	}
</style>
```
A width of 150px is just a starting point. Adjust this value until it fits on your page and add additional styles as you see fit. For styling help, reference our <a href="http://www.pardot.com/help/faqs/forms/basic-css-for-forms" target="_blank">Basic CSS for Forms</a> page.

### I just added a form or dynamic content, and it's not showing up to select it yet. ###

Go to Settings > Pardot Settings and click 'Reset Cache'. This should reinitialize and update your Pardot content.

## Changelog ##

### 1.1.1 ###
Make `<noscript>` default to Default Pardot Content

### 1.1.0 ###
1. Added dynamic content shortcodes
1. Added title field to form widget
1. Added 'Reset Cache' option

### 1.0.3 ###
Added form caching for faster rendering and less requests

### 1.0.2 ###
1. Fix a caching issue that was causing the most recently-used form to render on all posts/pages
1. Extended API cache timeout

### 1.0.1 ###
Fix bug with form order in content

### 1.0 ###
Initial release.