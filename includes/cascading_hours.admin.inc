<?

/**
 * Main admin settings page
 */
function cascading_hours_admin() {
  $page = "<h3>General Settings</h3>";
  $page .= "<code>TODO: settings form goes here.</code>";
  $page .= "<hr>";
  $page .= "<table><tr><th colspan='2'>Location</th></tr>";
  $locations = ch_get_locations();
  echo "<script>console.log(" . json_encode($locations) . ")</script>";

  if($locations == []) {
    $page .= "<tr><td colspan='2'>No locations found</td></tr>";
  }
  foreach($locations as $location) {
    $name = $location['name'];
    $location_id = $location['id'];
    $page .= "<tr><td>" . l($name, "admin/structure/cascading_hours/location/$location_id") . "</td>";
    $page .= "<td>" . l("delete", "admin/structure/cascading_hours/location/$location_id/delete") . "</td></tr>";
  }
  $page .= l("Add Location", "admin/structure/cascading_hours/location/add");

  return $page;
}

/**
 * Generates page to edit a location
 * @param int $location_id - the id of the location to edit
 */
function cascading_hours_admin_edit_location($location_id) {
  $location = ch_get_location_with_id($location_id);
  if($location["name"]) {
    //location exists, show edit form
    $name = $location["name"];
    $page = "<h3>" . t("Edit Location:") . " $name</h3>";
    $form = drupal_get_form("cascading_hours_edit_location_form", $location_id, $name);
    $page .= render($form);
    $page .= "<hr/>";

    $page .= l(t("Add Rule"), "admin/structure/cascading_hours/rule/add");
    $page .= "<table><tr><th>" . t("Rule") . "</th><th>" . t("Start Date") . "</th><th>" . t("End Date") . "</th><th colspan='2'>" . t("Priority") . "</th></tr>";
    $rules = ch_get_rules_with_location_id($location_id);
    echo "<script>console.log(" . json_encode($rules) . ")</script>";
    if($rules == []) {
      $page .= "<tr><td colspan='5'>" . t("No rules found") . "</td></tr></table>";
    }

    foreach($rules as $rule) {
      $name = $rule['alias']; //alias is optional, check if defined
      if($name) $name = "\"" . $name . "\"";
      else $name = "";

      $rule_id = $rule['id'];

      $page .= "<tr><td>" . l($rule_id . " " . $name, "admin/structure/cascading_hours/rule/$rule_id") . "</td>";
      $page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['start_date'])->format(DATE_ATOM) ."</td>";
      $page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['end_date'])->format(DATE_ATOM) ."</td>";
      $page .= "<td>" . $rule['priority'] . "</td></tr>";
      $page .= "<td>" . l("delete", "admin/structure/cascading_hours/rule/$rule_id/delete") . "</td></tr>";
    }
    $page .= "</table><hr/>";
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  } else {
    //location doesn't exist
    $page = "<h3>" . t("No Such Location") . "</h3>";
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  }

  return $page;
}

function cascading_hours_edit_location_form($form, &$form_state) {
    $form = [];

    $form['location_id'] = array(
      '#type' => 'value',
      '#value' => $form_state['build_info']['args'][0],
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => 'Location Name',
      '#required' => TRUE,
      '#default_value' => $form_state['build_info']['args'][1],
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    $form['actions']['submit']['#submit'][] = 'cascading_hours_edit_location_form_submit';

    return $form;
}

/**
 * Submission logic for location deletion
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_edit_location_form_submit($form, &$form_state) {

  ch_update_location_with_id($form_state['values']['location_id'], $form_state['values']['name']);
  // $form_state['redirect'] = 'admin/structure/cascading_hours';

}

/**
 * Generates page to add a location
 */
function cascading_hours_admin_add_location() {
  $page = "<h3>" . t("Add Location") . "</h3>";
  $form = drupal_get_form("cascading_hours_add_location_form");
  $page .= render($form);
  $page .= "<br/>";
  $page .= l(t("Go back"), "admin/structure/cascading_hours");
  return $page;
}

/**
 * Builds form for location creation
 * @see drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_add_location_form($form, &$form_state) {
  $form = [];

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => 'Location Name',
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Create',
  );

  $form['actions']['submit']['#submit'][] = 'cascading_hours_delete_location_form_submit';

  return $form;
}

/**
 * Submission logic for location creation
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_add_location_form_submit($form, &$form_state) {
  ch_create_location($form_state['values']['name']);
  $form_state['redirect'] = 'admin/structure/cascading_hours';
}

/**
 * Generates page to delete a location (with confirmation)
 * @param int $location_id - the id of the location to delete
 */
function cascading_hours_admin_delete_location($location_id) {
  $location = ch_get_location_with_id($location_id);
  if($location["name"]) {
    //location exists, show delete form
    $name = $location["name"];
    $page = "<h3>" . t("Delete Location:") . " $name</h3>";
    $page .= "<p><strong>" . t("Are you sure you want to delete this location? This action cannot be undone") . "</strong></p>";
    $form = drupal_get_form("cascading_hours_delete_location_form", $location_id);
    $page .= render($form);

    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  } else {
    //location doesn't exist
    $page = "<h3>" . t("No Such Location") . "</h3>";
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  }
}

/**
 * Builds confirmation form for location deletion
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_delete_location_form($form, &$form_state) {
  $form = [];

  $form['location_id'] = array(
    '#type' => 'value',
    '#value' => $form_state['build_info']['args'][0],
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Confirm',
  );

  $form['actions']['submit']['#submit'][] = 'cascading_hours_delete_location_form_submit';

  return $form;
}

/**
 * Submission logic for location deletion
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_delete_location_form_submit($form, &$form_state) {

  ch_delete_location_with_id($form_state['values']['location_id']);
  $form_state['redirect'] = 'admin/structure/cascading_hours';

}

/**
 * Generates page to edit a rule
 * @param int $rule_id - the id of the rule to edit
 */
function cascading_hours_admin_edit_rule($rule_id) {
  $rule = ch_get_rule_with_id($rule_id);

  if($rule['id']) {
    //rule exists
    $page = "<h3>" . t("TODO: Finish Edit Rule Page") . "</h3>";
    return $page;
  } else {
    //rule doesn't' exist
    $page = "<h3>" . t("No Such Rule") . "</h3>";
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  }

}

/**
 * Generates page to add a rule
 */
function cascading_hours_admin_add_rule() {
    $page = "<h3>" . t("TODO: Finish Add Rule Page") . "</h3>";
    return $page;
}

/**
 * Generates a confirmation page to delete a rule
 * @param int $rule_id - the id of the rule to delete
 */
function cascading_hours_admin_delete_rule($rule_id) {
  $rule = ch_get_rule_with_id($rule_id);

  if($rule['id']) {
    //rule exists
    $page = "<h3>" . t("TODO: Finish Delete Rule Page") . "</h3>";
    return $page;
  } else {
    //rule doesn't' exists
    $page = "<h3>" . t("No Such Rule") . "</h3>";
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
    return $page;
  }
}
