<?php

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# fetch zone details
$zone = $Zones->get_zone ($_params['tenant'], $_GET['zone_name']);

// init class
$AXFR = new AXFR ($Database);
// set dns, tcp and tsig parameters
$AXFR->set_nameservers (explode(",",$zone->dns));					// set anmeservers to query
$AXFR->set_tsig ($zone->tsig_name, $zone->tsig);					// set tsig parameters
$AXFR->set_zone_name ($zone->aname);								// set zone name to query
$AXFR->set_valid_types (explode(",", $zone->record_types));			// set valid dns record types
$AXFR->set_regexes ($zone->regex_include, $zone->regex_exclude);	// set regexes

// execute
$AXFR->execute();

// get result
$results = $AXFR->get_records ();

// error ?
if($results['success']==false) {
	$content[] = "<div class='alert alert-danger'>".$results['error']."</div>";
}
else {
	// calculate differences [create, remove, new etc]
	$AXFR->calculate_diffs ($zone->id, $zone->check_ip);

	// add records
	$AXFR->create_new_records ();

	// remove records not in DNS AXFR
	if ($zone->delete_records=="1") {
		$AXFR->delete_records ();
	}
	else {
		$AXFR->records['removed_records'] = [];
	}
}

# title
$title = _("AXFR zone sync");

# content
$content = [];
$content[] = "<h4>"._("Zone AXFR sync results").":</h4><hr>";
$content[] = "<ul>";
$content[] = "	<li>"._("Discovered records").": ".sizeof($AXFR->records['axfr_records'])."</li>";
$content[] = "	<li>"._("Existing records").": ".sizeof($AXFR->records['old_records'])."</li>";
$content[] = "	<li>"._("Removed records").": ".sizeof($AXFR->records['removed_records'])."</li>";
$content[] = "	<li>"._("Created records").": ".sizeof($AXFR->records['new_records'])."</li>";
$content[] = "</ul>";

# print modal
$Modal->modal_id = "#modal1";
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", true);