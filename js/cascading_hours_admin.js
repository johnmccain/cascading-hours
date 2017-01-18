jQuery(document).ready( function() {
	// jQuery(".datepicker").datepicker({dateFormat: 'mm/dd/yy'});
	console.log('test');
	jQuery('.timepicker').timepicker({
	    timeFormat: 'h:mm p',
	    interval: 15,
	    defaultTime: '11',
	    startTime: '00:00',
	    dynamic: false,
	    dropdown: true,
	    scrollbar: true
	});
});
