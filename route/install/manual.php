<?php
global $db, $installed;

if ($installed === true) {
	print '<div class="alert alert-danger">Installation is already complete (<code>$installed = true</code>).</div>';
	$step_class_1 = "steps-red";
	$step_class_2 = "steps-red";
	$step_class_3 = "steps-red";
	return;
}
?>

<h2 class="mb-3 text-center">Manual database installation</h2>
<p class="text-secondary mb-4">Below are SQL commands needed to install database files. Please run them on your MySQL/MariaDB server:</p>

<div class="my-4">
<pre>
# Drop existing database if exists
# ------------------------------------------------------------
DROP DATABASE IF EXISTS `<?php print htmlspecialchars($db['name'], ENT_QUOTES); ?>`;


# Create database
# ------------------------------------------------------------
CREATE DATABASE `<?php print htmlspecialchars($db['name'], ENT_QUOTES); ?>` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;


# Create user and grant permissions
# ------------------------------------------------------------
CREATE USER `<?php print htmlspecialchars($db['user'], ENT_QUOTES); ?>`@`<?php print htmlspecialchars($db['host'], ENT_QUOTES); ?>` IDENTIFIED BY '<?php print htmlspecialchars($db['pass'], ENT_QUOTES); ?>';
GRANT ALL PRIVILEGES ON `<?php print htmlspecialchars($db['name'], ENT_QUOTES); ?>`.* TO `<?php print htmlspecialchars($db['user'], ENT_QUOTES); ?>`@`<?php print htmlspecialchars($db['host'], ENT_QUOTES); ?>`;
FLUSH PRIVILEGES;


# Import schema (contents of db/SCHEMA.sql)
# ------------------------------------------------------------
<?php readfile(dirname(__FILE__)."/../../db/SCHEMA.sql"); ?>
</pre>

<br>
<span class='text-secondary'>Don't forget to set <code>$installed = true;</code> in your <code>config.php</code>!</span>
</div>

<div class="my-4">
  <a href="/install/manual-verify/" class="btn btn-primary w-100">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
    I entered commands to database
  </a>
</div>
