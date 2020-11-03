(function($){
	
	$(document).on('ready widget-added widget-updated', function(event, widget) {
		// WP color picker:
		// thanks to https://www.codecheese.com/2013/04/how-to-use-wp-color-picker-in-widgets/#comment-64600
		// and https://www.codecheese.com/2018/05/trigger-change-wpcolorpicker-in-widgets-and-customizer-page/
		$('.color-picker').not('[id*="__i__"]').wpColorPicker({
			defaultColor: $(this).attr('data-default-color'),
			change: function(e, ui) {
				$(e.target).val(ui.color.to_s('rgba')); // NOTE: not toString anymore; uses the -alpha picker's method
				
				// WP doesn't let you save the widget if all you did was use its own color-picker widget
				$(e.target).trigger('change'); // so trigger manually to enable the save button
			},
			/*clear: function(e) {
				$(e.target).trigger('change'); // likewise if we clear the color
				
				var _id = '#' + $(this).parent().find('.color-picker').attr('id');
				updateColors(_id);
			},*/
		});
		
		// close the fieldsets on initial view, and make sure the extra scaling form fields are shown or not
		$('.combined-taxonomies-tag-cloud legend', widget).each(function() { toggleFieldSet($(this), true); });
		// NOTE: wp resets the widget to its initial state after an update, losing the current open/close state without me
		//       saving them as hidden form fields - possible TODO
		
		
		// NOTE: set the initial states using .trigger() bubbles the event up the dom and makes wp think the
		//       widget needs saving again so I'm calling mini sub functions to work around it
		$('.combined-taxonomies-tag-cloud select.font_unit', widget).each(function() {
			updateFontUnits($(this));
		});
		
		$('*[data-css-var]', widget).each(function() {
			updateCssVars($(this));
		});
		
		/*$('.combined-taxonomies-tag-cloud .color-picker[id$="tcolor1"]', widget).each(function() { 
		//	updateColors('#' + $(this).attr('id'));
		});*/
		
		// hide form elements depending on what has been selected
		$('*[data-controls-others=true]', widget).each(function(idx, dom) {
			toggleManyFields(dom);
		});
		
		
	});
	
	// change the font list when updated
	$(document).on('change', 'select.font_family', function() {
		const _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
		$(this).siblings('.font_list').text(_stack).css('font-family', _stack);
	});
	
	
	// change the font units when updated
	$(document).on('change', 'select.font_unit', function() {
		updateFontUnits($(this));
	});
	
	
	$(document).on('change', '*[data-css-var]', function() {
		updateCssVars($(this));
	});
	
	
	// change the tag demo when updated
	$(document).on('change', 'select.fx_backgrounds, select.fx_shadows, select.fx_two_dee', function() {
		// get the current classes of the element, remove any of the same type (starts with the same fx_?? name) and add the new one
		const element = $('.tag-demo span a', $(this).parents().eq(2));
		const prefix = this.value.substring(0,5);
		let classes = element.attr('class').split(" ").filter(c => !c.startsWith(prefix));
		classes.push(this.value);
		element.attr('class', classes.join(" ").trim());
	});
	
	
	
	// toggle fieldsets by clicking on the legend
	$(document).on('click', '.combined-taxonomies-tag-cloud legend', function() {
		if ($(this).hasClass('closed')) {
			toggleFieldSet($(this), false);
		} else {
			toggleFieldSet($(this), true);
		}
	});
	
	// toggle other dom elements depending on state of checkboxes/selects
	$(document).on('change', '*[data-controls-others=true]', function() {
		toggleManyFields($(this).get(0));
	});
	
	
	
	
	
	// a little demo of whether or not you can read in the colours that have been picked
	$(document).on('change', '.color-picker', function() {
		updateColors('#' + $(this).attr('id'));
	});
	
	
	function updateColors(id) {
		let _color_1 = false;
		let _color_2 = false;
		
		// TODO: pass which two fields to constrast by data attr
		//console.log(id, $(id));
		
		if (id.indexOf('tcolor1') != -1) {
			_color_1 = id;
			_color_2 = id.replace(/tcolor1/i, 'tcolor2');
		} else if (id.indexOf('tcolor2') != -1) {
			_color_1 = id.replace(/tcolor2/i, 'tcolor1');
			_color_2 = id;
		}
		
		if (_color_1 != false && _color_2 != false) {
			
			$.ajax({
				type:			'POST',
				url:			cttc_ajax.url,
				data:			{
									'action': 'update_contrast_demo',
									'_ajax_nonce': cttc_ajax.nonce,
									'color1': $(_color_1).val(),
									'color2': $(_color_2).val(),
								},
				success:		function(response) {
									const tag_demo = $(_color_1).parents('p').siblings('p.tag-demo').children('span');
									const demo = tag_demo.children('a');
									const wcag = tag_demo.children('.wcag');
									
									if (response.ok == true) {
									
										//demo.css('--backColor1', $(_color_1).val());
										//demo.css('--textColor1', response.text1);
										//demo.css('--backColor2', $(_color_2).val());
										//demo.css('--textColor2', response.text2);
										
										wcag.html(response.wcag);
										
									} else {
										console.log(response);
										wcag.html('Error');
									}
								},
				dataType:		'json',
			});
			
		}
	}
	
	// like toggleClass except won't open a closed one if you didn't want it to 
	function toggleFieldSet(dom, close = true) {
		if (close && ! dom.hasClass('closed')) {
			dom.addClass('closed');
		} else if (! close && dom.hasClass('closed')) {
			dom.removeClass('closed');
		}
	}
	
	
	// marginally more advanced version of the original toggle, certainly more complex
	function toggleManyFields(dom) {
		
		// NOTE: avoid having both attributes on the same element without checking the order
		// NOTE: also, different elements could have conflicting hide/show demands - again, check the order or redesign the form
		let toggles = { 'show': [], 'hide': [] };
		const widget = $(dom).parents('form:first');
		
		if (dom.nodeName == 'INPUT' && dom.type == 'checkbox') {
			
			if (typeof $(dom).data('hide-these') !== 'undefined') {
				const state = (dom.checked == true) ? 'hide' : 'show';
				const elements = $(dom).data('hide-these').split(',');
				
				$(elements).each(function(i, which_string) {
					// takes advantage of my form being made of "p > label + thing" blocks
					toggles[state].push($('[id*="'+which_string+'"]', widget).parents('p:first'));
				});
			}
			if (typeof $(dom).data('show-these') !== 'undefined') {
				const state = (dom.checked == true) ? 'show' : 'hide';
				const elements = $(dom).data('show-these').split(',');
			
				$(elements).each(function(i, which_string) {
					toggles[state].push($('[id*="'+which_string+'"]', widget).parents('p:first'));
				});
			}
		
		} else if (dom.nodeName == 'SELECT')  {
			// selects are awkward
			
			$(dom).children('option').each(function(i, option) {
				if (typeof $(option).data('hide-these') !== 'undefined' && option.selected == true) {
					$($(option).data('hide-these').split(',')).each(function(i, which_string) {
						toggles['hide'].push($('[id*="'+which_string+'"]', widget).parents('p:first'));
					});
				}
				if (typeof $(option).data('show-these') !== 'undefined' && option.selected == true) {
					$($(option).data('show-these').split(',')).each(function(i, which_string) {
						toggles['show'].push($('[id*="'+which_string+'"]', widget).parents('p:first'));
					});
				}
				// update the contrast demo if needed
				if (typeof $(option).data('contrast') !== 'undefined' && option.selected == true) {
					$(option).parents('p:first').nextAll('p.color-demo').attr('data-contrast', $(option).data('contrast'));
				}
			});
		}
		
		$(toggles.hide).each(function(i, what) { what.addClass('hide'); });
		$(toggles.show).each(function(i, what) { what.removeClass('hide'); });
	}
	
	
	
	// NOTE: units are hard copied from the definition in the .php file
	const font_units = ['rem', 'em', 'pt', 'px', 'vw'];
	function updateFontUnits(dom) {
		const _unit = (font_units.indexOf(dom.get(0).value) >= 0) ? dom.get(0).value : '';
		const _widget = dom.parents().eq(4);
		$('.font_units', _widget).text(_unit);
	}
	
	
	function updateCssVars(dom) {
		const widget = dom.parents('form:first');
		const cssvar = dom.data('css-var');
		$(widget).css('--'+cssvar, dom.val());
		
		if (cssvar == 'backColor1') {
			getContrast({
				'against': dom.val(),
				'widget': $(widget),
				'set_var': 'textColor1',
			});
			
		} else if (cssvar == 'backColor2') {
			getContrast({
				'against': dom.val(),
				'widget': $(widget),
				'set_var': 'textColor2',
			});
		}
	}

	function getContrast(color) {
		$.ajax({
			type:			'POST',
			url:			cttc_ajax.url,
			data:			{
								'action': 'get_contrast',
								'_ajax_nonce': cttc_ajax.nonce,
								'color': color.against,
							},
			success:		function(response) {
								if (response.ok == true) {
									$(color.widget).css('--'+color.set_var, response.contrast);
									//wcag.html(response.wcag);
									
								} else {
									console.log('error', response);
									//wcag.html('Error');
								}
							},
			dataType:		'json',
		});
	}


	
})(jQuery);



// https://www.jasongaylord.com/blog/2020/05/21/copy-to-clipboard-using-javascript
var copy_elements = document.querySelectorAll("[data-copy-text]");
var copy_count;
for (copy_count = 0; copy_count < copy_elements.length; copy_count++) {
	copy_elements[copy_count].addEventListener("click", async(event) => {
		if (! navigator.clipboard) {
			return;
		}
		try {
			var copy_value = event.srcElement.getAttribute("data-copy-text");
			await navigator.clipboard.writeText(copy_value);
		} catch (error) {
			console.error("copy failed", error);
		}
	});
}
