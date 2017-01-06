<?
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=ISO-8859-1');
// drupal root and include
define('DRUPAL_ROOT', '/var/www/public/drupal');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';


// Load Drupal
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

drupal_load('module', 'cascading_hours');


$location = htmlspecialchars($_GET['location']);
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$schedule = ch_get_schedule_in_range_for_location_with_name($location, $start_date, $end_date);
if($schedule['error']) {
	echo json_encode($schedule);
} else {
	$obj = ch_generate_schedule($schedule, $start_date);
	echo json_encode($obj);
}
