<?php

# validate user session
$User->validate_session();
# validate tenant
$User->validate_tenant();

# fetch zone
$zone     = $Zones->get_zone($_params['tenant'], $_params['app']);
$hostname = $_params['id1'];

# invalid zone
if ($zone === null) {
	print '<div class="page-header">';
	print '	<h2 class="page-title">'._("Invalid zone").'</h2><hr>';
	print '</div>';
	$Result->show("danger", _("Zone does not exist."), false);
}
else {

# fetch host with current certificate
$host = $Database->getObjectQuery("
	SELECT h.*, z.name as zone_name, z.t_id as t_id, t.href as tenant_href, t.name as tenant_name,
	       t.recipients as tenant_recipients, c.certificate, c.serial as cert_serial
	FROM hosts h
	LEFT JOIN zones z ON h.z_id = z.id
	LEFT JOIN tenants t ON z.t_id = t.id
	LEFT JOIN certificates c ON h.c_id = c.id
	WHERE h.hostname = ? AND z.id = ?
", [$hostname, $zone->id]);

# invalid host or access denied
if ($host === null) {
	print '<div class="page-header">';
	print '	<h2 class="page-title">'._("Invalid host").'</h2><hr>';
	print '</div>';
	$Result->show("danger", _("Host does not exist in this zone."), false);
}
elseif ($user->admin != "1" && $host->t_id != $user->t_id) {
	print '<div class="page-header"><h2 class="page-title">'._("Access denied").'</h2><hr></div>';
	$Result->show("danger", _("Access denied."), false);
}
else {

# ---------- current certificate ----------
$cert_parsed = $Certificates->parse_cert($host->certificate);
$status      = $Certificates->get_status($cert_parsed, true, true, $hostname);

if     ($status['code'] == 0)  { $textclass = 'secondary'; }
elseif ($status['code'] == 1)  { $textclass = 'red'; }
elseif ($status['code'] == 2)  { $textclass = 'orange'; }
elseif ($status['code'] == 3)  { $textclass = 'green'; }
else                           { $textclass = 'secondary'; }

$days_valid = isset($cert_parsed['custom_validDays']) ? $cert_parsed['custom_validDays'] : "/";
if (is_numeric($days_valid)) {
	if     ($days_valid < 0)              { $days_class = "red"; }
	elseif ($days_valid <= $expired_days) { $days_class = "orange"; }
	else                                  { $days_class = "green"; }
} else {
	$days_class = "secondary";
}

$san_list = [];
if (isset($cert_parsed['extensions']['subjectAltName'])) {
	foreach (explode(",", $cert_parsed['extensions']['subjectAltName']) as $san) {
		$san = trim($san);
		if (strlen($san) > 0) { $san_list[] = $san; }
	}
}

# ---------- previous certificate ----------
$cert_old_parsed     = null;
$cert_old_status     = null;
$cert_old_textclass  = 'secondary';
$cert_old_days_valid = "/";
$cert_old_days_class = "secondary";
$cert_old_san_list   = [];

if (!empty($host->c_id_old)) {
	$cert_old = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$host->c_id_old]);
	if ($cert_old) {
		$cert_old_parsed = $Certificates->parse_cert($cert_old->certificate);
		$cert_old_status = $Certificates->get_status($cert_old_parsed, true, false, "");

		if     ($cert_old_status['code'] == 0)  { $cert_old_textclass = 'secondary'; }
		elseif ($cert_old_status['code'] == 1)  { $cert_old_textclass = 'red'; }
		elseif ($cert_old_status['code'] == 2)  { $cert_old_textclass = 'orange'; }
		elseif ($cert_old_status['code'] == 3)  { $cert_old_textclass = 'green'; }

		$cert_old_days_valid = isset($cert_old_parsed['custom_validDays']) ? $cert_old_parsed['custom_validDays'] : "/";
		if (is_numeric($cert_old_days_valid)) {
			if     ($cert_old_days_valid < 0)              { $cert_old_days_class = "red"; }
			elseif ($cert_old_days_valid <= $expired_days) { $cert_old_days_class = "orange"; }
			else                                           { $cert_old_days_class = "green"; }
		}

		if (isset($cert_old_parsed['extensions']['subjectAltName'])) {
			foreach (explode(",", $cert_old_parsed['extensions']['subjectAltName']) as $san) {
				$san = trim($san);
				if (strlen($san) > 0) { $cert_old_san_list[] = $san; }
			}
		}
	}
}

# ---------- misc ----------
$last_check_formatted  = $host->last_check  === NULL ? "/" : date("Y-m-d H:i", strtotime($host->last_check));
$last_change_formatted = $host->last_change === NULL ? "/" : date("Y-m-d H:i", strtotime($host->last_change));
$all_port_groups       = $SSL->get_all_port_groups();
$port_group_name       = isset($all_port_groups[$host->t_id][$host->pg_id]['name']) ? $all_port_groups[$host->t_id][$host->pg_id]['name'] : "/";
$recipients            = array_filter(explode(";", $host->h_recipients));
$tenant_recipients     = array_filter(array_map('trim', explode(";", str_replace(",", ";", $host->tenant_recipients))));

$icon_host = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 12m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>';
?>

<div class='page-header'>
	<h2 class='page-title'><?php print $url_items["zones"]['icon']." "._("Host details"); ?> [<?php print htmlspecialchars($hostname); ?>]</h2>
	<hr>
</div>

<div>
	<a href="/<?php print $_params['tenant']; ?>/zones/<?php print htmlspecialchars($zone->name); ?>/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div><br><br>

<div class='row'>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include("host-details.php"); ?>
	</div>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include("host-certificate.php"); ?>
	</div>
</div>

<?php include("host-recipients.php"); ?>

<?php include("host-logs.php"); ?>

<?php
} // end else (host valid)
} // end else (zone valid)
?>
