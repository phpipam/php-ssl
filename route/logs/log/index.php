<?php

# validate user session
$User->validate_session ();
# validate tenant
$User->validate_tenant ();

# fetch zone
$log = $Log-> get_log_by_id ($_params['app'], $user);

# not existing ?
if ($log==NULL) {
	// title
	print '<div class="page-header">';
	print '	<h2 class="page-title">'.$url_items["logs"]['icon']." ". _("Log details").'</h2>';
	print '	<hr>';
	print '</div>';

	// back
	print "<div>";
	print '<div class="btn-group" role="group">';
	print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
	print '</div>';
	print "</div>";

	// content
	print '<div class="page-content" style="margin-top:20px">';
	$Result->show("danger", _("Log does not exist."), false);
	print "</div>";
}
# ok
else {
	?>


	<div class='page-header'>
		<h2 class='page-title'><?php print  $url_items["logs"]['icon']." "._("Log details"); ?> [<?php print @$log->id; ?>]</h3>
		<hr>
	</div>


	<div>
		<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
	</div><br><br>


	<?php

	// nice log
	$log_nice = clone $log;
	// format
	$log_nice = $Log->format_log_entry ($log_nice);
	// fetch users
	$users = $User->get_all ();


	// prepare diffs
	$logdata1 = json_decode($log->json_object_old, true);
	$logdata2 = json_decode($log->json_object_new, true);

	// create keys
	$logdata1 = create_keys ($logdata1, $logdata2);
	$logdata2 = create_keys ($logdata2, $logdata1);


	// sort
	ksort($logdata1);
	ksort($logdata2);







	// div
	print "<div class='row'>";

	//
	// Details
	//
	print "<div class='col-xs-12 col-sm-12 col-md-8 col-lg-6' style='margin-top:10px;'>";
	print "	<div class='card'>";

	print " <div class='card-header'>"._("Log ID")." ".$log->id."</div>";


	print "	<div class='card-header'>";
	// table
	print "<table class='table table-borderless table-md table-hover table-zones-details'>";

	print "<tr>";
	print "	<th style='min-width:180px;width:100px;'>"._("User")."</th>";
	print "	<td><b>".$users[$log->object_u_id]->name."</b></td>";
	print "</tr>";

	// tenants
	if($user->admin=="1") {
		$tenant = $Database->getObject("tenants", $log->object_t_id);
		print "<tr>";
		print "	<th style='min-width:180px;'>"._("Tenant")."</th>";
		print "	<td><b>".$tenant->name."</b></td>";
		print "</tr>";
	}

	print "<tr>";
	print "	<th>"._("Object")."</th>";
	print "	<td>".ucwords($log->object)."</td>";
	print "</tr>";

	print "<tr>";
	print "	<th>"._("Action")."</th>";
	print "	<td>".$log_nice->action."</td>";
	print "</tr>";

	print "<tr>";
	print "	<th>"._("Content")."</th>";
	print "	<td>".$log->text."</td>";
	print "</tr>";

	print "<tr>";
	print "	<th>"._("Date")."</th>";
	print "	<td>".$log_nice->date."</td>";
	print "</tr>";

	if($User->get_user_permissions (3) && (strlen($log->json_object_old)>0 || strlen($log->json_object_new)>0) && $log->action!=="delete" && $log->action!=="add" && $log->action!=="refresh") {
		print "<tr>";
		print "	<th>"._("Change")."</th>";
		print "	<td>";
		print "	<pre class='diff' style='margin:0px;'>";
		$diff_arr = recursive_array_diff_withold($logdata2, $logdata1);
		print $Log->pretty_json(json_encode($diff_arr));
		print "	</pre>";
		print "</td>";
		print "</tr>";
	}


	print "</table>";
	print "</div>";
	print "</div>";
	print "</div>";



	print '<div></div>';

	if($User->get_user_permissions (3) && (strlen($log->json_object_old)>0 || strlen($log->json_object_new)>0) ) {

		// old
		if (strlen($log->json_object_old)>0) {
			print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6' style='margin-top:10px;'>";
			print "	<div class='card'>";

			print " <div class='card-header'>"._("Old object")."</div>";

			print " <div class='card-content'>";
			print "	<pre class='diff'>";
			if(strlen($log->json_object_old)>0)
			print $Log->pretty_json(json_encode($logdata1));
			else
			print 'NULL';
			print "	</pre>";
			print "</div>";
			print "</div>";
			print "</div>";
		}


		// new
		if (strlen($log->json_object_new)>0) {
			print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6' style='margin-top:10px;'>";
			print "	<div class='card'>";

			print " <div class='card-header'>"._("New object")."</div>";
			print " <div class='card-content'>";
			print "	<pre class='diff'>";
			if(strlen($log->json_object_new)>0)
			print $Log->pretty_json(json_encode($logdata2));
			else
			print 'NULL';		print "	</pre>";
			print "</div>";
			print "</div>";
			print "</div>";
		}
	}

	print "</div>";
}






function create_keys ($a1, $a2) {
	foreach ($a2 as $k2 => $v2) {
		if (!array_key_exists($k2, $a1)) {
			$a1[$k2] = NULL;
		}
	}
	return $a1;
}


function recursive_array_diff($a1, $a2) {
    $r = array();
    foreach ($a1 as $k => $v) {
        if (array_key_exists($k, $a2)) {
            if (is_array($v)) {
                $rad = recursive_array_diff($v, $a2[$k]);
                if (count($rad)) { $r[$k] = $rad; }
            } else {
                if ($v != $a2[$k]) {
                    $r[$k] = $v;
                }
            }
        } else {
            $r[$k] = $v;
        }
    }
    return $r;
}


function recursive_array_diff_withold($a1, $a2) {
    $r = array();
    foreach ($a1 as $k => $v) {
        if (array_key_exists($k, $a2)) {
            if (is_array($v)) {
                $rad = recursive_array_diff($v, $a2[$k]);
                if (count($rad)) { $r[$k] = $rad; }
            } else {
                if ($v != $a2[$k]) {
                	$v = is_null($v) ? 'null' : $v;
                    $r[$k] = $a2[$k]." => ".$v;
                }
            }
        } else {
            $r[$k] = $v;
        }
    }
    return $r;
}

