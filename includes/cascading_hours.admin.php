<?php

/**
 * @file cascading_hours.admin.php
 * admin interface functions
 */

/**
 * Main admin settings page
 */
function cascading_hours_admin()
{
	$page = "<h3>General Settings</h3>";
	$form = drupal_get_form("cascading_hours_admin_settings_form");
	$page .= render($form);
	$page .= "<br><hr/><br>";
	$page .= "<table><tr><th colspan='2'>Location</th></tr>";
	$locations = cascading_hours_get_locations();
	if ($locations == []) {
		$page .= "<tr><td colspan='2'>No locations found</td></tr>";
	}

	foreach($locations as $location) {
		$name = $location['name'];
		$location_id = $location['id'];
		$page .= "<tr><td>" . l($name, "admin/structure/cascading_hours/location/$location_id") . "</td>";
		$page .= "<td>" . l("delete", "admin/structure/cascading_hours/location/$location_id/delete") . "</td></tr>";
	}

	$page .= "</table>";
	$page .= l(t("Add Location"), "admin/structure/cascading_hours/location/add");
	$page .= '<hr/>';
	$page .= l(t("Edit Blocks"), "admin/structure/cascading_hours/block");
	return $page;
}

/**
 * Builds form for master settings
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_settings_form($form, &$form_state)
{
	$form = [];

	$form['cascading_hours_delete_old_rules'] = array(
		'#type' => 'checkbox',
		'#title' => t('Automatically delete old rules'),
		'#default_value' => variable_get('cascading_hours_delete_old_rules', false),
		'#description' => t("When enabled, rules with end dates older than a set age will be automatically deleted."),
		'#required' => true,
	);

	$form['cascading_hours_old_rules_age'] = array(
		'#type' => 'textfield',
		'#title' => t('Maximum rule age (in days)'),
		'#default_value' => variable_get('cascading_hours_old_rules_age', 14),
		'#description' => t("Maximum age in days of rules.  If \"Automatically delete old rules\" is enabled, rules with end dates older than this will be deleted."),
		'#attributes' => array(
			'class' => array(
				'numeric-field',
			) ,
			'#required' => true,
		)
	);

	$form['cascading_hours_delete_old_files'] = array(
		'#type' => 'checkbox',
		'#title' => t('Automatically delete old files'),
		'#default_value' => variable_get('cascading_hours_delete_old_files', false),
		'#description' => t("When enabled, files with created dates older than a set age will be automatically deleted."),
		'#required' => true,
	);

	$form['cascading_hours_old_files_age'] = array(
		'#type' => 'textfield',
		'#title' => t('Maximum export file age (in days)'),
		'#default_value' => variable_get('cascading_hours_old_files_age', 90),
		'#description' => t("Maximum age in days of export files.  If \"Automatically delete old files\" is enabled, export files with created dates older than this will be deleted."),
		'#attributes' => array(
			'class' => array(
				'numeric-field',
			) ,
			'#required' => true,
		)
	);

	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Save',
	);

	$form['#attached']['js'] = array(drupal_get_path('module', 'cascading_hours') ."/js/cascading_hours_admin.js");
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_settings_form_submit';

	return $form;
}

/**
 * Submission logic for master settings
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_settings_form_submit($form, &$form_state)
{
	$cascading_hours_delete_old_rules = $form_state['values']['cascading_hours_delete_old_rules'];
	$cascading_hours_old_rules_age = $form_state['values']['cascading_hours_old_rules_age'];
	$cascading_hours_delete_old_files = $form_state['values']['cascading_hours_delete_old_files'];
	$cascading_hours_old_files_age = $form_state['values']['cascading_hours_old_files_age'];
	if(!is_numeric($cascading_hours_old_rules_age)) {
		form_set_error("cascading_hours_old_rules_age", "Maximum rule age must be a number");
	} else if(!is_numeric($cascading_hours_old_files_age)) {
		form_set_error("cascading_hours_old_files_age", "Maximum file age must be a number");
	} else {
		variable_set('cascading_hours_delete_old_rules', (bool) $cascading_hours_delete_old_rules);
		variable_set('cascading_hours_old_rules_age', (int) $cascading_hours_old_rules_age);
		variable_set('cascading_hours_delete_old_files', (bool) $cascading_hours_delete_old_files);
		variable_set('cascading_hours_old_files_age', (int) $cascading_hours_old_files_age);
	}
}

/*** BLOCK UI METHODS ***/

