<?php
# zone
if(!isset($_params['app'])) {
	include("all.php");
}
elseif(isset($_params['id1'])) {
	include("zone/host/index.php");
}
else {
	include("zone/index.php");
}