<?php

#
# Edit host
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
$zone = $Zones->get_zone ($_GET['tenant'], $_GET['zone_name']);
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("host");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid action"), false, false, true);
	# btn
	$btn_text = "";
}
# invalid zone
elseif ($zone===null) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid zone"), false, false, true);
	# btn
	$btn_text = "";
}
else {
	// reset title
	$title = _(ucwords($_GET['action']))." "._("host to zone")." ".$zone->name;
	// content
	$content = [];

	// ports
	$ports = $SSL->get_all_port_groups ();

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle'>";
	// name
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Hostname")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='hostname-1'>";
	$content[] = "		<input type='hidden' name='action' value='".$_GET['action']."'>";
	$content[] = "		<input type='hidden' name='tenant' value='".$_GET['tenant']."'>";
	$content[] = "		<input type='hidden' name='id' value='$_GET[host_id]'>";
	$content[] = "		<input type='hidden' name='zone_id' value='{$zone->id}'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "<select name='pg-1' class='form-select form-select-sm'>";
	foreach($ports[$tenant->id] as $id=>$p) {
	$content[] =  "<option value='$id'>".$p['name']."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "	<td></td>";
	$content[] = "</tr>";

	$content[] = "</table>";
	$content[] = "</form>";
	$content[] = "<hr>";
	$content[] = "<btn class='btn btn-sm btn-default btn-outline-success' id='add_hosts'><i class='fa fa-plus'></i> "._("Add more")."</btn>";

	$content[] = "<div class='visually-hidden' id='hostcount'>1</div>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("hosts");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/zones/edit/add-hostnames-submit.php");

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
		append += "<tr><th style='width:100px;'><?php print _("Hostname"); ?></th>";
		append += "<td><input type='text' class='form-control form-control-sm' name='hostname-"+current+"'></td>";
		append += "<td><select name='pg-"+current+"' class='form-select form-select-sm'>";
		<?php foreach ($ports[$tenant->id] as $id=>$p) { ?>
		append += "<option value='<?php print $id; ?>'><?php print $p['name']; ?></option>";
		<?php } ?>
		append += "</td>";
		append += "<td><a class='btn btn-sm btn-danger remove_host'><i class='fa fa-times'></i></a></td>";

		// append to form
		$('form#modal-form table').append(append)
	})
	// remove host
	$(document).on("click", '.remove_host', function () {
		$(this).parent().parent().remove();
	})
})
</script>