=== Pardot ===
Contributors: cliffseal
Donate link: http://pardot.com
Tags: pardot, marketing automation, forms, dynamic content, tracking, web tracking
Requires at least: 4.6
Tested up to: 4.7
Stable tag: 1.4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Pardot with WordPress: easily track visitors, embed forms and dynamic content in pages and posts, or use the forms or dynamic content widgets.

== Description ==

Say hello to marketing automation simplicity! With a single login, your self-hosted WordPress installation will be securely connected with Pardot. With the selection of your campaign, you'll be able to track visitors and work with forms and dynamic content without touching a single line of code. You can use the widget to place a form or dynamic content anywhere a sidebar appears, or embed them in a page or post using a shortcode or the Pardot button on the Visual Editor's toolbar.

== Installation ==

1. Upload `pardot-for-wordpress` to your `/wp-content/plugins/` directory or go to Plugins > Add New in your WordPress Admin area and search for Pardot.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings > Pardot Settings to put in your email, password, and user key.
1. Select your campaign (for tracking code usage).

== Frequently Asked Questions ==

= How can I use the shortcodes without the Visual Editor? =

Two simple shortcodes are available for use.

= Form Shortcode =

`[pardot-form id="{Form ID}" title="{Form Name}" class="" width="100%" height="500" querystring=""]`

Use `[pardot-form]` with at least the `id` parameter. For instance, `[pardot-form id="1" title="Title"]` renders my Pardot form with an ID of 1.

Optional parameters:

The `title` parameter is included when using the toolbar button, but it's not required for display. There is no default.

The `class` parameter allows you to add additonal classes to the iframe element. There is no default, but the class `pardotform` is now automatically added, regardless of any additional classes.

The `width` parameter will set the width of the iframe in pixels or percentage. For example, "500", "500px", and "80%" are all valid. The default is 100%.

The `height` parameter will set the height of the iframe in pixels only. For example, "500" or "500px" are valid. The default is 500px.

The `querystring` parameter appends an arbitrary string to the end of the form's iframe source. This is helpful for passing data directly into the form. You can also do this with filters (see below).

= Dynamic Content Shortcode =

`[pardot-dynamic-content id="{Dynamic Content ID}" default="{Non-JavaScript Content}"]`

Use `[pardot-dynamic-content]` with at least the `id` parameter.

The `default` parameter is used for accessibility. Whatever is placed here is wrapped in `<noscript>` tags and is shown only to users who have JavaScript disabled. By default, it will automatically be your "Default Content" as designated in Pardot. So,

`[pardot-dynamic-content id="1" default="My default content."]`

would render something like:

`<script type="text/javascript" src="http://go.pardot.com/dcjs/99999/99/dc.js"></script><noscript>My default content.</noscript>`

...which would show the dynamic content to users with JavaScript enabled, and 'My default content' to users with it disabled. Note that, due to the way the WordPress Visual Editor works, HTML tags for the parameter will be URL encoded to avoid strange formatting.

= How do I change my campaign? =

Simply choose another campaign in Settings > Pardot Settings and click 'Save Settings'.

= Some of my form is cut off. What should I do? =

Since every WordPress theme is different, embedded forms won’t always automatically fit. You’ll want to make a Pardot Layout Template specifically for your WordPress theme:

1. Go to <a href="https://pi.pardot.com/form" target="_blank">Forms</a> in Pardot. Find and edit the form that needs updating.
1. Click ahead to the 'Look and Feel' step of the wizard and select the 'Styles' tab.
1. Set 'Label Alignment' to 'Above' and click 'Confirm and Save.'.
1. Click the link to the layout template being used by the form.
1. Edit the layout template and add the following to the <code><head></code> section of the template:
`<style type="text/css">
	#pardot-form input.text, #pardot-form textarea {
		width: 150px;
	}
</style>`

A width of 150px is just a starting point. Adjust this value until it fits on your page and add additional styles as you see fit. For styling help, reference our <a href="http://www.pardot.com/help/faqs/forms/basic-css-for-forms" target="_blank">Basic CSS for Forms</a> page.

