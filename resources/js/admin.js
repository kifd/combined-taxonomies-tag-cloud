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
		
		
		// hide form elements depending on what has been selected
		$('*[data-controls-others=true]', widget).each(function(idx, dom) {
			toggleManyFields(dom);
		});
		
		
	});
	
	
	// change the font list when updated
	$(document).on('change', 'select.font_family', function() {
		const _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
		$(this).siblings('.font_list').text(_stack).css('font-family', _stack);
		$(this).parents('form:first').css('--fontFamily', _stack);
	});
	
	
	// change the font units when updated (used for ui feedback and to concatenate onto is-size css-vars)
	$(document).on('change', 'select.font_unit', function() {
		updateFontUnits($(this));
	});
	
	
	// change the vars we use in the tag demo
	$(document).on('change', '*[data-css-var]', function() {
		updateCssVars($(this));
	});
	
	
	// change the tag demo classes when updated
	$(document).on('change', 'select.fx_backgrounds, select.fx_shadows, select.fx_two_dee', function() {
		const widget = $(this).parents('form:first');
		// get the current classes of the element, remove any of the same type (starts with the same fx_?? name) and add the new one
		const element = $('.tag-demo span a', widget);
		const prefix = this.value.substring(0,5);
		let classes = element.attr('class').split(" ").filter(c => !c.startsWith(prefix));
		classes.push(this.value);
		element.attr('class', classes.join(" ").trim());
	});
	
	
	
	$(document).on('change', 'select.taxonomies', function() {
		const widget = $(this).parents('form:first');
		setTerms({
			'widget': widget,
			'selected': $(this).val(),
			'set_dom': $('.exclude', widget),
		});
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
	
	
	
	
	
	
	// NOTE: units are hard copied from the definition in the .php file
	const font_units = ['rem', 'em', 'pt', 'px', 'vw'];
	let font_unit = 'em';
	
	function updateFontUnits(dom) {
		// TODO: call updateCssVars on data-is-size if this changes
		font_unit = (font_units.indexOf(dom.get(0).value) >= 0) ? dom.get(0).value : 'em';
		const _widget = dom.parents().eq(4);
		
		$('.font_units', _widget).text(font_unit);
	}
	
	
	function updateCssVars(dom) {
		const widget = dom.parents('form:first');
		const cssvar = dom.data('css-var');
		
		let val = dom.val();
		// css vars that need units need them before the browser inserts spaces where it shouldn't
		if (dom.data('is-size'))
			val+= font_unit;
		else if (dom.data('is-time'))
			val+= 's';
		
		$(widget).css('--'+cssvar, val);
		console.log('set '+cssvar+' to '+val);
		
		
		const wcag = {
			'backColor1': 'textColor1',
			'backColor2': 'textColor2',
			//'widgetBackgroundColor': both
		};
		
		
		if (wcag.hasOwnProperty(cssvar) && $('.auto_text_color', widget).prop('checked')) {
			
			let color = {
				'against': dom.val(),
				'widget': $(widget),
				'set_var': wcag[cssvar],
				'wcag': $(dom).parents('p').children('.wcag'),
				'additional': $(widget).css('--widgetBackgroundColor')
			}
			
			// NOTE: if the widget background is transparent, then we assume the background behind that is white
			// TODO: implement dark mode or use magic to find out what it really is
			if (typeof color.additional === 'undefined') color.additional = 'rgba(255,255,255,1)';
			
			// TODO: replace the ajax call to php with js implementation
			setContrast(color);
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
		
		$(toggles.hide).each(function(i, what) {
			what.addClass('hide');
			$('input', what).each(function(j, dom) {
				if (typeof $(dom).data('css-var') !== 'undefined') {
					$(widget).css('--'+$(dom).data('css-var'), '');
				}
			});
		});
		$(toggles.show).each(function(i, what) {
			what.removeClass('hide');
			$('input', what).each(function(j, dom) {
				if (typeof $(dom).data('css-var') !== 'undefined') {
					updateCssVars($(dom));
				}
			});
		});
	}
	
	
	
	function setContrast(color) {
		$.ajax({
			type:			'POST',
			url:			cttc_ajax.url,
			data:			{
								'action': 'cttc_get_contrast',
								'_ajax_nonce': cttc_ajax.nonce,
								'color': color.against,			// the background we're contrasting against
								'additional': color.additional, // and the widget background to the background in case of transparency
							},
			success:		function(response) {
								if (response.ok == true) {
									$(color.widget).css('--'+color.set_var, response.contrast);
									$(color.wcag).html(response.wcag);
									
									console.log('cool', response, color);
									//console.log(color.against, response.contrast, response.ratio, response.wcag);
									
								} else {
									console.log('error', response, color);
									//$(color.wcag).html('Error');
								}
							},
			dataType:		'json',
		});
	}
	
	
	function setTerms(taxonomies) {
		$.ajax({
			type:			'POST',
			url:			cttc_ajax.url,
			data:			{
								'action': 'cttc_get_terms',
								'_ajax_nonce': cttc_ajax.nonce,
								'taxonomies': taxonomies.selected,
							},
			success:		function(response) {
								if (response.ok == true) {
									$(taxonomies.set_dom).empty().html(response.options);
									/*$.each(response.terms, function(k, v) {
										$(taxonomies.set_dom).append("<option value='"+v.term_id+"'>"+v.name+'</option>');
									});*/
									//console.log('cool', response);
									
								} else {
									console.log('error', response);
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
