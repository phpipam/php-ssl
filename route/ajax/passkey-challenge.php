<?php

/**
 * Returns a WebAuthn challenge for either registration or authentication.
 *
 * GET ?action=register  — requires authenticated session
 * GET ?action=auth      — no session required
 */

require('../../functions/autoload.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    $WebAuthn = WebAuthn::from_request('php-ssl');

    if ($action === 'register') {
        $User->validate_session(false, false, false);
        $options = $WebAuthn->get_registration_options(
            (int) $user->id,
            $user->email,
            $user->name
        );
        print json_encode(['status' => 'ok', 'options' => $options]);
    }
    elseif ($action === 'auth') {
        // Optionally filter by email to allow non-resident-key flow
        $email      = trim($_GET['email'] ?? '');
        $passkeys   = [];
        if ($email !== '') {
            $u = $Database->getObjectQuery("SELECT id FROM users WHERE email = ?", [$email]);
            if ($u) {
                $rows = $Database->getObjectsQuery(
                    "SELECT credential_id FROM passkeys WHERE user_id = ?",
                    [(int) $u->id]
                );
                $passkeys = array_map('get_object_vars', $rows ?: []);
            }
        }
        $options = $WebAuthn->get_authentication_options($passkeys);
        print json_encode(['status' => 'ok', 'options' => $options]);
    }
    else {
        http_response_code(400);
        print json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}
catch (Exception $e) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
