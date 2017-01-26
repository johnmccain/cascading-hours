/**
 * @file
 * Handles interaction and loading of data for nav_view block
 */
(function($) {
	console.log('loaded cascading_hours_nav_view.js');
	var module = {
		/**
		 * Renders the current schedule data
		 */
		render: function() {
			//gets here
			console.log('render');
			console.log(this.data);
		},

		/**
		 * Loads schedule data for current location and reference date and triggers a render refresh
		 */
		loadData: function() {
			console.log(this);
			var url = document.origin + this.path + '/api.php?' + $.param({
				start: this.date.getTime() / 1000 | 0,
				end: (this.date.getTime() / 1000 | 0) + 60 * 60 * 24 * 7,
				location_id: this.location
			});
			console.log(url);
			$.getJSON(url,
				(function(me) {
					return function(data) {
						console.log(me);

						console.log(data);
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
		leftClick: function() {
			console.log('leftClick');
			this.date.setDate(this.date.getDate() - 7);
			this.loadData();
		},

		/**
		 * Handler for right date navigation click
		 */
		rightClick: function() {
			console.log('rightClick');
			this.date.setDate(this.date.getDate() + 7);
			this.loadData();
		},

		/**
		 * Handler for change in location
		 */
		locationChange: function() {
			console.log('locationChange');
			this.location = this.$locations.val();
			this.loadData();
		},

		/**
		 * Initializes the Nav View module
		 */
		init: function() {
			this.data = null;
			this.location = null;
			this.date = new Date();
			this.date.setDate(this.date.getDate() - this.date.getDay()); //gets last occuring Sunday
			//TODO: this.date is not at 12:00 am
			this.$block = $('.cascading-hours-nav-view');
			this.$left = $('.nav-view-left');
			this.$right = $('.nav-view-right');
			this.$locations = $('.nav-view-locations');
			this.path = this.$block.attr('data-module-path');
			this.$locations.bind('change', this.locationChange);
			this.$left.bind('click', this.leftClick);
			this.$right.bind('click', this.rightClick);
			this.locationChange();
		},
	};
	setTimeout(function() {
		module.init();
	}, 2000);
})(jQuery);
