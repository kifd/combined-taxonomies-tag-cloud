<?php

$config = array();

$config['fonts'] = array(
	// from https://gist.github.com/don1138/5761014
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
	'Monospace 1' =>
		'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace',
		
	// https://www.webfx.com/blog/web-design/a-web-designers-guide-to-linux-fonts/
	'Narrow Sans-Serif' =>
		'"Liberation Sans", "Nimbus Sans L", "FreeSans", "Helvetica Neue", Helvetica, Arial, sans-serif',
	'Wide Sans-Serif' =>
		'"DejaVu Sans", "Bitstream Vera Sans", Geneva, Verdana, sans-serif',
	'Narrow Serif' =>
		'"Liberation Serif", "Nimbus Roman No 9 L",  "FreeSerif", "Hoefler Text", Times, "Times New Roman", serif',
	'Antique Serif' =>
		'"Bitstream Charter", "URW Palladio L", Palatino, "Palatino Linotype", "Book Antiqua", serif',
	'Wide Serif' =>
		'"DejaVu Serif", "Bitstream Vera Serif", "Century Schoolbook L", "Lucida Bright", Georgia, serif',
	'Monospace 2' =>
		'"Liberation Mono", "Nimbus Mono L", "FreeMono", "DejaVu Mono", "Bitstream Vera Mono", "Lucida Console", "Andale Mono", "Courier New", monospace',
	
	
);
ksort($config['fonts']);


