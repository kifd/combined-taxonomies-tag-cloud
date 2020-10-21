=== Combined Taxonomies Tag Cloud ===
Contributors: keith_wp
Author Link: https://drakard.com/
Tags: tag cloud, taxonomy tag cloud, tag cloud widget, tag, cloud, widget, tag cloud post type, custom tag cloud, combined tag cloud, custom post tag cloud, change tag cloud, redirect single tag, remove single tag
Requires at least: 3.8 or higher
Tested up to: 5.5
Stable tag: 0.22
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A widget to make tag clouds out of multiple taxonomies, across multiple post types and control their appearance.

== Description ==

The normal WP Tag Cloud widget only uses one taxonomy at a time, and doesn't handle the default post_tag or category taxonomies being assigned to custom post types.

With this plugin, you can now configure (on a per-widget basis):

* which taxonomies and post types are to be included,
* the size of the smallest and largest tags (in different font units),
* which terms should always be excluded from the cloud,
* the maximum number of tags to show in the cloud,
* how to order the tags - alphabetically or by number of tagged posts (ascending or descending), or just randomly,
* how to treat tags with just one entry - leave alone, remove them or link directly to that post,
* whether to make links no-follow,
* what case the tags should appear as,
* the widget background and tag background/foreground colours,
* and how long each widget's output should be saved for, if at all.


== Installation ==

1. Upload the plugin files to the **/wp-content/plugins/combined-taxonomies-tag-cloud/** directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Appearance -> Widgets screen to add the Combined Tag Cloud widget to your sidebars, customising each widget individually.


== Frequently Asked Questions ==


== Screenshots ==

1. Enter your (optional) title, and pick which taxonomies and post types will be picked for this cloud.
2. Choose the size range of the tags, which ones should never appear and what order to display them in.
3. More options - how to deal with single count tags, link behaviour and tag appearance, as well as how long to store the resulting HTML.


== Changelog ==

= 0.23 =
* Enhancement: added 'show count' option
* Enhancement: added tag border color option
* Enhancement: rearranged the admin form UI into collapsible sections
* Enhancement: added mouseover help text to the admin form
* Bugfix: changed the way styling was added, so you can have your own styling on one widget and a predefined one on another
* Bugfix: can now clear color settings, oops

= 0.22 =
* Tested with WP 5.6 alpha
* Enhancement: added tag font stack selection
* Enhancement: added 'vw' to CSS unit selection because why not
* Enhancement: made the widget form hide unused options based on select dropdowns
* Bugfix: checks 'before_widget' param for CSS id, so themes that don't include it (like 2020...) can still have the widgets styled individually
* Bugfix: tidied up the widget form code
* Bugfix: removed a stray pre_get_posts() call

= 0.21.4 =
* Bugfix: was still trying to make a cloud even if we pulled no tags back

= 0.21.3 =
* Bugfix: transient was only saving a single instance
* Bugfix: crashed in a different place if you deleted a taxonomy while it was selected in a widget

= 0.21.2 =
* Tested with WP 5.2.1

= 0.21.1 =
* Tested with WP 4.6.1

= 0.21 =
* Bugfix: crashed if you deleted a taxonomy while it was selected in a widget

= 0.2 =
* Enhancement: added colour pickers
* Bugfix: tidied up the stylesheet

= 0.1 =
* Initial release.


== Upgrade Notice ==


