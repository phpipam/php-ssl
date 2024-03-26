<?php

# session
ob_start();

# autoload classes
require ("functions/autoload.php");

# check for config
if (!$Common->config_exists()) { die(_("Config file missing")); }

# no cache headers
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache");                         //HTTP 1.0
header("Expires: Sat, 26 Jul 2016 05:00:00 GMT");   //Date in the past
?>

<!doctype html>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="TEST">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.8, maximum-scale=0.8, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $title; ?></title>

	<!-- css -->
	<link href="https://fonts.googleapis.com/css?family=IBM+Plex+Sans:100,100i,400,600,700,800|Open+Sans|Raleway|Source+Sans+Pro" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<script src="https://kit.fontawesome.com/17a8c71dca.js?v=1" crossorigin="anonymous"></script>
	<link href="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="/css/style.css?v=<?php print md5(time()); ?>>">

	<!-- js -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

	<!-- bootstrap table -->
	<script src="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.js"></script>
	<script src="https://unpkg.com/bootstrap-table@1.19.1/dist/extensions/cookie/bootstrap-table-cookie.min.js"></script>
	<script src="https://unpkg.com/bootstrap-table@1.19.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>

	<script type="text/javascript" src="/js/magic.js?v=<?php print md5(time()); ?>"></script>

</head>

<body>
	<?php
	if($_params['tenant']=="login" || $_params['tenant']=="logout") {
		include ("route/login/index.php");
	}
	else {
	?>
	<!-- Header -->
	<?php include ("route/common/header.php"); ?>

	<!-- content -->
	<div class="container-fluid" style='margin-top:52px;'>
	  <div class="row">
	    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
	    	<?php include ("route/common/left-menu.php"); ?>
	    </nav>

	    <main class="col-md-9 ms-sm-auto col-lg-10 1px-md-4" style='padding-left:0px;padding-right:0px;'>
	    	<?php include ("route/content.php"); ?>
	    </main>
	  </div>
	</div>

	<!-- modal -->
	<div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	    </div>
	  </div>
	</div>

	<div class="modal fade" id="modal2" tabindex="-2" aria-labelledby="modal2" aria-hidden="true">
	  <div class="modal-dialog modal-xl">
	    <div class="modal-content">
	    </div>
	  </div>
	</div>

	<?php } ?>


	<!-- loader -->
	<div class="loading"><?php print _('Loading');?>...<br><i class="fa fa-spinner fa-spin"></i></div>
	<iframe class="download" style="display:none;"></iframe>
</body>
</html>