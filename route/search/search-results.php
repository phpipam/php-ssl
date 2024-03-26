<div class="container-fluid main" style='margin-top: 50px;'>
<hr>

	<?php
	// set from_search
	$from_search = true;

	// all certificates
	$all_certs = $Certificates->get_all ();


	// validate request !

	// none
	if(@$_POST['hosts']!=="on" && @$_POST['certificates']!=="on") {
		print '<h4>'._("Invalid search parameters").':</h4>';

		print "<div class='alert alert-warning'>"._("Nothing to search")."</div>";
		print '<br><br>';
	}
	// hosts
	elseif($_POST['hosts']=="on") {
		// title
		print '<h4>'._("Host search results").':</h4>';
		// search hosts
		$zone_hosts = $Zones->search_zone_hosts ($_POST['search']);

		// include table
		include(dirname(__FILE__)."/../zones/zone/zone-hosts.php");
	}
	// certificates
	if ($_POST['certificates']=="on") {
		$certificates = [];

		foreach ($all_certs as $c) {
			// parse
			$cert_parsed = $Certificates->parse_cert ($c->certificate);

			// search cname
			if(is_array($cert_parsed['subject']['CN'])) {
				foreach($cert_parsed['subject']['CN'] as $i) {
					if(strpos($i, $_POST['search'])!==false)   					  		{ $certificates[] = $c;	continue; }
				}
			}
			else {
				if(strpos($cert_parsed['subject']['CN'], $_POST['search'])!==false)   { $certificates[] = $c;	continue; }
			}

			// search serial
			if(strpos($cert_parsed['serialNumber'], $_POST['search'])!==false) 	  { $certificates[] = $c;	continue; }
			// search hex
			if(strpos($cert_parsed['serialNumberHex'], $_POST['search'])!==false) { $certificates[] = $c;	continue; }
			// search altnames
			if(strpos($cert_parsed['extensions']['subjectAltName'], $_POST['search'])!==false)  { $certificates[] = $c;	continue; }
		}

		// include table
		include(dirname(__FILE__)."/../certificates/all.php");
	}

	?>

</div>