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

                <?php
                $_login_passkeys_exist = false;
                try {
                    $_pk_count = $Database->getObjectQuery("SELECT COUNT(*) AS cnt FROM passkeys");
                    $_login_passkeys_exist = ($_pk_count && (int)$_pk_count->cnt > 0);
                } catch (Exception $e) {}
                if ($_login_passkeys_exist):
                ?>
                <div class="hr-text"><?php print _("or"); ?></div>

                <div>
                    <button type="button" class="btn btn-outline-secondary w-100" id="btn-passkey-login">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>
                        <?php print _("Sign in with Passkey"); ?>
                    </button>
                </div>

                <div id="passkey-login-result" style="display:none;margin-top: 20px;"></div>
                <?php endif; ?>

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

<script>
(function () {
    var btn    = document.getElementById('btn-passkey-login');
    var result = document.getElementById('passkey-login-result');

    if (!btn) return;

    if (!window.PublicKeyCredential) {
        btn.disabled = true;
        btn.title    = <?php print json_encode(_("Your browser does not support passkeys.")); ?>;
    }

    // SVGs match PHP Result::get_icon() — hardcoded, never user input
    var _icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>',
        danger:  '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'
    };

    function showMsg(type, msg) {
        result.textContent = '';
        var div  = document.createElement('div');
        div.className      = 'alert alert-' + type;
        div.style.fontSize = '13px';
        // icon: hardcoded SVG strings only, no user content
        if (_icons[type]) {
            var iconSpan = document.createElement('span');
            iconSpan.innerHTML = _icons[type]; // safe: constant SVG strings
            div.appendChild(iconSpan);
        }
        // message: text node — XSS-safe
        div.appendChild(document.createTextNode(' ' + msg));
        result.appendChild(div);
        result.style.display = '';
    }

    function b64url_decode(str) {
        var b64 = str.replace(/-/g, '+').replace(/_/g, '/');
        while (b64.length % 4) b64 += '=';
        var bin = atob(b64);
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }
    function b64url_encode(buf) {
        var bytes = buf instanceof ArrayBuffer ? new Uint8Array(buf) : new Uint8Array(buf.buffer || buf);
        var bin   = '';
        bytes.forEach(function (b) { bin += String.fromCharCode(b); });
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    btn.addEventListener('click', async function () {
        result.style.display = 'none';
        btn.disabled = true;

        try {
            var url = '/route/ajax/passkey-challenge.php?action=auth';

            var resp = await fetch(url);
            var data = await resp.json();
            if (data.status !== 'ok') throw new Error(data.message);

            // Use modern JSON API when available; fall back to manual decoding.
            var assertion;
            if (typeof PublicKeyCredential.parseRequestOptionsFromJSON === 'function') {
                assertion = await navigator.credentials.get({
                    publicKey: PublicKeyCredential.parseRequestOptionsFromJSON(data.options)
                });
            } else {
                var opts = data.options;
                opts.challenge = b64url_decode(opts.challenge);
                if (opts.allowCredentials) {
                    opts.allowCredentials = opts.allowCredentials.map(function (c) {
                        c.id = b64url_decode(c.id); return c;
                    });
                }
                assertion = await navigator.credentials.get({ publicKey: opts });
            }

            var encoded = (typeof assertion.toJSON === 'function')
                ? assertion.toJSON()
                : {
                    id:   assertion.id,
                    type: assertion.type,
                    response: {
                        authenticatorData: b64url_encode(assertion.response.authenticatorData),
                        clientDataJSON:    b64url_encode(assertion.response.clientDataJSON),
                        signature:         b64url_encode(assertion.response.signature),
                        userHandle:        assertion.response.userHandle ? b64url_encode(assertion.response.userHandle) : null,
                    }
                };

            var auth = await fetch('/route/ajax/passkey-auth.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ assertion: encoded }),
            });
            var authData = await auth.json();
            if (authData.status !== 'ok') throw new Error(authData.message);

            showMsg('success', <?php print json_encode(_("Login successful")); ?>);
            setTimeout(function () { window.location = authData.redirect || '/'; }, 800);
        }
        catch (err) {
            btn.disabled = false;
            if (err.name === 'NotAllowedError') {
                showMsg('warning', <?php print json_encode(_("Passkey authentication was cancelled.")); ?>);
            } else {
                showMsg('danger', err.message || <?php print json_encode(_("Passkey authentication failed.")); ?>);
            }
        }
    });
})();
</script>