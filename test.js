for (var i = 0; i < obj.length; ++i) {
	for (j = 0; j < obj[i].length; ++j) {
		var start = new Date(obj[i][j].start);
		var end = new Date(obj[i][j].end);
		// document.write('<p>' + (start.getMonth() + 1) + '/' + start.getDate() + '<br>' + start.getHours() + ':' + start.getMinutes() + ' - ' + end.getHours() + ':' + end.getMinutes() + '</p>');
	}
}

var testobj = [
	[{
		"start": "2017-01-03T08:00:00-06:00",
		"end": "2017-01-03T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-04T08:00:00-06:00",
		"end": "2017-01-04T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-05T08:00:00-06:00",
		"end": "2017-01-05T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-06T08:00:00-06:00",
		"end": "2017-01-06T15:00:00-06:00"
	}, {
		"start": "2017-01-06T15:30:00-06:00",
		"end": "2017-01-06T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-07T08:00:00-06:00",
		"end": "2017-01-07T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-08T08:00:00-06:00",
		"end": "2017-01-08T20:00:00-06:00"
	}],
	[{
		"start": "2017-01-09T08:00:00-06:00",
		"end": "2017-01-09T20:00:00-06:00"
	}]
];
