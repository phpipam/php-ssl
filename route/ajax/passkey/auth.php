<?php

/**
 * Authenticate a user with a passkey (no existing session required).
 *
 * POST JSON body: { assertion }  (assertion = navigator.credentials.get() response)
 *
 * On success returns { status: 'ok', redirect: '/' } and establishes a session.
 */

require('../../../functions/autoload.php');
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['assertion'])) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$assertion = $body['assertion'];

try {
    // Fetch all passkeys (for discoverable-credential flow we don't know which user yet)
    $credential_id = $assertion['id'] ?? '';
    if ($credential_id === '') {
        throw new RuntimeException('Missing credential id');
    }

    // Find the passkey row
    $pk_row = $Database->getObjectQuery(
        "SELECT p.*, u.email, u.name as u_name, u.t_id, u.disabled, u.lang_id, u.force_passkey
         FROM passkeys p
         JOIN users u ON u.id = p.user_id
         WHERE p.credential_id = ?",
        [$credential_id]
    );

    if (!$pk_row) {
        throw new RuntimeException('Passkey not found');
    }
    if ($pk_row->disabled) {
        throw new RuntimeException('Account is disabled');
    }

    $stored = [
        'credential_id' => $pk_row->credential_id,
        'public_key'    => $pk_row->public_key,
        'sign_count'    => (int) $pk_row->sign_count,
    ];

    $WebAuthn = WebAuthn::from_request('php-ssl');
    $WebAuthn->verify_authentication($assertion, [$stored]);

    // Update last_used_at and sign_count
    $new_sign_count = (int) ($assertion['response']['authenticatorData'] ?? 0);
    $Database->runQuery(
        "UPDATE passkeys SET last_used_at = NOW(), sign_count = ? WHERE credential_id = ?",
        [(int) max($stored['sign_count'], $new_sign_count), $credential_id]
    );

    // Establish session
    session_regenerate_id(true);
    $_SESSION['username']      = $pk_row->email;
    $_SESSION['passkey_login'] = $pk_row->id; // which passkey was used
    if (!empty($pk_row->lang_id)) {
        $_SESSION['lang_id'] = (int) $pk_row->lang_id;
    } else {
        unset($_SESSION['lang_id']);
    }

    $Log->write("users", (int) $pk_row->user_id, (int) $pk_row->t_id, (int) $pk_row->user_id,
        "passkey_login", false, "User logged in with passkey \"" . $pk_row->name . "\"");

    $redirect = $_SESSION['redirect_url'] ?? '/';
    unset($_SESSION['redirect_url']);

    print json_encode(['status' => 'ok', 'redirect' => $redirect]);
}
catch (Exception $e) {
    http_response_code(401);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
