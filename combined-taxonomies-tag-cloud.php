<?php
/*
Plugin Name: Combined Taxonomies Tag Cloud
Version: 0.22
Description: Makes a tag cloud widget out of multiple taxonomies across multiple post types.
Author: Keith Drakard
Author URI: https://drakard.com/


KNOWN (minor tbf) BUGS:
	- changing just a color in the widget admin doesn't make WP realise the form has changed
	- if any instances are set to "your own css", then it will make all instances lose styling
*/


if (! defined('WPINC')) die;

class CombinedTaxonomiesTagCloudPlugin {
	
	public function __construct() {
		load_plugin_textdomain('CombinedTaxonomiesTagCloud', false, dirname(plugin_basename(__FILE__)).'/languages');
		
		add_action('widgets_init', function() {
			register_widget('CombinedTaxonomiesTagCloudWidget');
			// unregister_widget('WP_Widget_Tag_Cloud');
		});
	}
	
}

$CombinedTaxonomiesTagCloud = new CombinedTaxonomiesTagCloudPlugin();




class CombinedTaxonomiesTagCloudWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(false,
			__('Combined Tag Cloud', 'CombinedTaxonomiesTagCloud'), array(
				'description' => __('More adaptable version of the basic WP tag cloud widget.', 'CombinedTaxonomiesTagCloud'),
				'classname' => 'widget_tag_cloud',
			));
		
		// only load if we're using the widget
		if (is_admin() OR is_active_widget(false, false, $this->id_base, true)) {
			add_action('wp_loaded', array($this, 'make_default_selections'));
			// admin needs the colour picker and its javascript, as well as a mini form styling
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_style('combined-taxonomies-tag-cloud-admin-style', plugins_url('admin.css', __FILE__), false, null);
				wp_enqueue_style('wp-color-picker'); 
				wp_enqueue_script('combined-taxonomies-tag-cloud-admin-script', plugins_url('admin.js', __FILE__), array('wp-color-picker'), null, true);
			});
			// only need our stylesheet on the front end, but we can't just use the wp_enqueue_scripts action as we may be adding inline styles
			add_action('wp_head', function() {
				wp_register_style('combined-taxonomies-tag-cloud-style', plugins_url('style.css', __FILE__), false, null);
			});
			add_action('wp_footer', function() {
				wp_enqueue_style('combined-taxonomies-tag-cloud-style');
			});
		}
	}

	public function make_default_selections() {
		$this->choices = array(
			'taxonomies'	=> get_taxonomies(array('show_ui' => true), 'objects'),
			'post_types'	=> get_post_types(array('show_ui' => true), 'objects'),
			'font_family'	=> array(
								__('Leave Alone', 'CombinedTaxonomiesTagCloud'),
								'Times New Roman',
							),
			'font_unit'		=> array('rem', 'em', 'pt', 'px', 'vw'),
			'orderby'		=> array(
								'name' => __('Alphabetically', 'CombinedTaxonomiesTagCloud'),
								'count' => __('By Count', 'CombinedTaxonomiesTagCloud'),
								'random' => __('Randomly', 'CombinedTaxonomiesTagCloud')
							),
			'single'		=> array(
								'leave' => __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
								'remove' => __('Remove', 'CombinedTaxonomiesTagCloud'),
								'link' => __('Link to Entry', 'CombinedTaxonomiesTagCloud')
							),
			'display'		=> array(
								'diy' => __('Your Own CSS', 'CombinedTaxonomiesTagCloud'),
								'flat' => __('Flat List', 'CombinedTaxonomiesTagCloud'),
								'ulist' => __('Block List', 'CombinedTaxonomiesTagCloud'),
								'olist' => __('Numbered List', 'CombinedTaxonomiesTagCloud'),
								'boxes' => __('Box Tags', 'CombinedTaxonomiesTagCloud'),
							),
			'textcase'		=> array(
								'' => __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
								'lower' => __('lowercase', 'CombinedTaxonomiesTagCloud'),
								'upper' => __('UPPERCASE', 'CombinedTaxonomiesTagCloud')
							),
			'save'			=> array(0, 1, 2, 4, 8, 12, 24, 48, 96), // hours
		);
		sort($this->choices['taxonomies']);
		sort($this->choices['post_types']);
		
		if (function_exists(‘ogf_fonts_array’)) {
			$uaf_font_families = ogf_fonts_array(); // Returns Array
			print_r($uaf_font_families);
			exit;
		}

		$this->defaults = array(
			'title' 		=> '',
			'post_types'	=> array('post'),
			'taxonomies'	=> array('post_tag'),
			'font_family'	=> 'leave',
			'font_unit'		=> 'rem',
			'smallest' 		=> 0.8,
			'largest' 		=> 2.4,
			'exclude'		=> array(0),
			'maximum'		=> 999,
			'orderby'		=> 'name',
			'order'			=> 0, // 0 = asc, 1 = desc
			'single'		=> 'leave',
			'nofollow'		=> 0,
			'textcase'		=> '',
			'display'		=> 'boxes',
			'wbackground'	=> '#ffffff',
			'tbackground'	=> '#ffffff',
			'tforeground'	=> '#000000',
			'save'			=> 0,
		);
	}



	// display the widget -----------------------------------
	public function widget($args, $instance) {
		$instance = wp_parse_args($instance, $this->defaults);
		$args = array_merge($args, $instance);

		$this->transient = 'combined_taxonomies_tag_cloud_'.$this->id;
		$output = get_transient($this->transient);

		if (! $output OR $instance['save'] == 0) {

			// need wpdb for the query and wp_post_types to get the labels (names to use in the post counts)
			global $wpdb, $wp_post_types;

			// get this out of the way
			$nofollow = (1 == $args['nofollow']) ? ' rel="nofollow"' : '';

			// our sql to retrieve the combined CPT/taxes
			$statement = "
				SELECT	t.term_id, t.name, tt.taxonomy, t.slug, p.post_type, COUNT(*) AS post_type_count
				FROM	{$wpdb->prefix}posts p
						INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
						INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
						INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
				WHERE	p.post_type IN (".implode(', ', array_fill(0, count($args['post_types']), '%s')).")
						AND tt.taxonomy IN (".implode(', ', array_fill(0, count($args['taxonomies']), '%s')).")
						AND t.term_id NOT IN (".implode(', ', array_fill(0, count($args['exclude']), '%d')).")
				GROUP BY t.name, p.post_type
				ORDER BY t.name
			";

			// nifty array_fill prep by DaveRandom @ http://stackoverflow.com/a/10634225
			$sql = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($statement), $args['post_types'], $args['taxonomies'], $args['exclude']));
			
			//echo $sql; exit;
			$data = $wpdb->get_results($sql);


			// need this as I'm not getting both the grand total and individual post type counts in the sql
			$tags = array(); foreach ($data as $tag) {
				if (! isset($tags[$tag->term_id])) {
					$tags[$tag->term_id] = array(
						'term_id'	=> $tag->term_id,
						'name'		=> $tag->name,
						'taxonomy'	=> $tag->taxonomy,
						'slug'		=> $tag->slug,
						'count'		=> 0,
						'types'		=> array(),
					);
				}
				$tags[$tag->term_id]['count']+= $tag->post_type_count; // uses this as the main font size determination
				$tags[$tag->term_id]['types'][$tag->post_type] = $tag->post_type_count; // and this to make a better link title
			}


			// mess with the default alphabetical ordering if needed
			if ('random' == $args['orderby']) {
				shuffle($tags);
			} elseif ('count' == $args['orderby']) {
				usort($tags, function($a, $b) {
					return $a['count'] - $b['count'];
				});
			}
			if (1 == $args['order']) {
				$tags = array_reverse($tags);
			}


			// and potentially remove orphaned tags *before* trimming
			if ('remove' == $args['single']) {
				foreach ($tags as $i => $tag) if (1 == $tag['count']) unset($tags[$i]);
			}


			// trim to the maximum amount we'll be showing
			$tags = array_slice($tags, 0, $args['maximum']);

			// skip the next bit if we have no tags left
			if (empty($tags)) return false;

			// work out the difference between our highest and lowest tag counts etc
			$weights = array(); foreach ($tags as $tag) $weights[] = $tag['count'];
			$max_qty = max(array_values($weights)); $min_qty = min(array_values($weights));
			$spread = $max_qty - $min_qty; if ($spread == 0) $spread = 1; // we don't want to divide by zero
			$step = ($args['largest'] - $args['smallest']) / $spread; // set the font-size increment
			$midway = round($args['smallest'] + (($args['largest'] - $args['smallest']) / 2), 2); // halfway point used to alter CSS effects


			// build our cloud
			$cloud = array();
			foreach ($tags as $i => $tag) {

				if ('link' == $args['single'] AND 1 == $tag['count']) {
					$objs = get_objects_in_term($tag['term_id'], $tag['taxonomy']);
					$link = get_permalink(array_pop($objs));
				} else {
					$link = get_term_link((int) $tag['term_id'], $tag['taxonomy']);
				}

				// in case we setup the cloud and then delete a taxonomy without updating the widget choices
				if (is_wp_error($link)) continue;
				
				// calculate the size of this tag - find the $value in excess of $min_qty, multiply by the font-size increment ($step) and add the $args['smallest'] set above
				$size = round($args['smallest'] + (($tag['count'] - $min_qty) * $step), 2);

				// style tags with class names
				$classes = $tag['taxonomy'].' '.$args['textcase'].' ';
				$classes.= ($midway >= $size) ? 'smaller' : 'larger';

				// build our link title from the component post type counts
				$link_title = array();
				foreach ($tag['types'] as $type => $count) {
					$link_title[]= (1 == $count) ? '1 '.$wp_post_types[$type]->labels->singular_name : $count.' '.$wp_post_types[$type]->labels->name;
				}
				$link_title = strtolower(implode(', ', $link_title));

				$cloud[] = '<a href="'.$link.'" style="font-size:'.$size.$args['font_unit'].'" class="'.$classes.'" title="'.$link_title.'"'.$nofollow.'>'.$tag['name'].'</a>';
			}

			$title = apply_filters('widget_title', $instance['title']); if ('' != $title) $title = $args['before_title'].$title.$args['after_title'];

			switch ($args['display']) {
				case 'olist':		$_tag = 'ol'; $_class = 'list'; $_cloud = '<li>'.implode('</li><li>', $cloud).'</li>';
									break;
				case 'ulist':		$_tag = 'ul'; $_class = 'list'; $_cloud = '<li>'.implode('</li><li>', $cloud).'</li>';
									break;
				case 'boxes':		$_tag = 'div'; $_class = 'boxes'; $_cloud = implode('', $cloud);
									break;
				case 'flat':		
				default:			$_tag = 'div'; $_class = ''; $_cloud = implode('', $cloud);
									break;
									
			}
			
			// NOTE: twentytwenty theme doesn't output widget IDs, unlike every other default WP theme,
			// so make sure there's something we can use to recognize individual widgets
			if (! stristr($args['before_widget'], ' id=')) {
				// only want to replace it once, so can't just use str_replace
				$pos = strpos($args['before_widget'], '>');
				if ($pos !== false) {
					$args['before_widget'] = substr_replace($args['before_widget'], ' id="'.$args['widget_id'].'">', $pos, 1);
				} else {
					// NOTE: the theme has registered the sidebar without tags before the widget, presumably for a reason, so leave it
				}
			}
			
			// make the html
			$output = $args['before_widget']."\n"
					. $title."\n"
					. sprintf('<%s class="combinedtagcloud %s">%s</%s>', $_tag, $_class, $_cloud, $_tag)."\n"
					. $args['after_widget']."\n";

			// and (possibly) save the resulting html so we don't need to do this again for a while
			if (0 < $args['save']) set_transient($this->transient, $output, $args['save'] * HOUR_IN_SECONDS);
		}
		
		
		if ('diy' == $args['display']) {
			wp_deregister_style('combined-taxonomies-tag-cloud-style');
		
		} else {
		
			$_font = '';
			if ($args['font_family'] != __('Leave Alone', 'CombinedTaxonomiesTagCloud')) {
				$_font = 'font-family:'.$args['font_family'].';';
			}
			
			$custom_css = sprintf('
				#%s .combinedtagcloud { background-color:%s; }
				#%s .combinedtagcloud a { background-color:%s; color:%s; %s }
				#%s .combinedtagcloud a:hover { background-color:%s; color:%s; }
			',
				$args['widget_id'], $args['wbackground'],
				$args['widget_id'], $args['tbackground'], $args['tforeground'], $_font,
				$args['widget_id'], $args['tforeground'], $args['tbackground']
			);
			
			wp_add_inline_style('combined-taxonomies-tag-cloud-style', $custom_css);
		}

		echo $output;
	}
	

	// update the widget ------------------------------------
	public function update($new, $old) {
		$instance = $old;
																				$instance['title'] = strip_tags($new['title']);

		// TODO: check post types and taxs in allowed array
		$instance['post_types'] = array(); foreach ($new['post_types'] as $type) $instance['post_types'][] = $type;
		$instance['taxonomies'] = array(); foreach ($new['taxonomies'] as $tax)  $instance['taxonomies'][] = $tax;

		if (in_array($new['font_family'], $this->choices['font_family']))		$instance['font_family'] = $new['font_family'];
		if (in_array($new['font_unit'], $this->choices['font_unit']))			$instance['font_unit'] = $new['font_unit'];
																				$instance['smallest'] = sprintf('%0.2f', (float) $new['smallest']);
																				$instance['largest'] = sprintf('%0.2f', (float) $new['largest']);
		$instance['exclude'] = array(0); foreach ($new['exclude'] as $term_id)	$instance['exclude'][] = absint($term_id);
		if (in_array($new['orderby'], array_keys($this->choices['orderby'])))	$instance['orderby'] = $new['orderby'];
																				$instance['order'] = (bool) $new['order'];
																				$instance['maximum'] = absint($new['maximum']);
		if (in_array($new['single'], array_keys($this->choices['single'])))		$instance['single'] = $new['single'];
																				$instance['nofollow'] = (bool) $new['nofollow'];
		if (in_array($new['textcase'], array_keys($this->choices['textcase'])))	$instance['textcase'] = $new['textcase'];
		if (in_array($new['display'], array_keys($this->choices['display'])))	$instance['display'] = $new['display'];

		if ($this->is_valid_colour($new['wbackground']))						$instance['wbackground'] = $new['wbackground'];
		if ($this->is_valid_colour($new['tbackground']))						$instance['tbackground'] = $new['tbackground'];
		if ($this->is_valid_colour($new['tforeground']))						$instance['tforeground'] = $new['tforeground'];

		if (in_array($new['save'], $this->choices['save']))						$instance['save'] = $new['save'];

		// either something's changed or we pressed the save button for the sake of it. regardless, delete our saved html and start again
		$this->transient = 'combined_taxonomies_tag_cloud_'.$this->id;
		delete_transient($this->transient);

		return $instance;
	}



	// form for the widget ----------------------------------
	public function form($instance) {
		// if (empty($instance)) return; // TODO: don't go through all this if we're not using the widget - need to account for freshly added widgets

		$instance = wp_parse_args((array) $instance, $this->defaults);

		// build up the various selects in the form (0=array of objects, 1=array of k/v pairs, 2=array of plain values)
		$fields = array('taxonomies' => 0, 'post_types' => 0, 'font_family' => 2, 'font_unit' => 2, 'orderby' => 1, 'single' => 1, 'display' => 1, 'textcase' => 1, 'save' => 2);
		$select = array();

		foreach ($fields as $field => $option) {
			$select[$field] = (0 == $option) // conveniently, only the 0s need a wide, multiple select... 
				? '<select id="'.esc_attr($this->get_field_id($field)).'[]" name="'.esc_attr($this->get_field_name($field)).'[]" class="widefat" size="6" multiple="true">'
				: '<select id="'.esc_attr($this->get_field_id($field)).'" name="'.esc_attr($this->get_field_name($field)).'">';

			foreach ($this->choices[$field] as $key => $value) {
				switch ($option) {
					case 0:		$key = $value->name; $value = $value->labels->name;
								$selected = (in_array($key, $instance[$field])); break;
					case 2:		$key = $value;
					case 1:		$selected = ($key == $instance[$field]); break;
				}
				$selected = ($selected) ? ' selected="selected"' : '';
				$select[$field].= '<option value="'.esc_attr($key).'"'.$selected.'>'.$value.'</option>';
			}
			$select[$field].= '</select>';
		}

		// taxonomies could've been deleted since we made this widget, and get_terms() crashes if any don't exist...
		foreach ($instance['taxonomies'] as $i => $tax) if (! taxonomy_exists($tax)) unset($instance['taxonomies'][$i]);

		// do this one separately because its a pita
		$all_terms = get_terms($instance['taxonomies'], array(
			'hide_empty'			 => false,
			'update_term_meta_cache' => false,
		));
		$sorted = array(); $this->sort_terms_hierarchically($all_terms, $sorted);

		$this->selected = $instance['exclude'];
		$select['excluded'] = '<select class="widefat"  id="'.esc_attr($this->get_field_id('exclude')).'[]" name="'.esc_attr($this->get_field_name('exclude')).'[]" size="10" multiple="true">'
						. $this->display_options_recursively($sorted)
						. '</select>';

		// TODO: do a loop like the selects if we have more than 2
		$checked = array(
			'order'		=> ($instance['order'] == 1) ? ' checked="checked"' : '',
			'nofollow'	=> ($instance['nofollow'] == 1) ? ' checked="checked"' : '',
		);

		
		// and now make the form itself
		// TODO: build this form programmatically if you add much more options
		$output.= '<div class="combined-taxonomies-tag-cloud">'
				
				. sprintf('<p><label class="half" for="%s">%s:</label><input type="text" class="widefat" id="%s" name="%s" value="%s"></p>',
						esc_attr($this->get_field_id('title')),
						__('Title', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title')),
						esc_attr($this->get_field_name('title')),
						esc_attr($instance['title'])
					)
				. '<hr>'
				
				// POST TYPES and TAXONOMIES in use
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('taxonomies')),
						__('Taxonomies To Use', 'CombinedTaxonomiesTagCloud'),
						$select['taxonomies']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('post_types')),
						__('Post Types To Use', 'CombinedTaxonomiesTagCloud'),
						$select['post_types']
					)
				. '<hr>'
				
				// TAG FONT attributes
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('font_family')),
						__('Tag Font', 'CombinedTaxonomiesTagCloud'),
						$select['font_family']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('font_unit')),
						__('Tag Size Units', 'CombinedTaxonomiesTagCloud'),
						$select['font_unit']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label><input type="text" size="3" id="%s" name="%s" value="%s"></p>',
						esc_attr($this->get_field_id('smallest')),
						__('Smallest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('smallest')),
						esc_attr($this->get_field_name('smallest')),
						(float) $instance['smallest']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label><input type="text" size="3" id="%s" name="%s" value="%s"></p>',
						esc_attr($this->get_field_id('largest')),
						__('Largest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('largest')),
						esc_attr($this->get_field_name('largest')),
						(float) $instance['largest']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('textcase')),
						__('Tag Text Case', 'CombinedTaxonomiesTagCloud'),
						$select['textcase']
					)
				. '<hr>'
				
				// NOT THESE TAGS
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('exclude')),
						__('Exclude These Tags', 'CombinedTaxonomiesTagCloud'),
						$select['excluded']
					)
				. '<hr>'
				
				// ORDERING
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('orderby')),
						__('Order Tags By', 'CombinedTaxonomiesTagCloud'),
						$select['orderby']
					)
				. sprintf('<p><label class="half" for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						esc_attr($this->get_field_id('order')),
						__('Reverse Order?', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('order')),
						esc_attr($this->get_field_name('order')),
						$checked['order']
					)
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label><input type="text" size="3" id="%s" name="%s" value="%s"></p>',
						__('The maximum number of tags that will be shown in this particular widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						__('Maximum Shown', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						esc_attr($this->get_field_name('maximum')),
						(int) $instance['maximum']
					)
				. '<hr>'
				
				// MISC
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label>%s</p>',
						__('How should single entry tags be treated?', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('single')),
						__('Tags With Just One Entry', 'CombinedTaxonomiesTagCloud'),
						$select['single']
					)
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Add the rel="nofollow" attribute to each of the tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						__('Make Links No-Follow?', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						esc_attr($this->get_field_name('nofollow')),
						$checked['nofollow']
					)
				. '<hr>'
				
				// STYLING
				. sprintf('<p><label class="half" for="%s">%s:</label>%s</p>',
						esc_attr($this->get_field_id('display')),
						__('List Style', 'CombinedTaxonomiesTagCloud'),
						$select['display']
					)
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the background color of the area underneath the title', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						__('Widget Background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						esc_attr($this->get_field_name('wbackground')),
						$instance['wbackground']
					)
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the background color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tbackground')),
						__('Tag Background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tbackground')),
						esc_attr($this->get_field_name('tbackground')),
						$instance['tbackground']
					)
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the foreground color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tforeground')),
						__('Tag Foreground', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tforeground')),
						esc_attr($this->get_field_name('tforeground')),
						$instance['tforeground']
					)
				. '<hr>'
				
				. sprintf('<p title="%s"><label class="half" for="%s">%s:</label>%s</p>',
						__('You can cache the generated tag cloud to save working it out every time', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('save')),
						__('Save Cloud For (Hours)', 'CombinedTaxonomiesTagCloud'),
						$select['save']
					)
				. '<hr>'
				. '</div>';

		echo $output;
	}

	private function display_options_recursively($terms = array(), $level = 0) {
		$output = '';
		foreach ($terms as $i => $term) {
			$selected = (in_array($term->term_id, $this->selected)) ? ' selected="selected"' : '';
			$padded_name = str_repeat('-- ', $level).$term->name;
			$output.= '<option class="level-'.$level.'" value="'.$term->term_id.'"'.$selected.'>'.$padded_name.' ('.$term->count.')</option>';
			if (isset($term->children) AND sizeof($term->children)) $output.= $this->display_options_recursively($term->children, $level+1);
		}
		return $output;
	}

	// by pospi @ http://wordpress.stackexchange.com/a/99516
	private function sort_terms_hierarchically(array &$cats, array &$into, $parentId = 0) {
		foreach ($cats as $i => $cat) {
			if ($cat->parent == $parentId) {
				$into[$cat->term_id] = $cat;
				unset($cats[$i]);
			}
		}
		foreach ($into as $topCat) {
			$topCat->children = array();
			$this->sort_terms_hierarchically($cats, $topCat->children, $topCat->term_id);
		}
	}

	private function is_valid_colour($value) {
		return preg_match('/^#(?:[0-9a-f]{3}){1,2}$/i', $value);
	}

}
