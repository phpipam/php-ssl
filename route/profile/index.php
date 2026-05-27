<?php

// Profile and settings tabs for the currently logged-in user

if (@$_params['app'] === 'profile' || !isset($_params['app'])) {
	include(dirname(__FILE__)."/../user/profile/index.php");
}
elseif (in_array(@$_params['app'], ['passkeys', 'notifications', 'activity', '2fa'])) {
	$_profile_tab_map = ['passkeys' => 'pf-passkeys', 'notifications' => 'pf-notifications', 'activity' => 'pf-logs', '2fa' => 'pf-2fa'];
	$_profile_default_tab = $_profile_tab_map[$_params['app']];
	include(dirname(__FILE__)."/../user/profile/index.php");
}
else {
	$Common->save_error("Invalid profile item");
	require(dirname(__FILE__)."/../error/404.php");
	die();
}