/**
 * Generates an adminstration page for cascading_hours block views
 * @return string - A markup string for the block admin page
 */
function cascading_hours_admin_blocks() {
	$blocks = cascading_hours_get_blocks();
	$locations = cascading_hours_get_locations();
	$page = '';
	$page .= l(t("Go back"), "admin/structure/cascading_hours/");
	$page .= '<hr/>';

	foreach($blocks as $block) {
		$location_ids = json_decode($block['locations']);
		$locations = [];
		foreach($location_ids as $lid) {
			$locations[] = cascading_hours_get_location_with_id($lid);
		}
		$page .= '<h3>' . $block['type'] .  $block['id'] . '</h3>';
		$page .= '<ul class="cascading_hours_inline_list">';
		foreach($locations as $location) {
			$page .= '<li>' . l($location['name'], 'admin/structure/cascading_hours/location/' . $location['id']) . '</a></li>';
		}
		if(count($locations) == 0) {
			$page .= '<li>No Locations</li>';
		}
		$page .= '</ul>';
		$page .= l(t('delete'), 'admin/structure/cascading_hours/block/' . $block['id'] . '/delete');
		$page .= '<br/>';
		$page .= l(t('configure'), 'admin/structure/block/manage/cascading_hours/' . $block['type'] . $block['id'] . '/configure');
		$page .= '<hr/><br/>';
	}
	$page .= l('Add New Block', 'admin/structure/cascading_hours/block/add');
	$page .= '<hr/>';
	$page .= l(t("Go back"), "admin/structure/cascading_hours/");
	drupal_add_css(drupal_get_path('module', 'cascading_hours') . '/css/cascading_hours_main.css');
	return $page;
}


/**
 * Generates a page for adding a cascading_hours block
 * @return string - A string of markup
 */
function cascading_hours_admin_block_add() {
	$page = '';
	$page .= '<h3>Add a Block</h3>';
	$page .= l(t("Go back"), "admin/structure/cascading_hours/block/");
	$page .= '<hr/>';
	$form = drupal_get_form('cascading_hours_admin_block_add_form');
	$page .= render($form);
	$page .= '<hr/>';
	$page .= l(t("Go back"), "admin/structure/cascading_hours/block/");
	return $page;
}

/**
 * Generates a form for adding a cascading_hours block view
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 * @return array - A renderable form array
 */
function cascading_hours_admin_block_add_form($form, &$form_state) {
	$form = [];

	$locations = cascading_hours_get_locations();
	$display_locations = [];
	foreach($locations as $location) {
		$display_locations[$location['id']] = $location['name'];
	}

	watchdog('cascading_hours', 'Display locations: ' . json_encode($display_locations));

	$form['block_type'] = array(
		'#type' => 'select',
		'#title' => t('Type'),
		'#description' => t('Type of block to create. (nav_view allows for navigation in time and between locations, week_view displays hours for the current week for all applicable locations)'),
		'#options' => drupal_map_assoc(array('nav_view', 'week_view')),
	);

	$form['block_locations'] = array(
		'#type' => 'checkboxes',
		'#title' => t('Select Locations to Display'),
		'#description' => t('Select locations for which the block should render schedules.'),
		'#options' => $display_locations,
	);

	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Save',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_block_add_form_submit';
	return $form;
}

