# Pardot Plugin for WordPress #
Integrate Pardot with WordPress: easily track visitors, embed forms and dynamic content in pages and posts, or use the forms or dynamic content widgets.

## Description ##

Say hello to marketing automation simplicity! With a single login, your self-hosted WordPress installation will be securely connected with Pardot. With the selection of your campaign, you'll be able to track visitors and work with forms and dynamic content without touching a single line of code. You can use the widget to place a form or dynamic content anywhere a sidebar appears, or embed them in a page or post using a shortcode or the Pardot button on the Visual Editor's toolbar.

## Installation ##

1. Upload `pardot-for-wordpress` to your `/wp-content/plugins/` directory or go to Plugins > Add New in your WordPress Admin area and search for Pardot.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings > Pardot Settings to put in your email, password, and user key.
1. Select your campaign (for tracking code usage).

## Frequently Asked Questions ##

### How do I add the tracking code? ###

Once you add your credentials to the Settings page and click "Save Settings" (which authenticates you), a dropdown of your campaigns will appear. Select the one you want to use for your tracking code, and click "Save Settings" again. The tracking code will automatically be added to the footer of every page.

### Why isn't the tracking code appearing on some or any of my pages? ###

Make sure you've authenticated successfully, first of all, then make sure you've selected a campaign on the Settings page and clicked "Save Settings".

If you've done this, it may be that your current template isn't coded well. This plugin hooks into the `wp_footer` action, which *should* be called every time a non-admin page loads. If you don't see this anywhere in your theme (a common place is your theme's `footer.php` file), you'll need to add `<?php wp_footer(); ?>` appropriately.

### How can I use the shortcodes without the Visual Editor? ###

Two simple shortcodes are available for use.

#### Form Shortcode ####

`[pardot-form id="{Form ID}" title="{Form Name}" class="" width="100%" height="500"]`

Use `[pardot-form]` with at least the `id` parameter. For instance, `[pardot-form id="1" title="Title"]` renders my Pardot form with an ID of 1.

Optional parameters:

The `title` parameter is included when using the toolbar button, but it's not required for display. There is no default.

The `class` parameter allows you to add additional classes to the iframe element. There is no default, but the class `pardotform` is now automatically added, regardless of any additional classes.

The `width` parameter will set the width of the iframe in pixels or percentage. For example, "500", "500px", and "80%" are all valid. The default is 100%.

The `height` parameter will set the height of the iframe in pixels only. For example, "500" or "500px" are valid. The default is 500px.

#### Dynamic Content Shortcode ####

`[pardot-dynamic-content id="{Dynamic Content ID}" height="{height in px/%}" width="{width in px/%}" class="{additional classes}"]`

Use `[pardot-dynamic-content]` with at least the `id` parameter.

The `default` parameter is used for accessibility. Whatever is placed here is wrapped in `<noscript>` tags and is shown only to users who have JavaScript disabled. By default, it will automatically be your "Default Content" as designated in Pardot. So, 

`[pardot-dynamic-content id="1"]` 

would render something like:

`<div data-dc-url="http://go.pardot.com/dcjs/99999/99/dc.js" style="height:auto;width:auto;" class="pardotdc">My default content.</div>`

...which would show the dynamic content to users with JavaScript enabled, and 'My default content' to users with it disabled.

The `class` parameter allows you to add additional classes to the dynamic content element. There is no default, but the class `pardotdc` is now automatically added, regardless of any additional classes.

The `width` parameter will set the width of the element in pixels or percentage. For example, "500px" and "80%" are valid. The default is "auto".

The `height` parameter will set the height of the element in pixels or percentage. For example, "500px" and "80%" are valid. The default is "auto".

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

### 1.3.10 ###

Improve WordPress 3.9 compatibility (Tiny popup titles; update Chosen)

### 1.3.9 ###

Fixes a small bug with a JS library being called in the wrong place

### 1.3.8 ###

1. Add Chosen selector to forms and dynamic content
2. Fix async DC bug
3. Enchance password authentication encoding

### 1.3.7 ###

1. Add Chosen selector to campaign settings
1. Fix authentication issue

### 1.3.6 ###

1. Adds support for 400+ campaigns, form, and dynamic content blocks
1. Updates branding

### 1.3.5 ###

1. Fixed a bug where pardotform class might be applied to closing iframe tag (thanks palpatine1976!)
1. Optimize code to remove some debug messages
1. Improve campaign retrieval for over 200 campaigns

### 1.3.4 ###

Fixed a bug where tracking code might show the wrong ID.

### 1.3.3 ###

1. Accounts for a minor API change in the tracking code
1. Adds support for 200+ campaigns

### 1.3.1 ###

Fixed a bug with `shortcode_exists` fatal error

### 1.3.1 ###

Fixed a bug with `has_shortcode` fatal error

### 1.3 ###

1. Use new asynchronous loading for Dynamic Content

### 1.2 ###

1. Added ability to specify height, width, and class on the form
1. Added class 'pardotform' to every iframe for easier styling

### 1.1.5 ###

1. Add some helpful links to the Reset Cache button
2. Minor UI tweaks
3. Updated the Pardot logos
4. Updated screenshots for 3.5

### 1.1.4 ###
1. Fix TinyMCE modal bug when no forms or dynamic content is present
1. Support for 200+ forms and dynamic content items
1. Other minor checks

### 1.1.3 ###
Checks for mcrypt and falls back safely if not (fixes blank admin screen bug)

### 1.1.2 ###
1. Clear cache when resetting all settings
1. Be more forgiving with login whitespace
1. Make some security improvements

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
