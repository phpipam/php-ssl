<?php

/**
 *
 * Cronjobs to be executed
 *
 */

# autoload classes
require ("functions/autoload.php");

# script can only be run from cli
if(php_sapi_name()!="cli") {
	$Cron->errors[] = "This script can only be run from cli!";
	$Cron->result_die ();
}
# fetch all cronjobs
$Cron->fetch_cronjobs ();

# set date
$date = date("Y-m-d H:i:s");

# exceute them
$Cron->execute_cronjobs ($date, $argv);

# check agent status
$Agent = new Agent ();
$Agent->test_agents ($Database, "google.com", 443, $date);