<?php
# validate user session
$User->validate_session ();
?>

<div class='header'>
	<h3><?php print _("Zones"); ?></h3>
</div>

<div class="container-fluid main">
<?php

# fetch zones
$zones = $Zones->get_all();
# tenants
$tenants = $Tenants->get_all ();

# groups
$zone_groups = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$zone_groups[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($zones)>0) {
	foreach ($zones as $z) {
		$zone_groups[$z->t_id][] = $z;
	}
}

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '<a href="/route/zones/edit/edit-zone.php?action=add&tenant='.$_params['tenant'].'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><i class="fa fa-plus"></i> '._("Create zone").'</a>';
print '</div><br><br>';

# text
print "<p>"._('List of all available zones').".</p>";

# none
print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


// header
print "<thead>";
print "<tr>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-field='type' style='width:50px;' data-width='50' data-width-unit='px'>"._("Type")."</th>";
print "	<th data-field='desc' class='d-none d-lg-table-cell'>"._("Description")."</th>";
print "	<th data-field='agent' class='d-none d-lg-table-cell'>"._("Agent")."</th>";
print "	<th data-field='check' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last check")."</th>";
print "	<th data-field='hosts' class='text-center' data-width='40' data-width-unit='px' style='width:40px;'><i class='fa fa-database' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Hosts")."'></i></th>";
print "	<th data-field='certs' class='text-center' data-width='40' data-width-unit='px' style='width:40px;'><i class='fa fa-certificate' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Certificates")."'></i></th>";
print "	<th data-field='expire_soon' class='text-center' data-width='40' data-width-unit='px' style='width:40px;'><i class='fa fa-certificate text-warning' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Expire soon")."'></i></th>";
print "	<th data-field='expired' class='text-center' data-width='40' data-width-unit='px' style='width:40px;'><i class='fa fa-certificate text-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Expired")."'></i></th>";
print "</tr>";
print "</thead>";

print "<tbody>";

if (sizeof($zone_groups)==0) {
	print "<tr>";
	print "	<td colspan=9><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>". _("No zones available").".</span></td>";
	print "</tr>";
}
else {
	// body
	foreach ($zone_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
		print "<tr class='header'>";
		print "	<td colspan=9><i class='fa fa-users text-muted'></i> "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
		print "</tr>";
		}

		if(sizeof($group)==0) {
		print "<tr>";
		print "	<td colspan=9><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>"._("No zones available").".</span></td>";
		print "</tr>";
		}
		else {
		foreach ($group as $t) {

			$status            = $t->ignore == 0 ? "" : "<span class='badge bg-danger'>"._("Not checked")."</span>";
			$hosts             = $Database->count_database_objects("hosts", "z_id", $t->id);
			$certs             = $Zones->count_zone_certs ($t->id);
			$last_check        = $Zones->get_last_check ($t->id);
			// cert count
			$expired_certs_cnt = $Certificates->count_expired_by_zone ($t->id, 0);
			$expire_soon       = $Certificates->count_expired_by_zone ($t->id, $user->days);
			$expire_soon 	   = $expire_soon-$expired_certs_cnt;
			// ikona levo
			$icon_color        = $expired_certs_cnt == 0 ? "text-success" : "text-danger";
			$icon_color        = $hosts==0 ? "text-muted" : $icon_color;
			$icon_color 	   = $expired_certs_cnt == 0 && $expire_soon!=0 ? "text-warning" : $icon_color;
			// klase za badge
			$warning_class     = $expire_soon==0 ? "" : "text-warning";
			$danger_class      = $expired_certs_cnt==0 ? "" : "text-danger";

			// aicon
			$aicon 			   = $t->atype=="local" ? "" : "<span class='badge' style='border: 1px solid #ccc;color:#ccc'>R</span>";

			print "<tr>";
			print "	<td><i class='fa fa-database $icon_color' style='color:#ccc;padding:0px 5px;'></i> <strong><a href='/".$t->href."/zones/".$t->name."/'>".$t->name." $status</td>";
			print "	<td><span class='badge bg-light text-dark $t->type'>".$t->type."</span></td>";
			print "	<td class='text-muted d-none d-lg-table-cell'>".$t->description."</td>";
			print "	<td class='text-muted d-none d-lg-table-cell'>".$t->agname." $aicon</td>";
			print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$last_check."</td>";
			print "	<td class='text-center'><span class='badge bg-light text-dark'>".$hosts."</span></td>";
			print "	<td class='text-center'><span class='badge bg-light text-dark'>".$certs."</span></td>";
			print "	<td class='text-center'><span class='badge bg-light text-dark $warning_class'>".$expire_soon."</span></td>";
			print "	<td class='text-center'><span class='badge bg-light text-dark $danger_class'>".$expired_certs_cnt."</span></td>";
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