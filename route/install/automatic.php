<?php
// Pre-fill form fields from config.php values
global $db, $installed;

// Block access if already installed
if ($installed === true) {
	print '<div class="alert alert-danger">Installation is already complete (<code>$installed = true</code>).</div>';
	return;
}
?>

<h2 class="mb-1 text-center">Automatic database installation</h2>
<p class="text-secondary mb-4 text-center">Provide MySQL admin credentials to create the database, user, and import the schema.</p>

<form action="/install/automatic-execute/" method="post">

  <h4 class="mb-2">Admin credentials</h4>
  <p class="text-secondary small mb-3">A MySQL user with privileges to create databases and users (e.g. <code>root</code>).</p>

  <div class="mb-3">
    <label class="form-label required">Admin username</label>
    <input type="text" name="admin_user" class="form-control" placeholder="root" autocomplete="off" required>
  </div>

  <div class="mb-4">
    <label class="form-label">Admin password</label>
    <input type="password" name="admin_pass" class="form-control" placeholder="" autocomplete="new-password">
  </div>

  <hr class="my-4">
  <h4 class="mb-2">Database settings</h4>
  <p class="text-secondary small mb-3">Read from <code>config.php</code> — edit that file to change these values.</p>

  <div class="row g-3 mb-3">
    <div class="col-8">
      <label class="form-label">DB host</label>
      <input type="text" name="db_host" class="form-control" value="<?php print htmlspecialchars($db['host'] ?? '127.0.0.1', ENT_QUOTES); ?>" readonly>
    </div>
    <div class="col-4">
      <label class="form-label">Port</label>
      <input type="number" name="db_port" class="form-control" value="<?php print (int)($db['port'] ?? 3306); ?>" readonly>
    </div>
  </div>

  <div class="mb-4">
    <label class="form-label">Database name</label>
    <input type="text" name="db_name" class="form-control" value="<?php print htmlspecialchars($db['name'] ?? 'php-ssl', ENT_QUOTES); ?>" readonly>
  </div>

  <hr class="my-4">
  <h4 class="mb-2">Application user</h4>
  <p class="text-secondary small mb-3">Read from <code>config.php</code> — this user will be created and granted access to the database.</p>

  <div class="mb-3">
    <label class="form-label">Username</label>
    <input type="text" name="app_user" class="form-control" value="<?php print htmlspecialchars($db['user'] ?? '', ENT_QUOTES); ?>" readonly>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="text" name="app_pass" class="form-control" value="<?php print htmlspecialchars($db['pass'] ?? '', ENT_QUOTES); ?>" readonly>
  </div>

  <div class="mb-4">
    <label class="form-label">User host</label>
    <input type="text" name="app_user_host" class="form-control" value="<?php print htmlspecialchars($db['host'] ?? 'localhost', ENT_QUOTES); ?>" readonly>
  </div>

  <hr class="my-4">
  <h4 class="mb-2">Options</h4>

  <label class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="reinstall" value="1">
    <span class="form-check-label">
      <strong>Reinstall</strong> &mdash; drop existing database before creating it
      <span class="d-block text-danger small">Warning: this will permanently delete all existing data.</span>
    </span>
  </label>

  <div class="my-4">
    <button type="submit" class="btn btn-primary w-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
      Install database
    </button>
  </div>

  <div class="my-2">
    <a href="/install/" class="btn btn-link w-100 text-secondary">Back</a>
  </div>

</form>
