<?php

#
# Refresh agent
#

# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

# fetch agent
$agent = $Database->getObject ("agents",$_GET['id']);

#
# title
#
$title = _("Refresh")." "._("agent");

# tenant validation
if($user->admin !== "1" && $user->t_id!=$_GET['tenant']) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Admin user required"), false, false, true);
	# btn
	$btn_text = "";
}
# validate agent
elseif (is_null($agent)) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid agent"), false, false, true);
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// check agent status
	$Agent = new Agent ();
	// test
	$Agent->test_agents ($Database, "google.com", 443, date("Y-m-d H:i:s"), $agent->id);

	// print
	$content[] = $Result->show("info", _("Agent updated"), false, false, true);
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), "", false, true);
