=== Combined Taxonomies Tag Cloud ===
Contributors: keith_wp
Author Link: https://drakard.com/
Tags: tag cloud, taxonomy tag cloud, tag cloud widget, tag, cloud, widget, tag cloud post type, custom tag cloud, combined tag cloud, custom post tag cloud, change tag cloud, redirect single tag, remove single tag
Requires at least: 3.8 or higher
Tested up to: 5.5
Requires PHP: 7.2
Stable tag: 0.32
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A widget to make tag clouds out of multiple taxonomies, across multiple post types and control their appearance.

== Description ==

The normal WP Tag Cloud widget only uses one taxonomy at a time, and doesn't handle the default post_tag or category taxonomies being assigned to custom post types.

With this plugin, you can configure (for each widget):

* NOTE: this list needs updating

* which taxonomies and post types are to be included,
* which terms should always be excluded from the cloud,
* tag text font, text color and tag size scaling,
* what case the tags should appear in, and their :hover decoration,
* the widget background, tag border and tag background colours,
* the tag cloud horizontal and vertical alignment,
* how to order the tags - alphabetically or by number of tagged posts (ascending or descending), or just randomly,
* how to treat tags with just one entry - leave alone, remove them or link directly to that post,,
* the maximum number of tags to show in the cloud,
* whether to make links no-follow,
* how long each widget's output should be saved for, if at all,
* and now also generate a shortcode for each widget instance and use them within posts.


== Installation ==

1. Upload the plugin files to the **/wp-content/plugins/combined-taxonomies-tag-cloud/** directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Appearance -> Widgets screen to add the Combined Tag Cloud widget to your sidebars, customising each widget individually.


== Frequently Asked Questions ==

= How do I select multiple taxonomies or post types? =

Depending on your operating system, you can normally use shift+click to select a range of options in a big dropdown on any webpage, or ctrl+click to select multiple individual ones.

You can also combine them if needed - select loads of options with shift+click first, and then unselect a few individual ones with ctrl+click.

= How do I show a cloud with the shortcode? =

Save an instance of the widget as normal, and the shortcode text will be created at the bottom of the widget form. Copy and paste that snippet where your want the cloud to be displayed - though make sure your theme supports shortcodes there!

If you don't want to display that cloud in a sidebar, then you can either drag it to the "Inactive Widgets" area of the Widgets admin after making it, or create it there directly.


== Screenshots ==

Note all screenshots are for versions older than 0.22 at the mo...

1. Enter your (optional) title, and pick which taxonomies and post types will be picked for this cloud.
2. Choose the size range of the tags, which ones should never appear and what order to display them in.
3. More options - how to deal with single count tags, link behaviour and tag appearance, as well as how long to store the resulting HTML.


== Changelog ==

= 0.32 =
* Enhancement: changed default wpColorPicker to one that has transparency control
* Enhancement: added more choices of effects
* Enhancement: added demo of tag effect into the widget form
* Enhancement: automatic contrasting text color over backgrounds
* Enhancement: more general widget appearance control (title color, widget padding etc)

= 0.31 =
* Enhancement: font unit selection now reflected across relevant options
* Enhancement: font stack applies to widget title as well
* Enhancement: added choice of tag highlighting effects
* Enhancement: now uses CSS vars for easier customising
* Bugfix: more code tidying and UI fiddling
* Bugfix: requires php7+ now due to type hinting etc

= 0.30 =
* Enhancement: added ability to display the widget via a shortcode
* Enhancement: added WCAG tag color contast checker
* Enhancement: added option to automatically highlight tags in the cloud that match the tags in the current post
* Enhancement: added adjustable row/column gaps
* Enhancement: can copy the shortcode to the clipboard with a click
* Bugfix: clearing just a color will also tell WP the form needs saving

= 0.23 =
* Enhancement: added 'show count' option
* Enhancement: added tag border color option
* Enhancement: rearranged the admin form UI into collapsible sections
* Enhancement: added mouseover help text to each element
* Enhancement/Bugfix: completely changed the way CSS was added, so you can have your own styling on one widget and a predefined one on another
* Enhancement: added tag text decoration option
* Bugfix: can now clear color settings, oops
* Bugfix: found a fix for WP not enabling the save button after just a color change

= 0.22 =
* Tested with WP 5.6 alpha
* Enhancement: added tag font stack selection
* Enhancement: added tag text size scaling choice
* Enhancement: added 'vw' to scaling unit selection because why not
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

= 0.20 =
* Enhancement: added colour pickers
* Bugfix: tidied up the stylesheet

= 0.10 =
* Initial release.


== To-Do List ==

* Check: transient saved re single page cloud highlighting
* merge rest of tag borders into effects
* js cleanup, esp re css vars
* demo to include border radius & font
* widget vertical alignment to follow per line?

* To Add: auto highlight matching tags on archive pages
* To Add: 	? only show tags for terms used in the category being viewed
* To Add: more effects
* To Add: different way relative sizes are calculated - ie. log
* To Add: temporarily save widget fieldset open/close states while editing
* To Add: colors per term - use termmeta?
* To Add: setting to toggle white-space wrap

* New Screenshots...

* To Fix: changing what taxonomies to use doesn't automatically update the widget re what terms to exclude etc - save it first

* To Add much later: use as a drop widget to make a cloud out of the words of a post, ignoring stop words
* To Add much later: and then combine with your word cloud work to make images...
