<?

\error_reporting(E_ALL);
ini_set("display_errors", 1);

// drupal root and include
define('DRUPAL_ROOT', '/var/www/public/drupal');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';


// Load Drupal
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

drupal_load('module', 'cascading_hours');

$location_id = ch_create_location("Test Location");
$testquery = db_select('cascading_hours_locations')
	  ->fields('id', 'name')
	  ->condition('id', $location_id, '=')
	  ->execute()
	  ->fetchAssoc();
echo json_encode($testquery);
