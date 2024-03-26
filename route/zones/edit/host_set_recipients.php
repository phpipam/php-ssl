<?php

#
# Add extra recipients
#


# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# fetch zone
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
$zone   = $Zones->get_zone ($_GET['tenant'], $_GET['zone_id']);
$host   = $Zones->get_host ($_GET['host_id']);

# set existing recipients
$recipients = explode(";", $host->h_recipients);

#
# title
#
$title = _("Manage extra recipients");


# invalid zone
if ($zone===null) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid zone"), false, false, true);
	# btn
	$btn_text = "";
}
# invalid host
elseif ($host===null || $host->z_id!=$zone->id) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid host"), false, false, true);
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// exitsing
	$first_recipients = sizeof($recipients)>0 ? $recipients[0] : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle'>";
	// name
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Email")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='hostname-0' placeholder='email' value='$first_recipients'>";
	$content[] = "		<input type='hidden' name='tenant' value='".$_GET['tenant']."'>";
	$content[] = "		<input type='hidden' name='id' value='$_GET[host_id]'>";
	$content[] = "		<input type='hidden' name='zone_id' value='{$zone->id}'>";
	$content[] = "	</td>";
	$content[] = "<td><a class='btn btn-sm btn-danger clear_host'><i class='fa fa-times'></i></a></td>";
	$content[] = "</tr>";

	// others
	if (sizeof($recipients)>1) {
		foreach ($recipients as $index=>$r) {
			if ($index > 0) {
				$content[] = "<tr>";
				$content[] = "	<th style='width:100px;'>"._("Email")."</th>";
				$content[] = "	<td>";
				$content[] = "		<input type='text' class='form-control form-control-sm' name='hostname-$index' placeholder='email' value='$r'>";
				$content[] = "	</td>";
				$content[] = "<td><a class='btn btn-sm btn-danger remove_host'><i class='fa fa-times'></i></a></td>";
			}
		}
	}

	$content[] = "</table>";
	$content[] = "</form>";
	$content[] = "<hr>";
	$content[] = "<btn class='btn btn-sm btn-default btn-outline-success' id='add_hosts'><i class='fa fa-plus'></i> "._("Add more")."</btn>";

	$content[] = "<div class='visually-hidden' id='hostcount'>1</div>";

	#
	# button text
	#
	$btn_text = _("Update recipients");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/zones/edit/host-set-recipients-submit.php");

?>


<script type="text/javascript">
$(document).ready(function() {
	// add hosts
	$('#add_hosts').click(function() {
		// add count
		var current = $('#hostcount').html();
		current++;
		$('#hostcount').html(current);

		// template
		var append = "";
		append += "<tr><th style='width:100px;'><?php print _("Email"); ?></th>";
		append += "<td><input type='text' class='form-control form-control-sm' name='hostname-"+current+"'></td>";
		append += "<td><a class='btn btn-sm btn-danger remove_host'><i class='fa fa-times'></i></a></td>";

		// append to form
		$('form#modal-form table').append(append)
	})
	// remove host
	$(document).on("click", '.remove_host', function () {
		$(this).parent().parent().remove();
	})
	// clear host
	$(document).on("click", '.clear_host', function () {
		$("input[name=hostname-0]").val("");
	})
})
</script>