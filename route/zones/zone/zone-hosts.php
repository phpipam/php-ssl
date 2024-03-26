<?php

# validate user session
$User->validate_session ();

$show_details = 1;

// header
if(!isset($from_search))
print '<h3>'._("Zone hosts").'</h3><hr>';

# none
if (!is_object($zone) && !isset($from_search)) {
	$Result->show("info", _("Invalid zone").".");
}
else {
	# all port groups
	$all_port_groups = $SSL->get_all_port_groups ();

	print '<div class="btn-group">';
	if(sizeof($zone_hosts)>1 && !isset($from_search)) {
	print '<a href="/" class="btn btn-sm btn-outline-secondary toggle-show-multiple"><i class="fa fa-pencil"></i> '._("Edit multiple").'</a>';
	}
	if(!isset($from_search))
	print '<a href="/route/zones/zone/import.php?z_id='.$zone->id.'" class="btn btn-sm btn-outline-success open_popup"><i class="fa fa-upload"></i> '._("Import").'</a>';
	print "</div>";

	// actions right
	if(sizeof($zone_hosts)>0 && !isset($from_search)) {
	print '<div class="btn-group float-end">';
	print '<a href="/route/zones/edit/edit-zone-truncate.php?zone_id='.$zone->id.'&tenant='.$_params['tenant'].'" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-trash" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Remove all hosts from zone").'"></i> '._("Remove all").'</a>';
	print '<a href="/route/zones/edit/edit-zone-cert-refresh-all.php?zone_id='.$zone->id.'&tenant='.$_params['tenant'].'" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-sync" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Rescan all hosts for new certificates").'"></i> '._("Rescan all").'</a>';
	print "</div>";
	}

	print "<div class='table-responsive'>";
	print "<table class='table table-hover align-top table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='true' data-cookie-id-table='zone' data-pagination='true' data-page-size='250' data-page-list='[50,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print " <th class='checkbox-hidden visually-hidden'><input type='checkbox' class='form-check-input select-all' name='select-all'></th>";
	print "	<th>"._("Hostname")."</th>";

	if(isset($from_search)) {
		if($user->admin=="1")
		print "	<th>"._("Zone")."/"._("Tenant")."</th>";
		else
		print "	<th>"._("Zone")."</th>";
	}

	if($show_details==0)
	print "	<th class='d-none d-lg-table-cell'>"._("Status")."</th>";
	print "	<th>"._("Certificate")."</th>";
	if($show_details==0)
	print "	<th class='d-none d-lg-table-cell'>"._("Domain")."</th>";
	if($show_details==0)
	print "	<th class='d-none d-xl-table-cell'>"._("Issuer")."</th>";
	if($show_details==1)
	print "	<th class='d-none d-xl-table-cell'>"._("Domain")." / "._("Issuer")."</th>";
	if($show_details==1)
	print "	<th class='d-none d-xl-table-cell'>"._("Checked / Changed")."</th>";
	if($show_details==0)
	print "	<th style='width:150px;'>"._("Valid To")."</th>";
	print "	<th style='width:50px;' data-width='50' data-width-unit='px' class='d-none d-xl-table-cell'>"._("Port")."</th>";
	print "	<th style='width:50px;' data-width='50' data-width-unit='px' class='d-none d-xl-table-cell'>"._("Port group")."</th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-volume-high' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notification status")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-check' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check status")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-refresh' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Refresh certificate")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-user' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Additional recipiens")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-xmark' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Remove host")."'></i></th>";

	print "</tr>";
	print "</thead>";

	// body
	print "<tbody>";
	if(sizeof($zone_hosts)==0) {
		print "<tr>";
		print " <td class='checkbox-hidden visually-hidden'></td>";
		$colspan = isset($from_search) ? 12 : 11;
		print "	<td colspan='$colspan'><div class='alert alert-info'>"._("No hosts").".</div></td>";
		print "</tr>";
	}
	else {
		foreach ($zone_hosts as $t) {
			// reset zone if search !
			if (isset($from_search)) {
				// fetch zone
				$zone = $Zones->get_zone ($t->href, $t->z_id);
			}

			// text on buttons
			$muted = $t->mute=="1" ? "<span class='badge bg-light text-dark bg-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notifications disabled")."'><i class='fa fa-volume-xmark'></i>" : "<span class='badge bg-light text-dark bg-success' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notifications enabled")."'><i class='fa fa-volume-high'></i>";
			$ignore = $t->ignore=="1" ? "<span class='badge bg-light text-dark bg-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check disabled")."'><i class='fa fa-xmark'></i>" : "<span class='badge bg-light text-dark bg-success' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check enabled")."'><i class='fa fa-check'></i>";
			$refresh = $t->ignore=="1" ? "<span class='badge bg-light text-dark bg-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check disabled")."'><i class='fa fa-xmark'></i>" : "<a href='/route/zones/edit/host_cert_refresh.php?tenant=".$zone->t_id."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge bg-light text-dark bg-success' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Refresh now")."'><i class='fa fa-refresh'></i></a>";

			// date
			$t->last_check  = $t->last_check===NULL||$t->ignore==1 ? "/"  : $t->last_check;
			$t->last_change = $t->last_change===NULL||$t->ignore==1 ? "/" : $t->last_change;

			// parse cert
			$cert_parsed = $Certificates->parse_cert ($all_certs[$t->c_id]->certificate);

			// get status
			$status = $Certificates->get_status ($cert_parsed, true, true, $t->hostname);

			// text class
			$danger_class = "";
			if($status['code']==0)		{ $textclass='muted'; }
			elseif($status['code']==1)	{ $textclass='danger';  $danger_class = "danger"; }
			elseif($status['code']==2)	{ $textclass='warning'; $danger_class = "warning";  }
			elseif($status['code']==3)	{ $textclass='success'; }
			else 						{ $textclass=''; }

			// port
			$t->port = strlen($t->port)>0 ? $t->port : "/";

			// dates
			if($t->last_check!="/")
			$t->last_check  = date("Y-m-d H:i", strtotime($t->last_check));
			$t->last_change = date("Y-m-d H:i", strtotime($t->last_change));


			// line
			print "<tr class='table-hosts table-$danger_class'>";
			// checkbox
			print " <td title='checkbox' class='checkbox-hidden visually-hidden'><input type='checkbox' class='form-check-input select-current' data-type='hosts' name='item-{$t->id}'></td>";
			// hostname
			print "	<td>";
			print "		<i class='fa fa-server text-$textclass' style='color:#ccc;padding:0px 5px;'></i> <strong>".$t->hostname;
			if($show_details==1 && $t->ip!=NULL)
			print "		<br><span class='visually-hi1dden text-muted' style='padding-left: 27px;font-weight:normal;font-size:11px;'>{$t->ip}</span>";
			print "	</td>";

			if(isset($from_search)) {
			print "<td class='d-none d-lg-table-cell'>";
			print "	<a href='/".$t->href."/zones/".$t->zone_name."/' target='_blank'>".$t->zone_name."</a><br>";
			if($user->admin=="1")
			print "<span class='text-muted'>".$t->name."</span>";
			print "</td>";
			}

			// status
			if($show_details==0)
			print "	<td class='d-none d-lg-table-cell'>".$status['text']."</td>";
			// serial
			if($cert_parsed['serialNumberHex']!="/") {
				print "<td>";
				print "	<a href='/".$t->href."/certificates/".$t->zone_name."/".$cert_parsed['serialNumber']."/' target='_blank'>".$cert_parsed['serialNumberHex']."</a>";
				if($show_details==1)
				print "<br>".$status['text']." <span class='text-muted' style='font-size:11px;'>".$cert_parsed['custom_validTo']."</span>";
				print "</td>";
			}
			else {
				if($show_details==0)
				print "	<td class='d-none d-lg-table-cell'>".$cert_parsed['serialNumberHex']."</td>";
				else
				print "	<td class='d-none d-lg-table-cell'>".$status['text']."</td>";
			}
			// domain
			if($show_details==0)
			print "	<td class='d-none d-lg-table-cell'>".$cert_parsed['subject']['CN']."</td>";
			// issuer
			if($show_details==0)
			print "	<td class='d-none d-xl-table-cell text-muted'>".$cert_parsed['issuer']['O']."</td>";
			// domain / issuer
			if($show_details==1) {
			print "	<td class='d-none d-lg-table-cell'>";
			print $cert_parsed['subject']['CN']."<br><span class='text-muted'>".$cert_parsed['issuer']['O']."</span>";
			print "</td>";
			}
			// last check
			if($show_details==1) {
				if($cert_parsed['serialNumberHex']!="/")
				print "	<td class='d-none d-xl-table-cell text-muted' style='font-size:11px;'>".$t->last_check."<br>".$t->last_change."</td>";
				else
				print "	<td class='d-none d-xl-table-cell text-muted' style='font-size:11px;'>".$t->last_check."<br>".$t->last_change."</td>";
			}

			// valid to
			if($show_details==0)
			print "	<td>".$cert_parsed['custom_validTo']."</td>";
			// found on port
			print "	<td class='d-none d-xl-table-cell'><span class='badge bg-ligh1t text-dark'>".$t->port."</span></td>";
			// portgroups for scan
			print "	<td class='d-none d-xl-table-cell'><span class='badge bg-light text-dark' data-bs-toggle='tooltip'data-bs-html='true' data-bs-placement='bottom' title='tcp/".implode("<br>tcp/", $all_port_groups[$t->t_id][$t->pg_id]['ports'])."'>".$all_port_groups[$t->t_id][$t->pg_id]['name']."</span></td>";

			// actions
			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/zones/edit/host_ignore_mute.php?type=mute&tenant=".$_params['tenant']."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'>".$muted."</a></span></td>";
			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/zones/edit/host_ignore_mute.php?type=ignore&tenant=".$_params['tenant']."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'>".$ignore."</a></span></td>";
			print "	<td class='actions d-none d-lg-table-cell text-center'>".$refresh."</span></td>";
			if(strlen($t->h_recipients)>5)
			print "	<td class='actions d-none d-lg-table-cell text-center'><span class='badge bg-light text-dark bg-success' data-bs-toggle='tooltip' data-bs-html='true' data-bs-placement='top' title='".str_replace(";","<br>", $t->h_recipients)."'><a href='/route/zones/edit/host_set_recipients.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><i class='fa fa-user'></i></a></span></td>";
			else
			print "	<td class='actions d-none d-lg-table-cell text-center'><span class='badge bg-light text-dark bg-success' data-bs-toggle='tooltip' data-bs-html='true' data-bs-placement='top' title='No extra recipients'><a href='/route/zones/edit/host_set_recipients.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><i class='fa fa-user text-muted'></i></a></span></td>";

			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/zones/edit/delete_hostname.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge bg-light text-dark bg-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Remove host")."'><i class='fa fa-trash'></i></td>";

			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}