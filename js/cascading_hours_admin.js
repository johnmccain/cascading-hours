jQuery(document).ready( function() {
	try {
		jQuery('.timepicker').timepicker({
			timeFormat: 'h:mm p',
			interval: 15,
			defaultTime: '11',
			startTime: '06:00',
			dynamic: false,
			dropdown: true,
			scrollbar: true
		});
	} catch(e) {
		//timepicker not loaded, do nothing
	}

	jQuery('.numeric-field').map(function() {
		this.onkeydown = liveValidateNumericField;
		this.onkeyup = liveValidateNumericField;
	});
});

/**
 * Removes any non-numeric characters from the value.
 * For use on text fields that should have numeric values only. Should be used as an event handler
 */
function liveValidateNumericField() {
	jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
}
