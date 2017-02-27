# cascading-hours
A Drupal module to simplify managing hours for multiple locations with complex schedules.

---
# Table of Contents

+ [Summary](#summary)
+ [Locations](#locations)
+ [Rules](#rules)
+ [Schedules](#schedules)
+ [Importing and Exporting](#import-export)
+ [Blocks](#blocks)
+ [API](#api)

---

## Summary
Cascading Hours manages schedules based on a system of rules.

__Locations__ represent an entity which has a schedule. Each location specified within Cascading Hours has a name and rules.

__Rules__ have a start and end date, a weight which determines precedence over other rules (where lower weights have greater precedence), and schedule blocks. Rules with greater weight might represent the "default" schedule, and those with lower weight (greater precedence) might be exceptions to the more general rule.

__Schedules__ represent schedule blocks and have a day of the week, a start time, and end time. Multiple schedule blocks can exist within a single day, allowing for a schedule which has breaks in between open hours (eg; 10:00am - 2:00pm, 4:00pm - 8:00pm).

The cascading hours configuration page can be found at admin/structure/cascading_hours or by clicking the "configure" link on the module page. On this page is a general settings form (for deleting old rules and export files), a link to the block management page, and the manage locations view.

Cascading Hours also has a public facing API for retrieving schedules via AJAX.

---
## <a name="locations"></a>Locations

The manage locations view is on the main Cascading Hours configuration page. This view includes a link to create a new location and a list of existing locations. Locations have only 2 properties, a unique name and an automatically generated integer id.

#### Creating a location

Locations can be created by selecting "Create Location" from the main configuration page. Location names must be unique.

#### Editing a location

Locations can be edited and have rules added to them through the individual location view. To access an individual location's configuration page, click on the name of a location from the main configuration page. This configuration page allows you to rename a rule, view and manage rules, and import/export schedules for the location.

#### Deleting a location

In order to delete a location, click the delete link next to the name of the location you wish to delete. You will be directed to a confirmation page. Please note that this action __cannot__ be undone and all data associated with the location will be lost.  All rules and schedules belonging to a deleted location will be permanently deleted as well.

---

## <a name="rules"></a>Rules

Rules can be viewed from the individual configuration page of the location to which they belong. Rules have properties:
+ __ID__ - An automatically assigned unique integer identifier
+ __Location__ - The location the rule applies to
+ __Alias__ - An optional string to help organize rules
+ __Start Date__ - The date a rule begins on (defaults to midnight that day, so inclusive)
+ __End Date__ - The date a rule ends on (defaults to midnight that day, so exclusive)
+ __Weight__ - The weight of the rule from -5 to 5. The lower the weight, the greater precedence the rule has
+ __Schedules__ - The schedules blocks that define the schedule a rule applies

#### Creating a rule

Rules can be created through the form accessible on an individual location configuration page as the "Add Rule" link. The add rule form asks you to specify the location (defaults to the location page you accessed the form from), an alias (optional), a start date, an end date, and a weight.

#### Editing a rule

Rules can be edited by clicking their name on the individual configuration page of the location to which they belong. The edit rule page has options to edit all rule attributes as well as to add schedule blocks to the rule. The schedule block form submits via AJAX to allow for quick creation of schedules (see _Creating a Schedule_).

#### Deleting a rule

In order to delete a rule, click the delete link next to the name of the rule you wish to delete on the location page the rule belongs to. You will be directed to a confirmation page. Please note that this action __cannot__ be undone and all data associated with the rule will be lost.  All schedules belonging to a deleted rule will be permanently deleted as well.

---

## <a name="schedules"></a>Schedules

Schedules represent schedule blocks that belong to a rule. Schedules have properties:
+ __ID__ - An automatically assigned unique integer identifier
+ __Start Time__
+ __End Time__
+ __Day__ - The day of the week the schedule block applies to. Stored as an integer (0 - 6, Sunday - Saturday) on the backend.

Schedules cannot be edited, only created and deleted.

#### Creating a schedule block

Schedules are created via the form on an individual rule page. This form is submitted via AJAX to allow for efficient creation of schedules. The end time must be after the start time of the schedule block. Multiple schedule blocks can be assigned to the same day.

#### Deleting a schedule block

Schedule blocks can be deleted from the individual rule display page of the rule they belong to. The delete link will take you to a confirmation form. Please note that this action __cannot__ be undone.

---

## <a name="import-export"></a>Importing and Exporting

A key feature of the Cascading Hours module is the ability to import and export spreadsheets of schedule data. This data must be in the CSV (Comma Separated Values) format. CSV files can be generated by any popular spreadsheet software. Schedules are imported and exported per location.

Cascading Hours uses the following spreadsheet format for importing and exporting schedules:

<table>
	<tr>
		<td>02/05/2017</td>
		<td>8:00 AM</td>
		<td>10:00 PM</td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td>*date*</td>
		<td>*start time of first block*</td>
		<td>*end time of first block*</td>
		<td>*start time of second block*</td>
		<td>*end time of second block (and so on)*</td>
	</tr>
	<tr>
		<td>...</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td>02/07/2017</td>
		<td>8:00 AM</td>
		<td>4:00 PM</td>
		<td>5:00 AM</td>
		<td>10:00 PM</td>
	</tr>
</table>

Days without any start/end time schedule block pairs are considered to be closed. All days in an import file must be consecutive, as is the case with all exported files.

#### Importing

To import a schedule, navigate to the individual location page of the location you wish to import a schedule for. Follow the "Import Schedule" link to see a page containing a form for you to upload your appropriately formatted CSV schedule file. After uploading, your file will be parsed and validated. Any syntax errors or unrecognized date/time formats will result in an error being displayed so you can correct the issue before attempting the import again. A successful parsing of the schedule will lead to a confirmation page displaying what schedule elements will be changed after the import. The imported schedule will be transformed into the longest contiguous rules that can be formed, and these rules will be given a weight of 5 to ensure that future rules have priority over the imported schedule. However, rules that conflict with the imported schedule will be deleted or altered (such as having start/end times changed to not conflict), and this action __cannot__ be undone.

#### Exporting

To export a schedule, navigate to the individual location page of the location you wish to export a schedule of. Click the "Export Schedule" link to continue to a form asking for the start and end date of the range you wish to export schedules for (this date range is inclusive). A schedule spreadsheet will be generated and a link to the newly created file will be displayed.

---

## <a name="blocks"></a>Blocks

Cascading Hours has 2 types of blocks for viewing schedule data, the Nav View block and the Week View block. Blocks can be configured to specify which locations a block should display data for. Blocks of either type can also be dynamically created and deleted, allowing for blocks displaying different sets of locations. One each of Nav View and Week View blocks are created at installation time. You can configure these from the standard Drupal block management page.  

The Cascading Hours block management page can be accessed from the main Cascading Hours configuration page through the "Manage Blocks" link.

Blocks are identified by their type concatenated with their block id (an automatically generated integer), for example: nav_view2 or week_view4

From the block management page you can see existing blocks and the locations they are set to display. There are links to configure, delete, and create blocks from this page.

#### Nav View

The Nav View block provides a more comprehensive and interactive schedule viewing medium than the Week View. The Nav View allows the user to select the location (from the locations specified by the administrator) for which to view schedules via a drop down menu. The user can also navigate forward and backward in time, week by week. The Nav View block requires Javascript and jQuery.

#### Week View

The Week View block simply displays the schedule for the current week for each location it is set to display. This block is ideal for something like a sidebar.

---

## <a name="api"></a>API

Cascading Hours offers a public read-only API so that schedule data can be accessed for any custom written scripts. An example of how to use this would be using AJAX to fill in a custom schedule view.

The API url is found by default at

> /modules/cascading_hours/api.php

The API queries for schedule data as specified by a number of get parameters. These parameters include:

+ location_id (integer) - The unique integer identifier of a Cascading Hours location.
+ location_name (string) - The name of a Cascading Hours location

 *(Note: this parameter is case sensitive and location names can be changed, so queries by location\_id are preferred if possible)*

+ start (integer) - The start datetime of the search time range as a Unix timestamp (the number of seconds since 1970)
+ end (integer) - The end datetime of the search time range as a Unix timestamp (the number of seconds since 1970)

So an example request to the api might be:

> example.com/modules/cascading_hours/api.php?location_id=4&start=1487545200&end=1488236400
