
(function($){
	
	$(document).on('ready widget-added widget-updated', function(event, widget) {
		// WP color picker:
		// thanks to https://www.codecheese.com/2013/04/how-to-use-wp-color-picker-in-widgets/#comment-64600
		// and https://www.codecheese.com/2018/05/trigger-change-wpcolorpicker-in-widgets-and-customizer-page/
		$('.color-field').not('[id*="__i__"]').wpColorPicker({
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
		//if (event.type != 'widget-updated') {
		$('.combined-taxonomies-tag-cloud legend', widget).each(function() { toggleFieldSet($(this), true); });
		// NOTE: wp resets the widget to its initial state after an update, losing the current open/close state without me
		//       saving them as hidden form fields
		
		
		$('.combined-taxonomies-tag-cloud .scale_tag', widget).each(function() { toggleScaleFields($(this), ! $(this).is(':checked')); });
		// NOTE: using .trigger() for the .scale_tag worked but made wp think it always needed saving for some reason
		
		
		$('.combined-taxonomies-tag-cloud .color-field[id$="tforeground"]', widget).each(function() { 
			checkContrast('#' + $(this).attr('id'));
		});
		
	});
	
	// change the font list when updated
	$(document).on('change', 'select.font_family', function() {
		var _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
		$(this).siblings('.font_list').text(_stack).css('font-family', _stack);
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
	$(document).on('click', '.combined-taxonomies-tag-cloud .scale_tag', function() {
		if ($(this).is(':checked')) {
			toggleScaleFields($(this), false);
		} else {
			toggleScaleFields($(this), true);
		}
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
								'colour1': $(_foreid).val(),
								'colour2': $(_backid).val(),
							},
			success:		function(response) {
								var demo = $(_foreid).parents('p.full').siblings('p.color-demo');
								var ratio = demo.children('span.ratio');
								ratio.css('color', $(_foreid).val());
								ratio.css('background-color', $(_backid).val());
								ratio.html(response.ratio);
								
								if (response.ok == true) {
									demo.children('span.wcag').html(response.wcag);
								} else {
									console.log(response);
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
	
	function toggleScaleFields(dom, close = true) {
		if (close && ! dom.hasClass('closed')) {
			dom.addClass('closed');
			dom.parent().nextAll('p').addClass('hide');
		} else if (! close && dom.hasClass('closed')) {
			dom.removeClass('closed');
			dom.parent().nextAll('p').removeClass('hide');
		}
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