= I just added a form or dynamic content, and it's not showing up to select it yet. =

Go to Settings > Pardot Settings and click 'Reset Cache'. This should reinitialize and update your Pardot content.

= The editor popup doesn't work, and I know that my WordPress installation is a little different. =

As of version 1.4, developers can now deal with various directory configurations that would previously cause the plugin to break. This is due to the plugin not being able to find `wp-load.php`.

To fix it, add a new file called `pardot-custom-wp-load.php` to the `plugins/pardot/includes` directory (this will never be overridden by updates). In that file, define a constant that gives the absolute path to your `wp-load.php` file. For instance:

`define('PARDOT_WP_LOAD', '/path/to/wp-load.php');`

= Filters =

`pardot_form_embed_code_[Form ID]`

Filter the entire embed code for a given form. A common usage for this is conditionally appending a query string. So, for instance, the following will filter the embed code for form #545 and append an arbitrary parameter along with the post ID of the page being viewed:

	function pardot_custom_append_querystring($body_html) {
		return preg_replace( '/src="([^"]+)"/', 'src="$1?this=that&postID=' . get_the_ID() . '"', $body_html );
	}

	add_filter( 'pardot_form_embed_code_54796', 'pardot_custom_append_querystring' );

You can apply any conditional logic you want. For instance, this will append the same information, but only if you're on the "About" page:

	function pardot_custom_append_querystring($body_html) {
		if ( is_page('About') ) {
			$body_html = preg_replace( '/src="([^"]+)"/', 'src="$1?this=that&postID=' . get_the_ID() . '"', $body_html );
		}
		return $body_html;
	}

	add_filter( 'pardot_form_embed_code_54796', 'pardot_custom_append_querystring' );

`pardot_https_regex`

Filter the regular expression used to find URLs to be converted to https://go.pardot.com. This is only used when "Use HTTPS?" is checked in the settings. You may want to filter this regex if you find it's not properly capturing and converting your URLs.

	function pardot_custom_filter_https_regex() {
		return "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,63}(\/\S*)?/";
	}

	add_filter( 'pardot_https_regex', 'pardot_custom_filter_https_regex' );

== Screenshots ==

1. Settings area
1. Pardot button in the Visual Editor toolbar
1. Choose from any form or Dynamic Content
1. Use forms in a widget
1. Use dynamic content in a widget
1. A form widget (with corrected styling)
1. A page can have two forms! Here, one is in the body and one in a widget.

== Changelog ==

= 1.4.3 =

Fixes a more obscure bug that would cause the plugin to become unauthenticated

= 1.4.2 =

Fixes a bug that would cause the plugin to become unauthenticated

= 1.4.1 =

1. Allow connection with API v4
1. Improve regex for HTTPS and add filtering

= 1.4 =

1. Add HTTPS option
1. Add "querystring" parameter in shortcode
1. Allow embed code to be filtered
1. Change "Pardot Settings" link to "Pardot"
1. Update branding
1. Allow override for wp-load.php in various installation configurations
1. Fixes errant notice on 404 pages

= 1.3.10 =

Improve WordPress 3.9 compatibility (Tiny popup titles; update Chosen)

= 1.3.9 =

Fixes a small bug with a JS library being called in the wrong place

= 1.3.8 =

1. Add Chosen selector to forms and dynamic content
2. Fix async DC bug
3. Enchance password authentication encoding

= 1.3.7 =

1. Add Chosen selector to campaign settings
1. Fix authentication issue

= 1.3.6 =

1. Adds support for 400+ campaigns, form, and dynamic content blocks
1. Updates branding

= 1.3.5 =

1. Fixed a bug where pardotform class might be applied to closing iframe tag (thanks palpatine1976!)
1. Optimize code to remove some debug messages
1. Improve campaign retrieval for over 200 campaigns

= 1.3.4 =

Fixed a bug where tracking code might show the wrong ID.

= 1.3.3 =

1. Accounts for a minor API change in the tracking code
1. Adds support for 200+ campaigns

= 1.3.1 =

Fixed a bug with `shortcode_exists` fatal error

= 1.3.1 =

