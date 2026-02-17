<?php
# validate user session
$User->validate_session ();

// header
if(!isset($from_search)) {
	print '<div class="page-header">';
	print '<h3>'._("Zone hosts").'</h3><hr>';
	print '</div>';
}

# none
if (!is_object($zone) && !isset($from_search)) {
	print '<div class="page-header">';
	$Result->show("danger", _("Invalid zone").".");
	print '</div>';
}
else {

	# top buttons
	if(!isset($from_search)) {
		print "<div class='text-left' style='margin-bottom:10px'>";
		print '<a href="/route/error/modal.php" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-outline-success btn-sm btn-5 d-none d-sm-inline-block"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg> '._("Import").'</a>';

		// actions right
		print '<div class="btn-group float-end">';
		print '<a href="/route/modals/zones/truncate.php?tenant='.$_params['tenant'].'&zone_id='.$zone->id.'" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-trash" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Remove all hosts from zone").'"></i> '._("Remove all").'</a>';
		print '<a href="/route/modals/zones/zone-cert-refresh-all.php?tenant='.$_params['tenant'].'&zone_id='.$zone->id.'" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-sync" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Rescan all hosts for new certificates").'"></i> '._("Rescan all").'</a>';
		print "</div>";

		print "</div>";
	}


	print "<div class='card' style='margin:$margin'>";

	print "<input type='hidden' id='zone_id' value='".@$zone->id."'>";
	print "<input type='hidden' id='search_term' value='".(@$_params['search'])."'>";

	print "<table
		class='table table-hover align-top table-sm'
		id='zone_hosts'
		data-toggle='table'
		data-mobile-responsive='true'
		data-check-on-init='true'
		data-classes='table table-hover table-sm'
		data-cookie='false'
		data-cookie-id-table='zonehosts'
		data-pagination='true'
		data-page-size='50'
		data-page-list='[50,250,500,All]'
		data-search='true'
		data-side-pagination='server'
		data-server-sort='true'
		data-ajax='ajaxRequest'
		data-loading-template='loadingMessage'
		data-loading-font-size='14'
		data-icons-prefix='fa'
		data-icon-size='xs'
		data-show-footer='false'
		data-smart-display='true'
		showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th data-field='status_badge' data-width='20' data-width-unit='px'></th>";
	print "	<th data-field='hostname_html'>"._("Hostname")."</th>";

	if(isset($from_search)) {
		if($user->admin=="1")
		print "	<th data-field='tenant'>"._("Zone")."/"._("Tenant")."</th>";
	}

	print "	<th data-field='serial_html'>"._("Certificate")."</th>";
	print "	<th data-field='domain_issuer' class='d-none d-lg-table-cell'>"._("Domain")." / "._("Issuer")."</th>";
	print "	<th data-field='checked_changed' class='d-none d-xl-table-cell'>"._("Checked / Changed")."</th>";
	print "	<th data-field='port_html' style='width:50px;' data-width='50' data-width-unit='px' class='d-none d-xl-table-cell'>"._("Ports")."</th>";
	print "	<th data-field='actions' style='width:50px;' data-width='50' data-width-unit='px' class='d-table-cell'></th>";

	print "</tr>";
	print "</thead>";
	print "</table>";
	print "</div>";
}

?>

<script>
window.ajaxRequest = params => {
    var zone_id = $('#zone_id').val();
    var search_term = $('#search_term').val();
    var data = params.data;
    // If zone_id exists, use it; otherwise it's a search across all zones
    if(zone_id) {
        data.zone_id = zone_id;
    }
    // If search_term exists, use it
    if(search_term) {
        data.search = search_term;
    }
    $.ajax({
        type: "POST",
        url: '/route/ajax/zone-hosts.php',
        data: data,
        dataType: "json",
        success: function (data) {
            params.success({
                "rows": data.rows,
                "total": data.total
            })
        },
        error: function (er) {
        	console.log(er)
            params.error(er);
        }
    });
}

function loadingMessage () {
  return '<span class="loading-wrap">' +
    '<span class="loading-text" style="font-size:14px;">Loading</span>' +
    '	<span class="animation-wrap"><span class="animation-dot"></span></span>' +
    '</span>'
}
</script>
