<?php

# title
if($expired_certs) {
	$days           = 0;
	$days_expired   = $user->days_expired;
	$title          = "Expired certificates [in last $days_expired days]";
	$link           = "expired";
	$not_found_text = "No expired certificates found";
}
else {
	$days           = $user->days;
	$days_expired   = 0;
	$title          = "Certificates that expire soon"." [in next $days days]";
	$link           = "expire_soon";
	$not_found_text = "No certificates found that will expire soon";
}

?>

<div class=' col-xs-12 col-lg-12'>
<div class='bubble'>
	<div class='bubble-header'><i class='fa fa-certificate'></i> <a href='/<?php print $user->href; ?>/certificates/<?php print $link; ?>/'><?php print _($title); ?></a></div>
	<div class='bubble-content'>
		<?php
		$certificates = $Certificates->get_expired ($days, $days_expired);

		// none
		if (sizeof($certificates)==0) {
			print "<div class='spanned'>";
			print _($not_found_text);
			print "</div>";
		}
		else {
			# tenants
			$tenants = $Tenants->get_all ();

			print "<table class='table align-top table-sm'>";

			print "<thead>";
			print "<tr>";
			if($user->admin=="1")
			print "	<th data-field='tenant'>"._("Tenant")."</th>";
			print "	<th data-field='serial'>"._("Serial number")."</th>";
			print "	<th data-field='status' style='width:20px;'>"._("Status")."</th>";
			print "	<th data-field='issuer'>"._("Issuer")."</th>";
			print "	<th data-field='domain'>"._("Common name")."</th>";
			print "	<th data-field='zone'>"._("Zone")."</th>";
			print "	<th data-field='valid'  class='align-top d-none d-xl-table-cell' data-width='150' data-width-unit='px'>"._("Valid to")."</th>";
			print "</tr>";
			print "</thead>";

			print "<tbody>";
			foreach ($certificates as $t) {
				// parse cert
				$cert_parsed = $Certificates->parse_cert ($t->certificate);

				// status
				$status = $Certificates->get_status ($cert_parsed);

				// text class
				$danger_class = "";
				if($status['code']==2)	{ $textclass='Expire soon'; $danger_class = "warning";  }
				else					{ $textclass='Expired';  	$danger_class = "danger"; }

				print "<tr>";
				if($user->admin=="1")
				print "	<td><a href='/".$user->href."/tenants/".$tenants[$t->t_id]->href."/'>".$tenants[$t->t_id]->name."</a></td>";
				print "<td class='align-top'>";
				print "	<a href='/".$t->href."/certificates/".$t->zone_name."/".$cert_parsed['serialNumber']."/'>".$cert_parsed['serialNumberHex']."</a>";
				print "</td>";
				print "	<td class='align-top'><span class='badge badge-status bg-light bg-$danger_class'>"._($textclass)."</span></td>";
				print "	<td class='align-top text-muted'>".$cert_parsed['issuer']['O']."</span></td>";
				print "	<td class='align-top'>".$cert_parsed['subject']['CN']."</td>";
				print "	<td class='align-top'><a href='/".$t->href."/zones/".$t->zone_name."/'>".$t->zone_name."</td>";
				print "	<td class='text-muted align-top d-none d-xl-table-cell'>".$cert_parsed['custom_validTo']." (".$cert_parsed['custom_validDays']." "._("days").")</td>";
				print "</tr>";
			}

			print "</tbody>";
			print "</table>";
			}
		?>
	</div>
</div>
</div>