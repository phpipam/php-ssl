<header class="navbar navbar-dark bg-dark flex-md-nowrap">
	<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/">php-ssl-scan</a>
</header>


<div class='header text-center'>
	<h3><?php print _("Login"); ?></h3>
</div>

<div class="container" style="max-width:600px">
	<div class='text-center login_text'><?php print _('Please enter your email and password to login to system').".<br>"._('In case of any issues please contact'); ?> <a href='mailto:<?php print $mail_sender_settings->email; ?>'><?php print _('system administrator'); ?></a>!</div>

	<div class="login">

	<form name="login" id="login" method="post">
	<div class="row" style='margin:0px;'>

		<!-- username -->
		<div class="col-xs-12"><strong><?php print _('E-Mail'); ?></strong></div>
		<div class="col-xs-12">
			<input type="text" id="username" name="username" class="login form-control input-sm" placeholder="<?php print _('E-Mail'); ?>" autofocus="autofocus" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
		</div>

		<!-- password -->
		<div class="col-xs-12" style="margin-top: 10px;"><strong><?php print _('Password'); ?></strong></div>
		<div class="col-xs-12">
		    <input type="password" id="password" name="password" class="login form-control input-sm" placeholder="<?php print _('Password'); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></input>
		</div>
		<div class="col-xs-12" style='margin-top:20px;'>
			<input type="submit" value="<?php print _('Login'); ?>" class="btn btn-sm btn-outline-success float-end"></input>
		</div>

	</div>

	</form>


	<!-- login response -->
	<div id="loginCheck" style='margin:10px;margin-top:20px;'>
	<?php
	# deauthenticate user
	if ( $User->is_authenticated(false) ) {
		$Result->show("success", _('You have logged out'));
		# destroy session
		$User->destroy_session();
	}
	?>
	</div>
	</div>

</div>