<?php

/**
 * Disables TOTP 2FA for the current user (self-service).
 * Clears both the secret and the enabled flag.
 */

require(dirname(__FILE__) . '/../../functions/autoload.php');

$User->validate_session(false, false, true);
$User->validate_csrf_token();

try {
    $Database->runQuery(
        "UPDATE `users` SET `totp_enabled` = 0, `totp_secret` = NULL WHERE `id` = ?",
        [(int)$user->id]
    );
} catch (Exception $e) {
    $Result->show("danger", _("Error disabling 2FA."), true);
}

$Log->write("users", $user->id, $user->t_id, $user->id, "2fa_disabled", false, "User disabled TOTP 2FA");

print $Result->show("success", _("Two-factor authentication has been disabled."), false, true);
