<?

/**
 * @param int $location_id - id of the location to import to
 */
function cascading_hours_admin_import($location_id)
{
    $location = cascading_hours_get_location_with_id($location_id);
    $page = '';
    if($location['name']) {
        $page .= "<h3>" . t("Import schedule for: ") . $location['name'] . "</h3>";
        $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
        $page .= "<hr/>";

        $form = drupal_get_form('cascading_hours_admin_import_form', $location['id']);
        $page .= render($form);

        $page .= "<hr/>";
        $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
    } else {
        $page .= "<h3>" . t("No Such Location") . "</h3>";
        $page .= "<hr/>";
        $page .= l(t("Go back"), "admin/structure/cascading_hours");
    }
    return $page;
}

/**
 * Builds form for uploading a csv to import as a schedule
 *
 * @param array $form
 * @param array &$form_state
 * @see cascading_hours_admin_import_form_submit()
 * @see cascading_hours_admin_import_form_validate()
 *
 */
function cascading_hours_admin_import_form($form, &$form_state)
{
    if(!isset($form_state['storage']['confirm'])) {
        //form is in upload stage, display upload form
        $location_id = $form_state['build_info']['args'][0];
        $form['file'] = array(
            '#type' => 'file',
            '#title' => t('Schedule'),
            '#description' => t('Upload a schedule file, allowed extensions: csv'),
        );

        $form['location_id'] = array(
            '#type' => 'value',
            '#value' => $location_id,
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
        );
    } else {
        //form is in confirmation form
        $form['diff'] = array(
            '#markup' => '<h4>Changes to be made:</h4>' . $_SESSION['cascading_hours_import_diff'],
        );
        $form['confirm_message'] = array(
            '#markup' => '<p>Are you sure you want to confirm these changes? <strong>Any rules in this range will be overwritten, and this action cannot be undone.</strong></p>',
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Confirm'),
        );
    }

    $form['actions']['submit']['#submit'][] = 'cascading_hours_admin_import_form_submit';

    return $form;
}

/**
 * Validate handler for cascading_hours_admin_import_form().
 *
 */
function cascading_hours_admin_import_form_validate($form, &$form_state)
{
    if(!isset($form_state['storage']['confirm'])) {
        //form is in upload stage, validate
        $file = file_save_upload('file', array(
            // Validate extension is a csv
            'file_validate_extensions' => array('csv'),
        ));
        // If the file passed validation:
        if ($file) {
            // Move the file into the Drupal file system.
            if ($file = file_move($file, 'public://')) {
                // Save the file for use in the submit handler.
                $form_state['storage']['file'] = $file;
            }
            else {
                form_set_error('file', t("Failed to write the uploaded file to the site's file folder."));
            }
        }
        else {
            form_set_error('file', t('No file was uploaded.'));
        }
    } else if(!isset($_SESSION['cascading_hours_import_data'])) {
        //missing session data
        drupal_set_message("Error: missing session data.", 'error');
        watchdog('cascading_hours', 'Error: missing session data on schedule import.', [], WATCHDOG_ERROR);
    }
}

/**
 * Submission logic for cascading_hours_admin_import_form().
 *
 */
function cascading_hours_admin_import_form_submit($form, &$form_state)
{
    if(!isset($form_state['storage']['confirm'])) {
        //form is in upload stage
        $file = $form_state['storage']['file'];
        $location_id = $form_state['values']['location_id'];
        // We are done with the file, remove it from storage.
        unset($form_state['storage']['file']);
        // Save file status.
        file_save($file);
        $path = drupal_realpath($file->uri);
        $contents = file_get_contents($path);
        //delete file now that we have the contents ready for parsing
        file_delete($file);

        $schedule = cascading_hours_import_parse($contents);
        if(!isset($schedule['errors'])) {
            $diff = cascading_hours_import_diff($schedule, $location_id);
            $_SESSION['cascading_hours_import_data'] = $schedule;
            $_SESSION['cascading_hours_import_diff'] = $diff;
            $_SESSION['cascading_hours_import_location_id'] = $location_id;
            $form_state['storage']['confirm'] = TRUE;
            //rebuild the form for confirmation
            $form_state['rebuild'] = TRUE;
        } else {
            //there was an error in the form
            drupal_set_message("Syntax Error(s): <ul><li>" . join("</li><li>", $schedule['errors']) . "</li></ul>", 'error');
        }
    } else {
        //form is in confirmation stage, apply changes
        watchdog('cascading_hours', 'got to import stage');
        drupal_set_message(json_encode($_SESSION['cascading_hours_import_data']));
        $location_id = $_SESSION['cascading_hours_import_location_id'];
        $schedule = $_SESSION['cascading_hours_import_data'];
        cascading_hours_import_schedule($schedule, $location_id);
    }
}

