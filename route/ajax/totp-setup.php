<?php

/**
 * Generates a new TOTP secret for the current user, stores it encrypted
 * (totp_enabled stays 0 until confirmed), and returns the otpauth:// URI
 * so the client can render a QR code.
 */

require(dirname(__FILE__) . '/../../functions/autoload.php');

$User->validate_session(false, false, true);
$User->validate_csrf_token();

require(dirname(__FILE__) . '/../../functions/assets/GoogleAuthenticator/GoogleAuthenticator.php');

$ga     = new PHPGangsta_GoogleAuthenticator();
$secret = $ga->createSecret(32);

// store encrypted, but NOT yet enabled
$stored = $User->totp_encrypt($secret, (int)$user->t_id);
try {
    $Database->runQuery(
        "UPDATE `users` SET `totp_secret` = ?, `totp_enabled` = 0 WHERE `id` = ?",
        [$stored, (int)$user->id]
    );
} catch (Exception $e) {
    $Result->show("danger", _("Error saving 2FA secret."), true);
}

// build otpauth URI
$issuer  = 'php-ssl';
$account = rawurlencode($issuer) . ':' . rawurlencode($user->email);
$uri     = 'otpauth://totp/' . $account
         . '?secret=' . rawurlencode($secret)
         . '&issuer=' . rawurlencode($issuer)
         . '&algorithm=SHA1&digits=6&period=30';

header('Content-Type: application/json');
print json_encode(['status' => 'ok', 'uri' => $uri]);
