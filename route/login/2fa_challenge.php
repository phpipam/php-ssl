<?php
// session must have a pending 2FA state; otherwise redirect to login
if (empty($_SESSION['2fa_pending']) || !empty($_SESSION['username'])) {
    header("Location: /login/");
    die();
}
?>

<div class='header text-center'>
    <h2 class="page-title d-flex justify-content-center" style="margin-top:60px;margin-bottom: 40px;">

        <?php if ($_SESSION['theme'] != "dark") { ?>
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
