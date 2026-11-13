<?php
// session must have a pending 2FA state; otherwise redirect to login
if (empty($_SESSION['2fa_pending']) || !empty($_SESSION['username'])) {
    header("Location: /login/");
    die();
}
?>

<div class='header text-center'>
    <h2 class="page-title d-flex justify-content-center" style="margin-top:60px;margin-bottom: 40px;">

        <?php include(__DIR__."/../common/logo.php"); ?>

        php-ssl-scan :: <?php print _("Two-Factor Authentication"); ?>
    </h2>
</div>

<div class="container container-tight py-4">
    <div class="card card-md">
        <div class="card-body">
            <p class="text-secondary mb-4">
                <?php print _("Enter the 6-digit code from your authenticator app."); ?>
            </p>

            <div class="mb-3">
                <label class="form-label"><?php print _("Verification code"); ?></label>
                <input type="text" id="totp-code" class="form-control" inputmode="numeric" pattern="[0-9]*"
                       maxlength="6" autocomplete="one-time-code" autofocus
                       placeholder="<?php print _('6-digit code'); ?>">
            </div>

            <div class="form-footer">
                <button type="button" id="btn-totp-verify" class="btn btn-primary w-100">
                    <?php print _("Verify"); ?>
                </button>
            </div>

            <div id="totp-result" style="display:none;margin-top:16px;"></div>

            <div class="mt-3 text-center">
                <a href="/route/ajax/totp-cancel.php" class="text-muted small">
                    <?php print _("Cancel and return to login"); ?>
                </a>
            </div>
        </div>

        <div class="hr-text">info</div>
        <div class="card-body text-muted justify-content-center">
            <?php print _("Open your authenticator app (Google Authenticator, Authy, etc.) and enter the current code."); ?>
        </div>
    </div>
</div>

<script>
(function () {
    var btn    = document.getElementById('btn-totp-verify');
    var input  = document.getElementById('totp-code');
    var result = document.getElementById('totp-result');

    var _icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>',
        danger:  '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>',
        info:    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'
    };

    function showMsg(type, msg) {
        result.textContent = '';
        var div = document.createElement('div');
        div.className = 'alert alert-' + type;
        div.style.fontSize = '13px';
        if (_icons[type]) {
            var span = document.createElement('span');
            span.innerHTML = _icons[type]; // safe: constant SVG strings, no user content
            div.appendChild(span);
        }
        div.appendChild(document.createTextNode(' ' + msg));
        result.appendChild(div);
        result.style.display = '';
    }

    function verify() {
        var code = input.value.replace(/\D/g, '');
        if (code.length !== 6) {
            showMsg('danger', <?php print json_encode(_("Please enter a 6-digit code.")); ?>);
            return;
        }
        btn.disabled = true;

        $.post('/route/ajax/totp-login.php', { code: code }, function (data) {
            var tmp = $('<div>').html(data);
            if (tmp.find('#login_redirect').length > 0) {
                showMsg('success', <?php print json_encode(_("Login successful")); ?>);
                setTimeout(function () {
                    window.location = tmp.find('#login_redirect').text() || '/';
                }, 800);
            } else {
                btn.disabled = false;
                // strip HTML for safe display — Result::show produces an alert div
                var alertDiv = tmp.find('.alert').first();
                var msg = alertDiv.length > 0 ? alertDiv.text().trim() : <?php print json_encode(_("Invalid verification code.")); ?>;
                showMsg('danger', msg);
                input.value = '';
                input.focus();
            }
        });
    }

    btn.addEventListener('click', verify);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') verify();
    });
    // auto-submit when 6 digits are entered
    input.addEventListener('input', function () {
        if (input.value.replace(/\D/g, '').length === 6) verify();
    });
})();
</script>
