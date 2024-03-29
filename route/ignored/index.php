<?php
# validate user session
$User->validate_session ();
?>

<div class='header'>
	<h3><?php print _("Ignored issuers"); ?></h3>
</div>

<div class="container-fluid main">
<?php

# fetch issuers
if($user->admin=="1")
$issuers = $Database->getObjectsQuery("select * from ignored_issuers");
else
$issuers = $Database->getObjectsQuery("select * from ignored_issuers where t_id = ?", [$user->t_id]);
# tenants
$tenants = $Tenants->get_all ();

# groups
$issuer_group = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$issuer_group[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($issuers)>0) {
	foreach ($issuers as $z) {
		$issuer_group[$z->t_id][] = $z;
	}
}

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '<a href="/route/ignored/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><i class="fa fa-plus"></i> '._("New ignored issuer").'</a>';
print '</div><br><br>';

# text
print "<p>"._('List of all ignored issuers - new certificates detected by this issuers will not be reported').". "._("Certificates issued by this issuers will still be available in system").".</p>";

# none
print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


// header
print "<thead>";
print "<tr>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-field='id' class='d-none d-lg-table-cell'>"._("Subject Key ID")."</th>";
print "	<th data-field='delete' class='text-center' style='width:20px;'><i class='fa fa-remove' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete agent")."'></i></th>";
print "</tr>";
print "</thead>";

print "<tbody>";

if (sizeof($issuer_group)==0) {
	print "<tr>";
	print "	<td colspan=3><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>". _("No issuers available").".</span></td>";
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
	foreach ($issuer_group as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=3><i class='fa fa-users text-muted'></i> "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr>";
			print "	<td colspan=3><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>"._("No ignored issuers configured").".</span></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $a) {
				print "<tr>";
				print "	<td><i class='fa fa-database $icon_color' style='color:#ccc;padding:0px 5px;'></i> <strong>".$a->name."</strong></td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".$a->ski."</td>";
				print "	<td class='text-center' style='width:20px;'><span class='badge bg-danger'><a href='/route/ignored/edit.php?id=".$a->id."&action=delete&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'><i class='fa fa-trash'></i></a></span></td>";
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