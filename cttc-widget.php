<?php

if (! defined('WPINC')) die;

class CombinedTaxonomiesTagCloudWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(false,
			__('Combined Tag Cloud', 'CombinedTaxonomiesTagCloud'), array(
				'description' => __('More adaptable version of the basic WP tag cloud widget.', 'CombinedTaxonomiesTagCloud'),
				'classname' => 'widget_tag_cloud',
			));
		
		// only load if we're using the widget (include inactive ones now we've got the shortcode way)
		if (is_admin() OR is_active_widget(false, false, $this->id_base, false)) {
			add_action('wp_loaded', array($this, 'make_default_selections'));
			// admin needs the colour picker and its javascript, as well as a mini form styling
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_style('combined-taxonomies-tag-cloud-admin-style', plugins_url('admin.css', __FILE__), false, null);
				wp_enqueue_style('wp-color-picker'); 
				wp_enqueue_script('combined-taxonomies-tag-cloud-admin-script', plugins_url('admin.js', __FILE__), array('wp-color-picker'), null, true);
				wp_localize_script('combined-taxonomies-tag-cloud-admin-script', 'font_stacks', $this->get_font_stacks());
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

	
	private function get_font_stacks(string $key = '') {
		// from https://gist.github.com/don1138/5761014
		$fonts = array(
			'System' => 
				'system, -apple-system, ".SFNSText-Regular", "San Francisco", "Roboto", "Segoe UI", "Helvetica Neue", "Lucida Grande", sans-serif',
			'Times New Roman' =>
				'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
			'Georgia' =>
				'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif", "Bitstream Vera Serif", "Liberation Serif", Georgia, serif',
			'Garamond' =>
				'"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',
			'Helvetica/Arial' =>
				'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
			'Verdana' =>
				'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
			'Trebuchet' =>
				'"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',
			'Impact' =>
				'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',
			'Monospace' =>
				'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace',
		);
		
		if (isset($fonts[$key])) {
			$fonts = $fonts[$key];
		} elseif ($key == '') {
			asort($fonts);
		} else {
			$fonts = '';
		}
		
		return $fonts;
	}
	
	
	
	public function make_default_selections() {
		// NOTE: $fields var in form() function below must having matching keys
		$this->choices = array(
			'align_h'			=> array(
									'left' => __('Left', 'CombinedTaxonomiesTagCloud'),
									'center' => __('Center', 'CombinedTaxonomiesTagCloud'),
									'right' => __('Right', 'CombinedTaxonomiesTagCloud'),
								),
			'align_v'			=> array(
									'top' => __('Top', 'CombinedTaxonomiesTagCloud'),
									'middle' => __('Center', 'CombinedTaxonomiesTagCloud'),
									'bottom' => __('Bottom', 'CombinedTaxonomiesTagCloud'),
								),
			'font_family'		=> array(__('Leave Alone', 'CombinedTaxonomiesTagCloud')) + array_keys($this->get_font_stacks()),
			'font_unit'			=> array('rem', 'em', 'pt', 'px', 'vw'),
			'orderby'			=> array(
									'name' => __('Alphabetically', 'CombinedTaxonomiesTagCloud'),
									'count' => __('By Count', 'CombinedTaxonomiesTagCloud'),
									'random' => __('Randomly', 'CombinedTaxonomiesTagCloud')
								),
			'post_types'		=> get_post_types(array('show_ui' => true), 'objects'),
			'save'				=> array(0, 1, 2, 4, 8, 12, 24, 48, 96), // hours
			'single'			=> array(
									'leave' => __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
									'remove' => __('Remove', 'CombinedTaxonomiesTagCloud'),
									'link' => __('Link to Entry', 'CombinedTaxonomiesTagCloud')
								),
			'taxonomies'		=> get_taxonomies(array('show_ui' => true), 'objects'),
			'text_case'			=> array(
									'' => __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
									'lower' => __('lowercase', 'CombinedTaxonomiesTagCloud'),
									'upper' => __('UPPERCASE', 'CombinedTaxonomiesTagCloud')
								),
			'text_decoration'	=> array(
									'' => __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
									'no_text_deco' => __('None', 'CombinedTaxonomiesTagCloud'),
									'under_always' => __('Always Underline', 'CombinedTaxonomiesTagCloud'),
									'under_hover' => __('Underline on Hover', 'CombinedTaxonomiesTagCloud'),
									'box_hover' => __('Box Shadow on Hover', 'CombinedTaxonomiesTagCloud'),
								),
		);
		sort($this->choices['taxonomies']);
		sort($this->choices['post_types']);
		
		$this->defaults = array(
			'align_h'			=> 'left',
			'align_v'			=> 'bottom',
			'exclude'			=> array(0),
			'font_family'		=> __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
			'font_unit'			=> 'em',
			'largest' 			=> 1.4,
			'maximum'			=> 999,
			'nofollow'			=> 0,
			'order'				=> 0, // 0 = asc, 1 = desc
			'orderby'			=> 'name',
			'post_types'		=> array('post'),
			'save'				=> 0,
			'scale_tag'			=> 0,
			'show_count'		=> 1,
			'single'			=> 'leave',
			'smallest' 			=> 0.6,
			'taxonomies'		=> array('post_tag'),
			'tbackground'		=> '',
			'tborder'			=> '',
			'text_case'			=> '',
			'text_decoration'	=> 'under_hover',
			'tforeground'		=> '',
			'title' 			=> '',
			'wbackground'		=> '',
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
				$classes = implode(' ', array(
					$tag['taxonomy'],
					$args['text_case'],
					($midway >= $size) ? 'smaller' : 'larger',
					($args['text_decoration'] != '') ? $args['text_decoration'] : '',
				));

				// build our link title from the component post type counts
				$link_title = array();
				foreach ($tag['types'] as $type => $count) {
					$link_title[]= (1 == $count) ? '1 '.$wp_post_types[$type]->labels->singular_name : $count.' '.$wp_post_types[$type]->labels->name;
				}
				$link_title = strtolower(implode(', ', $link_title));
				
				$cloud[] = sprintf('<a href="%s" %s class="%s" aria-label="%s" title="%s" %s>%s%s</a>',
					$link,
					($args['scale_tag']) ? sprintf(' style="font-size:%s%s"', $size, $args['font_unit']) : '',
					$classes, $link_title, $link_title, $nofollow,
					sprintf('<span class="tag-text">%s</span>', $tag['name']),
					($args['show_count']) ? sprintf(' <span class="tag-link-count">(%s)</span>', $tag['count']) : ''
				);
			}
			
			// NOTE: twentytwenty theme (at least) doesn't output widget IDs, unlike every other default WP theme,
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
			
			$title = apply_filters('widget_title', $instance['title']); if ('' != $title) $title = $args['before_title'].$title.$args['after_title'];
			
			$extra_classes = trim($args['align_h'].' '.$args['align_v']);
			
			// make the html
			$output = $args['before_widget']."\r\n"
					. $title."\n"
					. sprintf('<ul class="combinedtagcloud %s" role="list">%s</ul>',
						$extra_classes,
						'<li>'.implode("</li>\r\n<li>", $cloud).'</li>'
					)."\r\n"
					. $args['after_widget']."\r\n";

			// and (possibly) save the resulting html so we don't need to do this again for a while
			if (0 < $args['save']) set_transient($this->transient, $output, $args['save'] * HOUR_IN_SECONDS);
		}

		echo $output;
		
		
		$custom_css = '';
		
		if ($args['font_family'] != __('Leave Alone', 'CombinedTaxonomiesTagCloud')) {
			$custom_css = sprintf('#%s .combinedtagcloud a { font-family:%s; }',
				$args['widget_id'], $this->get_font_stacks($args['font_family'])
			);
		}
		
		if ($args['wbackground'] != '') {
			$custom_css.= sprintf('#%s .combinedtagcloud { background-color:%s; }', $args['widget_id'], $args['wbackground']);
		}

		if ($args['tborder'] != '') {
			$custom_css.= sprintf('#%s .combinedtagcloud li a { border-color:%s; }', $args['widget_id'], $args['tborder']);
		}
		
		if ($args['tbackground'] != '') {
			$custom_css.= sprintf('#%s .combinedtagcloud li a { background-color:%s; }', $args['widget_id'], $args['tbackground']);
			$custom_css.= sprintf('#%s .combinedtagcloud li a:hover { color:%s; }', $args['widget_id'], $args['tbackground']);
		}
		
		if ($args['tforeground'] != '') {
			$custom_css.= sprintf('#%s .combinedtagcloud li a { color:%s; }', $args['widget_id'], $args['tforeground']);
			$custom_css.= sprintf('#%s .combinedtagcloud li a:hover { background-color:%s; }', $args['widget_id'], $args['tforeground']);
		}
		
		if ($custom_css != '') {
			wp_add_inline_style('combined-taxonomies-tag-cloud-style', $custom_css);
		}
		
	}
	

	// update the widget ------------------------------------
	public function update($new, $old) {
		$instance = $old;
																				$instance['title'] = strip_tags($new['title']);

		// TODO: check post types and taxs in allowed array
		$instance['post_types'] = array(); foreach ($new['post_types'] as $type) $instance['post_types'][] = $type;
		$instance['taxonomies'] = array(); foreach ($new['taxonomies'] as $tax)  $instance['taxonomies'][] = $tax;

		if (in_array($new['font_family'], $this->choices['font_family']))		$instance['font_family'] = $new['font_family'];
																				$instance['scale_tag'] = (bool) $new['scale_tag'];
		if (in_array($new['font_unit'], $this->choices['font_unit']))			$instance['font_unit'] = $new['font_unit'];
																				$instance['smallest'] = sprintf('%0.2f', (float) $new['smallest']);
																				$instance['largest'] = sprintf('%0.2f', (float) $new['largest']);
																				
		if (in_array($new['align_h'], array_keys($this->choices['align_h'])))	$instance['align_h'] = $new['align_h'];
		if (in_array($new['align_v'], array_keys($this->choices['align_v'])))	$instance['align_v'] = $new['align_v'];
																				
		$instance['exclude'] = array(0);
		foreach ($new['exclude'] as $term_id)
			$instance['exclude'][] = absint($term_id);
			
		if (in_array($new['orderby'], array_keys($this->choices['orderby'])))	$instance['orderby'] = $new['orderby'];
																				$instance['order'] = (bool) $new['order'];
																				$instance['maximum'] = absint($new['maximum']);
																				
		if (in_array($new['single'], array_keys($this->choices['single'])))		$instance['single'] = $new['single'];
																				$instance['nofollow'] = (bool) $new['nofollow'];
																				
		if (in_array($new['text_case'], array_keys($this->choices['text_case'])))
			$instance['text_case'] = $new['text_case'];
			
		if (in_array($new['text_decoration'], array_keys($this->choices['text_decoration'])))
			$instance['text_decoration'] = $new['text_decoration'];
			
																				$instance['show_count'] = (bool) $new['show_count'];

		if ($this->is_valid_colour($new['wbackground']) OR $new['wbackground'] == '')
																				$instance['wbackground'] = $new['wbackground'];
		if ($this->is_valid_colour($new['tborder']) OR $new['tborder'] == '')
																				$instance['tborder'] = $new['tborder'];
		if ($this->is_valid_colour($new['tbackground']) OR $new['tbackground'] == '')
																				$instance['tbackground'] = $new['tbackground'];
		if ($this->is_valid_colour($new['tforeground']) OR $new['tforeground'] == '')
																				$instance['tforeground'] = $new['tforeground'];

																				
		if (in_array($new['save'], $this->choices['save']))						$instance['save'] = $new['save'];

		// either something's changed or we pressed the save button for the sake of it. regardless, delete our saved html and start again
		$this->transient = 'combined_taxonomies_tag_cloud_'.$this->id;
		delete_transient($this->transient);

		return $instance;
	}



	// form for the widget ----------------------------------
	public function form($instance) {
		// if (empty($instance)) return;
		// TODO: don't go through all this if we're not using the widget - need to account for freshly added widgets

		$instance = wp_parse_args((array) $instance, $this->defaults);

		// build up the various selects in the form (0=array of objects, 1=array of k/v pairs, 2=array of plain values)
		$fields = array(
			'align_h' => 1,
			'align_v' => 1,
			'font_family' => 2,
			'font_unit' => 2,
			'orderby' => 1,
			'post_types' => 0,
			'save' => 2,
			'single' => 1,
			'taxonomies' => 0,
			'text_case' => 1,
			'text_decoration' => 1,
		);
		
		$select = array();
		foreach ($fields as $field => $option) {
			$select[$field] = (0 == $option) // conveniently, only the 0s need a wide, multiple select... 
				? '<select id="'.esc_attr($this->get_field_id($field)).'[]" name="'.esc_attr($this->get_field_name($field)).'[]" class="widefat" size="6" multiple="true">'
				: '<select id="'.esc_attr($this->get_field_id($field)).'" class="'.$field.'" name="'.esc_attr($this->get_field_name($field)).'">';

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

		// TODO: do a loop like the selects if we keep adding more...
		$checked = array(
			'nofollow'	=> ($instance['nofollow'] == 1) ? ' checked="checked"' : '',
			'order'		=> ($instance['order'] == 1) ? ' checked="checked"' : '',
			'scale_tag'	=> ($instance['scale_tag'] == 1) ? ' checked="checked"' : '',
			'show_count'=> ($instance['show_count'] == 1) ? ' checked="checked"' : '',
		);

		
		// and now make the form itself
		// TODO: build this form programmatically if you add much more options
		$output = '<div class="combined-taxonomies-tag-cloud">'
				
				. sprintf('<p><label for="%s">%s:</label><input type="text" class="widefat" id="%s" name="%s" value="%s"></p>',
						esc_attr($this->get_field_id('title')),
						__('Title', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title')),
						esc_attr($this->get_field_name('title')),
						esc_attr($instance['title'])
					)
				
				// POST TYPES and TAXONOMIES in use
				. sprintf('<fieldset><legend>%s</legend><div>', __('What To Include', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Count only tags belonging to these types of taxonomy', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('taxonomies')),
						__('Taxonomies To Use', 'CombinedTaxonomiesTagCloud'),
						$select['taxonomies']
					)
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Count only tags belonging to these types of post', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('post_types')),
						__('Post Types To Use', 'CombinedTaxonomiesTagCloud'),
						$select['post_types']
					)
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Ignore these tags - though if posts have non-excluded tags as well, they will still get included', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('exclude')),
						__('Exclude These Tags', 'CombinedTaxonomiesTagCloud'),
						$select['excluded']
					)
				. '</div></fieldset>'
				
				// TAG FONT attributes
				. sprintf('<fieldset><legend>%s</legend><div>', __('Text', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="full"><label class="half" for="%s">%s:</label>%s<br><span class="font_list">%s</span></p>',
						__('Choose a font stack to apply to the tags - if in doubt, leave it for your theme or font plugin to handle', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_family')),
						__('Font Stack', 'CombinedTaxonomiesTagCloud'),
						$select['font_family'],
						$this->get_font_stacks($instance['font_family'])
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('You can change the case of all the tags if you wish to make them consistent', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('text_case')),
						__('Text Case', 'CombinedTaxonomiesTagCloud'),
						$select['text_case']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('Change how the tag text uses the CSS text decoration property', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('text_decoration')),
						__('Text Decoration', 'CombinedTaxonomiesTagCloud'),
						$select['text_decoration']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Show the count of the number of posts that have that term', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('show_count')),
						__('Show Count', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('show_count')),
						esc_attr($this->get_field_name('show_count')),
						$checked['show_count']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="checkbox" id="%s" class="%s" name="%s" value="1"%s></p>',
						__('Make the size of the tag bigger if more posts have it', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('scale_tag')),
						__('Scale Font', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('scale_tag')),
						'scale_tag',
						esc_attr($this->get_field_name('scale_tag')),
						$checked['scale_tag']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('These are the units that will be used to scale the tag size - best to follow what the rest of your theme is using', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_unit')),
						__('Font Units', 'CombinedTaxonomiesTagCloud'),
						$select['font_unit']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="text" size="3" id="%s" name="%s" value="%s"></p>',
						__('Even a tag with only one post will not be smaller than this', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('smallest')),
						__('Smallest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('smallest')),
						esc_attr($this->get_field_name('smallest')),
						(float) $instance['smallest']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="text" size="3" id="%s" name="%s" value="%s"></p>',
						__('Even a tag with a million posts will not be larger than this', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('largest')),
						__('Largest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('largest')),
						esc_attr($this->get_field_name('largest')),
						(float) $instance['largest']
					)
				. '</div></fieldset>'
				
				
				// COLOURS
				. sprintf('<fieldset><legend>%s</legend><div>', __('Colors', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="full"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the background color of the whole widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						__('Widget Background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						esc_attr($this->get_field_name('wbackground')),
						$instance['wbackground']
					)
				. sprintf('<p title="%s" class="full"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the border color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder')),
						__('Tag Border', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder')),
						esc_attr($this->get_field_name('tborder')),
						$instance['tborder']
					)
				. sprintf('<p title="%s" class="full"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the background color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tbackground')),
						__('Tag Background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tbackground')),
						esc_attr($this->get_field_name('tbackground')),
						$instance['tbackground']
					)
				. sprintf('<p title="%s" class="full"><label class="half" for="%s">%s:</label><input class="color-field" type="text" size="5" id="%s" name="%s" value="%s"></p>',
						__('Choose the foreground color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tforeground')),
						__('Tag Foreground', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tforeground')),
						esc_attr($this->get_field_name('tforeground')),
						$instance['tforeground']
					)
				. '</div></fieldset>'
				
				// ALIGNMENT
				. sprintf('<fieldset><legend>%s</legend><div>', __('Tag Alignment', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('How to align the tags horizontally within the widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('align_h')),
						__('Horizontal', 'CombinedTaxonomiesTagCloud'),
						$select['align_h']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('How to align the tags vertically within each line of the cloud', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('align_v')),
						__('Vertical', 'CombinedTaxonomiesTagCloud'),
						$select['align_v']
					)
				. '</div></fieldset>'
				
				// ORDERING
				. sprintf('<fieldset><legend>%s</legend><div>', __('Tag Display', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('How do you want to order the resulting tags?', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('orderby')),
						__('Order Tags By', 'CombinedTaxonomiesTagCloud'),
						$select['orderby']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Choose to reverse whatever order you just picked', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('order')),
						__('Reverse Order', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('order')),
						esc_attr($this->get_field_name('order')),
						$checked['order']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="number" size="3" id="%s" name="%s" value="%s"></p>',
						__('The maximum number of tags that will be shown in this particular widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						__('Maximum Shown', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						esc_attr($this->get_field_name('maximum')),
						(int) $instance['maximum']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('What to do with tags that only have one post associated with them', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('single')),
						__('Single Entry Tags', 'CombinedTaxonomiesTagCloud'),
						$select['single']
					)
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Add the rel=&quot;nofollow&quot; attribute to each of the tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						__('Make Links No-Follow', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						esc_attr($this->get_field_name('nofollow')),
						$checked['nofollow']
					)
				. '</div></fieldset>'
				
				// CACHE
				. sprintf('<fieldset><legend>%s</legend><div>', __('Caching', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="half"><label for="%s">%s:</label>%s</p>',
						__('You can cache the generated tag cloud to save working it out every time', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('save')),
						__('Save Cloud For (Hours)', 'CombinedTaxonomiesTagCloud'),
						$select['save']
					)
				. '</div></fieldset>'
				
				// SHORTCODE
				. sprintf('<fieldset><legend>%s</legend><div>', __('Shortcode', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s" class="full">%s</p>',
						__('You can use this shortcode to display the widget on its own', 'CombinedTaxonomiesTagCloud'),
						($this->number == '__i__')
							? __('Save the widget to make the shortcode', 'CombinedTaxonomiesTagCloud')
							: sprintf('<span data-copy-text="%s">%s: %s</span>',
								sprintf('[cttc cloud=%d]', $this->number),
								__('Copy and paste this', 'CombinedTaxonomiesTagCloud'),
								sprintf('[cttc cloud=%d]', $this->number)
							)
						)
				. '</div></fieldset>'
				
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