/**
 * Submission logic for adding a cascading_hours block view
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_block_add_form_submit($form, &$form_state) {
	watchdog('cascading_hours', 'Block locations: ' . json_encode($form_state['values']['block_locations']));

	$locations = [];
	foreach($form_state['values']['block_locations'] as $id => $checked) {
		if($checked) {
			$locations[] = $id;
		}
	}

	cascading_hours_create_block($form_state['values']['block_type'], json_encode($locations));
	$form_state['redirect'] = 'admin/structure/cascading_hours/block';
}

/**
 * Generates a confirmation page for deleting a cascading_hours block
 * @param int $block_id - id of the block to be deleted
 * @return string - A string of markup
 */
function cascading_hours_admin_block_delete($block_id) {
	$block = cascading_hours_get_block_with_id($block_id);
	if (isset($block['type'])) {
		// location exists, show delete form
		$name = $block['type'] . $block['id'];
		$page = "<h3>" . t("Delete Block") . ": $name</h3>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/block");
		$page .= "<hr/>";
		$page .= "<p><strong>" . t("Are you sure you want to delete this block? This action cannot be undone") . "</strong></p>";
		$form = drupal_get_form("cascading_hours_admin_block_delete_form", $block['id']);
		$page .= render($form);
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/block");
		return $page;
	}
	else {
		// location doesn't exist

		$page = "<h3>" . t("No Such Block") . "</h3>";
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/block");
		return $page;
	}
}

/**
 * Generate confirmation form for deleting blocks
 * @param array $form
 * @param array &$form_state
 * @return array - A renderable form array
 */
function cascading_hours_admin_block_delete_form($form,  &$form_state) {
	$form['block_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][0],
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Confirm',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_block_delete_form_submit';
	return $form;
}

/**
 * Submission logic for deleting blocks
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_block_delete_form_submit($form,  &$form_state) {
	$form_state['redirect'] = 'admin/structure/cascading_hours/block/';
	cascading_hours_delete_block_with_id($form_state['values']['block_id']);
}

/*** LOCATION UI METHODS ***/

/**
 * Generates page to edit a location
 *
 * @param int $location_id - the id of the location to edit
 */
function cascading_hours_admin_edit_location($location_id)
{
	$location = cascading_hours_get_location_with_id($location_id);
	if ($location["name"]) {

		// location exists, show edit form

		$name = $location["name"];
		$page = "<h3>" . t("Edit Location:") . " $name</h3>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/");
		$page .= "<hr/>";
		$form = drupal_get_form("cascading_hours_admin_edit_location_form", $location);
		$page .= render($form);
		$page .= "<hr/>";
		$page .= l(
			t("Add Rule"), "admin/structure/cascading_hours/rule/add", array(
			'query' => array(
				'location' => $location['id']
			)
			)
		);
		$page .= "<table><tr><th>" . t("Rule") . "</th><th>" . t("Start Date") . "</th><th>" . t("End Date") . "</th><th>" . t("Weight") . "</th><th></th></tr>";
		$rules = cascading_hours_get_rules_with_location_id($location_id);
		if ($rules == []) {
			$page .= "<tr><td colspan='5'>" . t("No rules found") . "</td></tr>";
		}

		foreach($rules as $rule) {
			$name = $rule['alias']; //alias is optional, check if defined
			$name = $name ? "\"" . $name . "\"" : $name;
			$rule_id = $rule['id'];
			$page .= "<tr><td>" . l($rule_id . " " . $name, "admin/structure/cascading_hours/rule/$rule_id") . "</td>";
			$page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['start_date'])->format('m/d/Y') . "</td>";
			$page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['end_date'])->format('m/d/Y') . "</td>";
			$page .= "<td>" . $rule['priority'] . "</td>";
			$page .= "<td>" . l(t("delete"), "admin/structure/cascading_hours/rule/$rule_id/delete") . "</td></tr>";
		}

		$page .= "</table>";
		$page .= l(t("Import Schedule"), "admin/structure/cascading_hours/import/" . $location['id']);
		$page .= '<br/>';
		$page .= l(t("Export Schedule"), "admin/structure/cascading_hours/export/" . $location['id']);
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/");
		return $page;
	}
	else {
		// location doesn't exist
		$page = "<h3>" . t("No Such Location") . "</h3>";
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/");
		return $page;
	}

	return $page;
}

