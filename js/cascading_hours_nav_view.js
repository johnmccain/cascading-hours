/**
 * @file
 * Handles interaction and loading of data for nav_view block
 */
(function($) {
	var module = {
		/**
		 * Renders the current schedule data
		 */
		render: function() {
			//gets here
			console.log('render');
			// console.log(this);

			var arr = [];
			var endDate = new Date(this.date);
			endDate.setDate(endDate.getDate() + 6);
			arr.push('<p>' + this.dateFormat(this.date) + ' - ' + this.dateFormat(endDate) + '</p>');
			console.log(this.data);
			for(var day = 0; day < 7; ++day) {
				arr.push('<p>');
				arr.push(this.weekdays[day]);
				arr.push(': ');
				if(!this.data[day] || this.data[day].length === 0) {
					arr.push('Closed');
				} else {
					var blocks = [];
					this.data[day].map(function(block) {
						blocks.push(this.timeFormat(new Date(block.start)) + '-' + this.timeFormat(new Date(block.end)));
					}, this);
					arr.push(blocks.join(', '));
				}
				arr.push('</p>');
			}
			$('.nav-view-schedule').html(arr.join(''));
		},

		/**
		 * @param {Date} date - A date object to convert to time string
		 * @return {string} - A time string in the form h:mm am/pm
		 */
		timeFormat: function(date) {
			var hours = date.getHours();
			var minutes = date.getMinutes();
			var timeStr;
			if(hours > 12) {
				timeStr = (hours % 12) + ':' + ('0' + minutes).slice(-2) + 'pm';
			} else {
				timeStr = hours + ':' + ('0' + minutes).slice(-2) + 'am';
			}
			return timeStr;
		},

		/**
		 * @param {Date} date - A date object to convert to date string
		 * @return {string} - A date string in the form h:mm am/pm
		 */
		dateFormat: function(date) {
			var month = date.getMonth() + 1;
			var day = date.getDate();
			var year = date.getFullYear();
			return month + '/' + day + '/' + year;
		},

		/**
		 * Loads schedule data for current location and reference date and triggers a render refresh
		 */
		loadData: function() {
			var url = document.origin + this.path + '/api.php?' + $.param({
				start: this.date.getTime() / 1000 | 0,
				end: (this.date.getTime() / 1000 | 0) + 60 * 60 * 24 * 7,
				location_id: this.location
			});
			console.log(url);
			$.getJSON(url,
				(function(me) {
					return function(data) {
						if (data.error) {
							console.error('Error loading schedule data');
						} else {
							me.data = data;
							me.render();
						}
					};
				}(this)));
		},

		/**
		 * Handler for left date navigation click
		 */
		leftClick: function(event) {
			console.log('leftClick');
			var me = this;
			if(event && event.data.me) {
				me = event.data.me;
			}
			me.date.setDate(me.date.getDate() - 7);
			me.loadData();
		},

		/**
		 * Handler for right date navigation click
		 */
		rightClick: function(event) {
			console.log('rightClick');
			var me = this;
			if(event && event.data.me) {
				me = event.data.me;
			}
			me.date.setDate(me.date.getDate() + 7);
			me.loadData();
		},

		/**
		 * Handler for change in location
		 */
		locationChange: function(event) {
			console.log('locationChange');
			var me = this;
			if(event && event.data.me) {
				me = event.data.me;
			}
			me.location = me.$locations.val();
			me.loadData();
		},

		/**
		 * Initializes the Nav View module
		 */
		init: function() {
			this.data = null;
			this.location = null;
			this.date = new Date();
			this.date.setDate(this.date.getDate() - this.date.getDay()); //gets last occuring Sunday
			this.date.setHours(0, 0, 0, 0); //at midnight
			this.weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
			this.$block = $('.cascading-hours-nav-view');
			this.$left = $('.nav-view-left');
			this.$right = $('.nav-view-right');
			this.$locations = $('.nav-view-locations');
			this.path = this.$block.attr('data-module-path');
			this.$locations.bind('change', {me: this}, this.locationChange);
			this.$left.bind('click', {me: this}, this.leftClick);
			this.$right.bind('click', {me: this}, this.rightClick);
			this.locationChange();
		},
	};
	setTimeout(function() {
		module.init();
	}, 2000);
})(jQuery);
