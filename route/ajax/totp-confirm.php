<?php

/**
 * Verifies the TOTP code entered during setup, then sets totp_enabled = 1.
 */

require(dirname(__FILE__) . '/../../functions/autoload.php');

$User->validate_session(false, false, true);
$User->validate_csrf_token();

require(dirname(__FILE__) . '/../../functions/assets/GoogleAuthenticator/GoogleAuthenticator.php');

$post = $User->strip_input_tags($_POST);
$code = preg_replace('/\D/', '', (string)($post['code'] ?? ''));

if (strlen($code) !== 6) {
    $Result->show("danger", _("Please enter a 6-digit code."), true);
}

// reload fresh user row (setup may have just written the secret)
$u = $Database->getObject("users", (int)$user->id);
if (!$u || empty($u->totp_secret)) {
    $Result->show("danger", _("No 2FA secret found. Please start setup again."), true);
}

$secret = $User->totp_decrypt((string)$u->totp_secret, (int)$u->t_id);
if ($secret === '') {
    $Result->show("danger", _("Could not read 2FA secret."), true);
}

$ga = new PHPGangsta_GoogleAuthenticator();
if (!$ga->verifyCode($secret, $code, 1)) {
    $Result->show("danger", _("Invalid verification code. Please try again."), true);
}

// activate
try {
    $Database->runQuery(
        "UPDATE `users` SET `totp_enabled` = 1 WHERE `id` = ?",
        [(int)$user->id]
    );
} catch (Exception $e) {
    $Result->show("danger", _("Error enabling 2FA."), true);
}

$Log->write("users", $user->id, $user->t_id, $user->id, "2fa_enabled", false, "User enabled TOTP 2FA");

print $Result->show("success", _("Two-factor authentication has been enabled."), false, true);
