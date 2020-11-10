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
			// load the config after wp has finished loading, else you won't have all the CPTs...
			add_action('wp_loaded', array($this, 'load_config'));
			
			// admin needs the colour picker and its javascript, as well as a mini form styling
			add_action('admin_enqueue_scripts', function() {
				// general admin form styling
				wp_enqueue_style('combined-taxonomies-tag-cloud-admin-style', plugins_url('resources/css/admin.css', __FILE__), false, null);
				
				// https://github.com/Automattic/Iris/issues/13 - now 7+ years and counting
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_script('wp-color-picker-alpha', plugins_url('resources/wp-color-picker-alpha/src/wp-color-picker-alpha.js', __FILE__), array('wp-color-picker'), null, true);
				
				// admin js
				wp_enqueue_script('combined-taxonomies-tag-cloud-admin-script', plugins_url('resources/js/admin.js', __FILE__), array('wp-color-picker-alpha'), null, true);
				wp_localize_script('combined-taxonomies-tag-cloud-admin-script', 'font_stacks', $this->get_font_stacks());
				wp_localize_script('combined-taxonomies-tag-cloud-admin-script', 'cttc_ajax', $this->get_ajax());
				
				// and include the front styling so we can demo the tag effects
				wp_enqueue_style('combined-taxonomies-tag-cloud-style', plugins_url('resources/css/style.css', __FILE__), false, null);
			});
			
			
			add_action('wp_ajax_cttc_get_terms', array($this, 'get_terms'));
			add_action('wp_ajax_cttc_get_contrast', array($this, 'get_contrast'));
			
			// only need our stylesheet on the front end, but we can't just use the wp_enqueue_scripts action as we may be adding inline styles
			add_action('wp_head', function() {
				wp_register_style('combined-taxonomies-tag-cloud-style', plugins_url('resources/css/style.css', __FILE__), false, null);
			});
			add_action('wp_footer', function() {
				wp_enqueue_style('combined-taxonomies-tag-cloud-style');
			});
		}
	}
	
	
	// array definitions were getting a bit on the long side, so moved out to a separate file
	public function load_config() {
		require_once 'config.php';
		foreach ($config as $key => $value) {
			$this->{$key} = $value;
		}
		$this->choices['font_family'] += array_keys($this->get_font_stacks());
	}
	
	
	
	// display the widget -----------------------------------
	public function widget($args, $instance) {
		$instance = wp_parse_args($instance, $this->defaults);
		$args = array_merge($args, $instance);
		
		// check it's vaguely sensible
		if (empty($args['post_types']) OR empty($args['taxonomies'])) {
			echo sprintf('<p>%s</p>', __('CTTC: You need to pick some post types and taxonomies to go in this widget.', 'CombinedTaxonomiesTagCloud'));
			return false;
		}
		
		// get any tags we're auto highlighting on this page
		$page_term_ids = $this->get_highlight_ids($args, $instance);
		
		// and make a transient id of this widget id + the tags to highlight
		$this->transient = sprintf('combined_taxonomies_tag_cloud_%s_%s', $this->id, md5(json_encode($page_term_ids)));
		
		// have we made it before?
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
					$args['fx_two_dee'].' '.$args['fx_backgrounds'].' '.$args['fx_shadows'],
					($midway >= $size) ? 'smaller' : 'larger',
					($args['text_decoration'] != '') ? $args['text_decoration'] : '',
					(in_array($tag['term_id'], $page_term_ids)) ? 'highlight' : '',
				));

				// build our link title from the component post type counts
				$link_title = array();
				foreach ($tag['types'] as $type => $count) {
					$link_title[]= (1 == $count) ? '1 '.$wp_post_types[$type]->labels->singular_name : $count.' '.$wp_post_types[$type]->labels->name;
				}
				$link_title = strtolower(implode(', ', $link_title));
				
				$cloud[] = sprintf('<a href="%s" %s class="%s" aria-label="%s" title="%s" %s>%s%s</a>',
					$link,
					($args['scale_tag']) ? sprintf(' style="font-size:%.1f%s"', $size, $args['font_unit']) : '',
					$classes, $link_title, $link_title, $nofollow,
					sprintf('<span class="tag-text">%s</span>', $tag['name']),
					($args['show_count']) ? sprintf(' <span class="tag-link-count">(%s)</span>', $tag['count']) : ''
				);
			}
			
			// NOTE: twentytwenty theme (at least) doesn't output widget IDs, unlike every other default WP theme,
			//       so make sure there's something we can use to recognize individual widgets
			if (! stristr($args['before_widget'], ' id=')) {
				// only want to replace it once, so can't just use str_replace
				$pos = strpos($args['before_widget'], '>');
				if ($pos !== false) {
					$args['before_widget'] = substr_replace($args['before_widget'], ' id="'.$args['widget_id'].'">', $pos, 1);
				} else {
					// NOTE: the theme has deliberately registered the sidebar without even a bare tag before the widget
					//       presumably for a reason, so leave it - it should just look like the default cloud settings
				}
			}
			
			$title = apply_filters('widget_title', $instance['title']); if ('' != $title) $title = $args['before_title'].$title.$args['after_title'];
			
			// make the html
			$output = $args['before_widget']."\r\n"
					. $title."\n"
					. sprintf('<ul class="combined-taxonomies-tag-cloud" role="list">%s</ul>',
						'<li>'.implode("</li>\r\n<li>", $cloud).'</li>'
					)."\r\n"
					. $args['after_widget']."\r\n";

			// and (possibly) save the resulting html so we don't need to do this again for a while
			if (0 < $args['save']) set_transient($this->transient, $output, $args['save'] * HOUR_IN_SECONDS);
		}
		
		echo $output;
		
		
		// now get together all our css params (can't put this in the transient without ignoring wp_add_inline_style)
		$css_vars = array(
			sprintf('--fontFamily:%s;', $this->get_font_stacks($args['font_family'])),
			
			sprintf('--titleAlignment:%s;', $args['align_title']),
			vsprintf('--titleColor:rgba(%d,%d,%d,%.2f);', $args['title_color']),
			
			vsprintf('--widgetBackgroundColor:rgba(%d,%d,%d,%.2f);', $args['wbackground']),
			sprintf('--widgetBorderRadius:%.2f%s;', $args['wborder_radius'], $args['font_unit']),
			sprintf('--widgetPadding:%.2f%s;', $args['wpadding'], $args['font_unit']),
			sprintf('--widgetFontSize:%.2f%s;', $args['font_base'], $args['font_unit']),
			
			sprintf('--columnGap:%.2f%s;', $args['column_gap'], $args['font_unit']),
			sprintf('--rowGap:%.2f%s;', $args['row_gap'], $args['font_unit']),
			
			sprintf('--alignHorizontal:%s;', $args['align_h']),
			sprintf('--alignVertical:%s;', $args['align_v']),
			
			sprintf('--linkPaddingX:%.2f%s;', $args['tag_padding_x'], $args['font_unit']),
			sprintf('--linkPaddingY:%.2f%s;', $args['tag_padding_y'], $args['font_unit']),
			
			sprintf('--fxTimingSpeed:%.2fs;', $args['fx_timing']),
		
			vsprintf('--backColor1:rgba(%d,%d,%d,%.2f);', $args['tcolor1']),
			vsprintf('--backColor2:rgba(%d,%d,%d,%.2f);', $args['tcolor2']),
			vsprintf('--textColor1:rgba(%d,%d,%d,%.2f);', $args['tag_text_color_1']),
			vsprintf('--textColor2:rgba(%d,%d,%d,%.2f);', $args['tag_text_color_2']),
			
			sprintf('--shadowColor:%s;', ($args['fx_shadows'] != 'fx_sh_none') ? $args['tshadow'] : 'transparent'),
			
			sprintf('--borderRadius:%.2f%s;', $args['border_radius'], $args['font_unit']),
			
			($args['border_style'] != '') ? sprintf('--borderStyle:%s;', $args['border_style']) : '',
			($args['border_style'] != '') ? sprintf('--borderColor1:%s;', $args['tborder1']) : '',
			($args['border_style'] != '') ? sprintf('--borderColor2:%s;', $args['tborder2']) : '',
			($args['border_style'] != '') ? sprintf('--borderWidth:%.2f%s;', $args['border_width'], $args['font_unit']) : '',
		);
		
		
		// like the sql prepare statement...
		wp_add_inline_style('combined-taxonomies-tag-cloud-style', call_user_func_array('sprintf', array_merge(array('#%s { '.implode(' ', array_fill(0, count($css_vars), '%s')).' }'), array($args['widget_id']), $css_vars)));
		
	}
	

	// update the widget ------------------------------------
	public function update($new, $instance) {
		
		// NOTE: validate the checkboxes before wp_parse_args because unchecked boxes don't get passed via POST
		$checkboxes = array('auto_text_color', 'nofollow', 'order', 'scale_tag', 'show_count');
		foreach ($checkboxes as $box) {
			$instance[$box] = (isset($new[$box]) AND $new[$box]);
		}
		
		// now we can fill in any blanks in $new
		$new = wp_parse_args($new, $this->defaults);
		
		// TODO: check post types and taxs in allowed array
		$instance['exclude'] = array(0);   foreach ($new['exclude'] as $term_id) $instance['exclude'][] = absint($term_id);
		$instance['post_types'] = array(); foreach ($new['post_types'] as $type) $instance['post_types'][] = $type;
		$instance['taxonomies'] = array(); foreach ($new['taxonomies'] as $tax)  $instance['taxonomies'][] = $tax;
		
		// validation by simple cast (or strip_tags wp function)
		$instance['border_radius'] = sprintf('%0.2f', (float) $new['border_radius']);
		$instance['border_width'] = sprintf('%0.2f', (float) $new['border_width']);
		$instance['column_gap'] = sprintf('%0.2f', (float) $new['column_gap']);
		$instance['font_base'] = sprintf('%0.2f', (float) $new['font_base']);
		$instance['fx_timing'] = sprintf('%0.2f', (float) $new['fx_timing']);
		$instance['largest'] = sprintf('%0.2f', (float) $new['largest']);
		$instance['maximum'] = absint($new['maximum']);
		$instance['row_gap'] = sprintf('%0.2f', (float) $new['row_gap']);
		$instance['smallest'] = sprintf('%0.2f', (float) $new['smallest']);
		$instance['tag_padding_x'] = sprintf('%0.2f', (float) $new['tag_padding_x']);
		$instance['tag_padding_y'] = sprintf('%0.2f', (float) $new['tag_padding_y']);
		$instance['title'] = strip_tags($new['title']);
		$instance['wborder_radius'] = sprintf('%0.2f', (float) $new['wborder_radius']);
		$instance['wpadding'] = sprintf('%0.2f', (float) $new['wpadding']);
		
		
		// validation by comparison to defined choices
		// TODO: link this to the $fields (0-2) definitions so you can do this all in a loop...
		if (in_array($new['font_family'], $this->choices['font_family']))
			$instance['font_family'] = $new['font_family'];
			
		if (in_array($new['font_unit'], $this->choices['font_unit']))
			$instance['font_unit'] = $new['font_unit'];
			
		if (in_array($new['save'], $this->choices['save']))
			$instance['save'] = $new['save'];

		$keys = array('align_h', 'align_title', 'align_v', 'border_style', 'fx_backgrounds', 'fx_shadows', 'fx_two_dee', 'highlight', 'orderby', 'single', 'text_case', 'text_decoration');
		foreach ($keys as $key) {
			if (in_array($new[$key], array_keys($this->choices[$key]))) $instance[$key] = $new[$key];
		}
		
		
		// and colors now need to always be defined (hard to work out contrasting colours if you don't know what to contrast)
		$instance['title_color'] = ($this->is_valid_color($new['title_color']))
			? $this->convert_color_to_rgba($new['title_color']) : $this->defaults['title_color'];
			
		$instance['wbackground'] = ($this->is_valid_color($new['wbackground']))
			? $this->convert_color_to_rgba($new['wbackground']) : $this->defaults['wbackground'];
			
		$instance['tcolor1'] = ($this->is_valid_color($new['tcolor1']))
			? $this->convert_color_to_rgba($new['tcolor1']) : $this->defaults['tcolor1'];
		$instance['tcolor2'] = ($this->is_valid_color($new['tcolor2']))
			? $this->convert_color_to_rgba($new['tcolor2']) : $this->defaults['tcolor2'];
		
		
		if ($instance['auto_text_color']) {
			$instance['tag_text_color_1'] = $this->get_contrasting_text_color($this->blend_arrays($instance['tcolor1'], $instance['wbackground']));
			$instance['tag_text_color_2'] = $this->get_contrasting_text_color($this->blend_arrays($instance['tcolor2'], $instance['wbackground']));
		} else {
			$instance['tag_text_color_1'] = ($this->is_valid_color($new['tag_text_color_1']))
			? $this->convert_color_to_rgba($new['tag_text_color_1']) : $this->defaults['tag_text_color_1'];
			$instance['tag_text_color_2'] = ($this->is_valid_color($new['tag_text_color_2']))
			? $this->convert_color_to_rgba($new['tag_text_color_2']) : $this->defaults['tag_text_color_2'];
		}
		
		$instance['tborder1'] = ($this->is_valid_color($new['tborder1'])) ? $new['tborder1'] : $this->defaults['tborder1'];
		$instance['tborder2'] = ($this->is_valid_color($new['tborder2'])) ? $new['tborder2'] : $this->defaults['tborder2'];
		$instance['tshadow'] = ($this->is_valid_color($new['tshadow'])) ? $new['tshadow'] : $this->defaults['tshadow'];
		
		// BUG: not deleting single page highlighting now
		// either something's changed or we pressed the save button for the sake of it. regardless, delete our saved html and start again
		$this->transient = 'combined_taxonomies_tag_cloud_'.$this->id;
		delete_transient($this->transient);

		return $instance;
	}



	// form for the widget ----------------------------------
	public function form($instance) {
		// if (empty($instance)) return;
		// TODO: don't go through all this if we're not using the widget - need to account for freshly added widgets
		
		$instance = $this->update((array) $instance, $this->defaults);
		
		// build up the various selects in the form (0=array of objects, 1=array of k/v pairs, 2=array of plain values)
		$fields = array(
			'align_h' => 1,
			'align_title' => 1,
			'align_v' => 1,
			'border_style' => 1,
			'fx_backgrounds' => 1,
			'fx_shadows' => 1,
			'fx_two_dee' => 1,
			'font_family' => 2,
			'font_unit' => 2,
			'highlight' => 1,
			'orderby' => 1,
			'post_types' => 0,
			'save' => 2,
			'single' => 1,
			'taxonomies' => 0,
			'text_case' => 1,
			'text_decoration' => 1,
		);
		
		// TODO: this is getting silly now
		$css_vars = array(
			'align_h' => 'alignHorizontal',
			'align_v' => 'alignVertical',
			'border_style' => 'borderStyle',
			// 'font_family' => 'fontFamily', // handled in a separate js function to the other css vars because the key isn't the value
		);
		
		
		$select = array();
		foreach ($fields as $field => $option) {
		
			$options = array();
			$controls_others = false;
			
			foreach ($this->choices[$field] as $key => $value) {
				switch ($option) {
					case 0:		$key = $value->name; $value = $value->labels->name;
								$selected = (in_array($key, $instance[$field])); break;
					case 2:		$key = $value;
					case 1:		$selected = ($key == $instance[$field]); break;
				}
				
				// NOTE: bare minimum of type checking from the options, so watch what you put
				$data_attrs = '';
				if (is_array($value) AND isset($value['name'])) {
					$name = $value['name']; unset($value['name']);
					foreach ($value as $data_key => $data_value) {
						if (is_array($data_value)) {
							$data_value = implode(',', $data_value);
						}
						$data_attrs.= sprintf('data-%s="%s" ', strtolower($data_key), $data_value);
						
						if (in_array($data_key, array('hide-these', 'show-these'))) {
							$controls_others = true;
						}
					}
					$value = $name;
				}
				
				$options[] = sprintf('<option value="%s" %s %s>%s</option>',
					esc_attr($key),
					($selected) ? ' selected="selected"' : '',
					$data_attrs,
					$value
				);
			}
			
			$select[$field] = sprintf('<select id="%s%s" class="%s%s" name="%s%s"%s%s%s>%s</select>',
				esc_attr($this->get_field_id($field)),
				(0 == $option) ? '[]' : '',
				
				(0 == $option) ? 'widefat ' : '',
				esc_attr($field),
				
				esc_attr($this->get_field_name($field)),
				(0 == $option) ? '[]' : '',
				
				(0 == $option) ? ' size="6" multiple="true"' : '',
				($controls_others) ? ' data-controls-others="true"' : '',
				(isset($css_vars[$field])) ? ' data-css-var="'.esc_attr($css_vars[$field]).'"' : '',

				implode('', $options)
			);
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
		$select['excluded'] = '<select class="widefat exclude" id="'.esc_attr($this->get_field_id('exclude')).'[]" name="'.esc_attr($this->get_field_name('exclude')).'[]" size="10" multiple="true">'
						. $this->display_options_recursively($sorted)
						. '</select>';

		// TODO: do a loop like the selects if we keep adding more...
		$checked = array(
			'auto_text_color'	=> ($instance['auto_text_color'] == 1) ? ' checked="checked"' : '',
			'nofollow'			=> ($instance['nofollow'] == 1) ? ' checked="checked"' : '',
			'order'				=> ($instance['order'] == 1) ? ' checked="checked"' : '',
			'scale_tag'			=> ($instance['scale_tag'] == 1) ? ' checked="checked"' : '',
			'show_count'		=> ($instance['show_count'] == 1) ? ' checked="checked"' : '',
		);

		
		// and now make the form itself
		// TODO: build this form programmatically if you add much more options
		
		$output = '<div class="combined-taxonomies-tag-cloud">'
				
				# POST TYPES and TAXONOMIES to use #############################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Types &amp; Taxonomies', 'CombinedTaxonomiesTagCloud'))
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Count only tags belonging to these types of post', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('post_types')),
						__('Include These Post Types', 'CombinedTaxonomiesTagCloud'),
						$select['post_types']
					)
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Count only tags belonging to these types of taxonomy', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('taxonomies')),
						__('Include These Taxonomies', 'CombinedTaxonomiesTagCloud'),
						$select['taxonomies']
					)
				. sprintf('<p title="%s" class="full"><label for="%s">%s:</label>%s</p>',
						__('Ignore these tags - though if posts have non-excluded tags as well, they will still get included', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('exclude')),
						__('Exclude These Tags', 'CombinedTaxonomiesTagCloud'),
						$select['excluded']
					)
				. '</div></fieldset>'
				
				
				# FONT SETUP ######################################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Fonts', 'CombinedTaxonomiesTagCloud'))
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s<br><span class="font_list">%s</span></p>',
						__('Choose a font stack - if in doubt, leave it for your theme or font plugin to handle', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_family')),
						__('Font Stack', 'CombinedTaxonomiesTagCloud'),
						$select['font_family'],
						$this->get_font_stacks($instance['font_family'])
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('These are the units used within the widget - best to follow what the rest of your theme is using', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_unit')),
						__('Font Units', 'CombinedTaxonomiesTagCloud'),
						$select['font_unit']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0.01" step="0.01" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('Changing this will affect the relative sizing of all the other widget text', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_base')),
						__('Base Font Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('font_base')),
						esc_attr($this->get_field_name('font_base')),
						(float) $instance['font_base']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="checkbox" id="%s" class="%s" name="%s" data-controls-others="true" data-show-these="smallest,largest" value="1"%s></p>',
						__('Make the size of the tag bigger if more posts have it', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('scale_tag')),
						__('Scale Font', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('scale_tag')),
						'scale_tag',
						esc_attr($this->get_field_name('scale_tag')),
						$checked['scale_tag']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0.1" step="0.1" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('Even a tag with only one post will not be smaller than this', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('smallest')),
						__('Smallest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('smallest')),
						esc_attr($this->get_field_name('smallest')),
						(float) $instance['smallest']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0.1" step="0.1" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('Even a tag with a million posts will not be larger than this', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('largest')),
						__('Largest Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('largest')),
						esc_attr($this->get_field_name('largest')),
						(float) $instance['largest']
					)
				. '</div></fieldset>'
				
				
				# WIDGET APPEARANCE ############################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Widget Appearance', 'CombinedTaxonomiesTagCloud'))
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="text" id="%s" name="%s" value="%s"></p>',
						__('Add a title to the tag cloud', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title')),
						__('Title', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title')),
						esc_attr($this->get_field_name('title')),
						esc_attr($instance['title'])
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-alpha-enabled="true" data-css-var="titleColor"></p>',
						__('Set the color of the title text', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title_color')),
						__('Title Color', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('title_color')),
						esc_attr($this->get_field_name('title_color')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['title_color']),
						$this->defaults['title_color']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('How to align the title horizontally within the widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('align_title')),
						__('Title Alignment', 'CombinedTaxonomiesTagCloud'),
						$select['align_title']
					)
					
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.1" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('How much spacing is between each tag in the widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('column_gap')),
						__('Column Gap', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('column_gap')),
						esc_attr($this->get_field_name('column_gap')),
						(float) $instance['column_gap']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.1" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('How much spacing is between each row of tags in the widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('row_gap')),
						__('Row Gap', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('row_gap')),
						esc_attr($this->get_field_name('row_gap')),
						(float) $instance['row_gap']
					)
				
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('How to align the tags horizontally within the widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('align_h')),
						__('Horizontal Alignment', 'CombinedTaxonomiesTagCloud'),
						$select['align_h']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('How to align the tags vertically within each line of the cloud', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('align_v')),
						__('Vertical Alignment', 'CombinedTaxonomiesTagCloud'),
						$select['align_v']
					)
					
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-alpha-enabled="true" data-css-var="widgetBackgroundColor"></p>',
						__('Set the background color of the whole widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						__('Widget Background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wbackground')),
						esc_attr($this->get_field_name('wbackground')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['wbackground']),
						$this->defaults['wbackground']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('Make the corners of the widget round - only useful if you have a background color!', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wborder_radius')),
						__('Rounded Corners', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wborder_radius')),
						esc_attr($this->get_field_name('wborder_radius')),
						(float) $instance['wborder_radius']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s"><span class="font_units"></span></p>',
						__('Add padding to the whole widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wpadding')),
						__('Padding', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('wpadding')),
						esc_attr($this->get_field_name('wpadding')),
						(float) $instance['wpadding']
					)
				
				. '</div></fieldset>'
				
				
				# WIDGET BEHAVIOUR #############################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Widget Behavior', 'CombinedTaxonomiesTagCloud'))
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('How the tag cloud will be ordered', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('orderby')),
						__('Order Tags By', 'CombinedTaxonomiesTagCloud'),
						$select['orderby']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Choose to reverse whatever order you just picked', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('order')),
						__('Reverse Order', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('order')),
						esc_attr($this->get_field_name('order')),
						$checked['order']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="1" step="1" size="3" id="%s" name="%s" value="%s"></p>',
						__('The maximum number of tags that will be shown in this particular widget', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						__('Maximum Shown', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('maximum')),
						esc_attr($this->get_field_name('maximum')),
						(int) $instance['maximum']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('Automatically add the highlight style to matching tags in the cloud when ...', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('highlight')),
						__('Auto Highlight', 'CombinedTaxonomiesTagCloud'),
						$select['highlight']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('What to do with tags that only have one post associated with them', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('single')),
						__('Single Entry Tags', 'CombinedTaxonomiesTagCloud'),
						$select['single']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Add the rel=&quot;nofollow&quot; attribute to each of the tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						__('Make Links No-Follow', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('nofollow')),
						esc_attr($this->get_field_name('nofollow')),
						$checked['nofollow']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('You can cache the generated tag cloud to save working it out every time', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('save')),
						__('Save Cloud For (Hours)', 'CombinedTaxonomiesTagCloud'),
						$select['save']
					)
				. '</div></fieldset>'
				
				
				
				# GENERAL TAG LOOK #############################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Tag Appearance', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="checkbox" id="%s" name="%s" value="1"%s></p>',
						__('Show the count of the number of posts that have that term', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('show_count')),
						__('Show Count in Tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('show_count')),
						esc_attr($this->get_field_name('show_count')),
						$checked['show_count']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('You can change the case of all the tags if you wish to make them consistent', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('text_case')),
						__('Text Case', 'CombinedTaxonomiesTagCloud'),
						$select['text_case']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('Change how the tag text uses the CSS text decoration property', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('text_decoration')),
						__('Text Decoration', 'CombinedTaxonomiesTagCloud'),
						$select['text_decoration']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s" data-css-var="linkPaddingX" data-is-size="true"><span class="font_units"></span></p>',
						__('Add horizontal padding to each tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_padding_x')),
						__('Horizontal Padding', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_padding_x')),
						esc_attr($this->get_field_name('tag_padding_x')),
						(float) $instance['tag_padding_x']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s" data-css-var="linkPaddingY" data-is-size="true"><span class="font_units"></span></p>',
						__('Add vertical padding to each tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_padding_y')),
						__('Vertical Padding', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_padding_y')),
						esc_attr($this->get_field_name('tag_padding_y')),
						(float) $instance['tag_padding_y']
					)
				. '</div></fieldset>'
				
				
				# TAG FANCY BITS ###############################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Tag Effects', 'CombinedTaxonomiesTagCloud'))
				
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s" data-css-var="fxTimingSpeed" data-is-time="true"><span class="time_units">%s</span></p>',
						__('How quickly will the highlighting effects happen', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('fx_timing')),
						__('Effect Speed', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('fx_timing')),
						esc_attr($this->get_field_name('fx_timing')),
						(float) $instance['fx_timing'],
						__('seconds', 'CombinedTaxonomiesTagCloud')
					)
				
				// --- Backgrounds ---------------------------------------------------------------------------------------------
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('What background effect to apply to these tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('fx_backgrounds')),
						__('Background', 'CombinedTaxonomiesTagCloud'),
						$select['fx_backgrounds']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="backColor1">%s</p>',
						__('Choose the first color this effect uses', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tcolor1')),
						__('Color 1', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tcolor1')),
						esc_attr($this->get_field_name('tcolor1')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['tcolor1']),
						vsprintf('rgba(%d,%d,%d,%.2f)', $this->defaults['tcolor1']),
						sprintf('<span class="wcag" title="%s">%s</span>',
							__('When the auto text color is used on top of this color, what rating will it get under the WCAG guidelines for contrast?', 'CombinedTaxonomiesTagCloud'),
							$this->get_wcag_color_and_rating($instance['tcolor1'], $instance['wbackground'], true)
						)
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="backColor2">%s</p>',
						__('Choose the second color this effect uses', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tcolor2')),
						__('Color 2', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tcolor2')),
						esc_attr($this->get_field_name('tcolor2')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['tcolor2']),
						vsprintf('rgba(%d,%d,%d,%.2f)', $this->defaults['tcolor2']),
						sprintf('<span class="wcag" title="%s">%s</span>',
							__('When the auto text color is used on top of this color, what rating will it get under the WCAG guidelines for contrast?', 'CombinedTaxonomiesTagCloud'),
							$this->get_wcag_color_and_rating($instance['tcolor2'], $instance['wbackground'], true)
						)
					)
				
				// --- Foregrounds ---------------------------------------------------------------------------------------------
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="checkbox" id="%s" class="%s" name="%s" data-controls-others="true" data-hide-these="tag_text_color_1,tag_text_color_2" value="1"%s></p>',
						__('Automatically pick text colors that contrast the background', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('auto_text_color')),
						__('Auto Text Color', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('auto_text_color')),
						'auto_text_color',
						esc_attr($this->get_field_name('auto_text_color')),
						$checked['auto_text_color']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="textColor1"></p>',
						__('Choose the color to use normally', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_text_color_1')),
						__('Text Color 1', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_text_color_1')),
						esc_attr($this->get_field_name('tag_text_color_1')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['tag_text_color_1']),
						vsprintf('rgba(%d,%d,%d,%.2f)', $this->defaults['tag_text_color_1'])
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="textColor2"></p>',
						__('Choose the color to use for highlighting', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_text_color_2')),
						__('Text Color 2', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tag_text_color_2')),
						esc_attr($this->get_field_name('tag_text_color_2')),
						vsprintf('rgba(%d,%d,%d,%.2f)', $instance['tag_text_color_2']),
						vsprintf('rgba(%d,%d,%d,%.2f)', $this->defaults['tag_text_color_2'])
					)
					
				// --- Borders -------------------------------------------------------------------------------------------------
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('Alter the appearance of the border of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('border_style')),
						__('Border Style', 'CombinedTaxonomiesTagCloud'),
						$select['border_style']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="borderColor1"></p>',
						__('Choose the border color of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder1')),
						__('Color', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder1')),
						esc_attr($this->get_field_name('tborder1')),
						$instance['tborder1'],
						$this->defaults['tborder1']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="borderColor2"></p>',
						__('Choose the border color of a tag when highlighted', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder2')),
						__('Color (Highlight)', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tborder2')),
						esc_attr($this->get_field_name('tborder2')),
						$instance['tborder2'],
						$this->defaults['tborder2']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0.01" step="0.01" size="3" id="%s" name="%s" value="%s" data-css-var="borderWidth" data-is-size="true"><span class="font_units"></span></p>',
						__('How big is the border of a tag', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('border_width')),
						__('Size', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('border_width')),
						esc_attr($this->get_field_name('border_width')),
						(float) $instance['border_width']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input type="number" min="0" step="0.01" size="3" id="%s" name="%s" value="%s" data-css-var="borderRadius" data-is-size="true"><span class="font_units"></span></p>',
						__('Make the corners of the tag round - NOTE: set this to twice the border width to make the corners round on the inside as well as the outside', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('border_radius')),
						__('Rounded Corners', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('border_radius')),
						esc_attr($this->get_field_name('border_radius')),
						(float) $instance['border_radius']
					)
					
				// --- Shadows -------------------------------------------------------------------------------------------------
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('What shadow effect to apply to these tags when highlighted', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('fx_shadows')),
						__('Shadow', 'CombinedTaxonomiesTagCloud'),
						$select['fx_shadows']
					)
				. sprintf('<p title="%s"><label for="%s">%s:</label><input class="color-picker" type="text" size="5" id="%s" name="%s" value="%s" data-default-color="%s" data-alpha-enabled="true" data-css-var="shadowColor"></p>',
						__('Choose the shadow color this effect uses', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tshadow')),
						__('Shadow Color', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('tshadow')),
						esc_attr($this->get_field_name('tshadow')),
						$instance['tshadow'],
						$this->defaults['tshadow']
					)
				
				// --- Movement ------------------------------------------------------------------------------------------------
				. sprintf('<p title="%s"><label for="%s">%s:</label>%s</p>',
						__('What movement effect will apply to these tags', 'CombinedTaxonomiesTagCloud'),
						esc_attr($this->get_field_id('fx_two_dee')),
						__('Movement', 'CombinedTaxonomiesTagCloud'),
						$select['fx_two_dee']
					)
					
				. '</div></fieldset>'
				
			
				# SHORTCODE ####################################################################################################
				
				. sprintf('<fieldset><legend>%s</legend><div>', __('Shortcode', 'CombinedTaxonomiesTagCloud'))
				. sprintf('<p title="%s">%s</p>',
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
				
				
				
				# DEMO #########################################################################################################
					
				. sprintf('<p title="%s" class="tag-demo"><b>%s:</b><span class="half"><a class="%s">%s</a></span></p>',
						__('See what the tags will look like', 'CombinedTaxonomiesTagCloud'),
						__('Example Tag', 'CombinedTaxonomiesTagCloud'),
						$instance['fx_two_dee'].' '.$instance['fx_backgrounds'].' '.$instance['fx_shadows'],
						__('Tag Text', 'CombinedTaxonomiesTagCloud')
					)
					
				
				. '</div>';

		echo $output;
	}

	
	
	private function get_highlight_ids($args, $instance) {
		$page_term_ids = array();
		
		// highlight when on single pages of supported post types
		if ($args['highlight'] == 'single' AND is_singular($args['post_types'])) {
			$post_id = get_the_ID();
			// using multiple get_the_terms because they're likely to have been cached already
			foreach ($instance['taxonomies'] as $tax) {
				// collect all the terms this post has from all our selected taxonomies
				$page_term_ids = array_merge($page_term_ids, (array) get_the_terms($post_id, $tax));
			}
			
			$page_term_ids = array_diff(array_column($page_term_ids, 'term_id'), $args['exclude']);
			
			
		/*
		// highlight when on an archive page of selected post types
		} elseif (is_post_type_archive($args['post_types'])) {
			// which post archive is it
			$post_type = get_query_var('post_type');
			if (is_array($post_type)) $post_type = reset($post_type);
			$post_type_obj = get_post_type_object($post_type);
			// what taxonomies does that type of post have
			$object_taxonomies = get_object_taxonomies($post_type_obj->name);
			// and what terms are in those taxonomies
			$page_term_ids = get_terms(array(
				'taxonomy' => $object_taxonomies,
				'hide_empty' => false,
			));
			
			$page_term_ids = array_column($page_term_ids, 'term_id');
		
		// highlight when on an archive page of selected taxonomies
		} elseif (is_tax($args['taxonomies'])) {
		
			global $wp_query;
			$page_term_ids = get_terms(array(
				'taxonomy' => $wp_query->get_queried_object()->taxonomy,
				'hide_empty' => false,
			));
			
			$page_term_ids = array_column($page_term_ids, 'term_id');
		*/
		}
		
		
		
		// print_r($page_term_ids);
		return $page_term_ids;
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

	// by pospi @ https://wordpress.stackexchange.com/a/99516
	private function sort_terms_hierarchically(array &$cats, array &$into, $parentId = 0) {
		foreach ($cats as $i => $cat) {
			if ($cat->parent == $parentId) {
				$into[] = $cat;
				unset($cats[$i]);
			}
		}
		foreach ($into as $topCat) {
			$topCat->children = array();
			$this->sort_terms_hierarchically($cats, $topCat->children, $topCat->term_id);
		}
	}
	
	/*
	private function flatten(array $array) {
		$return = array();
		array_walk_recursive($array, function($a) use (&$return) {
			$return[] = $a;
			$return = array_merge($return, $this->flatten($a->children));
		});
		return $return;
	}
	*/
	
	
	private function get_font_stacks(string $key = '') {
		if (isset($this->fonts[$key])) {
			$fonts = $this->fonts[$key];
			
		} elseif ($key == '') {
			$fonts = $this->fonts;
			
		} else {
			$fonts = '';
		}
		return $fonts;
	}
	
	
	

	private function is_valid_color($value): bool {
		if (is_string($value)) {
			return
				preg_match('/^#(?:[0-9a-f]{3}){1,2}$/i', $value) or 
				preg_match('/^#(?:[0-9a-f]{2}){3,4}$/i', $value) or 
				// NOTE: won't catch invalid RGB values > 255 but the regex is simpler and colour values like that will just wrap
				preg_match('/^rgb\((?:\s*\d+\s*,){2}\s*[\d]+\)$/i', $value) or
				preg_match('/^rgba\((\s*\d+\s*,){3}\s*[\d\.]+\)$/i', $value);
		
		} elseif (is_array($value) AND sizeof($value) == 4) {
			$okay = true;
			foreach ($value as $i => $channel) {
				if (! (($i < 3 AND $channel >= 0 AND $channel <= 255) OR ($channel >= 0 AND $channel <= 1))) {
					$okay = false;
					break;
				}
			}
			return $okay;
		
		} else {
			return false;
		}
	}
	
	
	
	
	
	
	
	public function get_ajax() {
		return array(
			'url'	=> admin_url('admin-ajax.php'),
			'nonce'	=> wp_create_nonce('cttc_nonce'),
		);
	}
	
	
	// called via ajax to get the terms for the selected taxonomies
	public function get_terms() {
		$response = array(
			'ok'		=> false,
			'terms'		=> array(),
		);
		
		if (check_ajax_referer('cttc_nonce')) {
			$taxonomies = (isset($_POST['taxonomies'])) ? (array) $_POST['taxonomies'] : false;
			if ($taxonomies) {
				$this->selected = array(); // don't make it complicated by trying to save existing selections and comparing...
				
				$terms = get_terms($taxonomies, array(
					'hide_empty'			 => false,
					'update_term_meta_cache' => false,
				));
				$sorted = array();
				$this->sort_terms_hierarchically($terms, $sorted);
				
				$response = array(
					'ok' => true,
					//'terms' => $this->flatten($tree),
					'options' => $this->display_options_recursively($sorted),
				);
			}
		}
		
		echo json_encode($response);
		wp_die();
	}
	
	
	// called via ajax to get a contrasting colour
	public function get_contrast() {
		$response = array(
			'ok'		=> false,
			'contrast'	=> '#ff0000',
			'ratio'		=> __('0', 'CombinedTaxonomiesTagCloud'),
			'wcag'		=> __('Error', 'CombinedTaxonomiesTagCloud'),
		);
		
		if (check_ajax_referer('cttc_nonce')) {
			if (isset($_POST['color']) AND $this->is_valid_color($_POST['color'])) {
				if (isset($_POST['additional']) AND $this->is_valid_color($_POST['additional'])) {
			
					$color = $this->convert_color_to_rgba($_POST['color']);
					$additional = $this->convert_color_to_rgba($_POST['additional']);
					
					$response = array_merge(array('ok' => true), $this->get_wcag_color_and_rating($color, $additional));
				}
			}
		}
		
		echo json_encode($response);
		wp_die();
	}
	
	
	
	// all colours should be at least valid at this point, but may be hex/rgb/rgba ...
	private function convert_color_to_rgba($color): array {
		
		if (is_string($color)) {
			
			// convert non-rgb strings (ie. hex) like #f0f, #ff00ff, #ff00ffff to an array
			if (strpos($color, 'rgb') === false) {
				
				// arosolino @ https://stackoverflow.com/a/17115500
				$rgba = array_map(
					function($c) {
						return hexdec(str_pad($c, 2, $c));
					}, str_split(ltrim($color, '#'), strlen($color) > 4 ? 2 : 1)
				);
			
			} else {
				$rgba = explode(',', trim($color, 'rgba()'));
			}
			
			if (sizeof($rgba) == 3) $rgba[] = 1; // wasn't transparent
			
		} elseif (is_array($color)) {
			
			$rgba = $color;
			
		}
		
		return $rgba;
	}
	
	
	
	private function get_wcag_color_and_rating(array $color, array $additional, bool $just_rating = false) {
		
		$blend = $this->blend_arrays($color, $additional);
		
		$contrast = $this->get_contrasting_text_color($blend);
		
		// https://www.w3.org/TR/UNDERSTANDING-WCAG20/visual-audio-contrast-contrast.html
		$ratio = $this->get_contrast_ratio(array($blend, $contrast));
		
		if ($ratio >= 7)
			$wcag = 'AAA';
		else if ($ratio >= 4.5)
			$wcag = 'AA';
		else if ($ratio >= 3)
			$wcag = 'A';
		else
			$wcag = __('Fail', 'CombinedTaxonomiesTagCloud');
		
		$return = $wcag; if (! $just_rating) {
			$return = array(
				'contrast'	=> vsprintf('rgba(%d,%d,%d,%.2f)', $contrast),
				'against'	=> $blend,
				'ratio'		=> $ratio,
				'wcag'		=> sprintf('<span class="details">%s:</span> %s <span class="details">%s</span>',
					__('WCAG Rating', 'CombinedTaxonomiesTagCloud'),
					$wcag,
					sprintf(__('(Contrast Ratio of %.2f)', 'CombinedTaxonomiesTagCloud'), $ratio)
				),
			);
		}
		
		return $return;
	}
	
	
	private function blend_arrays(array $color1, array $color2): array {
		$rgba = $color1;
		
		// only blend with the other colour if we have some transparency
		if ($color1[3] < 1) {
			
			// and if it's completely transparent then forget it
			if ($color1[3] == 0) {
				$rgba = $color2;
				
			} else {
				
				// NOTE: check https://stackoverflow.com/a/40962043 for a more accurate blending
				//       and more in the thread at https://lists.w3.org/Archives/Public/w3c-wai-ig/2012OctDec/0066.html
				$rgba = array(
					round($color1[0]*$color1[3] + $color2[0]*(1-$color1[3])),
					round($color1[1]*$color1[3] + $color2[1]*(1-$color1[3])),
					round($color1[2]*$color1[3] + $color2[2]*(1-$color1[3])),
					$color1[3] *1,
				);
			}
		}
		
		return $rgba;
	}
	
	
	private function get_contrasting_text_color(array $rgba): array {
		return ($this->get_luminance($this->convert_rgba_to_hex($rgba)) >= 0.1791) ? [0,0,0,1] : [255,255,255,1];
	}
	
	
	
	private function get_contrast_ratio(array $colors): float {
		// $colors should be an array of two strings (hex codes)
		$colors = array_map([$this, 'get_luminance'], array_map([$this, 'convert_rgba_to_hex'], $colors));
		
		rsort($colors);
		$contrast = ($colors[0] + 0.05) / ($colors[1] + 0.05);
		return round($contrast, 3);
	}
	
	private function get_luminance(string $hex): float {
		// convert hex code into linear (0-1) colour values
		$channels = array_map(array($this, 'convert_rgb_to_linear'), $this->get_decimal_color($hex));
		// get Y
		$luminance = (0.2126*$channels[0] + 0.7152*$channels[1] + 0.0722*$channels[2]);
		return $luminance;
	}
	
	
	private function get_decimal_color(string $hex): array {
		list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
		return array($r / 255, $g / 255, $b / 255);
	}

	private function convert_rgb_to_linear(float $value): float {
		return ($value <= 0.04045) ? $value / 12.92 : pow((($value + 0.055)/1.055), 2.4);
	}
	
	private function convert_rgba_to_hex(array $rgba): string {
		return sprintf("#%02x%02x%02x", $rgba[0], $rgba[1], $rgba[2]);
	}
	
	/*
	// https://stackoverflow.com/a/56678483
	private function get_perceptual_lightness(string $hex): float {
		$luminance = $this->get_luminance($hex);
		// and convert to L*
		$lightness = ($luminance <= 0.008856) ? $luminance * 903.3 : (pow($luminance, (1/3)) * 116) - 16;
		return $lightness;
	}
	private function convert_rgb_string_to_hex(string $rgb): string {
		if (strpos($rgb, ',')) { // then the colour is either RGB or RGBA (remember we've already checked validity)
			$rgb = explode(',', $rgb);
			$hex = sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
		} else {
			$hex = $rgb;
		}
		return $hex;
	}*/
	
	
}
