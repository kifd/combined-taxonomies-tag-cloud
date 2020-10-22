
(function($){
	
	// WP color picker:
	// thanks to https://www.codecheese.com/2013/04/how-to-use-wp-color-picker-in-widgets/#comment-64600
	// and https://www.codecheese.com/2018/05/trigger-change-wpcolorpicker-in-widgets-and-customizer-page/
	$(document).on('ready widget-added widget-updated', function(event, widget) {      
		$('.color-field').not('[id*="__i__"]').wpColorPicker({ 
			change: function(e, ui) {
				$(e.target).val(ui.color.toString());
				// WP doesn't let you save the widget if all you did was use its own color-picker widget
				$(e.target).trigger('change'); // so trigger manually to enable the save button
			},
		});
	});
	
	
	// change the font list when updated
	$(document).on('change', 'select.font_family', function() {
		var _stack = (this.value in font_stacks) ? font_stacks[this.value] : '';
		jQuery(this).siblings('.font_list').text(_stack).css('font-family', _stack);
	});
	
	
	
	// toggle open fieldsets by clicking on the legend
	$(document).on('click', '.combined-taxonomies-tag-cloud legend', function() {
		if (! $(this).hasClass('closed')) {
			$(this).next('div').attr('class', 'hide');
		} else {
			$(this).next('div').attr('class', 'show');
		}
		$(this).toggleClass('closed');
	});
	
	
	// hide/show extra scaling info if scaling in use
	$(document).on('change', 'input.scale_tag', function() {
		if (! jQuery(this).is(':checked')) {
			jQuery(this).parent().nextAll('p').addClass('hide');
		} else {
			jQuery(this).parent().nextAll('p').removeClass('hide');
		}
	});
	
	
	// make sure to trigger those events on page load
	$(document).bind('ready', function() {
		//$('select.font_family').trigger('change');
		$('input.scale_tag').trigger('change');
		$('.combined-taxonomies-tag-cloud legend').trigger('click');
	});
	// and also trigger when widget saved
	$(document).bind('ajaxComplete', function(e, xhr, settings) {
		// if we don't have the timeout key then it's (afaict) a deliberate save and not an autosave
		if (! (settings.hasOwnProperty('timeout'))) {
			// get which widget was saved so that we don't accidentally toggle any others
			var _widget = '#' + e.currentTarget.activeElement.offsetParent.id;
			$('input.scale_tag', _widget).trigger('change');
			$('.combined-taxonomies-tag-cloud legend', _widget).trigger('click');
		}
	});
	
	
})(jQuery);
