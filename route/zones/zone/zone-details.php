<?php

// validate user session
$User->validate_session ();

// set desciription
$zone->description = $zone->description=="" ? "/" : $zone->description;

// div
print "<div class='row'>";

// table
print "<div class='col-xs-12 col-md-6'>";
print "<table class='table table-borderless table-auto table-details table-condensed table-zone-management'>";

print "<tr>";
print "	<th style='min-width:180px;'>"._("Zone name")."</th>";
print "	<td><b>".$zone->name."</b></td>";
print "</tr>";

// tenants
if($user->admin=="1") {
$tenant = $Database->getObject("tenants", $zone->t_id);
print "<tr>";
print "	<th style='min-width:180px;'>"._("Tenant")."</th>";
print "	<td><b>".$tenant->name."</b></td>";
print "</tr>";
}

print "<tr>";
print "	<th>"._("Type")."</th>";
print "	<td><span class='badge bg-light text-dark' style='width:auto'>".$zone->type."</span></td>";
print "</tr>";

print "<tr>";
print "	<th>"._("Scan agent")."</th>";
print "	<td>".$zone->agname." (".$zone->url.")</td>";
print "</tr>";


$zone->recipients = str_replace(",", "<br>", $zone->recipients);
print "<tr>";
print "	<th>"._("Mail recipients")."</th>";
print "	<td>".$zone->recipients."</td>";
print "</tr>";

print "<tr>";
print "	<th>"._("Description")."</th>";
print "	<td><span class='text-muted'>".$zone->z_description."</span></td>";
print "</tr>";

print "<tr>";
print "<th></th>";
print "<td>";
print '<a href="/route/zones/edit/edit-zone.php?action=edit&tenant='.$_params['tenant'].'&zone_name='.$zone->name.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm btn-outline-success"><i class="fa fa-pencil"></i> '._("Edit zone").'</a>';
# validate permissions
if($User->get_user_permissions (3))
print '<a style="margin-left:5px" href="/route/zones/edit/edit-zone.php?action=delete&tenant='.$_params['tenant'].'&zone_name='.$zone->name.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i> '._("delete zone").'</a>';
print "</td>";
print "</tr>";


// axfr
if ($zone->type=="axfr") {
$zone->tsig = $zone->tsig=="" ? "<span class='text-muted'>/</span>" : $zone->tsig;
$zone->delete_records = $zone->delete_records==1 ? "Yes" : "No";

print "<tr>";
print "	<td colspan='2' class='hr'><hr></td>";
print "</tr>";
print "<tr>";
print "	<th>"._("Authoritative DNS")."</th>";
print "	<td>".$zone->dns."</td>";
print "</tr>";
print "	<th>"._("Zone name")."</th>";
print "	<td><strong>".$zone->aname."</strong></td>";
print "</tr>";
if($zone->tsig_name!="") {
print "<tr>";
print "	<th>"._("TSIG name")."</th>";
print "	<td>".$zone->tsig_name."</td>";
print "</tr>";
print "<tr>";
print "	<th>"._("TSIG")."</th>";
print "	<td>".$zone->tsig."</td>";
print "</tr>";
}
print "<tr>";
print "	<th>"._("Valid records")."</th>";
print "	<td>"._($zone->record_types)."</td>";
print "</tr>";
print "<tr>";
print "	<th>"._("Delete records")."</th>";
print "	<td>"._($zone->delete_records)."</td>";
print "</tr>";
// print "<tr>";
// print "	<th>"._("Include patterns")."</th>";
// print "	<td>"._($zone->regex_include)."</td>";
// print "</tr>";
// print "<tr>";
// print "	<th>"._("Exclude patterns")."</th>";
// print "	<td>"._($zone->regex_exclude)."</td>";
// print "</tr>";



print "<tr>";
print "	<td colspan='2' class='hr'><hr></td>";
print "</tr>";
print "<tr>";
print "	<th>"._("Sync zone")."</th>";
print "	<td><a href='/route/zones/edit/edit-zone-axfr-sync.php?&tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm btn-outline-success' data-bs-toggle='modal' data-bs-target='#modal1'><i class='fa fa-refresh'></i> "._("Sync now")."</a></td>";
print "</tr>";
}

else {

print "<tr>";
print "	<td colspan='2' class='hr'><hr></td>";
print "</tr>";
print "<tr>";
print "	<th>"._("Add host")."</th>";
print "	<td><a href='/route/zones/edit/add-hostnames.php?action=add&tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm btn-outline-success' data-bs-toggle='modal' data-bs-target='#modal1'><i class='fa fa-plus'></i> "._("Add host")."</a></td>";
print "</tr>";
}

print "</table>";
print "</div>";




// stats
$stats = ["hosts"=>0, "certificates"=>[], "unknown"=>0, "expired"=>0, "expire_soon"=>0, "mismatched"=>0];

// loop
foreach ($zone_hosts as $t) {
	// add stats
	$stats['hosts']++;

	// parse cert
	$cert_parsed = $Certificates->parse_cert ($all_certs[$t->c_id]->certificate);

	// get status
	$status  = $Certificates->get_status ($cert_parsed, false, true, $t->hostname);

	// unknown
	if($status['code']==0) { $stats['unknown']++; }
	// expired
	if($status['code']==1) { $stats['expired']++; }
	// expire soon
	if($status['code']==2) { $stats['expire_soon']++; }
	// mismatched
	if($status['code']==10) { $stats['mismatched']++; }
	// add stats cert
	if($t->c_id!="") $stats['certificates'][] = $t->c_id;
	// unique
	$stats['certificates'] = array_unique($stats['certificates']);
}


// expire class - red color
$expire_class      = $stats['expired']>0 ? "circle-expire" : "";
$expire_class_soon = $stats['expire_soon']>0 ? "circle-expire-soon" : "";


print "<div class='col-xs-12 col-md-6'>";
print "<div class='row text-center'>";
// hosts
print "<div class='col'>";
print "<div>";
print "<div class='circle'>".$stats['hosts']."</div>";
print "<div class='text-muted circle-text'>"._("Hosts")."</div>";
print "</div>";
print "</div>";

// certificates
print "<div class='col'>";
print "<div>";
print "<div class='circle'>".sizeof($stats['certificates'])."</div>";
print "<div class='text-muted circle-text'>"._("Certificates")."</div>";
print "</div>";
print "</div>";

// unknown
print "<div class='col'>";
print "<div>";
print "<div class='circle'>".$stats['unknown']."</div>";
print "<div class='text-muted circle-text'>"._("Not found")."</div>";
print "</div>";
print "</div>";

// Expire soon
if($stats['expire_soon']>0) {
print "<div class='col'>";
print "<div>";
print "<div class='circle $expire_class $expire_class_soon' data-dst-text='Expires soon'>".$stats['expire_soon']."</div>";
print "<div class='text-muted circle-text'>"._("Expire soon")."</div>";
print "</div>";
print "</div>";
}

// Expired
if($stats['expired']>0) {
print "<div class='col'>";
print "<div>";
print "<div class='circle $expire_class'  data-dst-text='Expired'>".$stats['expired']."</div>";
print "<div class='text-muted circle-text'>"._("Expired")."</div>";
print "</div>";
print "</div>";
}

// mismatched
if($stats['mismatched']>0) {
print "<div class='col'>";
print "<div>";
print "<div class='circle $expire_class'  data-dst-text='mismatch'>".$stats['mismatched']."</div>";
print "<div class='text-muted circle-text'>"._("Missmatched")."</div>";
print "</div>";
print "</div>";
}


print "</div>";

print "</div>";
print "</div>";