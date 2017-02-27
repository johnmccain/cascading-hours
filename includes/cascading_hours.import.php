<?php

/**
 * @file cascading_hours.import.php
 * functions necessary for importing schedule data from a .csv
 */

/**
 * @param string $str - The string for which remove any encapsulating quotes
 * @return string - The string with any encapsulating quotes removed
 */
function cascading_hours_remove_encapsulating_quotes($str) {
    if(!empty($str) && $str{0} == '"' && $str{strlen($str) - 1} == '"') {
        return substr($str, 1, -1);
    }
    return $str;
}

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
 * @param array $form
 * @param array &$form_state
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
        unset($form_state['storage']['confirm']);
        $location_id = $_SESSION['cascading_hours_import_location_id'];
        $schedule = $_SESSION['cascading_hours_import_data'];
        if(cascading_hours_import_schedule($schedule, $location_id)) {
            //successfully imported schedule, display success message
            drupal_set_message('Success! <br/>' . json_encode($_SESSION['cascading_hours_import_data']));
        }
        watchdog('cascading_hours', "Imported schedule for location with id $location_id");
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
        array_map('cascading_hours_remove_encapsulating_quotes', $row);
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

    $numeric_iterator = 0;

    $diff = [];
    foreach ($schedule as $key => $sched) {

        $curr = [];
        if(isset($curr_schedule[$numeric_iterator])) {
            foreach($curr_schedule[$numeric_iterator] as $val) {
                $block = [];
                $block['start_time'] = Date('h:i a', strtotime($val['start']));
                $block['end_time'] = Date('h:i a', strtotime($val['end']));
                $curr[] = $block;
            }
        }
        if(json_encode($sched) != json_encode($curr)) {
            $a = [];
            $b = [];
            if($curr == []) {
                $a[] = 'closed';
            }
            foreach($curr as $val) {
                $a[] = $val['start_time'] . '-' . $val['end_time'];
            }
            if($sched == []) {
                $b[] = 'closed';
            }
            foreach($sched as $val) {
                $b[] = $val['start_time'] . '-' . $val['end_time'];
            }
            $diff[] = '<b>' . $key . ': Change </b><i>' . join(', ', $a) . '</i><b> to </b><i>' . join(', ', $b) . '</i>';
        }
        $numeric_iterator++;
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

    $old_rules = cascading_hours_get_rules_contained_within_range_with_location_id($location_id, $import_start, $import_end);
    foreach($old_rules as $rule) {
        cascading_hours_delete_rule_with_id($rule['id']);
    }

    //adjust start/end dates of rules that overlap but are not contained within the import region
    $new_end = new DateTime();
    $new_end->setTimestamp($import_start);
    $new_end->modify('-1 day');
    $new_start = new DateTime();
    $new_start->setTimestamp($import_end);
    $new_start->modify('+1 days');

    db_update('cascading_hours_rules')
    ->fields(
        array(
        'end_date' => $new_end->format('Y-m-d H:i:s'),
        )
    )
    ->condition('location_id', $location_id, '=')
    ->condition(
        db_and()
        ->condition('end_date', date('Y-m-d H:i:s', $import_start), '>')
        ->condition('end_date', date('Y-m-d H:i:s', $import_end), '<=')
        )
    ->execute();

    db_update('cascading_hours_rules')
    ->fields(
        array(
        'start_date' => $new_start->format('Y-m-d H:i:s'),
        )
    )
    ->condition('location_id', $location_id, '=')
    ->condition(
        db_and()
        ->condition('start_date', date('Y-m-d H:i:s', $import_start), '>=')
        ->condition('start_date', date('Y-m-d H:i:s', $import_end), '<')
        )
    ->execute();

    //find rules overlapping the import range--split them
    $rules = db_select('cascading_hours_rules', 'c')
    ->fields('c')
    ->condition('location_id', $location_id, '=')
    ->condition(
        db_and()
        ->condition('start_date', date('Y-m-d H:i:s', $import_start), '<')
        ->condition('end_date', date('Y-m-d H:i:s', $import_end), '>')
        )
        ->execute();
    $rules = cascading_hours_query_to_array($rules);
    foreach($rules as $rule) {
        //create a new rule with same end, priority, location_id, and schedules as original but starts after import range
        $new_id = cascading_hours_create_rule($rule['location_id'], $rule['priority'], $new_start->getTimestamp(), strtotime($rule['end_date']), isset($rule['alias']) ? $rule['alias'] . ' split' : NULL);
        $old_sched = cascading_hours_get_schedules_with_rule_id($rule['id']);
        foreach($old_sched as $sched) {
            cascading_hours_create_schedule($new_id, $sched['day'], strtotime($sched['start_time']), strtotime($sched['end_time']));
        }
        //move end date of original rule to before the import range
        db_update('cascading_hours_rules')
        ->fields(
            array(
            'end_date' => $new_end->format('Y-m-d H:i:s'),
            )
        )
        ->condition('id', $rule['id'], '=')
        ->execute();
    }

    //find contiguous rule blocks
    //$rule_ranges holds an array of delimeter blocks with start & end timestamp pairs (end exclusive)
    $rule_ranges = [];
    //$delim holds the last beginning of a rule range
    $delim = new DateTime();
    $delim->setTimestamp($import_start);
    $date_iterator = new DateTime();
    for($date_iterator->setTimestamp($import_start); isset($schedule[$date_iterator->format('Y-m-d')]); $date_iterator->modify('+1 day')) {
        $sched = $schedule[$date_iterator->format('Y-m-d')];
        $date_iterator->modify('-7 days'); //last week
        $prev_day = $date_iterator->getTimestamp();
        $prev_sched = isset($schedule[$date_iterator->format('Y-m-d')]) ? $schedule[$date_iterator->format('Y-m-d')] : NULL;
        $date_iterator->modify('+7 days'); //bring back to present

        if($prev_day > $delim->getTimestamp() && $prev_sched && json_encode($sched) != json_encode($prev_sched)) {
            //time for a new rule
            $range['start'] = $delim->getTimestamp();
            $range['end'] = $date_iterator->getTimestamp();
            $delim->setTimestamp($date_iterator->getTimestamp());
            $rule_ranges[] = $range;
        }
    }

    $rule_ranges[] = array('start' => $delim->getTimestamp(),
                           'end' => $date_iterator->modify('-1 day')->getTimestamp());
    $num = 1;
    foreach($rule_ranges as &$rule) {
        $rule['id'] = cascading_hours_create_rule($location_id, 5, $rule['start'], $rule['end'], date('Y-m-d') . ': import #' . $num++);
        $created = []; //keep track of weekdays schedules have already been created for to avoid duplicates
        for($date_iterator->setTimestamp($rule['start']); $date_iterator->getTimestamp() < $rule['end']; $date_iterator->modify('+1 day')) {
            if(isset($created[(int)$date_iterator->format('w')])) {
                break; //weekday schedules already made
            } else {
                $created[(int)$date_iterator->format('w')] = true;
                $schedule_blocks = $schedule[$date_iterator->format('Y-m-d')];
                foreach($schedule_blocks as $key => $block) {
                    cascading_hours_create_schedule($rule['id'], (int)$date_iterator->format('w'), strtotime($block['start_time']), strtotime($block['end_time']));
                }
            }
        }
    }
    return true;
}
