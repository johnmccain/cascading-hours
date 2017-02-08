<?php

/*** functions necessary for exporting schedule data as a .csv ***/

/**
 * @param int $location_id - id of the location to export from
 */
function cascading_hours_admin_export($location_id)
{
    $location = cascading_hours_get_location_with_id($location_id);
    $page = '';
    if($location['name']) {
        $page .= "<h3>" . t("export schedule for: ") . $location['name'] . "</h3>";
        $page .= l(t("Go back"), "admin/structure/cascading_hours/location/" . $location['id']);
        $page .= "<hr/>";

        $form = drupal_get_form('cascading_hours_admin_export_form', $location['id']);
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
 * Builds form for uploading a csv to export as a schedule
 *
 * @param array $form
 * @param array &$form_state
 * @see cascading_hours_admin_export_form_submit()
 * @see cascading_hours_admin_export_form_validate()
 *
 */
function cascading_hours_admin_export_form($form, &$form_state)
{
    $location_id = $form_state['build_info']['args'][0];
    watchdog('cascading_hours', 'exporting location ' . $location_id);

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
    $form['location_id'] = array(
        '#type' => 'value',
        '#value' => $location_id,
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
    );

    $form['actions']['submit']['#submit'][] = 'cascading_hours_admin_export_form_submit';

    return $form;
}

/**
 * Submission logic for cascading_hours_admin_export_form().
 *
 * @param array $form
 * @param array &$form_state
 */
function cascading_hours_admin_export_form_submit($form, &$form_state)
{
    $start_date = strtotime($form_state['values']['start_date']);
    $end_date = new DateTime();
    $end_date->setTimestamp(strtotime($form_state['values']['end_date']));
    $end_date->modify('+1 day');
    $end_date = $end_date->getTimestamp();
    $location_id = $form_state['values']['location_id'];
    $location = cascading_hours_get_location_with_id($location_id);
    watchdog('cascading_hours', date('Y-m-d', $start_date) . ', ' . date('Y-m-d', $end_date));

    $schedule = cascading_hours_get_schedule_in_range_for_location_with_id($location['id'], $start_date, $end_date);
    watchdog('cascading_hours', json_encode($schedule));
    $schedule = cascading_hours_generate_schedule($schedule, $start_date);

    watchdog('cascading_hours', json_encode($schedule));

    $arr = [];
    $date_iterator = new DateTime();
    for($date_iterator->setTimestamp($start_date); $date_iterator->getTimestamp() < $end_date; $date_iterator->modify('+1 day')) {
        $arr[$date_iterator->format('m/d/Y')] = false;
    }
    foreach($schedule as $blocks) {
        if(isset($blocks[0])) {
            $index = Date('m/d/Y', strtotime($blocks[0]['start']));
            $arr[$index] = [];
            foreach($blocks as $block) {
                $tmp = [];
                $tmp['start'] = Date('h:i a', strtotime($block['start']));
                $tmp['end'] = Date('h:i a', strtotime($block['end']));
                $arr[$index][] = $tmp;
            }
        }
    }
    $data = [];
    foreach($arr as $key => $row) {
        $line = [];
        $line[] = $key;
        if($row) {
            foreach($row as $block) {
                $line[] = $block['start'];
                $line[] = $block['end'];
            }
        }
        $data[] = join(',', $line);
    }
    $data = join("\n", $data) . "\n";

    watchdog('cascading_hours', $data);

    $filename = 'cascading_hours_export_' . date('Y-m-d_H_i_s');
    $filename .= '_' . $location['name'] . '_id' . $location['id'];
    //we subtract 1 from the day to compensate for adjustment made earlier to make date params inclusive
    $filename .= '_' . date('Y-m-d', $start_date) . '_to_' . date('Y-m-d', $end_date - (24 * 60 * 60)) . '.csv';

    $file = file_save_data($data, 'public://' . $filename);
    drupal_set_message('Schedule exported successfully. ' . l(t('Access the exported schedule here.'), file_create_url($file->uri)));
}
