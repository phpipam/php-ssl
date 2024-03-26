<div class='header'>
	<h3><?php print _("Fetch website certificate"); ?></h3>
</div>

<!-- back -->
<div class="container-fluid main">
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> <?php print _("Back"); ?></a>
</div>


<div class="container-fluid main">
	<?php
	// validate request, remove POST if invalid
	if(isset($_POST['website'])) {
		// remove https / https for IP checking
		$_POST['website_ip_check'] = str_replace(["http://", "https://"], "", $_POST['website']);
		// validate
		if(!$User->validate_url($_POST['website']) && !$User->validate_ip($_POST['website_ip_check'])) {
			unset ($_POST['website'], $_POST['website_ip_check']);
		}
	}
	?>

	<form class='form-inline' method="post">
	<div class="row" style='max-width:500px;'>
		<div class="col-xs-12">
			Enter hostname or IP address:
			<input type="text" name="website" value="<?php print $_POST['website']; ?>" class="form-control form-control-sm" placeholder="<?php print _('https://google.com'); ?>" required>
		</div>

		<div class="col-xs-12">
			Select scanning agent:
			<select name='agent_id' class="form-control form-control-sm">
				<?php
				// all agents
				$all_agents = $Database->getObjectsQuery("select * from agents order by id asc");

				// print
				if(is_array($all_agents)) {
					foreach ($all_agents as $agent) {
						// select
						$selected = @$_POST['agent_id']==$agent->id ? "selected" : "";
						// print
						print "<option value='".$agent->id."' $selected>$agent->name</option>";
					}
				}
				?>
			</select>
		</div>

		<div class="col-xs-12 text-right">
			<br>
			<button type="submit" class="btn btn-sm btn-outline-success float-end"><?php print _("Fetch"); ?></button>
		</div>
	</form>
	</div>
</div>



<?php
// error ?
if(sizeof($User->errors)>0) {
	print "<div class='container-fluid main'>";
	$Result->show("danger", _($User->errors[0]), false);
	print "</div>";
}
elseif(isset($_POST['website'])) {
	include('result.php');
}