/**
 * Builds form for editing a location
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_edit_location_form($form, &$form_state)
{
	$form = [];
	$location = $form_state['build_info']['args'][0];
	$form['location_id'] = array(
		'#type' => 'value',
		'#value' => $location['id'],
	);
	$form['name'] = array(
		'#type' => 'textfield',
		'#title' => 'Location Name',
		'#required' => true,
		'#default_value' => $location['name'],
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Save',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_edit_location_form_submit';
	return $form;
}

/**
 * Submission logic for location deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_edit_location_form_submit($form, &$form_state)
{
	cascading_hours_update_location($form_state['values']['location_id'], filter_xss($form_state['values']['name']));
}

/**
 * Generates page to add a location
 */
function cascading_hours_admin_add_location()
{
	$page = "<h3>" . t("Add Location") . "</h3>";
	$page .= l(t("Go back"), "admin/structure/cascading_hours");
	$page .= "<hr/>";
	$form = drupal_get_form("cascading_hours_admin_add_location_form");
	$page .= render($form);
	$page .= "<br/>";
	$page .= "<hr/>";
	$page .= l(t("Go back"), "admin/structure/cascading_hours");
	return $page;
}

/**
 * Builds form for location creation
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_add_location_form($form, &$form_state)
{
	$form = [];
	$form['name'] = array(
		'#type' => 'textfield',
		'#title' => 'Location Name',
		'#required' => true,
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Create',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_add_location_form_submit';
	return $form;
}

/**
 * Submission logic for location creation
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_add_location_form_submit($form, &$form_state)
{
	cascading_hours_create_location(filter_xss($form_state['values']['name']));
	$form_state['redirect'] = 'admin/structure/cascading_hours';
}

/**
 * Generates page to delete a location (with confirmation)
 *
 * @param int $location_id - the id of the location to delete
 */
function cascading_hours_admin_delete_location($location_id)
{
	$location = cascading_hours_get_location_with_id($location_id);
	if (isset($location["name"])) {

		// location exists, show delete form
		$name = $location["name"];
		$page = "<h3>" . t("Delete Location:") . " $name</h3>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
		$page .= "<hr/>";
		$page .= "<p><strong>" . t("Are you sure you want to delete this location? This action cannot be undone") . "</strong></p>";
		$form = drupal_get_form("cascading_hours_admin_delete_location_form", $location_id);
		$page .= render($form);
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
		return $page;
	}
	else {
		// location doesn't exist

		$page = "<h3>" . t("No Such Location") . "</h3>";
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
		return $page;
	}
}

/**
 * Builds confirmation form for location deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_location_form($form, &$form_state)
{
	$form = [];
	$form['location_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][0],
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Confirm',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_delete_location_form_submit';
	return $form;
}

/**
 * Submission logic for location deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_location_form_submit($form, &$form_state)
{
	cascading_hours_delete_location_with_id($form_state['values']['location_id']);
	$form_state['redirect'] = 'admin/structure/cascading_hours';
}

/*** RULE UI METHODS ***/

/**
 * Generates page to add a rule
 */
function cascading_hours_admin_add_rule()
{
	$location_id = isset($_GET['location']) ? (int)$_GET['location'] : null;
	$location = [];
	if ($location_id != null) {
		$location = cascading_hours_get_location_with_id($location_id);
	}

	if (isset($location['name'])) {
		$page = "<h3>" . t("Add Rule to \"") . $location['name'] . "\"</h3>";
		$form = drupal_get_form("cascading_hours_admin_add_rule_form", $location['id']);
	}
	else {
		$page = "<h3>" . t("Add Rule") . "</h3>";
		$form = drupal_get_form("cascading_hours_admin_add_rule_form");
	}
	if (isset($location['name'])) {
		$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
	}
	else {
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
	}
	$page .= "<hr/>";
	$page .= render($form);
	$page .= "<hr/>";
	if (isset($location['name'])) {
		$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
	}
	else {
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
	}

	return $page;
}

