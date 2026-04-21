<?php

/**
 * Delete a passkey belonging to the current user.
 *
 * POST JSON body: { id }  (passkey row id)
 */

require('../../functions/autoload.php');
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body = json_decode(file_get_contents('php://input'), true);
$pk_id = (int) ($body['id'] ?? 0);

if ($pk_id <= 0) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => 'Invalid id']);
    exit;
}

try {
    $pk = $Database->getObjectQuery(
        "SELECT * FROM passkeys WHERE id = ? AND user_id = ?",
        [$pk_id, (int) $user->id]
    );
    if (!$pk) {
        throw new RuntimeException('Passkey not found');
    }

    $Database->runQuery("DELETE FROM passkeys WHERE id = ?", [$pk_id]);

    $Log->write("users", $user->id, $user->t_id, $user->id, "passkey_delete", false,
        "Passkey \"" . $pk->name . "\" deleted");

    print json_encode(['status' => 'ok', 'message' => 'Passkey deleted']);
}
catch (Exception $e) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
