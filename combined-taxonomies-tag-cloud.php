<?php
/*
Plugin Name: Combined Taxonomies Tag Cloud
Version: 0.21.3
Plugin URI: http://drakard.com/
Description: Makes a tag cloud widget out of multiple taxonomies across multiple post types.
Author: Keith Drakard
Author URI: http://drakard.com/
*/


if (! defined('WPINC')) die;

class CombinedTaxonomiesTagCloudPlugin {

	public function __construct() {
		load_plugin_textdomain('CombinedTaxonomiesTagCloud', false, dirname(plugin_basename(__FILE__)).'/languages');

		add_action('widgets_init', function() {
			register_widget('CombinedTaxonomiesTagCloudWidget');
		//	unregister_widget('WP_Widget_Tag_Cloud');
		});

		add_filter('pre_get_posts', array($this, 'add_post_types_to_the_loop'));
	}


	// make sure that the taxonomies are added to the relevant tag archive pages
	public function add_post_types_to_the_loop($query) {
		if ($query->is_main_query() AND ! is_admin()) {

			// we're on an archive page, so make sure we include all the possible post types in the loop
			if (is_tag() OR is_category()) { // shouldn't need is_tax() as that already pulls in the others

				$looking_for = array();
				foreach ($query->tax_query->queries as $tax) {
					$looking_for[] = $tax['taxonomy'];
				}

				$all_post_types = get_post_types(array(
					'show_ui' => true,
				));

				$include = array(); foreach ($all_post_types as $type) {
					$post_type_has_these = get_object_taxonomies($type);
					if (array_intersect($looking_for, $post_type_has_these)) $include[] = $type;
				}

				$query->set('post_type', $include);
			}

		}
	}
	
}

$CombinedTaxonomiesTagCloud = new CombinedTaxonomiesTagCloudPlugin();








class CombinedTaxonomiesTagCloudWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(false, __('Combined Tag Cloud', 'CombinedTaxonomiesTagCloud'), array('description' => __('More adaptable version of the basic WP tag cloud widget.', 'CombinedTaxonomiesTagCloud'), 'classname' => 'widget_tag_cloud'));
		
		// only load if we're using the widget
		if (is_admin() OR is_active_widget(false, false, $this->id_base, true)) {
			add_action('wp_loaded', array($this, 'make_default_selections'));
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_style('wp-color-picker'); 
				wp_enqueue_script('combined-taxonomies-tag-cloud-script', plugins_url('admin.js', __FILE__), array('wp-color-picker'), false, true);
			});

			// only need our stylesheet on the front end, hence the wp_ hooks
			add_action('wp_head', function() { wp_register_style('combined-taxonomies-tag-cloud-style', plugins_url('style.css', __FILE__), false, '0.1'); });
			add_action('wp_footer', function() { wp_enqueue_style('combined-taxonomies-tag-cloud-style'); });
		}
	}

	public function make_default_selections() {
		$this->choices = array(
			'taxonomies'	=> get_taxonomies(array('show_ui' => true), 'objects'),
			'post_types'	=> get_post_types(array('show_ui' => true), 'objects'),
			'unit'			=> array('rem', 'em', 'pt', 'px'),
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
								'diy' => __('Your Own Stylesheet', 'CombinedTaxonomiesTagCloud'),
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

		$this->defaults = array(
			'title' 		=> '',
			'post_types'	=> array('post'),
			'taxonomies'	=> array('post_tag'),
			'unit'			=> 'rem',
			'smallest' 		=> 0.8,
			'largest' 		=> 2.4,
			'exclude'		=> array(0),
			'maximum'		=> 999,
			'orderby'		=> 'name',
			'order'			=> 0, // 0 = asc, 1 = desc
			'single'		=> 'leave',
			'nofollow'		=> 0,
			'textcase'		=> '',
			'display'		=> 'flat',
			'wbackground'	=> '#ffffff',
			'tbackground'	=> '#ffffff',
			'tforeground'	=> '#000000',
			'save'			=> 12,
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

				$cloud[] = '<a href="'.$link.'" style="font-size:'.$size.$args['unit'].'" class="'.$classes.'" title="'.$link_title.'"'.$nofollow.'>'.$tag['name'].'</a>';
			}



			$title = apply_filters('widget_title', $instance['title']); if ('' != $title) $title = $args['before_title'].$title.$args['after_title'];

			switch ($args['display']) {
				case 'diy':			
				case 'flat':		$cloud = '<div class="combinedtagcloud">'.implode('', $cloud).'</div>';
									break;
				case 'olist':		$cloud = '<ol class="combinedtagcloud list"><li>'.implode('</li><li>', $cloud).'</li></ol>'; break;
				case 'ulist':		$cloud = '<ul class="combinedtagcloud list"><li>'.implode('</li><li>', $cloud).'</li></ul>'; break;
				case 'boxes':		$cloud = '<div class="combinedtagcloud boxes">'.implode('', $cloud).'</div>'; break;
				default:			$cloud = '';
			}

			$output = $args['before_widget']."\n"
					. $title."\n".$cloud."\n"
					. $args['after_widget']."\n";


			// save the resulting html so we don't need to do this again for a while
			if (0 < $args['save']) set_transient($this->transient, $output, $args['save'] * HOUR_IN_SECONDS);
		}


		// BUG TODO: if you set this to diy for any instance of the widget, then all preceeding widgets will also lose their styles...
		if ('diy' == $args['display']) {
			wp_deregister_style('combined-taxonomies-tag-cloud-style');

		} else {

			$custom_css = '
				#'.$args['widget_id'].' .combinedtagcloud {
					background-color:'.$args['wbackground'].';
				}
				#'.$args['widget_id'].' a {
					background-color:'.$args['tbackground'].'; color:'.$args['tforeground'].';
				}
				#'.$args['widget_id'].' a:hover {
					background-color:'.$args['tforeground'].'; color:'.$args['tbackground'].';
				}
			';

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

		if (in_array($new['unit'], $this->choices['unit']))								$instance['unit'] = $new['unit'];
																						$instance['smallest'] = sprintf('%0.2f', (float) $new['smallest']);
																						$instance['largest'] = sprintf('%0.2f', (float) $new['largest']);
		$instance['exclude'] = array(0); foreach ($new['exclude'] as $term_id)			$instance['exclude'][] = absint($term_id);
		if (in_array($new['orderby'], array_keys($this->choices['orderby'])))			$instance['orderby'] = $new['orderby'];
																						$instance['order'] = (bool) $new['order'];
																						$instance['maximum'] = absint($new['maximum']);
		if (in_array($new['single'], array_keys($this->choices['single'])))				$instance['single'] = $new['single'];
																						$instance['nofollow'] = (bool) $new['nofollow'];
		if (in_array($new['textcase'], array_keys($this->choices['textcase'])))			$instance['textcase'] = $new['textcase'];
		if (in_array($new['display'], array_keys($this->choices['display'])))			$instance['display'] = $new['display'];

		if ($this->is_valid_colour($new['wbackground']))								$instance['wbackground'] = $new['wbackground'];
		if ($this->is_valid_colour($new['tbackground']))								$instance['tbackground'] = $new['tbackground'];
		if ($this->is_valid_colour($new['tforeground']))								$instance['tforeground'] = $new['tforeground'];

		if (in_array($new['save'], $this->choices['save']))								$instance['save'] = $new['save'];

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
		$fields = array('taxonomies' => 0, 'post_types' => 0, 'unit' => 2, 'orderby' => 1, 'single' => 1, 'display' => 1, 'textcase' => 1, 'save' => 2);
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

		$output = '<style type="text/css">'
				. ' .combined-taxonomies-tag-cloud { margin:0 0 1rem; }'
				. ' .combined-taxonomies-tag-cloud .half { width:50%; display:inline-block; }'
				. ' .combined-taxonomies-tag-cloud p { margin:0.2rem 0; }'
				. ' .combined-taxonomies-tag-cloud hr { margin:1rem 0; padding:0.1rem; border:0.1rem dotted #ccc; background-color:#fafafa; }'
				. ' .combined-taxonomies-tag-cloud select, .combined-taxonomies-tag-cloud input { margin:0.4rem 0; }'
				. ' .combined-taxonomies-tag-cloud select[multiple] { min-height:5rem; }'
				. ' .combined-taxonomies-tag-cloud input { height:1.8rem; }'
				. ' .combined-taxonomies-tag-cloud input[type=checkbox] { margin:0!important; width:1.4rem; }'
				. ' .combined-taxonomies-tag-cloud .wp-picker-container { vertical-align:top; }'
				. '</style>';

		// TODO: build this form programmatically if you add much more options
		$output.= '<div class="combined-taxonomies-tag-cloud">'
				
				. '<p><label for="'.esc_attr($this->get_field_id('title')).'">'.__('Title', 'CombinedTaxonomiesTagCloud').':</label>'
				. '<input type="text" class="widefat" id="'.esc_attr($this->get_field_id('title')).'" name="'.esc_attr($this->get_field_name('title')).'"'
				. ' value="'.esc_attr($instance['title']).'"></p>'
				. '<hr>'
				
				. '<p><label for="'.esc_attr($this->get_field_id('taxonomies')).'">'.__('Taxonomies To Use', 'CombinedTaxonomiesTagCloud').':</label>'.$select['taxonomies'].'</p>'
				. '<p><label for="'.esc_attr($this->get_field_id('post_types')).'">'.__('Post Types To Use', 'CombinedTaxonomiesTagCloud').':</label>'.$select['post_types'].'</p>'
				. '<hr>'

				. '<p><label class="half" for="'.esc_attr($this->get_field_id('unit')).'">'.__('Tag Size Units', 'CombinedTaxonomiesTagCloud').':</label>'.$select['unit'].'</p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('smallest')).'">'.__('Smallest Tag Size', 'CombinedTaxonomiesTagCloud').'</label>'
					. '<input type="text" size="3" id="'.esc_attr($this->get_field_id('smallest')).'" name="'.esc_attr($this->get_field_name('smallest')).'" value="'.(float) $instance['smallest'].'"></p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('largest')).'">'.__('Largest Tag Size', 'CombinedTaxonomiesTagCloud').'</label>'
					. '<input type="text" size="3" id="'.esc_attr($this->get_field_id('largest')).'" name="'.esc_attr($this->get_field_name('largest')).'" value="'.(float) $instance['largest'].'"></p>'
				. '<hr>'
				
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('exclude')).'">'.__('Exclude These Tags', 'CombinedTaxonomiesTagCloud').':</label>'.$select['excluded'].'</p>'
				. '<hr>'

				. '<p><label class="half" for="'.esc_attr($this->get_field_id('orderby')).'">'.__('Order Tags By', 'CombinedTaxonomiesTagCloud').':</label>'.$select['orderby'].'</p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('order')).'">'.__('Reverse Order?', 'CombinedTaxonomiesTagCloud').':</label>'
					. '<input type="checkbox" id="'.esc_attr($this->get_field_id('order')).'" name="'.esc_attr($this->get_field_name('order')).'" value="1"'.$checked['order'].'></p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('maximum')).'">'.__('Maximum Shown', 'CombinedTaxonomiesTagCloud').':</label>'
					. '<input type="text" size="3" id="'.esc_attr($this->get_field_id('maximum')).'" name="'.esc_attr($this->get_field_name('maximum')).'" value="'.(int) $instance['maximum'].'"></p>'
				. '<hr>'

				. '<p><label class="half" for="'.esc_attr($this->get_field_id('single')).'">'.__('Tags With Just One Entry', 'CombinedTaxonomiesTagCloud').':</label>'.$select['single'].'</p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('nofollow')).'">'.__('Make Links No-Follow?', 'CombinedTaxonomiesTagCloud').':</label>'
					. '<input type="checkbox" id="'.esc_attr($this->get_field_id('nofollow')).'" name="'.esc_attr($this->get_field_name('nofollow')).'" value="1"'.$checked['nofollow'].'></p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('textcase')).'">'.__('Tag Text Case', 'CombinedTaxonomiesTagCloud').':</label>'.$select['textcase'].'</p>'
				. '<hr>'

				. '<p><label class="half" for="'.esc_attr($this->get_field_id('display')).'">'.__('Tag Style', 'CombinedTaxonomiesTagCloud').':</label>'.$select['display'].'</p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('wbackground')).'">'.__('Widget Background', 'CombinedTaxonomiesTagCloud').'</label>'
					. '<input type="text" class="color-field" size="5" id="'.esc_attr($this->get_field_id('wbackground')).'" name="'.esc_attr($this->get_field_name('wbackground')).'" value="'.$instance['wbackground'].'"></p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('tbackground')).'">'.__('Tag Background', 'CombinedTaxonomiesTagCloud').'</label>'
					. '<input type="text" class="color-field" size="5" id="'.esc_attr($this->get_field_id('tbackground')).'" name="'.esc_attr($this->get_field_name('tbackground')).'" value="'.$instance['tbackground'].'"></p>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('tforeground')).'">'.__('Tag Foreground', 'CombinedTaxonomiesTagCloud').'</label>'
					. '<input type="text" class="color-field" size="5" id="'.esc_attr($this->get_field_id('tforeground')).'" name="'.esc_attr($this->get_field_name('tforeground')).'" value="'.$instance['tforeground'].'"></p>'
				

				. '<hr>'
				. '<p><label class="half" for="'.esc_attr($this->get_field_id('save')).'">'.__('Save Cloud For (Hours)', 'CombinedTaxonomiesTagCloud').':</label>'.$select['save'].'</p>'
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
