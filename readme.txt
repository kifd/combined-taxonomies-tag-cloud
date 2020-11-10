=== Combined Taxonomies Tag Cloud ===
Contributors:		keith_wp
Author Link:		https://drakard.com/
Requires at least:	3.8 or higher
Tested up to:		5.5
Requires PHP	:	7.2
Stable tag:			0.34
License:			GPLv3 or later
License URI:		https://www.gnu.org/licenses/gpl-3.0.html
Tags:				custom tag cloud, tag cloud, tag, tag cloud widget, change tag cloud

A widget to make tag clouds out of multiple taxonomies, across multiple post types and control their appearance and behavior.


== Description ==

The normal WP Tag Cloud widget only uses one taxonomy at a time, and doesn't properly handle taxonomies being assigned to custom post types.

With this plugin, you can configure (for each cloud):

* which the taxonomies and post types are to be included, and which terms shouldn't be,
* fonts used in the widget and how they scale,
* widget title color and alignment, and general cloud spacing and alignment,
* how the tags look normally, and how they behave when highlighted,
* automatically assign contrasting text colors to meet accessibility guidelines,
* set tags to be highlighted when they have been used elsewhere on that post,
* how the tags should be sorted (alphabetically, by count or just randomly),
* what happens to tags with just one entry (leave alone, remove them or link directly to that post),
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

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-1-types-and-taxonomies.jpg  Pick which taxonomies and post types will be used for this cloud.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-2-fonts.jpg  Alter the font stack and sizes used.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-3-widget-appearance.jpg  Widget title settings and general positioning.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-4-widget-behaviour.jpg  More mechanics of a cloud.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-5-tag-appearance.jpg  Basic appearance of each tag.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-6-tag-effects.jpg  More control over how a tag normally looks and behaves when highlighted.]

[https://raw.githubusercontent.com/kifd/combined-taxonomies-tag-cloud/master/assets/screenshot-7-shortcode.jpg  Use a shortcode to display a cloud in areas outside of widgets.]


== Upgrade Notice ==

= 0.34 =

First WP release since the 0.21.4 version, with a pretty big overhaul of how much the plugin does (see changelog.txt for the list).
