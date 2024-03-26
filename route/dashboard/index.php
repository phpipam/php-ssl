<div class='header'>
	<h3><?php print _("Dashboard"); ?></h3>
</div>


<div class="container-fluid dashboard main">
<div class="row">

	<?php
	// agent-errors
	include("card-agent-errors.php");
	// links
	include("card-links.php");
	// statistics
	include("card-statistics.php");
	// expire soon certificates
	$expired_certs = false;
	include("card-certificates-expire.php");
	// expired certificates
	$expired_certs = true;
	include("card-certificates-expire.php");
	// checks
	include("card-checks.php");	?>

</div>
</div>