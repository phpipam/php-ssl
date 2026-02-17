<hr>

<div class="main" style='margin-top: 30px;'>
	<?php
	// set from_search
	$from_search = true;

	// validate request !

	// none
	if(@$_params['hosts']!=="on" && @$_params['certificates']!=="on") {
		print "<div class='alert alert-warning'>";
		print '<strong>'._("Invalid search parameters").':</strong>'._("Nothing to search")."</div>";
		print "</div>";
	}
	// hosts
	if($_params['hosts']=="on") {
		// title
		print '<h2 class="h3">'._("Host search results").':</h2>';

		// include table
		print '<div class="card">';
		include(dirname(__FILE__)."/../zones/zone/zone-hosts.php");
		print '</div>';
	}
	// certificates
	if ($_params['certificates']=="on") {
		// title
		print '<h2 class="h3" style="margin-top:30px">'._("Certificate search results").':</h2>';

		// include table (AJAX will handle search)
		print "<div class='card'>";
		include(dirname(__FILE__)."/../certificates/all.php");
		print "</div>";
	}
	?>
</div>