/**
 * Builds form for location creation
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_add_rule_form($form, &$form_state)
{
	$form = [];
	$locations = cascading_hours_get_locations();
	$location_select = [];
	foreach($locations as $location) {
		$location_select[$location['id']] = $location['name'];
	}

	if ($form_state['build_info']['args']) {
		$default_location = $form_state['build_info']['args'][0];
		$form['location'] = array(
			'#type' => 'select',
			'#title' => t('Location') ,
			'#options' => $location_select,
			'#default_value' => $default_location, //defaults to location_id passed as argument
			'#description' => t('The location this rule applies to.') ,
			'#required' => true,
		);
	}
	else {
		$form['location'] = array(
			'#type' => 'select',
			'#title' => t('Location') ,
			'#options' => $location_select,
			'#description' => t('The location this rule applies to.') ,
			'#required' => true,
		);
	}

	$form['alias'] = array(
		'#type' => 'textfield',
		'#title' => t('Rule Alias') ,
		'#required' => false,
	);
	$form['start_date'] = array(
		'#type' => 'date_popup',
		'#title' => t('Start Date') ,
		'#date_format' => 'm/d/Y',
		'#attributes' => array(
			'class' => array(
				'datepicker',
			) ,
			'#required' => true,
		)
	);
	$form['end_date'] = array(
		'#type' => 'date_popup',
		'#title' => t('End Date') ,
		'#date_format' => 'm/d/Y',
		'#attributes' => array(
			'class' => array(
				'datepicker',
			) ,
			'#required' => true,
		)
	);
	$form['priority'] = array(
		'#type' => 'weight',
		'#title' => t('Weight') ,
		'#default_value' => 0,
		'#delta' => 5,
		'#description' => t('Rules with lower weight value have precedence.') ,
		'#required' => true,
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Create',
	);

	// $form['#attached']['js'] = array(drupal_get_path('module', 'cascading_hours') ."/js/cascading_hours_admin.js");

	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_add_rule_form_submit';
	return $form;
}

/**
 * Submission logic for location creation
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_add_rule_form_submit($form, &$form_state)
{
	$location_id = $form_state['values']['location'];
	$priority = $form_state['values']['priority'];
	$start_date = strtotime($form_state['values']['start_date']);
	$end_date = strtotime($form_state['values']['end_date']);
	$alias = filter_xss($form_state['values']['alias']);
	if (!$alias) {
		$alias = null;
	}
	cascading_hours_create_rule($location_id, $priority, $start_date, $end_date, $alias);

	$form_state['redirect'] = 'admin/structure/cascading_hours/location/' . $location_id;
}

/**
 * Generates a confirmation page to delete a rule
 *
 * @param int $rule_id - the id of the rule to delete
 */
function cascading_hours_admin_delete_rule($rule_id)
{
	$rule = cascading_hours_get_rule_with_id($rule_id);
	$location_id = $rule['location_id'];
	$location = cascading_hours_get_location_with_id($location_id);
	if (isset($rule['id'])) {

		// rule exists

		$alias = ($rule['alias'] ? " \"" . $rule['alias'] . "\"" : "");
		$page = "<h3>" . t("Delete Rule: ") . $rule['id'] . $alias . "</h3>";
		if (isset($location['id'])) {
			$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
		}
		else {
			$page .= l(t("Go back"), "admin/structure/cascading_hours");
		}
		$page .= "<hr/>";
		$page .= "<p><strong>" . t("Are you sure you want to delete this rule? This action cannot be undone") . "</strong></p>";
		$form = drupal_get_form("cascading_hours_admin_delete_rule_form", $rule['id'], $location['id']);
		$page .= render($form);
	}
	else {
		$page = "<h3>" . t("No Such Rule") . "</h3>";
	}

	$page .= "<hr/>";
	if (isset($location['id'])) {
		$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
	}
	else {
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
	}

	return $page;
}

