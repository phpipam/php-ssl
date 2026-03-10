<?php

/**
 *
 * Class, config etc autoloading
 *
 */

# config
include (dirname(__FILE__)."/../config.php");

# include classes
include ("classes/class.PDO.php");
include ("classes/class.Result.php");
include ("classes/class.Validate.php");
include ("classes/class.Common.php");
include ("classes/class.URL.php");
include ("classes/class.Config.php");
include ("classes/class.SSL.php");
include ("classes/class.Cron.php");
include ("classes/class.Tenants.php");
include ("classes/class.Zones.php");
include ("classes/class.Certificates.php");
include ("classes/class.User.php");
include ("classes/class.Modal.php");
include ("classes/class.Mail.php");
include ("classes/class.Thread.php");
include ("classes/class.AXFR.php");
include ("classes/class.Agent.php");
include ("classes/class.Log.php");
include ("classes/class.ADsync.php");

# load classes
try {
	$Result       = new Result ();
	$Common       = new Common ();
	$URL          = new URL ();
	$Database     = new Database_PDO ();
	$Modal 		  = new Modal ();
	$Config       = new Config ($Database);
	$User         = new User ($Database);
	$SSL          = new SSL ($Database);
	$Cron         = new Cron ($Database, $User->get_current_user());
	$Tenants      = new Tenants ($Database);
	$Zones        = new Zones ($Database, $User->get_current_user());
	$Certificates = new Certificates ($Database, $User->get_current_user());
	$Log 		  = new Log ($Database);

	# save user to local var
	$user = $User->get_current_user();

	# validate requested path
	$URL->validate_path ($user);

	# set params from GET and args
	$_params = $URL->get_params ();

	# menu
	include ("config.menu.php");
}
catch (Exception $e) {
	// SQL error ?
	if(strpos($e->getMessage(), "SQLSTATE")!==false) {
		$error_title = "Failed to connect to SQL database";
		$error_text = str_replace("Stack trace", "<br><br>Stack trace", $e->getMessage());
		$error_text = str_replace("\n", "<br>", $error_text);

		// do we need to install product ?
		if (@$installed!==true) {
			// html
			$title   = "php-ssl installation";
			$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];
			// load content - error
			$_params = ['tenant'=>'install'];
		}
		else {
			// load content - error
			$_params = ['tenant'=>'error', "route"=>"generic"];
		}
	}
	// generic
	else {
		$error_title = "Error";
		$error_text = str_replace("\n", "<br>", $e->getMessage());

		// load content - error
		$_params = ['tenant'=>'error', "route"=>"generic"];
	}
}