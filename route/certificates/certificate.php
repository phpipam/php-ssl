<?php

# validate tenant
$User->validate_tenant ();

// fetch item
if(!isset($is_from_fetch)) {
	// validate zone
	$zone_check = $Zones->get_zone ($_params['tenant'], $_params['app']);
	// fetch cert
	if(!is_null($zone_check)) {
		$certificate = $Certificates->get_certificate_from_zone ($_params['id1'], $_params['tenant'], $zone_check->id);
	}
}

// invalid zone
if(is_null($zone_check) && !isset($is_from_fetch)) {
	print '<div class="header">';
	print '	<h3>'._("Zone not found").'</h3>';
	print '</div>';
	print '<div class="container-fluid main">';
	print '	<a href="/'.$_params['tenant'].'/certificates/" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i>'. _("All certificates").'</a>';
	print '</div>';
	print '<div class="container-fluid main">';
	print '	<div class="alert alert-danger">'._("Zone not found").'</div>';
	print '</div>';
}
// null ?
elseif(is_null($certificate)) {
	print '<div class="header">';
	print '	<h3>'._("Certificate not found").'</h3>';
	print '</div>';
	print '<div class="container-fluid main">';
	print '	<a href="/'.$_params['tenant'].'/certificates/" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i>'. _("All certificates").'</a>';
	print '</div>';
	print '<div class="container-fluid main">';
	print '	<div class="alert alert-danger">'._("Certificate not found").'</div>';
	print '</div>';

}
else {
	// decode and print cert
	$certificate_details = $Certificates->parse_cert ($certificate->certificate);

	// CN
	if(is_array($certificate_details['subject']['CN'])) {
		$certificate_details['subject']['CN_all'] = implode("<br>", $certificate_details['subject']['CN']);
		$certificate_details['subject']['CN']     = $certificate_details['subject']['CN'][0];
	}
	else {
		$certificate_details['subject']['CN_all'] = $certificate_details['subject']['CN'];
	}
?>

<div class='header'>
	<h3><?php print _("Certificate details"); ?> :: <?php print $certificate_details['subject']['CN']; ?></h3>
</div>

<!-- back -->
<div class="container-fluid main">
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> <?php print _("Back"); ?></a>
</div>

<div class="container-fluid main">
<?php
	print '<div class="table-responsive-sm">';
	print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='margin-bottom:50px;' >";

	// get public key details
	$key = openssl_pkey_get_public($certificate->certificate);
	$key_details = openssl_pkey_get_details($key);

	// get hash
	$cert = openssl_x509_read($certificate->certificate);

	// status
	$status = $Certificates->get_status ($certificate_details, true, false);

	// valid_period
	$valid_period = $certificate_details['custom_validAllDays']>398 ? "<br><span class='text-danger'><i class='fa fa-exclamation-triangle'></i> "._("Certificate validity is more than 398 days")."</span>" : "";

	// text class
	if($status['code']==0)		{ $textclass='muted'; }
	elseif($status['code']==1)	{ $textclass='danger'; }
	elseif($status['code']==2)	{ $textclass='warning'; }
	elseif($status['code']==3)	{ $textclass='success'; }
	else 						{ $textclass=''; }

	// no altnames
	if(!isset($certificate_details['extensions']['subjectAltName'])) {
		$certificate_details['extensions']['subjectAltName'] = "/";
	}

	// Subject name
	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:20px;'>"._("Issued to")."</h4><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Common name")."</th>";
	print "	<td>".$certificate_details['subject']['CN_all']."</td>";
	print "</tr>";
	print "	<th>"._("Valid for domains")."</th>";
	print "	<td>".str_replace(",","<br>",$certificate_details['extensions']['subjectAltName'])."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Status")."</th>";
	print "	<td>".$status['text']."$valid_period</td>";
	print "</tr>";
	print "<tr>";
	print "	<th></th>";
	print "	<td><hr>";
	print "<a href='/route/zones/edit/download-certificate.php?certificate=".base64_encode($certificate->certificate)."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge bg-light text-dark bg-success' style='width:auto;'><i class='fa fa-certificate'></i> "._("Download")."</a>";

	if(!isset($is_from_fetch))
	print "<br><a href='/route/zones/edit/delete_certificate.php?tenant=".$_params['tenant']."&serial=".$certificate_details['serialNumber']."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge bg-light text-dark bg-danger' style='width:auto;'><i class='fa fa-trash'></i> "._("Remove certificate");
	print "</td>";
	print "</tr>";

	// found domains
	if(!isset($is_from_fetch)) {

		// get all hosts
		$hosts = $Certificates->get_certificate_hosts ($certificate->id);

		print "<tr>";
		print "	<td colspan='2'><h4 style='margin-top:50px;'>"._("Assigned to hosts")."</h4><hr></td>";
		print "</tr>";
		if(sizeof($hosts)==0) {
			print "<tr>";
			print "	<th></th>";
			print "	<td>"._("None")."</td>";
			print "</tr>";
		}
		else {
			// group by zone
			$hosts_grouped = [];
			foreach ($hosts as $h) {
				$hosts_grouped[$h->name][] = $h;
			}
			foreach($hosts_grouped as $group=>$host) {
				print "<tr>";
				print "	<th>"._("Zone")." <a href='/".$_params['tenant']."/zones/".$h->name."/'>".$host[0]->name."</a></th>";
				print "	<td>";
				foreach ($host as $h) {
					// check valoidity of certificate
					$h_cert_status = $Certificates->get_status ($certificate_details, true, true, $h->hostname);

					$h->ip = $User->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
					$h->ip = strlen($h->ip)>0 ? "[".$h->ip."]" : "";

					print "<a href='/".$_params['tenant']."/zones/".$h->name."/".$h->hostname."/'>".$h->hostname."</a> <span class='text-muted'>".$h->ip."</span> ".$h_cert_status['text']."<br>";
				}
				print "</td>";
				print "</tr>";
			}
		}
	}

	// Certificate details
	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Certificate details")."</h4><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Serial number")."</th>";
	print "	<td>".chunk_split($certificate_details['serialNumberHex'], 2, ' ')."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Key size")."</th>";
	print "	<td>".$key_details['bits']." kB</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Version")."</th>";
	print "	<td>".$certificate_details['version']."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Signature algorithm")."</th>";
	print "	<td>".$certificate_details['signatureTypeSN']."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Valid from")."</th>";
	print "	<td>".date("Y-m-d H:i:s", $certificate_details['validFrom_time_t'])."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Valid Until")."</th>";
	print "	<td class='text-$textclass'>".date("Y-m-d H:i:s", $certificate_details['validTo_time_t'])." (".$certificate_details['custom_validDays']." "._("days remaining").")</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Lifetime")."</th>";
	print "	<td>".$certificate_details['custom_validAllDays']." "._("days")." $valid_period</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Purposes")."</th>";
	print "	<td>";
	foreach($certificate_details['custom_purposes'] as $p=>$val) {
		$icon = $val == "Yes" ? "fa-check text-success" : "fa-times text-danger";
		print "<span class='badge'><i class='fa $icon' style='width:15px;'></i></span> ".$p."<br>";
	}
	print "</td>";
	print "</tr>";

	// Fingerptints
	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Fingerprints")."</h4><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("SHA-512")."</th>";
	print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA512'), 2, ' ')."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("SHA-256")."</th>";
	print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA256'), 2, ' ')."</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("SHA-1")."</th>";
	print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA1'), 2, ' ')."</td>";
	print "</tr>";

	// Issuer
	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Issuer")."</h4><hr></td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Common name")."</th>";
	print "	<td>".$certificate_details['issuer']['CN']."</td>";
	print "</tr>";
	if(strlen($certificate_details['issuer']['O'])>0) {
	print "<tr>";
	print "	<th>"._("Organisation name")."</th>";
	print "	<td>".$certificate_details['issuer']['O']."</td>";
	print "</tr>";
	}
	if(strlen($certificate_details['issuer']['C'])>0) {
	print "<tr>";
	print "	<th>"._("Country")."</th>";
	print "	<td>".$certificate_details['issuer']['C']."</td>";
	print "</tr>";
	}
	if(strlen($certificate_details['issuer']['ST'])) {
	print "<tr>";
	print "	<th>"._("County")."</th>";
	print "	<td>".$certificate_details['issuer']['ST']."</td>";
	print "</tr>";
	}
	if(strlen($certificate_details['issuer']['L'])) {
	print "<tr>";
	print "	<th>"._("Locality")."</th>";
	print "	<td>".$certificate_details['issuer']['L']."</td>";
	print "</tr>";
	}

	// Extensions
	unset($certificate_details['extensions']['ct_precert_scts']);
	unset($certificate_details['extensions']['subjectAltName']);

	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Extensions")."</h4><hr></td>";
	print "</tr>";
	foreach($certificate_details['extensions'] as $ext_key=>$e) {
		print "<tr>";
		print "	<th>".ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $ext_key))."</th>";
		print "	<td>".str_replace(",","<br>",$e)."</td>";
		print "</tr>";
	}

	// chain
	$delimiter = "-----BEGIN CERTIFICATE-----\n";
	$chains = array_reverse(array_values(array_filter(explode($delimiter, $certificate->chain))));


	// chain
	$cert_chain = $SSL->process_certificate_chain ($certificate->chain);

	print "<tr>";
	print "	<td colspan='2'><h4 style='margin-top:30px;'>"._("Certificate chain")."</h4><hr></td>";
	print "</tr>";

	// print chain
	$int = 1;
	$valid_cert = true;
	$valid_cert_text = [];


	// chain print
	foreach ($cert_chain as $index=>$cert) {

		// title
		if($index==sizeof($cert_chain)-1) 	{ $title = "Server"; }
		elseif($index==0) 					{ $title = "Root"; }
		else 								{ $title = _("Intermediate")." #".$int; $int++; }

		// errors ?
		if(sizeof($cert['errors'])>0) {
			$valid_cert = false;

			$validto_class 				  = isset($cert['errors']['validto']) ? "text-danger" : "";
			$authorityKeyIdentifier_class = isset($cert['errors']['authorityKeyIdentifier']) ? "text-danger" : "";
			$basicConstraints_class 	  = isset($cert['errors']['basicConstraints']) ? "text-danger" : "";
		}
		else {
			$validto_class 				  = "text-muted";
			$authorityKeyIdentifier_class = "text-muted";
			$basicConstraints_class 	  = "text-muted";
		}

		// get hash
		$cert['raw'] = "-----BEGIN CERTIFICATE-----\n".$cert['raw'];
		$cert_x509 = openssl_x509_read($cert['raw']);

		print "<tr>";
		print "	<th style='padding-top: 15px;'>"._($title)."</th>";
		print "	<td style='padding-left: 10px;padding-top: 15px;'>";
		print "<strong><a href=''>".$cert['certificate']['subject']['CN']."</a></strong><br>";
		print _("Issued by").": ".$cert['certificate']['issuer']['CN']."<br>";
		print "<span class='text-muted $validto_class'>"._("Expires on").": ".date("Y-m-d H:i:s", $cert['certificate']['validTo_time_t'])."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("SHA-256 Fingerprint").": ".chunk_split(openssl_x509_fingerprint($cert_x509, 'SHA256'), 2, ' ')."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("Subject Key Identifier").": ".$cert['certificate']['extensions']['subjectKeyIdentifier']."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted $authorityKeyIdentifier_class'>"._("Authority Key Identifier").": ".str_replace("keyid:", "", $cert['certificate']['extensions']['authorityKeyIdentifier'])."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted $basicConstraints_class'>"._("basicConstraints").": ".$cert['certificate']['extensions']['basicConstraints']."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("keyUsage").": ".$cert['certificate']['extensions']['keyUsage']."</span><br>";
		print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'><a href='/route/zones/edit/download-certificate.php?certificate=".base64_encode($cert['raw'])."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge bg-light text-dark bg-success' style='width:auto;'><i class='fa fa-certificate'></i> "._("Download")."</a></span><br>";
		if(sizeof($cert['errors'])>0) {
			print "<span class='text-danger'><ul style='margin-bottom:0px;list-style-type: none;padding-left:0px;margin-top:10px;'>";
			foreach ($cert['errors'] as $e) {
				print "<li style='font-size:11px;'>$e</li>";
			}
			print "</ul>";
		}
		print "</td>";
		print "</tr>";
	}

	// fail ?
	print "<tr>";
	print "	<th></th>";
	print "	<td style='padding-left: 10px'><hr>";
	if($valid_cert===true) {
		print "<span class='text-success'><i class='fa fa-check'></i> "._("Certificate chain is Valid")."</span>";
	}
	else {
		print "<span class='text-danger'><i class='fa fa-times'></i> ". _("Certificate chain is Invalid").".</span>";
	}
	print "</td>";
	print "</tr>";

	// server

	print "</table>";
	print '</div>';
}
?>
</div>