// NOTE: $fields var in form() function must have matching keys
// TODO: given that I've now expanded the values for the show/hide controls, add the rest of the field definitions and be done with it...
$config['choices'] = array(
	'align_h'			=> array(
							'left'			=> __('Left', 'CombinedTaxonomiesTagCloud'),
							'center'		=> __('Center', 'CombinedTaxonomiesTagCloud'),
							'right'			=> __('Right', 'CombinedTaxonomiesTagCloud'),
						),
	'align_title'		=> array(
							'left'			=> __('Left', 'CombinedTaxonomiesTagCloud'),
							'center'		=> __('Center', 'CombinedTaxonomiesTagCloud'),
							'right'			=> __('Right', 'CombinedTaxonomiesTagCloud'),
						),
	'align_v'			=> array(
							'start'			=> __('Top', 'CombinedTaxonomiesTagCloud'),
							'center'		=> __('Center', 'CombinedTaxonomiesTagCloud'),
							'end'			=> __('Bottom', 'CombinedTaxonomiesTagCloud'),
						),
	'border_style'		=> array(
							''				=> array(
								'name'			=> __('None', 'CombinedTaxonomiesTagCloud'),
								'hide-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'solid'			=> array(
								'name'			=> __('Solid', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'dotted'		=> array(
								'name'			=> __('Dots', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'dashed'		=> array(
								'name'			=> __('Dashes', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'double'		=> array(
								'name'			=> __('Double Lines', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'groove'		=> array(
								'name'			=> __('Grooved', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'ridge'			=> array(
								'name'			=> __('Ridged', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'inset'			=> array(
								'name'			=> __('Inset', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
							'outset'		=> array(
								'name'			=> __('Outset', 'CombinedTaxonomiesTagCloud'),
								'show-these'	=> array('tborder1', 'tborder2', 'border_width'),
							),
						),
	
	'fx_backgrounds'	=> array(
							'fx_bg_none' 		=> array(
								'name'				=> __('None', 'CombinedTaxonomiesTagCloud'),
								'hide-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_static' 		=> array(
								'name'				=> __('Static', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1'),
								'hide-these'		=> array('tcolor2'),
							),
							'fx_bg_fade'		=> array(
								'name'				=> __('Switch', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_sweep_up'	=>  array(
								'name'				=> __('Sweep Up', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_sweep_down'	=>  array(
								'name'				=> __('Sweep Down', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_sweep_left'	=>  array(
								'name'				=> __('Sweep Left', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_sweep_right'	=>  array(
								'name'				=> __('Sweep Right', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_radial_in'	=>  array(
								'name'				=> __('Radial In', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_radial_out'	=>  array(
								'name'				=> __('Radial Out', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_rectangle_in'	=>  array(
								'name'				=> __('Rectangle In', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_rectangle_out'	=>  array(
								'name'				=> __('Rectangle Out', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_underline_center_out'	=> array(
								'name'				=> __('Underline Center Out', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_overline_center_out'	=> array(
								'name'				=> __('Overline Center Out', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
							'fx_bg_bothline_center_out'	=> array(
								'name'				=> __('Both Center Out', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tcolor1', 'tcolor2'),
							),
						),
	'fx_shadows'		=> array(
							'fx_sh_none' 			=> array(
								'name'				=> __('None', 'CombinedTaxonomiesTagCloud'),
								'hide-these'		=> array('tshadow'),
							),
							'fx_sh_shadow'			=> array(
								'name'				=> __('Shadow', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tshadow'),
							),
							'fx_sh_inset'			=> array(
								'name'				=> __('Shadow Inset', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tshadow'),
							),
							'fx_sh_glow'			=> array(
								'name'				=> __('Shadow Glow', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tshadow'),
							),
							'fx_sh_box_inset'		=> array(
								'name'				=> __('Box Shadow Inset', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tshadow'),
							),
							'fx_sh_box_outset'		=> array(
								'name'				=> __('Box Shadow Outset', 'CombinedTaxonomiesTagCloud'),
								'show-these'		=> array('tshadow'),
							),
						),
	'fx_two_dee'		=> array(
							'fx_2d_none' 		=> array(
								'name'				=> __('None', 'CombinedTaxonomiesTagCloud'),
							),
							'fx_2d_grow'		=> array(
								'name'				=> __('Grow', 'CombinedTaxonomiesTagCloud'),
							),
							'fx_2d_shrink'		=> array(
								'name'				=> __('Shrink', 'CombinedTaxonomiesTagCloud'),
							),
							'fx_2d_rotate'		=> array(
								'name'				=> __('Rotate', 'CombinedTaxonomiesTagCloud'),
							),
							'fx_2d_grow_rotate'		=> array(
								'name'				=> __('Grow &amp; Rotate', 'CombinedTaxonomiesTagCloud'),
							),
							'fx_2d_skew'		=> array(
								'name'				=> __('Skew', 'CombinedTaxonomiesTagCloud'),
							),
						),
	
	'font_family'		=> array(__('Leave Alone', 'CombinedTaxonomiesTagCloud')),
	'font_unit'			=> array('rem', 'em', 'pt', 'px', 'vw'),
	'highlight'			=> array(
							''				=> __('None', 'CombinedTaxonomiesTagCloud'),
							'single'		=> __('On Single Pages', 'CombinedTaxonomiesTagCloud'),
						),
	'orderby'			=> array(
							'name'			=> __('Alphabetically', 'CombinedTaxonomiesTagCloud'),
							'count'			=> __('By Count', 'CombinedTaxonomiesTagCloud'),
							'random'		=> __('Randomly', 'CombinedTaxonomiesTagCloud')
						),
	'post_types'		=> get_post_types(array('show_ui' => true), 'objects'),
	'save'				=> array(0, 1, 2, 4, 8, 12, 24, 48, 96), // hours
	'single'			=> array(
							''				=> __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
							'remove'		=> __('Remove', 'CombinedTaxonomiesTagCloud'),
							'link'			=> __('Link to Entry', 'CombinedTaxonomiesTagCloud')
						),
	'taxonomies'		=> get_taxonomies(array('show_ui' => true), 'objects'),
	'text_case'			=> array(
							''				=> __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
							'lower'			=> __('lowercase', 'CombinedTaxonomiesTagCloud'),
							'upper'			=> __('UPPERCASE', 'CombinedTaxonomiesTagCloud')
						),
	'text_decoration'	=> array(
							''				=> __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
							'no_text_deco'	=> __('None', 'CombinedTaxonomiesTagCloud'),
							'under_always'	=> __('Always Underline', 'CombinedTaxonomiesTagCloud'),
							'under_hover'	=> __('Underline on Hover', 'CombinedTaxonomiesTagCloud'),
							'box_hover'		=> __('Box Shadow on Hover', 'CombinedTaxonomiesTagCloud'),
							'under_dotted'	=> __('Underline to Dots', 'CombinedTaxonomiesTagCloud'),
						),
);
sort($config['choices']['taxonomies']);
sort($config['choices']['post_types']);

$config['defaults'] = array(
	'align_h'			=> 'left',
	'align_title'		=> 'left',
	'align_v'			=> 'start',
	'auto_text_color'	=> 1,
	'border_radius'		=> 0.10,
	'border_style'		=> '',
	'border_width'		=> 0.01,
	'column_gap'		=> 0.50,
	'exclude'			=> array(0),
	'font_base'			=> 1.00,
	'font_family'		=> __('Leave Alone', 'CombinedTaxonomiesTagCloud'),
	'font_unit'			=> 'em',
	'fx_backgrounds'	=> 'fx_bg_none',
	'fx_shadows'		=> 'fx_sh_none',
	'fx_two_dee'		=> 'fx_2d_none',
	'fx_timing'			=> 0.3,
	'highlight'			=> '',
	'largest' 			=> 1.40,
	'maximum'			=> 999,
	'nofollow'			=> 0,
	'order'				=> 0, // 0 = asc, 1 = desc
	'orderby'			=> 'name',
	'post_types'		=> array('post'),
	'row_gap'			=> 0.50,
	'save'				=> 0,
	'scale_tag'			=> 0,
	'show_count'		=> 1,
	'single'			=> '',
	'smallest' 			=> 0.60,
	'taxonomies'		=> array('post_tag'),
	'tcolor1'			=> [255,255,255,0],
	'tcolor2'			=> [255,255,255,0],
	'tborder1'			=> '#ffffffff',
	'tborder2'			=> '#000000ff',
	'tag_padding_x'		=> 1.00,
	'tag_padding_y'		=> 0.50,
	'tag_text_color_1'	=> [0,0,0,1],
	'tag_text_color_2'	=> [0,0,0,1],
	'tshadow'			=> '#00000099',
	'text_case'			=> '',
	'text_decoration'	=> 'under_hover',
	'title' 			=> '',
	'title_color'		=> [0,0,0,1],
	'wbackground'		=> [255,255,255,0],
	'wborder_radius'	=> 0.00,
	'wpadding'			=> 0.00,
);
