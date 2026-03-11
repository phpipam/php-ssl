<?php
global $db, $installed;

$error = null;

// Try connecting with application credentials from config.php
try {
	$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8";
	$pdo = new PDO($dsn, $db['user'], $db['pass'], [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (PDOException $e) {
	$error = 'Could not connect to the database: ' . $e->getMessage();
}

// Check that the logs table exists (confirms schema was imported)
if ($error === null) {
	try {
		$stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
		if ($stmt->rowCount() === 0) {
			$error = 'Connected to the database, but the <code>logs</code> table was not found. The schema may not have been imported correctly.';
		}
	} catch (PDOException $e) {
		$error = 'Database query failed: ' . $e->getMessage();
	}
}

if ($error !== null) {
	$step_class_3 = "steps-red";
	?>
	<h2 class="mb-3 text-center text-danger">Verification failed</h2>

	<div class="alert alert-danger mb-4" style="display:block">
	  <strong>Error:</strong> <?php print $error; ?>
	</div>

	<div class="my-4">
	  <a href="/install/manual/" class="btn btn-primary w-100">Back — try again</a>
	</div>
	<?php
	return;
}

// Success
?>
<h2 class="mb-3 text-center text-success">Verification passed!</h2>

<div class="alert alert-success mb-4" style="display:block">
  Connected to <strong><?php print htmlspecialchars($db['name'], ENT_QUOTES); ?></strong> and confirmed schema is present.
</div>

<hr class="my-4">

<div class="alert alert-success mb-4" style='display:block'>
  Default username and password is <strong>admin/admin</strong>.
</div>

<hr class="my-4">

<div class="alert alert-warning mb-4" style="display:block">
  <strong>Action required:</strong><hr>Open <code>config.php</code> and set <code>$installed = true;</code> to complete the installation.
</div>

<div class="my-4">
  <a href="/" class="btn btn-primary w-100">Go to application</a>
</div>
<div class="my-2">
  <a href="/install/" class="btn btn-link w-100 text-secondary">Back to installation</a>
</div>
