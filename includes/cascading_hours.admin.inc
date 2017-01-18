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

  if($locations == []) {
    $page .= "<tr><td colspan='2'>No locations found</td></tr>";
  }
  foreach($locations as $location) {
    $name = $location['name'];
    $location_id = $location['id'];
    $page .= "<tr><td>" . l($name, "admin/structure/cascading_hours/location/$location_id") . "</td>";
    $page .= "<td>" . l("delete", "admin/structure/cascading_hours/location/$location_id/delete") . "</td></tr>";
  }
  $page .= "</table>";
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
    $form = drupal_get_form("cascading_hours_edit_location_form", $location);
    $page .= render($form);
    $page .= "<hr/>";

    $page .= l(t("Add Rule"), "admin/structure/cascading_hours/rule/add", array('query' => array('location' => $location['id'])));
    $page .= "<table><tr><th>" . t("Rule") . "</th><th>" . t("Start Date") . "</th><th>" . t("End Date") . "</th><th>" . t("Priority") . "</th><th></th></tr>";
    $rules = ch_get_rules_with_location_id($location_id);
    if($rules == []) {
      $page .= "<tr><td colspan='5'>" . t("No rules found") . "</td></tr></table>";
    }

    foreach($rules as $rule) {
      $name = $rule['alias']; //alias is optional, check if defined
      $name = $name ? "\"" . $name . "\"" : $name;

      $rule_id = $rule['id'];

      $page .= "<tr><td>" . l($rule_id . " " . $name, "admin/structure/cascading_hours/rule/$rule_id") . "</td>";
      $page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['start_date'])->format('m/d/Y') ."</td>";
      $page .= "<td>" . DateTime::createFromFormat('Y-m-d H:i:s', $rule['end_date'])->format('m/d/Y') ."</td>";
      $page .= "<td>" . $rule['priority'] . "</td>";
      $page .= "<td>" . l(t("delete"), "admin/structure/cascading_hours/rule/$rule_id/delete", array('query' => array('location' => $location['id']))) . "</td></tr>";
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

    $location = $form_state['build_info']['args'][0];

    $form['location_id'] = array(
      '#type' => 'value',
      '#value' => $location['id'],
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => 'Location Name',
      '#required' => TRUE,
      '#default_value' => $location['name'],
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
  ch_update_location($form_state['values']['location_id'], $form_state['values']['name']);
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

  $form['actions']['submit']['#submit'][] = 'cascading_hours_add_location_form_submit';

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
  if(isset($location["name"])) {
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
 * Generates page to add a rule
 */
function cascading_hours_admin_add_rule() {
  $location_id = isset($_GET['location']) ? (int) $_GET['location'] : NULL;
  $location = [];
  if($location_id != NULL) {
    $location = ch_get_location_with_id($location_id);
  }

  if(isset($location['name'])) {
    $page = "<h3>" . t("Add Rule to \"") . $location['name'] . "\"</h3>";
    $form = drupal_get_form("cascading_hours_add_rule_form", $location['id']);
  } else {
    $page = "<h3>" . t("Add Rule") . "</h3>";
    $form = drupal_get_form("cascading_hours_add_rule_form");
  }
  $page .= render($form);
  $page .= "<hr/>";
  if(isset($location['name'])) {
    $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
  } else {
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
  }

  return $page;
}

/**
 * Builds form for location creation
 * @see drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_add_rule_form($form, &$form_state) {
  $form = [];

  $locations = ch_get_locations();
  $location_select = [];
  foreach($locations as $location) {
    $location_select[$location['id']] = $location['name'];
  }
  if($form_state['build_info']['args']) {
    $default_location = $form_state['build_info']['args'][0];
    $form['location'] = array(
         '#type' => 'select',
         '#title' => t('Location'),
         '#options' => $location_select,
         '#default_value' => $default_location, //defaults to location_id passed as argument
         '#description' => t('The location this rule applies to.'),
         '#required' => TRUE,
     );
  } else {
    $form['location'] = array(
         '#type' => 'select',
         '#title' => t('Location'),
         '#options' => $location_select,
         '#description' => t('The location this rule applies to.'),
         '#required' => TRUE,
     );
  }

  $form['alias'] = array(
    '#type' => 'textfield',
    '#title' => t('Rule Alias'),
    '#required' => FALSE,
  );

  $form['start_date'] = array(
    // '#type' => 'textfield',
    '#type' => 'date_popup',
    '#date_format' => 'm/d/Y',
    '#attributes' => array(
        'class' => array(
          'datepicker',
        ),
    '#title' => t('Start Date'),
    '#required' => TRUE,
  ));

  $form['end_date'] = array(
    // '#type' => 'textfield',
    '#type' => 'date_popup',
    '#date_format' => 'm/d/Y',
    '#attributes' => array(
        'class' => array(
          'datepicker',
        ),
    '#title' => t('End Date'),
    '#required' => TRUE,
  ));

  $form['priority'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => 0,
      '#delta' => 5,
      '#description' => t('Rules with lower weight value have precedence.'),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Create',
    );

  // $form['#attached']['library'] = array('system', 'ui');
  $form['#attached']['js'] = array(drupal_get_path('module', 'cascading_hours') ."/js/cascading_hours_admin.js");

  $form['actions']['submit']['#submit'][] = 'cascading_hours_add_rule_form_submit';

  return $form;
}

/**
 * Submission logic for location creation
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_add_rule_form_submit($form, &$form_state) {
  $location_id = $form_state['values']['location'];
  $priority = $form_state['values']['priority'];
  $start_date = strtotime($form_state['values']['start_date']);
  $end_date = strtotime($form_state['values']['end_date']);
  $alias = $form_state['values']['alias'];
  if(!$alias) $alias = NULL;
  ch_create_rule($location_id, $priority, $start_date, $end_date, $alias);

    // $form_state['values']['name']);
  $form_state['redirect'] = 'admin/structure/cascading_hours';
}

/**
 * Generates a confirmation page to delete a rule
 * @param int $rule_id - the id of the rule to delete
 */
function cascading_hours_admin_delete_rule($rule_id) {
  $rule = ch_get_rule_with_id($rule_id);

  $location_id = isset($_GET['location']) ? (int) $_GET['location'] : NULL;
  $location = [];
  if($location_id != NULL) {
    $location = ch_get_location_with_id($location_id);
  }

  if(isset($rule['id'])) {
    //rule exists
    $alias = ($rule['alias'] ? " \"" . $rule['alias'] . "\"" : "");
    $page = "<h3>" . t("Delete Rule: ") . $rule['id'] . $alias . "</h3>";
    $page .= "<p><strong>" . t("Are you sure you want to delete this rule? This action cannot be undone") . "</strong></p>";
    $form = drupal_get_form("cascading_hours_delete_rule_form", $rule['id'], $location['id']);
    $page .= render($form);
  } else {
    //rule doesn't' exists
    $page = "<h3>" . t("No Such Rule") . "</h3>";
  }

  if(isset($location['id'])) {
    $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
  } else {
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
  }

  return $page;
}

/**
 * Builds confirmation form for rule deletion
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_delete_rule_form($form, &$form_state) {
  $form = [];

  $form['rule_id'] = array(
    '#type' => 'value',
    '#value' => $form_state['build_info']['args'][0],
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Confirm',
  );

  $form['actions']['submit']['#submit'][] = 'cascading_hours_delete_rule_form_submit';

  return $form;
}

/**
 * Submission logic for rule deletion
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_delete_rule_form_submit($form, &$form_state) {

  ch_delete_rule_with_id($form_state['values']['rule_id']);
  if(isset($form_state['build_info']['args'][1])) {
    $form_state['redirect'] = 'admin/structure/cascading_hours/location/' . $form_state['build_info']['args'][1];
  } else {
    $form_state['redirect'] = 'admin/structure/cascading_hours';
  }

}

/**
 * Generates page to edit a rule
 * @param int $rule_id - the id of the rule to edit
 */
function cascading_hours_admin_edit_rule($id) {

  $rule = ch_get_rule_with_id($id);
  if(isset($rule['id'])) {
    $alias = $rule['alias'] ? "\"" . $rule['alias'] . "\"" : "";
    $page = "<h3>" . t("Edit Rule: ") . $rule['id'] . $alias . "</h3>";
    $form = drupal_get_form("cascading_hours_edit_rule_form", $rule);

    $page .= render($form);

  } else {
    $page = "<h3>" . t("No Such Rule") . "</h3>";
  }
  $page .= "<hr/>";
  $location = ch_get_location_with_id($rule['location_id']);
  if(isset($location['name'])) {
    $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $rule['location_id']);
  } else {
    $page .= l(t("Go back"), "admin/structure/cascading_hours");
  }

  return $page;
}

/**
 * Builds form for location editing
 * @see drupal_get_form
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_edit_rule_form($form, &$form_state) {
  $form = [];
  $rule = $form_state['build_info']['args'][0];

  $locations = ch_get_locations();
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
      '#title' => t('Location'),
      '#options' => $location_select,
      '#default_value' => $rule['location_id'],
      '#description' => t('The location this rule applies to.'),
      '#required' => TRUE,
  );

  $form['alias'] = array(
    '#type' => 'textfield',
    '#title' => t('Rule Alias'),
    '#default_value' => $rule['alias'],
    '#required' => FALSE,
  );

  $form['start_date'] = array(
    // '#type' => 'textfield',
    '#type' => 'date_popup',
    '#date_format' => 'm/d/Y',
    '#attributes' => array(
        'class' => array(
          'datepicker',
        ),
    '#title' => t('Start Date'),
    '#default_value' => $rule['start_date'],
    '#required' => TRUE,
  ));

  $form['end_date'] = array(
    // '#type' => 'textfield',
    '#type' => 'date_popup',
    '#date_format' => 'm/d/Y',
    '#attributes' => array(
        'class' => array(
          'datepicker',
        ),
    '#title' => t('End Date'),
    '#default_value' => $rule['end_date'],
    '#required' => TRUE,
  ));

  $form['priority'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => 0,
      '#delta' => 5,
      '#description' => t('Rules with lower weight value have precedence.'),
      '#default_value' => $rule['priority'],
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

  $form['#attached']['js'] = array(drupal_get_path('module', 'cascading_hours') ."/js/cascading_hours_admin.js");

  $form['actions']['submit']['#submit'][] = "cascading_hours_edit_rule_form_submit";

  return $form;
}

/**
 * Submission logic for location editing
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_edit_rule_form_submit($form, &$form_state) {
  $rule_id = $form_state['values']['rule_id'];
  $location_id = $form_state['values']['location_id'];
  $priority = $form_state['values']['priority'];
  $start_date = strtotime($form_state['values']['start_date']);
  $end_date = strtotime($form_state['values']['end_date']);
  $alias = $form_state['values']['alias'];
  if(!$alias) $alias = NULL;
  ch_update_rule($rule_id, $location_id, $priority, $start_date, $end_date, $alias);

  $form_state['redirect'] = "admin/structure/cascading_hours/location/" . $location_id;
}
