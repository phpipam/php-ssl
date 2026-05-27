<script src="/js/qrcode.min.js"></script>

<div class="card-body">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
        </svg>
        <?php print _("Two-Factor Authentication"); ?>
    </h3>
</div>

<div class="card-body">

<?php
// Variables available from profile/index.php: $view_user, $user, $Database, $User
if (!empty($view_user->force_passkey)): ?>
<div class="">
    <div class="alert alert-info">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
        <?php print _("Password login is disabled — this account requires passkey authentication. 2FA is not prompted on passkey login."); ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($view_user->totp_enabled)): ?>

    <!-- 2FA is currently enabled -->
    <div class="alert alert-success mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
        <?php print _("Two-factor authentication is enabled on your account."); ?>
    </div>

    <p class="text-secondary"><?php print _("To disable two-factor authentication, click the button below. You will need to re-scan the QR code if you want to enable it again."); ?></p>

    <button type="button" class="btn btn-danger" id="btn-totp-disable">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
        <?php print _("Disable 2FA"); ?>
    </button>

    <div id="totp-disable-result" style="margin-top:12px;display:none;"></div>

<?php else: ?>

    <!-- 2FA setup flow -->
    <p class="text-secondary"><?php print _("Two-factor authentication adds an extra layer of security. After enabling it, you will be asked for a code from your authenticator app each time you log in."); ?></p>

    <div id="totp-setup-initial">
        <button type="button" class="btn btn-primary" id="btn-totp-start">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7h14" /><path d="M12 3v4" /><path d="M8 7c0 5.523 -4 9 -4 9h16s-4 -3.477 -4 -9" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
            <?php print _("Set up 2FA"); ?>
        </button>
    </div>

    <!-- shown after "Set up 2FA" is clicked -->
    <div id="totp-setup-qr" style="display:none;margin-top:16px;">
        <hr>
        <p><?php print _("1. Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)."); ?></p>
        <div id="totp-qrcode" style="display:inline-block;background:#fff;padding:8px;border-radius:4px;"></div>
        <p class="mt-2 text-secondary small">
            <?php print _("Can't scan? Enter this code manually:"); ?>
            <code id="totp-secret-text" style="word-break:break-all;"></code>
        </p>
        <hr>
        <p><?php print _("2. Enter the 6-digit code from your app to confirm setup."); ?></p>
        <div class="row g-2 align-items-center" style="max-width:360px;">
            <div class="col">
                <input type="text" id="totp-confirm-code" class="form-control" inputmode="numeric"
                       pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                       placeholder="<?php print _('6-digit code'); ?>">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-success" id="btn-totp-confirm">
                    <?php print _("Confirm"); ?>
                </button>
            </div>
        </div>
        <div id="totp-confirm-result" style="margin-top:10px;display:none;"></div>
    </div>

<?php endif; ?>

</div>

<script>
(function () {
    var csrf = <?php print json_encode($User->create_csrf_token()); ?>;

    /* ---- helper: show result (html is trusted server output from $Result->show()) ---- */
    function showResult(id, html) {
        $('#' + id).html(html).show();
    }

    /* ---- SETUP FLOW ---- */
    var btnStart = document.getElementById('btn-totp-start');
    if (btnStart) {
        btnStart.addEventListener('click', function () {
            btnStart.disabled = true;

            $.post('/route/ajax/totp-setup.php', { csrf_token: csrf }, function (data) {
                if (data.status !== 'ok') {
                    btnStart.disabled = false;
                    alert(data.message || <?php print json_encode(_("Error starting 2FA setup.")); ?>);
                    return;
                }

                document.getElementById('totp-setup-qr').style.display = '';
                document.getElementById('totp-setup-initial').style.display = 'none';

                // extract base32 secret from URI for manual entry display
                var m = data.uri.match(/[?&]secret=([^&]+)/);
                var secret = m ? decodeURIComponent(m[1]) : '';
                document.getElementById('totp-secret-text').textContent = secret;

                // render QR code
                new QRCode(document.getElementById('totp-qrcode'), {
                    text:           data.uri,
                    width:          180,
                    height:         180,
                    correctLevel:   QRCode.CorrectLevel.M
                });

                document.getElementById('totp-confirm-code').focus();
            }, 'json').fail(function () {
                btnStart.disabled = false;
                alert(<?php print json_encode(_("Error starting 2FA setup.")); ?>);
            });
        });
    }

    /* ---- CONFIRM ---- */
    var btnConfirm = document.getElementById('btn-totp-confirm');
    if (btnConfirm) {
        function confirmTotp() {
            var code = document.getElementById('totp-confirm-code').value.replace(/\D/g, '');
            if (code.length !== 6) {
                showResult('totp-confirm-result',
                    '<div class="alert alert-danger">' + <?php print json_encode(_("Please enter a 6-digit code.")); ?> + '</div>');
                return;
            }
            btnConfirm.disabled = true;

            $.post('/route/ajax/totp-confirm.php', { code: code, csrf_token: csrf }, function (data) {
                showResult('totp-confirm-result', data);
                if (data.indexOf('alert-success') !== -1) {
                    setTimeout(function () { window.location.reload(); }, 1200);
                } else {
                    btnConfirm.disabled = false;
                    document.getElementById('totp-confirm-code').value = '';
                    document.getElementById('totp-confirm-code').focus();
                }
            });
        }

        btnConfirm.addEventListener('click', confirmTotp);
        document.getElementById('totp-confirm-code').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') confirmTotp();
        });
    }

    /* ---- DISABLE ---- */
    var btnDisable = document.getElementById('btn-totp-disable');
    if (btnDisable) {
        btnDisable.addEventListener('click', function () {
            if (!confirm(<?php print json_encode(_("Are you sure you want to disable two-factor authentication?")); ?>)) return;
            btnDisable.disabled = true;

            $.post('/route/ajax/totp-disable.php', { csrf_token: csrf }, function (data) {
                showResult('totp-disable-result', data);
                if (data.indexOf('alert-success') !== -1) {
                    setTimeout(function () { window.location.reload(); }, 1200);
                } else {
                    btnDisable.disabled = false;
                }
            });
        });
    }
})();
</script>
