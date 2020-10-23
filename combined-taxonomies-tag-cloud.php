<?php
/*
Plugin Name: Combined Taxonomies Tag Cloud
Version: 0.30
Description: Makes a tag cloud widget out of multiple taxonomies across multiple post types.
Author: Keith Drakard
Author URI: https://drakard.com/
*/


if (! defined('WPINC')) die;

class CombinedTaxonomiesTagCloudPlugin {
	
	public function __construct() {
		load_plugin_textdomain('CombinedTaxonomiesTagCloud', false, dirname(plugin_basename(__FILE__)).'/languages');
		
		add_action('widgets_init', function() {
			require_once 'cttc-widget.php';
			register_widget('CombinedTaxonomiesTagCloudWidget');
			// unregister_widget('WP_Widget_Tag_Cloud');
		});
		
		add_shortcode('cttc', array($this, 'widget_shortcode'));
	}
	
	
	// only previously saved widgets can be called like this, else I'm duplicating the entire params
	public function widget_shortcode($attributes, $content = null) {
		$defaults = array(
			'cloud'	=> 0,
			'echo'	=> false,
		);
		$args = shortcode_atts($defaults, $attributes, 'cttc');
		
		// ensure we have the same types as expected from the defaults
		foreach ($args as $key => $value) settype($args[$key], gettype($defaults[$key]));
		
		$output = '';
		if ($args['cloud'] > 0) {
			
			// all our widget instances are saved in the options table
			$widget_options_all = get_option('widget_combinedtaxonomiestagcloudwidget');
			
			if (is_array($widget_options_all) AND isset($widget_options_all[$args['cloud']])) {
				$instance = $widget_options_all[$args['cloud']];
				// add the id of this widget, doctored to show it's via the shortcode
				$instance['widget_id'] = 'combinedtaxonomiestagcloudwidget-'.$args['cloud'].'-shortcode';
				// get the widget output
				ob_start(); the_widget('CombinedTaxonomiesTagCloudWidget', $instance); $output = ob_get_clean();
				
			} else {
				$output = sprintf('<p>%s</p>',
					sprintf(__('CTTC Error: "%s" was not recognized as a valid tag cloud ID', 'CombinedTaxonomiesTagCloud'), $attributes['cloud'])
				);
			}
		} elseif (isset($attributes['cloud'])) {
			$output = sprintf('<p>%s</p>',
				sprintf(__('CTTC Error: "%s" was not recognized as a numeric tag cloud ID', 'CombinedTaxonomiesTagCloud'), $attributes['cloud'])
			);
		} else {
			$output = sprintf('<p>%s</p>',
				sprintf(__('CTTC Error: You need to enter a tag cloud ID to show a tag cloud', 'CombinedTaxonomiesTagCloud'))
			);
		}
		
		if ($args['echo']) {
			echo $output;
		}
		
		return $output;
	}
	
}

$CombinedTaxonomiesTagCloud = new CombinedTaxonomiesTagCloudPlugin();

