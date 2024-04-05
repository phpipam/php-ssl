<?php
# validate user session
$User->validate_session ();
?>

<div class='header'>
	<h3><?php print _("Scan agents"); ?></h3>
</div>

<div class="container-fluid main">
<?php

# fetch agents
if($user->admin=="1")
$agents = $Database->getObjectsQuery("select * from agents where atype = 'remote'");
else
$agents = $Database->getObjectsQuery("select * from agents where atype = 'remote' and t_id = ?", [$user->t_id]);
# tenants
$tenants = $Tenants->get_all ();

# groups
$agent_groups = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$agent_groups[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($agents)>0) {
	foreach ($agents as $z) {
		$agent_groups[$z->t_id][] = $z;
	}
}

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '<a href="/route/agents/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><i class="fa fa-plus"></i> '._("Create agent").'</a>';
print '</div><br><br>';

# text
print "<p>"._('List of all agents').".</p>";

# errors
require(dirname(__FILE__)."/../dashboard/card-agent-errors.php");

# none
print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


// header
print "<thead>";
print "<tr>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-field='status' style='width:50px;' data-width='50' data-width-unit='px'>"._("Status")."</th>";
print "	<th data-field='desc' class='d-none d-lg-table-cell'>"._("URL")."</th>";
print "	<th data-field='zones' class='text-center' style='width:20px;''><i class='fa fa-database' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Zones")."'></i></th>";
print "	<th data-field='hosts' class='text-center' style='width:20px;'><i class='fa fa-server' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Hosts")."'></i></th>";
print "	<th data-field='check' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last check")."</th>";
print "	<th data-field='checks' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last success")."</th>";
print "	<th data-field='edit' class='text-center' style='width:20px'><i class='fa fa-pencil' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Edit agent")."'></i></th>";
print "	<th data-field='refresh' class='text-center' style='width:20px'><i class='fa fa-refresh' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Retest agent")."'></i></th>";
print "	<th data-field='delete' class='text-center' style='width:20px;'><i class='fa fa-remove' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete agent")."'></i></th>";
print "</tr>";
print "</thead>";

print "<tbody>";

if (sizeof($agent_groups)==0) {
	print "<tr>";
	print "	<td colspan=10><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>". _("No agents available").".</span></td>";
	print "</tr>";
}
else {

	// init agent
	$Agent = new Agent ();
	// get conf
	$config = $Config->get_config($user->t_id);
	// get errors
	$agent_errors = $Agent->get_agent_connection_errors($Database, $config['agentTimeout'], $user->admin, $user->t_id);

	// body
	foreach ($agent_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=10><i class='fa fa-users text-muted'></i> "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr>";
			print "	<td colspan=10><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>"._("No agents available").".</span></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $a) {

				// cnt
				$count_zones = $Database->getObjectQuery("select count(*) as cnt from zones where agent_id = ?", [$a->id]);
				$count_hosts = $Database->getObjectQuery("select count(*) as cnt from zones as z, hosts as h where h.z_id = z.id and z.`agent_id` = ?", [$a->id]);

				// error if status not ok
				$status = array_key_exists($a->id, $agent_errors) ? "<span class='badge bg-danger text-dark'>"._("Error")."</span>" : "<span class='badge bg-success text-dark'>"._("OK")."</span>";
				// never checked
				$status = is_null($a->last_check) ? "<span class='badge bg-warning text-dark'>"._("Unknown")."</span>" : $status;
				// never success
				$status = is_null($a->last_success) ? "<span class='badge bg-warning text-dark'>"._("Unknown")."</span>" : $status;

				print "<tr>";
				print "	<td><i class='fa fa-database $icon_color' style='color:#ccc;padding:0px 5px;'></i> <strong>".$a->name."</strong></td>";
				print "	<td>$status</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".$a->url."</td>";
				print "	<td class='text-center' style='width:20px;border-left:1px solid rgba(200,200,200,0.3);'><span class='badge bg-light text-dark'>".$count_zones->cnt."</span></td>";
				print "	<td class='text-center' style='width:20px;border-right:1px solid rgba(200,200,200,0.3);'><span class='badge bg-light text-dark'>".$count_hosts->cnt."</span></td>";
				print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$a->last_check."</td>";
				print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$a->last_success."</td>";
				print "	<td class='text-center' style='width:20px;border-left:1px solid rgba(200,200,200,0.3);'><span class='badge bg-success'><a href='/route/agents/edit.php?id=".$a->id."&action=edit&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(34,155,115) !important;'><i class='fa fa-pencil'></i></a></span></td>";
				print "	<td class='text-center' style='width:20px'><span class='badge bg-light text-dark'><a href='/route/agents/refresh.php?id=".$a->id."&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(34,155,115) !important;'><i class='fa fa-refresh'></i></a></span></td>";
				print "	<td class='text-center' style='width:20px;'><span class='badge bg-danger'><a href='/route/agents/edit.php?id=".$a->id."&action=delete&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'><i class='fa fa-trash'></i></a></span></td>";
				print "</tr>";
			}
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>
</div>