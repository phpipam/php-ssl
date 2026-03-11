<div class='header text-center'>
	<h2 class="page-title d-flex justify-content-center" style="margin-top:60px;margin-bottom: 40px;">

            <?php if($_SESSION['theme']!="dark") { ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="#066fd1"/>
              <g transform="translate(4.7, 4.7) scale(0.94)">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </g>
            </svg>
            <?php } else { ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="white"/>
              <g transform="translate(4.7, 4.7) scale(0.94)">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </g>
            </svg>
            <?php } ?>

        php-ssl-scan :: <?php print _("Login"); ?>
    </h2>
</div>


<div class="container container-tight py-4">
	<div class="card card-md">
        <div class="card-body">
            <!-- <h2 class="h2 text-center mb-4">Login to your account</h2> -->
            <form action="./" method="get" autocomplete="off" novalidate="" id="login" name="login">

                <div class="mb-3">
                    <label class="form-label"><?php print _('E-Mail address'); ?></label>
                    <input type="text" class="form-control" placeholder="<?php print _('Your E-Mail'); ?>" name="username" id="username"  autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                </div>

                <div class="mb-2">
                    <label class="form-label"><?php print _('Password'); ?></label>
                    <div class="input-group input-group-flat">
                        <input type="password" id="password" class="form-control" placeholder="<?php print _('Your password'); ?>" name="password" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                </div>


                <?php
                // fetch all active domains
                $domains = $User->get_active_domains ();
                // if only one ignore
                if (sizeof($domains)==1) {
                    print '<input type="hidden" class="form-control" name="domain" value="'.$domains[0]->id.'">';
                }
                else {
                ?>
                <div class="mb-3">
                    <label class="form-label">Domain</label>
                    <select class="form-select" name="domain">
                    <?php
                    // print
                    foreach ($domains as $d) {
                        print "<option value='$d->id'>$d->name</option>";
                    }
                    ?>
                    </select>
                </div>
                <?php } ?>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100"><?php print _("Sign in"); ?></button>
                </div>

                <!-- Logout -->
                <div class="mb-2" id="loginCheck" style="margin-top:30px;margin-bottom:0px;">
					<?php
					# deauthenticate user
					if ( $User->is_authenticated(false) ) {
						$Result->show("success", _('You have logged out'));
						# destroy session
						$User->destroy_session();
					}
					?>
                </div>


            </form>
        </div>

        <div class="hr-text">info</div>

        <div class="card-body text-muted justify-content-center">
            <?php print _('Please enter your email and password to login to system').". "._('In case of any issues please contact'); ?> <a href='mailto:<?php print $mail_sender_settings->email; ?>'><?php print _('system administrator'); ?></a>.
        </div>
    </div>
</div>