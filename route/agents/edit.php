<?php

#
# Edit agent
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
if($_GET['action']!=="add")
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

# fetch agent
if($_GET['action']!=="add")
$agent = $Database->getObject ("agents",$_GET['id']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("agent");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid action"), false, false, true);
	# btn
	$btn_text = "";
}
# rtenant validation
elseif($user->admin !== "1" && $user->t_id!=$_GET['tenant']) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Admin user required"), false, false, true);
	# btn
	$btn_text = "";
}
# validate agent
elseif ($_GET['action']!=="add" && is_null($agent)) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid agent"), false, false, true);
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle table-zone-management'>";
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr><td colspan='2'><h4>"._("Agent details")."</h3></td></tr>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Agent name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".@$agent->name."' $disabled>";
	$content[] = "		<input type='hidden' name='action' value='".$_GET['action']."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='id' value='".$_GET['id']."'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// tenant - admin
	if($user->admin === "1" && $_GET['action']=="add") {
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Tenant")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='t_id' class='form-select form-select-sm' style='width:auto'>";
	foreach($Tenants->get_all () as $id=>$t) {
	$content[] =  "<option value='$t->id'>".$t->name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	}
	// URL
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("URL")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='url' value='".@$agent->url."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// description
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Description")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='comment' value='".@$agent->comment."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("agent");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/agents/edit-submit.php");