/**
 * Builds confirmation form for rule deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_rule_form($form, &$form_state)
{
	$form = [];
	$form['rule_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][0],
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Confirm',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_delete_rule_form_submit';
	return $form;
}

/**
 * Submission logic for rule deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_rule_form_submit($form, &$form_state)
{
	cascading_hours_delete_rule_with_id($form_state['values']['rule_id']);
	if (isset($form_state['build_info']['args'][1])) {
		$form_state['redirect'] = 'admin/structure/cascading_hours/location/' . $form_state['build_info']['args'][1];
	}
	else {
		$form_state['redirect'] = 'admin/structure/cascading_hours';
	}
}

/**
 * Generates page to edit a rule
 *
 * @param int $rule_id - the id of the rule to edit
 */
function cascading_hours_admin_edit_rule($id)
{
	$rule = cascading_hours_get_rule_with_id($id);
	$location = cascading_hours_get_location_with_id($rule['location_id']);
	if (isset($rule['id'])) {
		$alias = $rule['alias'] ? "\"" . $rule['alias'] . "\"" : "";
		$page = "<h3>" . t("Edit Rule: ") . $rule['id'] . " " . $alias . "</h3>";
		if (isset($location['name'])) {
			$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $rule['location_id']);
		}
		else {
			$page .= l(t("Go back"), "admin/structure/cascading_hours");
		}
		$page .= "<hr/>";

		$form = drupal_get_form("cascading_hours_admin_edit_rule_form", $rule);
		$page .= render($form);
		$page .= "<hr/>";
		$schedules = drupal_get_form('cascading_hours_admin_add_schedule_form', $rule['id']);
		$page .= "<h3>Schedule Blocks</h3>";
		$page .= render($schedules);
	}
	else {
		$page = "<h3>" . t("No Such Rule") . "</h3>";
	}

	$page .= "<hr/>";
	if (isset($location['name'])) {
		$page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $rule['location_id']);
	}
	else {
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
	}

	return $page;
}

/**
 * Builds form for location editing
 *
 * @see   drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_edit_rule_form($form, &$form_state)
{
	$form = [];
	$rule = $form_state['build_info']['args'][0];
	$locations = cascading_hours_get_locations();
	$location_select = [];
	foreach($locations as $location) {
		$location_select[$location['id']] = $location['name'];
	}

	$form['rule_id'] = array(
		'#type' => 'value',
		'#value' => $rule['id'],
	);
	$form['location_id'] = array(
		'#type' => 'select',
		'#title' => t('Location') ,
		'#options' => $location_select,
		'#default_value' => $rule['location_id'],
		'#description' => t('The location this rule applies to.') ,
		'#required' => true,
	);
	$form['alias'] = array(
		'#type' => 'textfield',
		'#title' => t('Rule Alias') ,
		'#default_value' => $rule['alias'],
		'#required' => false,
	);
	$form['start_date'] = array(
		'#type' => 'date_popup',
		'#title' => t('Start Date') ,
		'#date_format' => 'm/d/Y',
		'#default_value' => format_date(strtotime($rule['start_date']), 'custom', 'm/d/Y'),
		'#attributes' => array(
			'class' => array(
				'datepicker',
			) ,
			'#required' => true,
		)
	);
	$form['end_date'] = array(
		'#type' => 'date_popup',
		'#title' => t('End Date') ,
		'#date_format' => 'm/d/Y',
		'#default_value' => format_date(strtotime($rule['end_date']), 'custom', 'm/d/Y') ,
		'#attributes' => array(
			'class' => array(
				'datepicker',
			) ,
			'#required' => true,
		)
	);
	$form['priority'] = array(
		'#type' => 'weight',
		'#title' => t('Weight') ,
		'#default_value' => 0,
		'#delta' => 5,
		'#description' => t('Rules with lower weight value have precedence.') ,
		'#default_value' => $rule['priority'],
		'#required' => true,
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Save',
	);

	// $form['#attached']['js'] = array(drupal_get_path('module', 'cascading_hours') ."/js/cascading_hours_admin.js");

	$form['actions']['submit']['#submit'][] = "cascading_hours_admin_edit_rule_form_submit";
	return $form;
}

/**
 * Submission logic for location editing
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_edit_rule_form_submit($form, &$form_state)
{
	$rule_id = $form_state['values']['rule_id'];
	$location_id = $form_state['values']['location_id'];
	$priority = $form_state['values']['priority'];
	$start_date = strtotime($form_state['values']['start_date']);
	$end_date = strtotime($form_state['values']['end_date']);
	$alias = filter_xss($form_state['values']['alias']);
	if (!$alias) { $alias = null;
	}
	cascading_hours_update_rule($rule_id, $location_id, $priority, $start_date, $end_date, $alias);
	$form_state['redirect'] = "admin/structure/cascading_hours/rule/" . $rule_id;
}

/*** SCHEDULE UI METHODS ***/

