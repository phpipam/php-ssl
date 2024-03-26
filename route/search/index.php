<div class='header'>
	<h3><?php print _("Search"); ?></h3>
</div>

<!-- back -->
<div class="container-fluid main">
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> <?php print _("Back"); ?></a>
</div>


<div class="container-fluid main">
	<?php
	// check/uncheck values on post
	$host_checked = @$_POST['hosts']=="on" ? "checked" : "";
	$cert_checked = @$_POST['certificates']=="on" ? "checked" : "";
	// default
	if(!isset($_POST['search'])) {
		$host_checked = "checked";
		$cert_checked = "checked";
	}
	?>

	<form class='form-inline' method="post">

		<div class="input-group" style='max-width:500px;'>
			<input type="text" name="search" value="<?php print $_POST['search']; ?>" class="form-control form-control-sm" placeholder="<?php print _('Enter search string'); ?>" required>

			<button type="submit" class="btn btn-sm btn-outline-success"><?php print _("Search"); ?></button>
		</div>

		<div class="form-check" style='padding-top: 10px;'>
			<input type="checkbox" name="hosts" class="form-check-input" id="hosts" <?php print $host_checked; ?>>
			<label class="form-check-label" for="hosts"></label><?php print _("Search hosts"); ?>
		</div>
		<div class="form-check">
			<input type="checkbox" name="certificates" class="form-check-input" id="certificates" <?php print $cert_checked; ?>>
			<label class="form-check-label" for="certificates"></label><?php print _("Search certificates"); ?>
		</div>
	</form>
</div>


<?php
if(isset($_POST['search'])) {
	include('search-results.php');
}