/**
 * Parses a schedule csv string into a format suitable for comparison and insertion.
 * Validates and checks for syntax errors. Syntactical errors lead to rejection and an error message being displayed.
 * Rejects the input if: a row has even number of columns, parsing of a date fails, parsing of a time fails, days are not contiguous and chronological
 * @param string $contents - contents of the csv of schedule data to be parsed
 * @return array - array of schedule block arrays keyed by date strings, or array with single field 'error' if parsing failed
 */
function cascading_hours_import_parse($contents)
{
    //parse file contents from string to multidimensional array
    $errors = [];
    $contents = explode("\n", $contents);
    $schedule = [];
    $prev_day = false;
    foreach($contents as $key => &$row) {
        $row = explode(",", $row);
        if(count($row) % 2 != 1) {
            //there should be an odd number of columns for each row (date + pairs of start/end times)
            $errors[] = t("On row $key: incorrect number of columns.  Each row should begin with a date and continue with pairs of start/end times.");
        } else {
            if(count($contents) - 1 == $key && $row[0] == "") {
                //end of file line, skip but no error
                continue;
            } else if(strtotime($row[0]) == false) {
                $errors[] = t("On row $key: unable to parse date.");
                continue;
            } else if($prev_day && strtotime($row[0]) - $prev_day > 90000) {
                //if more than 1 day has passed since last day then mark an error (90000s = 25hrs to account for days with daylight savings time)
                $errors[] = t("On row $key: day skipped. All days in import list should be sequential.");
            }
            $prev_day = strtotime($row[0]);
            $blocks = [];
            $tmp = array_slice($row, 1);
            for($i = 0; $i + 1 < count($tmp); $i += 2) {
                if($tmp[$i] == "") {
                    continue;
                }
                $block = [];
                if(strtotime($tmp[$i]) == false || strtotime($tmp[$i + 1] == false)) {
                    $errors[] = t("On row $key: unable to parse time(s).");
                    continue;
                }
                $block['start_time'] = date("h:i a", strtotime($tmp[$i]));
                $block['end_time'] = date("h:i a", strtotime($tmp[$i + 1]));
                $blocks[] = $block;
            }
            $schedule[date("Y-m-d", strtotime($row[0]))] = $blocks;
        }
    }

    if($errors != []) {
        $schedule = array('errors' => $errors);
    }
    return $schedule;
}

/**
 * Calculates the difference between the current and imported schedules for user feedback purposes
 * @pre $schedule is a valid importable schedule
 * @param array $schedule - array of schedule block arrays keyed by date strings
 * @param int $location_id - id of the location to calculate the schedule changes for
 * @return string - a string of list markup noting all differences between the new and current schedules
 */
function cascading_hours_import_diff($schedule, $location_id) {

    end($schedule);
    $end = strtotime(key($schedule));
    reset($schedule);
    $start = strtotime(key($schedule));
    $curr_schedule = cascading_hours_get_schedule_in_range_for_location_with_id($location_id, $start, $end);

    $curr_schedule = cascading_hours_generate_schedule($curr_schedule, $start);

    $iterator = new MultipleIterator;
    $iterator->attachIterator(new ArrayIterator($schedule));
    $iterator->attachIterator(new ArrayIterator($curr_schedule));

    $diff = [];
    foreach ($iterator as $keys => $values) {
        $curr = [];
        foreach($values[1] as $val) {
            $block = [];
            $block['start_time'] = Date('h:i a', strtotime($val['start']));
            $block['end_time'] = Date('h:i a', strtotime($val['end']));
            $curr[] = $block;
        }
        if(json_encode($values[0]) != json_encode($curr)) {
            $a = [];
            $b = [];
            foreach($curr as $val) {
                $a[] = $val['start_time'] . '-' . $val['end_time'];
            }
            foreach($values[0] as $val) {
                $b[] = $val['start_time'] . '-' . $val['end_time'];
            }
            $diff[] = '<b>' . $keys[0] . ': Change </b><i>' . join(', ', $a) . '</i><b> to </b><i>' . join(', ', $b) . '</i>';
        }
    }
    $str = '<ul><li>' . join('</li><li>', $diff) . '</li></ul>';
    return $str;
}

/**
 * Imports a schedule to a certain location.  Deletes/modifies all rules so that the import rules are the only ones in the import range.
 * @param array $schedule - keyed array with date strings as keys, arrays of schedule blocks as values
 * @param int $location_id
 */
function cascading_hours_import_schedule($schedule, $location_id) {
    //delete all rules with start dates > import start and end dates < input end
    end($schedule);
    $import_end = strtotime(key($schedule));
    reset($schedule);
    $import_start = strtotime(key($schedule));

    $rules = cascading_hours_get_rules_contained_within_range_with_location_id($location_id, $import_start, $import_end);
    foreach($rules as $rule) {
        cascading_hours_delete_rule_with_id($rule['id']);
    }
}
