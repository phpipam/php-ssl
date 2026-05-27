<?php

/**
 * Verifies a TOTP code submitted during the 2FA login challenge.
 * On success, completes the session (sets $_SESSION['username']).
 */

require(dirname(__FILE__) . '/../../functions/autoload.php');

// must have a pending 2FA state and NOT be fully logged in
if (empty($_SESSION['2fa_pending']) || !empty($_SESSION['username'])) {
    $Result->show("danger", _("Invalid request."), true);
}

$pending = $_SESSION['2fa_pending'];

// sanitise and extract the 6-digit code
$post  = $User->strip_input_tags($_POST);
$code  = preg_replace('/\D/', '', (string)($post['code'] ?? ''));

if (strlen($code) !== 6) {
    $Result->show("danger", _("Please enter a 6-digit code."), true);
}

// basic rate-limit: max 5 attempts per pending session
if (!isset($_SESSION['2fa_attempts'])) {
    $_SESSION['2fa_attempts'] = 0;
}
$_SESSION['2fa_attempts']++;
if ($_SESSION['2fa_attempts'] > 5) {
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_attempts']);
    $Result->show("danger", _("Too many failed attempts. Please log in again."), true);
}

// load library
require(dirname(__FILE__) . '/../../functions/assets/GoogleAuthenticator/GoogleAuthenticator.php');

// fetch user
$user_obj = $Database->getObjectQuery("SELECT * FROM `users` WHERE `email` = ?", [$pending['email']]);
if (!$user_obj || empty($user_obj->totp_enabled)) {
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_attempts']);
    $Result->show("danger", _("Invalid 2FA state. Please log in again."), true);
}

// decrypt secret
$secret = $User->totp_decrypt((string)$user_obj->totp_secret, (int)$user_obj->t_id);
if ($secret === '') {
    $Result->show("danger", _("Could not read 2FA secret. Please contact an administrator."), true);
}

// verify — allow ±1 time window (30 s) to accommodate clock drift
$ga = new PHPGangsta_GoogleAuthenticator();
if (!$ga->verifyCode($secret, $code, 1)) {
    $Result->show("danger", _("Invalid verification code."), true);
}

// success — complete the session
session_regenerate_id(true);
$_SESSION['username'] = $pending['email'];
unset($_SESSION['2fa_pending'], $_SESSION['2fa_attempts']);

// restore language
if (!empty($user_obj->lang_id)) {
    $_SESSION['lang_id'] = (int)$user_obj->lang_id;
} else {
    unset($_SESSION['lang_id']);
}

$redirect = $pending['redirect'] ?? '/';

$Log->write("user", $user_obj->id, $user_obj->t_id, $user_obj->id, "login", false, "User has logged in (2FA verified)");

print $Result->show("success", _("Login successful"));
print "<div id='login_redirect'>" . htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') . "</div>";
