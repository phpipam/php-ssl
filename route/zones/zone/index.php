<?php

# validate user session
$User->validate_session ();
# validate tenant
$User->validate_tenant ();

# fetch zone
$zone = $Zones->get_zone ($_params['tenant'], $_params['app']);


# not existing ?
if ($zone==NULL) {
	print "<div class='header'>";
	print "	<h3>"._("Invalid zone")."</h3>";
	print "</div>";

	print "<div class='container-fluid main'>";

	print '<div class="btn-group" role="group">';
	print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
	print '</div>';
	print '<br><br>';

	$Result->show("danger", _("Zone does not exist."), false);
	print "</div>";
}
# ok
else {

	# hosts
	if(is_object($zone)) {
		// hosts
		$zone_hosts = $Zones->get_zone_hosts ($zone->id);
		// certificates
		$all_certs = $Certificates->get_all ();
	}
?>


<div class='header'>
	<h3><?php print _("Zone details"); ?>  [<?php print @$zone->name; ?>]</h3>
</div>


<div class="container-fluid main">
<?php

# add, back
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '</div>';
print '<br><br>';
?>
</div>

<!-- details -->
<div class="container-fluid main">
	<?php include("zone-details.php"); ?>
</div>

<!-- hosts -->
<div class="container-fluid main" style='margin-top:50px;'>
	<?php include("zone-hosts.php"); ?>
</div>
<?php } ?>