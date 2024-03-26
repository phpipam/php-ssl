<?php

#
# Refresh cert
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (2, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# get tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

// title
$title = _("Update host SSL certificate");
// content
$content = [];

# try to fetch certificate
try {
	// set execution time
	$execution_time = date('Y-m-d H:i:s');
    // fetch hosts
	$host = $Database->getObjectQuery("select *,h.id as host_id,z.name as zone_name,a.name as agname,z.t_id as t_id from agents as a, `hosts` as h, `zones` as z where h.`z_id` = z.id and z.agent_id = a.id and z.`t_id` = ? and h.ignore = 0 and h.id = ?", [$tenant->id, $_GET['host_id']]);

	// fetch cert
	$host_certificate = $SSL->fetch_website_certificate ($host, $execution_time, $tenant->id);

	// update cert if fopund
	if ($host_certificate!==false) {
		$cert_id = $SSL->update_db_certificate ($host_certificate, $host->t_id, $host->z_id, $execution_time);
		// get IP if not set from remote agent
		$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
		// if Id of certificate changed
		if($host->c_id!=$cert_id) {
			$SSL->assign_host_certificate ($host->hostname, $ip, $host->host_id, $cert_id, $host_certificate['port'], $execution_time);
		}

		// parse cert and set text
		$cert_parsed = $Certificates->parse_cert ($host_certificate['certificate']);

		// status
		$status = $Certificates->get_status ($cert_parsed, true, true, $host->hostname);

		$cert_text = [];
		$cert_text[] = _("Issuer").": ".$cert_parsed['issuer']['O'];
		$cert_text[] = _("Status").": ".$status['text'];
		$cert_text[] = _("Subject").": ".$cert_parsed['subject']['CN'];
		$cert_text[] = _("Serial").": ".$cert_parsed['serialNumberHex'];
		$cert_text[] = _("Valid to").": ".$cert_parsed['custom_validTo']." (".$cert_parsed['custom_validDays']." days)";
		$cert_text[] = _("Scan agent").": ".$host->agname;
		$cert_text[] = "<a href='".$tenant->href."/certificates/".$host->zone_name."/".$cert_parsed['serialNumber']."/' target='_blank' class='btn btn-sm btn-outline-success'> "._("Show details")."</a>";

		// ok
		$content[] = $Result->show("success", _("Certificate fetched"), false, false, true, false);
		$content[] = "<hr>";
		$content[] = implode("<br>", $cert_text);
	}
	// error
	else {
		$content[] = $Result->show("danger alert-block", _("Failed to obtain certificate")." :: ".end($SSL->errors).".", false, false, true, false);
		// print if more errors are present
		if(sizeof($SSL->errors)>1) {
			$content[] = "<hr>";
			$content[] = "Errors:";
			$content[] = "<ul><li class='text-muted'>".implode("</li><li class='text-muted'>",$SSL->errors)."</ul>";
		}
	}
} catch (Exception $e) {
    // print error
	$content[] = $Result->show("danger", $e->getMessage(), false, false, true, false);
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), "", "", true);