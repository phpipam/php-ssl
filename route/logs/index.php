<?php


// load specific log details
if(isset($_params['app'])) {
	if (is_numeric($_params['app'])) {
		include ('log/index.php');
	}
	else {
		$Common->save_error("Invalid log id");
		include (dirname(__FILE__)."/../error/500.php");
		die();
	}
}
// table
else {
	include ('all.php');
}