/**
 * Builds form for schedule creation
 * Ajax submission enabled, generates a list of schedules associated with a rule
 *
 * @see   drupal_get_form
 * @pre   A valid rule_id is passed in as a param to the drupal_get_form function
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_add_schedule_form($form, &$form_state)
{
	$rule_id = $form_state['build_info']['args'][0];
	if (!is_numeric($rule_id)) {
		$form['error_markup'] = array(
			'#type' => 'markup',
			'#prefix' => '<h3 style="color: red;">',
			'#suffix' => '</h3>',
			'#markup' => 'Error: schedule form requires a rule_id as a param at time of generation',
		);
		return $form;
	}

	$table = cascading_hours_admin_generate_schedule_table($rule_id);
	$form['schedule_table'] = array(
		'#type' => 'markup',
		'#prefix' => '<div id="schedule_table">',
		'#suffix' => '</div>',
		'#markup' => $table,
	);
	$form['rule_id'] = array(
		'#type' => 'value',
		'#value' => $rule_id,
	);
	$form['day'] = array(
		'#type' => 'select',
		'#title' => t('Day of Week') ,
		'#options' => array(
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
		) ,
		'#default_value' => 0,
		'#required' => true,
	);
	$form['start_time'] = array(
		'#type' => 'textfield',
		'#title' => t('Start Time') ,
		'#size' => 20,
		'#required' => true,
		'#attributes' => array(
			'class' => array(
				'timepicker',
			) ,
		)
	);
	$form['end_time'] = array(
		'#type' => 'textfield',
		'#title' => t('End Time') ,
		'#size' => 20,
		'#required' => true,
		'#attributes' => array(
			'class' => array(
				'timepicker',
			) ,
		)
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#ajax' => array(
			'callback' => 'cascading_hours_admin_add_schedule_form_submit_callback',
			'wrapper' => 'schedule_table',
		) ,
		'#value' => t('Create') ,
	);
	$form['#attached']['js'] = array(
		drupal_get_path('module', 'cascading_hours') . "/js/cascading_hours_admin.js",
		drupal_get_path('module', 'cascading_hours') . "/js/jquery.timepicker.js"
	);
	$form['#attached']['css'] = array(
		drupal_get_path('module', 'cascading_hours') . "/css/jquery.timepicker.css"
	);
	return $form;
}

/**
 * Callback for submit_driven example.
 *
 * Select the 'box' element, change the markup in it, and return it as a
 * renderable array.
 *
 * @return array
 *   Renderable array (the box element)
 */
function cascading_hours_admin_add_schedule_form_submit_callback($form, $form_state)
{
	$rule_id = $form_state['values']['rule_id'];
	$day = $form_state['values']['day'];
	$start_time = strtotime($form_state['values']['start_time']);
	$end_time = strtotime($form_state['values']['end_time']);

	// validate

	$err = false;
	if ($start_time >= $end_time) {
		form_set_error("end_time", "End time must be after start time.");
		$err = true;
	}

	// create (if validation successful)

	if (!$err) {
		cascading_hours_create_schedule($rule_id, $day, $start_time, $end_time);
	}

	// update schedule_table

	$table = cascading_hours_admin_generate_schedule_table($rule_id);
	$element = $form['schedule_table'];
	$element['#markup'] = $table;
	return $element;
}

