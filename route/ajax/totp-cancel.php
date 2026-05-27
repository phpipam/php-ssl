<?php

/**
 * Cancels a pending 2FA login — clears the pending session state and redirects to login.
 */

require(dirname(__FILE__) . '/../../functions/autoload.php');

unset($_SESSION['2fa_pending'], $_SESSION['2fa_attempts']);

header("Location: /login/");
die();
