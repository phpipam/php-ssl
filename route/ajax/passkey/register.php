<?php

/**
 * Register a new passkey for the currently authenticated user.
 *
 * POST JSON body: { name, credential }  (credential = navigator.credentials.create() response)
 */

require('../../../functions/autoload.php');
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => 'Invalid JSON body']);
    exit;
}

$name       = trim($body['name'] ?? '');
$credential = $body['credential'] ?? null;

if ($name === '' || !$credential) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => 'Missing name or credential']);
    exit;
}
if (strlen($name) > 255) {
    $name = substr($name, 0, 255);
}

try {
    $WebAuthn = WebAuthn::from_request('php-ssl');
    $data     = $WebAuthn->verify_registration($credential);

    // Check for duplicate credential_id
    $existing = $Database->getObjectQuery(
        "SELECT id FROM passkeys WHERE credential_id = ?",
        [$data['credential_id']]
    );
    if ($existing) {
        throw new RuntimeException('This passkey is already registered');
    }

    $Database->runQuery(
        "INSERT INTO passkeys (user_id, name, credential_id, public_key, sign_count) VALUES (?, ?, ?, ?, ?)",
        [(int) $user->id, $name, $data['credential_id'], $data['public_key'], $data['sign_count']]
    );

    $Log->write("users", $user->id, $user->t_id, $user->id, "passkey_register", false,
        "Passkey \"" . $name . "\" registered");

    print json_encode(['status' => 'ok', 'message' => 'Passkey registered successfully']);
}
catch (Exception $e) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
