<?
//FIXME
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=ISO-8859-1');

// drupal root and include
define('DRUPAL_ROOT', '/var/www/public/drupal');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';


// Load Drupal
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

drupal_load('module', 'cascading_hours');

$start_date = (int)$_GET['start'];
$end_date = (int)$_GET['end'];

$schedule;
if(isset($_GET['location_id'])) {
	$location_id = (int)$_GET['location_id'];
	$schedule = cascading_hours_get_schedule_in_range_for_location_with_id($location_id, $start_date, $end_date);
} else if(isset($_GET['location_name'])) {
	$location_name = htmlspecialchars($_GET['location_name']);
	$schedule = cascading_hours_get_schedule_in_range_for_location_with_name($location_name, $start_date, $end_date);
} else {
	$arr = [];
	$arr['error'] = 'No location defined';
	echo json_encode($error);
	die();
}
if($schedule['error']) {
	echo json_encode($schedule);
} else {
	$obj = cascading_hours_generate_schedule($schedule, $start_date);
	echo json_encode($obj);
}
