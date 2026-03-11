<h2 class="mb-3 text-center">Manual database installation</h2>
<p class="text-secondary mb-4">Below are SQL commands needed to install database files. Please run them on your MySQL/mariaDB server:</p>


<div class="my-4">
<pre>
# Drop existing database if exists
# ------------------------------------------------------------
DROP DATABASE if exists `lala`;


# Create database
# ------------------------------------------------------------
CREATE DATABASE `<?php print $db['name'] ?>` DEFAULT CHARACTER SET = `utf8`;


# Create user and grant permissions
# ------------------------------------------------------------
GRANT ALL on `<?php print $db['name'] ?>`.* to `<?php print $db['user'] ?>`@localhost identified by `<?php print $db['pass'] ?>`;


<?php print include(dirname(__FILE__)."/../../db/SCHEMA.sql"); ?>
</pre>


<br>
<span class='text-secondary'>Dont forget to set $installed = true; to your config.php !</span>

</div>


<div class="my-4">
  <a href="/install/" class="btn btn-primary w-100" data-target='manual-import-show'><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg> I entered commads to database.</a>
</div>