<?php

/**
 *
 * Load appropriate content
 *
 */

# validate user session
$User->validate_session ();

# valid flag
$valid = false;

# validate route
foreach ($url_items as $type=>$nav) {
	if (array_key_exists($_params['route'], $url_items[$type])) {
		$valid = true;
		continue;
	}
}
if(!$valid) {
	print "<div class='header'><h3>"._("Error")."</h3></div>";
	print '<div class="container-fluid main">';
	$Common->save_error("Invalid route");
	$Common->result_die ();
}

# include route
if(file_exists(dirname(__FILE__)."/".$_params['route']."/index.php")) {
	include ($_params['route']."/index.php");
}
else {
	print "<div class='header'><h3>"._("Error")."</h3></div>";
	print '<div class="container-fluid main">';
	$Common->save_error("Invalid route");
	$Common->result_die ();
}