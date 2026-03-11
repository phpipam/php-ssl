<?php
global $Common, $installed;

// Block if already installed
if ($installed === true) {
	print '<div class="alert alert-danger"><strong>Error:</strong> Installation is already complete (<code>$installed = true</code> in config.php).</div>';
	print '<div class="my-4"><a href="/install/" class="btn btn-secondary w-100">Back</a></div>';
	return;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: /install/automatic/');
	exit;
}

// Collect POST values (strip_tags is already applied by URL class for path params; sanitize here for POST)
$params = [
	'admin_user'    => strip_tags(trim($_POST['admin_user'] ?? '')),
	'admin_pass'    => $_POST['admin_pass'] ?? '',
	'db_host'       => strip_tags(trim($_POST['db_host'] ?? '127.0.0.1')),
	'db_port'       => (int)($_POST['db_port'] ?? 3306),
	'db_name'       => strip_tags(trim($_POST['db_name'] ?? '')),
	'app_user'      => strip_tags(trim($_POST['app_user'] ?? '')),
	'app_pass'      => $_POST['app_pass'] ?? '',
	'app_user_host' => strip_tags(trim($_POST['app_user_host'] ?? '')),
	'reinstall'     => !empty($_POST['reinstall']),
];

// Run installation
$result = $Common->install_database($params);

if ($result['success']) {
	// Success
	?>
	<h2 class="mb-3 text-center text-success">Installation complete!</h2>

	<div class="alert alert-success mb-4">
	  <strong>Database installed successfully.</strong>
	</div>

	<?php if (!empty($result['log'])) { ?>
	<div class="mb-4">
	  <p class="text-secondary mb-1"><strong>Installation log:</strong></p>
	  <ul class="list-unstyled small">
	    <?php foreach ($result['log'] as $line) { ?>
	    <li class="mb-1">
	      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-success me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
	      <?php print htmlspecialchars($line, ENT_QUOTES); ?>
	    </li>
	    <?php } ?>
	  </ul>
	</div>
	<?php } ?>

	<hr class="my-4">

	<div class="alert alert-success mb-4" style='display:block'>
	  Default username and password is <strong>admin/admin</strong>.
	</div>

	<hr class="my-4">


	<div class="alert alert-warning mb-4" style='display:block'>
	  <strong>Action required:</strong><hr>Open <code>config.php</code> and set <code>$installed = true;</code> to complete the installation.
	</div>

	<div class="my-4">
	  <a href="/" class="btn btn-primary w-100">Go to application</a>
	</div>
	<div class="my-2">
	  <a href="/install/" class="btn btn-link w-100 text-secondary">Back to installation</a>
	</div>

	<?php
} else {
	// Failure
	$step_class_2 = "steps-red";
	$step_class_3 = "steps-red";
	?>
	<h2 class="mb-3 text-center text-danger">Installation failed</h2>

	<?php foreach ($result['errors'] as $err) { ?>
	<div class="alert alert-danger mb-3" style='displ1ay:block'>
	  <strong>Error:</strong><?php print htmlspecialchars($err, ENT_QUOTES); ?>
	</div>
	<?php } ?>

	<?php if (!empty($result['log'])) { ?>
	<div class="mb-4">
	  <p class="text-secondary mb-1"><strong>Steps completed before failure:</strong></p>
	  <ul class="list-unstyled small">
	    <?php foreach ($result['log'] as $line) { ?>
	    <li class="mb-1 text-secondary">
	      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z" /></svg>
	      <?php print htmlspecialchars($line, ENT_QUOTES); ?>
	    </li>
	    <?php } ?>
	  </ul>
	</div>
	<?php } ?>

	<div class="my-4">
	  <a href="/install/automatic/" class="btn btn-primary w-100">Try again</a>
	</div>
	<div class="my-2">
	  <a href="/install/" class="btn btn-link w-100 text-secondary">Back to installation</a>
	</div>
	<?php
}
?>
