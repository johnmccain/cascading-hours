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
 * @see cascading_hours_admin_import_form_submit()
 * @see cascading_hours_admin_import_form_validate()
 *
 */
function cascading_hours_admin_import_form($form_state)
{

    $location_id = isset($form_state['build_info']['args'][0]) ? $form_state['build_info']['args'][0] : NULL;

    $form['file'] = array(
        '#type' => 'file',
        '#title' => t('Schedule'),
        '#description' => t('Upload a schedule file, allowed extensions: csv'),
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
    );

    return $form;
}

/**
 * Validate handler for cascading_hours_admin_import_form().
 *
 */
function cascading_hours_admin_import_form_validate($form, &$form_state)
{
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
}

/**
 * Submission logic for cascading_hours_admin_import_form().
 *
 */
function cascading_hours_admin_import_form_submit($form, &$form_state)
{
    $file = $form_state['storage']['file'];
    // We are done with the file, remove it from storage.
    unset($form_state['storage']['file']);
    // Make the storage of the file permanent.
    // $file->status = FILE_STATUS_PERMANENT;
    // Save file status.
    file_save($file);
    // Set a response to the user.
    $path = drupal_realpath($file->uri);
    $contents = file_get_contents($path);
    file_delete($file);
    cascading_hours_import_parse($contents);
    // drupal_set_message(t('The form has been submitted and the image has been saved, filename: @filename.', array('@filename' => json_encode($file))));
}

/**
 * @param string $contents - contents of the csv of schedule data to be parsed
 */
function cascading_hours_import_parse($contents)
{
    //parse file contents from string to multidimensional array
    $errors = [];
    $contents = explode("\n", $contents);
    $schedule = [];
    $prev_day = false;
    foreach($contents as $key => $row) {
        $row = explode(",", $row);
        if(count($row) % 2 != 1) {
            //there should be an odd number of columns for each row (date + pairs of start/end times)
            $errors[] = t("On row $key: incorrect number of columns.  Each row should begin with a date and continue with pairs of start/end times.");
        } else {
            if(strtotime($row[0] == false)) {
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
                $block['start_time'] = date("H:i:s", strtotime($tmp[$i]));
                $block['end_time'] = date("H:i:s", strtotime($tmp[$i + 1]));
                $blocks[] = $block;
            }
            $schedule[date("Y-m-d", strtotime($row[0]))] = $blocks;
        }
    }
    if($errors != []) {
        drupal_set_message("Syntax Error(s): <ul><li>" . join("</li><li>", $errors) . "</li></ul>", 'error');
    } else {
        cascading_hours_import_diff($schedule);
    }
}

function cascading_hours_import_diff($schedule) {
    drupal_set_message(json_encode($schedule));
}
