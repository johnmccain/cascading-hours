<?
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=ISO-8859-1');
include 'cascading_hours.module';

if($_GET['insert']) {
	$location_id = ch_create_locatio('KJHK');
	$rule_id = ch_create_rule($location_id, 0, strtotime('last Monday'), strtotime('next Thursday'));
	ch_create_schedule($rule_id, 0, strtotime('12:00 am'), strtotime('5:00 pm'));
	ch_create_schedule($rule_id, 1, strtotime('9:00 am'), strtotime('5:00 pm'));
	ch_create_schedule($rule_id, 2, strtotime('9:00 am'), strtotime('5:00 pm'));
	ch_create_schedule($rule_id, 3, strtotime('8:00 am'), strtotime('6:00 pm'));
	ch_create_schedule($rule_id, 4, strtotime('12:00 am'), strtotime('5:00 pm'));
	ch_create_schedule($rule_id, 5, strtotime('12:00 am'), strtotime('5:00 pm'));
	ch_create_schedule($rule_id, 6, strtotime('12:00 am'), strtotime('5:00 pm'));
} else {
	$location = $_GET['location'];
	$start_date = $_GET['start_date'];
	$end_date = $_GET['end_date'];

	$schedule = ch_get_schedule_in_range_for_location_with_name($location, $start_date, $end_date);
	$obj = ch_generate_schedule($schedule, $start_date);
	echo json_encode($obj);

}