Fixed a bug with `has_shortcode` fatal error

= 1.3 =

Use new asynchronous loading for Dynamic Content

= 1.2 =

1. Added ability to specify height, width, and class on the form
1. Added class 'pardotform' to every iframe for easier styling

= 1.1.5 =

1. Add some helpful links to the Reset Cache button
2. Minor UI tweaks
3. Updated the Pardot logos
4. Updated screenshots for 3.5

= 1.1.4 =

1. Fix TinyMCE modal bug when no forms or dynamic content is present
1. Support for 200+ forms and dynamic content items
1. Other minor checks

= 1.1.3 =

Checks for mcrypt and falls back safely if not (fixes blank admin screen bug)

= 1.1.2 =

1. Clear cache when resetting all settings
1. Be more forgiving with login whitespace
1. Make some security improvements

= 1.1.1 =
Make `<noscript>` default to Default Pardot Content

= 1.1.0 =
1. Added dynamic content shortcodes
1. Added title field to form widget
1. Added 'Reset Cache' option

= 1.0.3 =
Added form caching for faster rendering and less requests

= 1.0.2 =
1. Fix a caching issue that was causing the most recently-used form to render on all posts/pages
1. Extended API cache timeout

= 1.0.1 =
Fix bug with form order in content

= 1.0 =
Initial release.

== Upgrade Notice ==

= 1.4.3 =

Fixes a more obscure bug that would cause the plugin to become unauthenticated

= 1.4.2 =

Fixes a bug that would cause the plugin to become unauthenticated.

= 1.4.1 =

This update fixes an issue with the new Pardot API version and improves the HTTPS functionality used to find and replace the Pardot URLs.

= 1.4 =

This update adds an option to embed HTTPS forms (activate it in Settings > Pardot), adds the "querystring" parameter to the shortcode, makes the form embed code filterable, allows custom overrides for various directory configurations, updates branding, and fixes some bugs.

= 1.3.10 =

This update improves compatibility with WordPress 3.9 in the Visual Editor.

= 1.3.9 =

This update fixes a bug that caused some Dashboards to act funny.

= 1.3.8 =

This update improves form and dynamic content selection, fixes a bug with asynchronous dynamic content loading, and improves password encoding.

= 1.3.7 =

This update improves campaign selection and fixes a bug with settings where certain user credentials would fail to authenticate.

= 1.3.6 =

This update adds support for 400+ campaigns, forms, and dynamic content blocks (cheers to Twig Interactive). We've also updated some branding.

= 1.3.5 =

This update fixes a bug where pardotform class might be applied to closing iframe tag (thanks palpatine1976!), optimizes code to remove some debug messages, and improves campaign retrieval for over 200 campaigns. <3

= 1.3.4 =

Fixes a bug where tracking code might show the wrong ID.

= 1.3.3 =

Accounts for a minor API change in the tracking code; adds support for 200+ campaigns

= 1.3.2 =

1.3.2 fixes two fatal error bugs that were showing on install. With today's overall 1.3 update, you can now load multiple pieces of Dynamic Content without a performance hit with our new asynchronous loading technique!

= 1.3 =

Load multiple pieces of Dynamic Content without a performance hit with our new asynchronous loading technique!

= 1.2 =

Thanks to your feedback, we've added the ability to specify height, width, and additional classes on the form iframes; the 'pardotform' class is also automatically added to every form iframe for easier styling

= 1.1.3 =

Fixes blank admin screen bug (by checking for mcrypt and falling back safely if not)

= 1.1.2 =

1. Clear cache when resetting all settings
1. Be more forgiving with login whitespace

= 1.1.1 =
Make `<noscript>` default to Default Pardot Content

= 1.1.0 =
1. Added dynamic content shortcodes
1. Added title field to form widget
1. Added 'Reset Cache' option

= 1.0.3 =
Added form caching for faster rendering and less requests

= 1.0.2 =
1. Fix a caching issue that was causing the most recently-used form to render on all posts/pages
1. Extended API cache timeout

= 1.0.1 =
Fix bug with form order in content

= 1.0 =
Initial release.der in content

= 1.0 =
Initial release.
