<div class='header'>
	<h3><?php print _("Users"); ?></h3>
</div>

<!-- back -->
<div class="container-fluid main">
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> <?php print _("Back"); ?></a>
</div>
<br>


<div class="container-fluid main">
<?php

# fetch users
$users = $User->get_all ();
# tenants
$tenants = $Tenants->get_all ();
// regrouped certs
$cert_tenant_groups = [];

// create groups for admins
if($user->admin=="1") {
	foreach($tenants as $t) {
		$cert_tenant_groups[$t->id] = [];
	}
}

// regroup
if(sizeof($users)>0) {
	foreach ($users as $z) {
		$cert_tenant_groups[$z->t_id][] = $z;
	}
}


// set text for no certs
$no_user_text = "No users available";

print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='true' data-cookie-id-table='certs' data-pagination='true' data-page-size='250' data-page-list='[50,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


$hide_hosts = $_params['app']=="hosts" ? "" : "visually-hidden";

// header
print "<thead>";
print "<tr>";
print "	<th data-field='icon' data-width='20' data-width-unit='px'></th>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-field='email'>"._("Email")."</th>";
print "	<th data-field='permission'>"._("Permission")."</th>";
print "	<th data-field='warning'>"._("Warning")."</th>";
print "	<th data-field='expire'>"._("Expire")."</th>";
print "</tr>";
print "</thead>";

print "<tbody>";
// body
if(sizeof($tenants)==0) {
	print "<tr>";
	print "	<td colspan=6><i class='fa fa-certificate' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>"._($no_user_text).".</span></td>";
	print "</tr>";
}
else {
foreach ($cert_tenant_groups as $tenant_id=>$group) {

	if($user->admin=="1") {
	print "<tr class='header'>";
	print "	<td colspan=6><i class='fa fa-users text-muted'></i> "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
	print "</tr>";
	}

	if(sizeof($group)==0) {
		print "<tr>";
		print "	<td colspan=6><i class='fa fa-certificate text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>"._($no_user_text).".</span></td>";
		print "</tr>";
	}
	else {
		foreach ($group as $t) {
			print "<tr>";
			print "<td><i class='fa fa-user d-none d-sm-table-cell' style='color:#ccc;padding:0px 5px;'></i></td>";
			print "	<td class='align-top'>".$t->name."</td>";
			print "	<td class='align-top'>".$t->email."</td>";
			print "	<td class='align-top'>"._($User->get_permissions_nice($t->permission))."</td>";
			print "	<td class='align-top'>".$t->days." "._("days")."</td>";
			print "	<td class='align-top'>".$t->days_expired." "._("days")."</td>";

			print "</tr>";
		}
	}
}
}
print "</tbody>";
print "</table>";
print "</div>";
?>
</div>