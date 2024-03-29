<?php
# validate user session - requires admin
$User->validate_session ();
?>

<div class='header'>
	<h3><?php print _("Cron jobs"); ?></h3>
</div>

<div class="container-fluid main">

<?php

# add
print '<div class="btn-group" role="group">';
print '<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
print '<a href="/route/cron/edit.php" class="open_popup btn btn-sm btn-outline-success"><i class="fa fa-plus"></i> '._("Create job").'</a>';
print '</div><br><br>';

# text
print "<p>";
print _('List of scheduled cronjobs').". "._("Each tenant has separate scripts that will be executed only if scheduled here").". "._("");
print "<ul>";
foreach ($Cron->get_valid_scripts() as $j) {

	$script_nice = $Cron->name_script($j);

	print "<li>"._($script_nice[name])." :: <span class='text-muted'>"._($script_nice['desc']).".</span></li>";
}
print "</ul>";

print _("All scripts are called from main cron.php cronjob (and executed if scheduled here) that should be executed every 5 minutes").". "._("Crontab example").":<br>";
print "<pre class='text-muted' style='padding: 5px 10px; border-radius:6px; border:1px solid rgba(0,0,0,0.1);float:left'>";
print "# php-ssl cronjob
*/5 * * * * /usr/bin/php /usr/local/www/cron.php";
print "</pre>";

print "<div class='clearfix'></div>";

print "</p>";


# fetch jobs
$jobs = $Cron->fetch_tenant_cronjobs ();
# scripts
$scripts = $Cron->get_valid_scripts ();
# tenants
$tenants = $Tenants->get_all ();

# groups
$cron_groups = [];

// create groups for admins
if($user->admin=="1") {
	foreach($tenants as $t) {
		$cron_groups[$t->id] = $scripts;
	}
}
// regroup
if(sizeof($jobs)>0) {
	foreach ($jobs as $j) {
		$cron_groups[$j->t_id][$j->script] = $j;
	}
}

# none
print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


// header
print "<thead>";
print "<tr>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-width='100' data-width-unit='px' data-field='minute' class='text-center d-none d-lg-table-cell'>"._("Minute")."</th>";
print "	<th data-width='100' data-width-unit='px'  data-field='hour' class='text-center d-none d-lg-table-cell'>"._("Hour")."</th>";
print "	<th data-width='100' data-width-unit='px' data-field='day' class='text-center d-none d-lg-table-cell'>"._("Day")."</th>";
print "	<th data-width='100' data-width-unit='px' data-field='weekday' class='text-center d-none d-lg-table-cell'>"._("Weekday")."</th>";
print "	<th data-width='150' data-width-unit='px' data-field='check' class='text-center d-none d-lg-table-cell' style='width:150px;'>"._("Last executed")."</th>";
print "</tr>";
print "</thead>";

print "<tbody>";

if (sizeof($cron_groups)==0) {
	print "<tr>";
	print "	<td colspan=6> <i class='fa fa-clock-o' style='color:#ccc;padding:0px 5px;'></i><span class='text-info'>". _("No jobs available").".</span></td>";
	print "</tr>";
}
else {
	// body
	foreach ($cron_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
		print "<tr class='header'>";
		print "	<td colspan=6><i class='fa fa-users text-muted'></i> "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/'>".$tenants[$tenant_id]->name."</a></td>";
		print "</tr>";
		}

		foreach ($group as $script => $t) {

			if(!is_object($t)) {
				$t = new StdClass ();
				$t->script = $script;
				$t->minute = "-";
				$t->hour = "-";
				$t->day = "-";
				$t->weekday = "-";
				$t->last_executed = "-";

				$trclass = "text-danger";
			}
			else {
				$trclass = "";
			}

			if($t->script=="update_certificates")		{ $script_name = "Update SSL certificates"; }
			elseif($t->script=="axfr_transfer")			{ $script_name = "Zone transfers"; }
			elseif($t->script=="remove_orphaned")		{ $script_name = "Remove orhaned certificates"; }
			elseif($t->script=="expired_certificates")	{ $script_name = "Notify about expired certificates"; }
			else										{ $script_name = "Unknown"; }

			print "<tr class='$trclass'>";
			print "	<td><i class='fa fa-clock-o' style='color:#ccc;padding:0px 5px;'></i> <strong>".$script_name."</strong></td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->minute."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->hour."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->day."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->weekday."</td>";
			print "	<td class='text-muted'>".$t->last_executed."</span></td>";
			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>

</div>