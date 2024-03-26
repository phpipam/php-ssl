<?php
# validate user session - requires admin
$User->validate_session (true);
?>

<div class='header'>
	<h3><?php print _("Tenants"); ?></h3>
</div>

<div class="container-fluid main">

<?php

# fetch tenants
$tenants = $Tenants->get_all();

# add
print '<div class="btn-group" role="group">';
print '<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '<a href="/route/tenants/edit.php?action=add" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm btn-outline-success"><i class="fa fa-plus"></i> '._("Create tenant").'</a>';
print '</div><br><br>';

# text
print "<p>"._('List of all available tenants in the system').".</p>";

# nont
if (sizeof($tenants)==0) {
	$Result->show("info", _("No tenants available").".");
}
else {
	print "<div class='table-responsive'>";
	print "<table class='table table-hover align-top table-sm' style='margin-bottom:0px;'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th>"._("Name")."</th>";
	print "	<th>"._("Status")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th class='text-center' style='width:20px;'><i class='fa fa-server' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Agents")."'></i></th>";
	print "	<th class='text-center' style='width:20px;'><i class='fa fa-database' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Zones")."'></i></th>";
	print "	<th class='text-center' style='width:20px;'><i class='fa fa-user' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Users")."'></i></th>";
	print "	<th class='text-center' style='width:20px;border-left:1px solid rgba(200,200,200,0.3);'><i class='fa fa-pencil' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Edit tenant")."'></i></th>";
	print "	<th class='text-center' style='width:20px;'><i class='fa fa-remove' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete tenant")."'></i></th>";
	print "</tr>";
	print "</thead>";

	// body
	print "<tbody>";
	foreach ($tenants as $t) {


	$status = $t->active == 1 ? "<span class='badge bg-success'>Active</span>" : "<span class='badge bg-danger'>Disabled</span>";
	$zones  = $Database->count_database_objects("zones", "t_id", $t->id);
	$users  = $Database->count_database_objects("users", "t_id", $t->id);
	$agents = $Database->count_database_objects("agents", "t_id", $t->id);

	print "<tr>";
	print "	<td><i class='fa fa-user-o' style='color:#ccc;padding:0px 5px;'></i><strong><a href='/route/tenants/edit.php?id=".$t->id."&action=edit' data-bs-toggle='modal' data-bs-target='#modal1'>".$t->name."</td>";
	print "	<td>".$status."</td>";
	print "	<td class='text-muted'>".$t->description."</td>";
	print "	<td class='text-center'><span class='badge bg-light text-dark'>".$agents."</span></td>";
	print "	<td class='text-center'><span class='badge bg-light text-dark'>".$zones."</span></td>";
	print "	<td class='text-center'><span class='badge bg-light text-dark'>".$users."</span></td>";
	print "	<td class='text-center' style='width:20px;border-left:1px solid rgba(200,200,200,0.3);'><span class='badge bg-success'><a href='/route/tenants/edit.php?id=".$t->id."&action=edit' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(34,155,115) !important;'><i class='fa fa-pencil'></i></a></span></td>";
	print "	<td class='text-center' style='width:20px;'><span class='badge bg-danger'><a href='/route/tenants/edit.php?id=".$t->id."&action=delete' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'><i class='fa fa-trash'></i></a></span></td>";
	print "</tr>";
	}

	print "</table>";
	print "</div>";
}
?>

</div>