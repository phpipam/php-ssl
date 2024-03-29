<?php

#
# Edit issuer - submit
#



# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch tentant and validate
if($_POST['action']=="add") {
	// get tenant
	$tenant = $Tenants->get_tenant_by_href ($_POST['t_id']);
	// invalid tenant
	if($tenant===null)
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
	// not allowed
	if($user->admin !== "1" && $user->t_id!=$_POST['t_id'])
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}
else {
	// get issuer
	$issuer = $Database->getObject ("ignored_issuers",$_POST['id']);
	// invalid issuer
	if($issuer===null)
	$Result->show("danger", _("Invalid issuer").".", true, false, false, false);
	// not allowed
	if($user->admin !== "1" && $user->t_id!=$issuer->t_id)
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}

# add, edit
if ($_POST['action']!="delete") {
	// name
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name value").".", true, false, false, false);
	// url
	if($Common->validate_alphanumeric($_POST['ski'])===false)
	$Result->show("danger", _("Invalid SKI value").".", true, false, false, false);
}

// general update parameters
$update = [
	"name"            => $_POST['name'],
	"ski"             => $_POST['ski'],
];

// add - add t_id
if($_POST['action']=="add") {
	$update['t_id'] = $tenant->id;
}

// edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $issuer->id;
}

# ok, validations passed, insert
try {
	// add
	if($_POST['action']=="add") {
		$Database->insertObject("ignored_issuers", $update);
		// ok
		$Result->show("success", _("Ignored issuer created").".", false, false, false, false);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("ignored_issuers", $update['id']);
		// ok
		$Result->show("success", _("Ignored issuer deleted").".", false, false, false, false);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}