<?php

# session
ob_start();

# check for config file
if (!file_exists(__DIR__ . "/../config/config.php") && $_SERVER['REQUEST_URI']!="/install/")
{
	// html
	$title   = "Config file missing";
	$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];
	// error page
	$error_title = "Configuration file is missing.";
	// config.php in the application root = installation from before the config/ directory was introduced
	if (file_exists(__DIR__ . "/../config.php")) {
		$error_text = "Configuration files moved to the config/ directory.<br>Please move config.php from the application root to config/config.php.";
	}
	else {
		$error_text = "To know how to connect to database config/config.php file needs to be present.<br>Please copy config/config.dist.php to config/config.php and change it accordingly.";
	}

	$_params = ['tenant'=>'error', "route"=>"generic"];
}
else
{
	# autoload classes
	require (__DIR__ . "/../functions/autoload.php");
}

# no cache headers
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache");                         //HTTP 1.0
header("Expires: Sat, 26 Jul 2016 05:00:00 GMT");   //Date in the past

// which view is rendered — decides if layout (navigation) attributes apply
if (isset($_SESSION['2fa_pending']) && empty($_SESSION['username']))     { $_view = "2fa"; }
elseif ($_params['tenant']=="login" || $_params['tenant']=="logout")     { $_view = "login"; }
elseif ($_params['tenant']=="report")                                    { $_view = "report"; }
elseif ($_params['tenant']=="error" && $_params['route']=="generic")     { $_view = "error"; }
elseif ($_params['tenant']=="install")                                   { $_view = "install"; }
elseif (isset($user) && !empty($user->disabled))                         { $_view = "disabled"; }
elseif (isset($user) && !empty($user->changePass))                       { $_view = "changepass"; }
else                                                                     { $_view = "app"; }

// layout / theme (Layout class may be missing if config or extensions are missing)
if (!isset($Layout) && class_exists('Layout')) { $Layout = new Layout (); }
$_html_attributes = isset($Layout) ? $Layout->html_attributes($_view === "app") : 'data-bs-theme="dark" data-theme="dark" data-bs-theme-base="slate" style="color-scheme: dark;"';
?>

<!doctype html>
<html lang="en" <?php print $_html_attributes; ?>>

<head>
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
	<title><?php print $title ?? ''; ?></title>

	<!-- favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

	<!-- css -->
	<link href="/css/tabler.1.7.5.min.css" rel="stylesheet">
	<link href="/css/tabler-themes.1.7.5.min.css" rel="stylesheet">
	<link href="/css/bootstrap-table.1.26.0.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="/css/style.css?version=<?php print filemtime(__DIR__ . "/css/style.css"); ?>">

	<!-- color scheme "auto": follow OS preference before first paint -->
	<script>
	(function () {
		var h = document.documentElement;
		if (h.getAttribute('data-bs-theme-mode') === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
			h.setAttribute('data-bs-theme', 'dark'); h.setAttribute('data-theme', 'dark'); h.style.colorScheme = 'dark';
		}
	})();
	</script>

	<!-- js -->
    <script src="/js/jquery-3.6.0.min.js"></script>
    <script src="/js/popper.2.11.8.js"></script>
    <script src="/js/tippy.js"></script>
	<script src="/js/tabler.1.7.5.min.js"></script>
	<script src="/js/bootstrap-table.1.26.0.min.js"></script>
	<script src="/js/magic.js?version=<?php print filemtime(__DIR__ . "/js/magic.js"); ?>"></script>

</head>

<body>
	<?php
	// 2FA pending — intercept every route until TOTP is verified
	if ($_view == "2fa") {
		include (__DIR__ . "/../route/login/2fa_challenge.php");
	}
	// login and logout
	elseif ($_view == "login") {
		include (__DIR__ . "/../route/login/index.php");
	}
	// public testSSL report by hash — no auth required
	elseif ($_view == "report") {
		include (__DIR__ . "/../route/report/index.php");
	}
	// generic errors
	elseif ($_view == "error") {
		include (__DIR__ . "/../route/error/generic.php");
	}
	// installation
	elseif ($_view == "install") {
		include (__DIR__ . "/../route/install/index.php");
	}
	// disabled account — render without header/sidebar
	elseif ($_view == "disabled") {
		include (__DIR__ . "/../route/disabled/index.php");
	}
	// force password change — render without header/sidebar
	elseif ($_view == "changepass") {
		include (__DIR__ . "/../route/user/changepass/index.php");
	}
	// default
	else {
		$_horizontal = $Layout->is_horizontal();
	?>

	<!-- content (Tabler page layout: sidebar | header | top menu | page-wrapper) -->
	<div class="page">
		<?php if (!$_horizontal) { ?>
		<aside class="navbar navbar-vertical navbar-expand-lg">
			<?php include (__DIR__ . "/../route/common/left-menu.php"); ?>
		</aside>
		<?php } ?>

		<?php include (__DIR__ . "/../route/common/header.php"); ?>

		<?php if ($_horizontal) { include (__DIR__ . "/../route/common/top-menu.php"); } ?>

		<div class="page-wrapper">
			<div class="page-body">
				<?php include (__DIR__ . "/../route/common/checks.php"); ?>
				<?php include (__DIR__ . "/../route/content.php"); ?>
			</div>
		</div>
	</div>

	<!-- modal -->
	<div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	    </div>
	  </div>
	</div>

	<div class="modal fade" id="modal3" tabindex="-3" aria-labelledby="modal3" aria-hidden="true">
	  <div class="modal-dialog modal-lg">
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
	<div class="loading progress progress-sm">
        <div class="progress-bar progress-bar-indeterminate"></div>
    </div>

	<iframe class="download" style="display:none;"></iframe>
</body>
</html>