<?php

/**
 * Apply all database structure fixes identified by validate_database().
 * Admin-only, XHR POST with CSRF token.
 */

require('../../../functions/autoload.php');

header('Content-Type: application/json');

// XHR POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
	http_response_code(405);
	print json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

// Require session and admin
$User->validate_session();
$User->validate_csrf_token();

if ($user->admin !== '1') {
	http_response_code(403);
	print json_encode(['success' => false, 'error' => 'Admin access required']);
	exit;
}

// Run validation
$validation = $Common->validate_database($Database);

if ($validation['error'] !== null) {
	print json_encode(['success' => false, 'error' => $validation['error']]);
	exit;
}

if ($validation['ok']) {
	print json_encode(['success' => true, 'applied' => 0]);
	exit;
}

// Apply fixes
$applied  = 0;
$errors   = [];

foreach ($validation['issues'] as $issue) {
	try {
		$Database->runQuery($issue['fix_sql']);
		$applied++;
	} catch (Exception $e) {
		$errors[] = $issue['fix_sql'] . ': ' . $e->getMessage();
	}
}

if (!empty($errors)) {
	print json_encode(['success' => false, 'error' => implode('; ', $errors), 'applied' => $applied]);
	exit;
}

print json_encode(['success' => true, 'applied' => $applied]);
