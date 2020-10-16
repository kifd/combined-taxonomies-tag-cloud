
(function($){
	
	// thanks to Istvan @ http://zourbuth.com/archives/877/how-to-use-wp-color-picker-in-widgets/#comment-64600
	// for a solution that doesn't populate the initial widget with an additional non-functioning colour picker
	
	var parent = $('body');
	if ($('body').hasClass('widgets-php')) {
		parent = $('.widget-liquid-right');
	}

	jQuery(document).ready(function($) {
		parent.find('.color-field').wpColorPicker();

		jQuery(document).on('widget-added', function(e, widget) {
			widget.find('.color-field').wpColorPicker();
		});

		jQuery(document).on('widget-updated', function(e, widget) {
			widget.find('.color-field').wpColorPicker();
		});

		jQuery(document).bind('ajaxComplete', function() {
			parent.find('.color-field').wpColorPicker();
		});
		
		
		// change the font list when updated
		parent.on('change', 'select.font_family', function() {
			var _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
			jQuery(this).siblings('.font_list').text(_stack).css('font-family', _stack);
		});
		
		parent.on('change', 'select.display', function() {
			if (this.value == 'diy') {
				jQuery(this).parent().nextUntil('hr', 'p').attr('class', 'hide');
			} else {
				jQuery(this).parent().nextUntil('hr', 'p').attr('class', 'show');
			}
		});
	});
	

	
	
})(jQuery);