/**
 * Generates page to delete a schedule (with confirmation)
 *
 * @param int $schedule_id - the id of the schedule to delete
 */
function cascading_hours_admin_delete_schedule($schedule_id)
{
	$schedule = cascading_hours_get_schedule_with_id($schedule_id);
	if (isset($schedule['id'])) {

		// schedule exists
		$page = "<h3>" . t("Delete Schedule: ") . $schedule['id'] . "</h3>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/rule/" . $schedule['rule_id']);
		$page .= "<hr/>";
		$page .= "<p><strong>" . t("Are you sure you want to delete this schedule? This action cannot be undone") . "</strong></p>";
		$form = drupal_get_form('cascading_hours_admin_delete_schedule_form', $schedule['id']);
		$page .= render($form);
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours/rule/" . $schedule['rule_id']);
	}
	else {
		// schedule doesn't exist

		$page = "<h3>" . t("No Such Schedule") . "</h3>";
		$page .= "<hr/>";
		$page .= l(t("Go back"), "admin/structure/cascading_hours");
	}

	return $page;
}

/**
 * Builds confirmation form for schedule deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_schedule_form($form, &$form_state)
{
	$form = [];
	$form['schedule_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][0],
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Confirm',
	);
	$form['actions']['submit']['#submit'][] = 'cascading_hours_admin_delete_schedule_form_submit';
	return $form;
}

/**
 * Submission logic for schedule deletion
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_delete_schedule_form_submit($form, &$form_state)
{
	$schedule = cascading_hours_get_schedule_with_id($form_state['values']['schedule_id']);
	if (isset($schedule['rule_id'])) {
		$form_state['redirect'] = 'admin/structure/cascading_hours/rule/' . $schedule['rule_id'];
	}
	else {
		$form_state['redirect'] = 'admin/structure/cascading_hours';
	}

	cascading_hours_delete_schedule_with_id($form_state['values']['schedule_id']);
}

/**
 *  Comparison function for sorting arrays of schedule blocks
 *
 *  @param  array $a - schedule
 *  @param  array $b - schedule
 *  @return int - negative if $a < $b, 0 if $a == $b, positive if $a > $b
 */
function cascading_hours_day_cmp($a, $b)
{
	return $a['day'] - $b['day'];
}

/**
 * Generates the markup for a table of schedules with a given rule id
 *
 * @param  int $rule_id
 * @return string - A string of markup for a table of schedules
 */
function cascading_hours_admin_generate_schedule_table($rule_id)
{
	$day_string = array(
		0 => 'Sun',
		1 => 'Mon',
		2 => 'Tues',
		3 => 'Wed',
		4 => 'Thur',
		5 => 'Fri',
		6 => 'Sat',
	);
	$schedules = cascading_hours_get_schedules_with_rule_id($rule_id);
	usort($schedules, "cascading_hours_day_cmp");
	$table = "<table><tr><th>" . t("id") . "</th><th>" . t("Day") . "</th><th>" . t("Start Time") . "</th><th>" . t("End Time") . "</th><th></th></tr>";
	if ($schedules == []) {
		$table .= "<tr><td colspan='5'>" . t("No schedules found") . "</td></tr>";
	}

	foreach($schedules as $schedule) {
		$table .= "<tr><td>" . $schedule['id'] . "</td>";
		$table .= "<td>" . $day_string[$schedule['day']] . "</td>";
		$table .= "<td>" . date('h:i A', strtotime($schedule['start_time'])) . "</td>";
		$table .= "<td>" . date('h:i A', strtotime($schedule['end_time'])) . "</td>";
		$table .= "<td>" . l(t("delete"), "admin/structure/cascading_hours/schedule/" . $schedule['id'] . "/delete") . "</td></tr>";
	}

	$table .= "</table>";
	return $table;
}
