(function($){
	
	$(document).on('ready widget-added widget-updated', function(event, widget) {
		// WP color picker:
		// thanks to https://www.codecheese.com/2013/04/how-to-use-wp-color-picker-in-widgets/#comment-64600
		// and https://www.codecheese.com/2018/05/trigger-change-wpcolorpicker-in-widgets-and-customizer-page/
		$('.color-field').not('[id*="__i__"]').wpColorPicker({
			defaultColor: $(this).attr('data-default-color'),
			change: function(e, ui) {
				$(e.target).val(ui.color.toString());
				// WP doesn't let you save the widget if all you did was use its own color-picker widget
				$(e.target).trigger('change'); // so trigger manually to enable the save button
			},
			clear: function(e) {
				$(e.target).trigger('change'); // likewise if we clear the color
				
				var _id = '#' + $(this).parent().find('.color-field').attr('id');
				checkContrast(_id);
			},
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
		$('.combined-taxonomies-tag-cloud .color-field[id$="tforeground"]', widget).each(function() { 
			checkContrast('#' + $(this).attr('id'));
		});
		// hide form elements depending on what has been selected
		$('*[data-hide-these]', widget).each(function(idx, dom) {
			toggleManyFields(dom);
		});
		
	});
	
	// change the font list when updated
	$(document).on('change', 'select.font_family', function() {
		var _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
		$(this).siblings('.font_list').text(_stack).css('font-family', _stack);
	});
	
	// change the font units when updated
	$(document).on('change', 'select.font_unit', function() {
		updateFontUnits($(this));
	});
	
	// toggle fieldsets by clicking on the legend
	$(document).on('click', '.combined-taxonomies-tag-cloud legend', function() {
		if ($(this).hasClass('closed')) {
			toggleFieldSet($(this), false);
		} else {
			toggleFieldSet($(this), true);
		}
	});
	
	// hide/show extra scaling info if scaling in use
	$(document).on('change', '.combined-taxonomies-tag-cloud input[data-hide-these]', function() {
		toggleManyFields($(this).get(0));
	});
	// NOTE: have to attach the event to the select parent element, not the option element with the data attr
	$(document).on('change', $('.combined-taxonomies-tag-cloud option[data-hide-these]').parent(), function(e) {
		$(e.target).children().each(function(idx, dom) {
			toggleManyFields(dom);
		});
	});
	
	$(document).on('change', '.color-field', function() {
		checkContrast('#' + $(this).attr('id'));
	});
	
	
	
	
	
	function checkContrast(id) {
		var _foreid = false;
		var _backid = false;
		
		if (id.indexOf('tforeground') != -1) {
			_foreid = id;
			_backid = id.replace(/tforeground/i, 'tbackground');
		} else if (id.indexOf('tbackground') != -1) {
			_foreid = id.replace(/tbackground/i, 'tforeground');
			_backid = id;
		}
		
		if (_foreid != false && _backid != false) {
			updateContrastDemo(_foreid, _backid);
		}
	}
	
	
	
	
	function updateContrastDemo(_foreid, _backid) {
		$.ajax({
			type:			'POST',
			url:			cttc_ajax.url,
			data:			{
								'action': 'update_contrast_demo',
								'_ajax_nonce': cttc_ajax.nonce,
								'color1': $(_foreid).val(),
								'color2': $(_backid).val(),
							},
			success:		function(response) {
								var demo = $(_foreid).parents('p').siblings('p.color-demo');
								var ratio = demo.children('span.ratio');
								ratio.css('color', $(_foreid).val());
								ratio.css('background-color', $(_backid).val());
								ratio.html(response.ratio);
								
								if (response.ok == true) {
									demo.children('span.wcag').html(response.wcag);
								} else {
									//console.log(response);
									demo.children('span.wcag').html('');
								}
							},
			dataType:		'json',
		});
	}
	
	// like toggleClass except won't open a closed one if you didn't want it to 
	function toggleFieldSet(dom, close = true) {
		if (close && ! dom.hasClass('closed')) {
			dom.addClass('closed');
		} else if (! close && dom.hasClass('closed')) {
			dom.removeClass('closed');
		}
	}
	
	// more advanced version of the original toggle
	function toggleManyFields(dom) {
		if (typeof $(dom).data('hide-these') !== 'undefined') {
			if (dom.nodeName == 'OPTION' || (dom.nodeName == 'INPUT' && dom.type == 'checkbox')) {
				
				// do we want to hide or show the 'hide-these' elements?
				var hide = (dom.selected == true || dom.checked == true) ? true : false;
				if (typeof $(dom).data('hide-invert') !== 'undefined') hide = !hide;
				
				var form = $(dom).parents('form:first');
				var elements = $(dom).data('hide-these').split(',');
				// TODO: bail if not array or no form
				
				//console.log(elements, hide);
				
				$(elements).each(function(i, what) {
					// takes advantage of my form being made of "p > label + thing" blocks
					if (hide) {
						$('[id*="'+what+'"]', form).parents('p:first').addClass('hide');
					} else {
						$('[id*="'+what+'"]', form).parents('p:first').removeClass('hide');
					}
				});
			}
		}
	}
	
	
	
	// NOTE: units are hard copied from the definition in the .php file
	var font_units = ['rem', 'em', 'pt', 'px', 'vw'];
	function updateFontUnits(dom) {
		var _unit = (font_units.indexOf(dom.get(0).value) >= 0) ? dom.get(0).value : '';
		var _widget = dom.parents().eq(4);
		$('.font_units', _widget).text(_unit);
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
