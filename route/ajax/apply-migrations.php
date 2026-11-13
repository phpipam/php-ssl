<?php

/**
 * Apply pending database migrations.
 * Admin-only, AJAX (XMLHttpRequest) POST only.
 */

require('../../functions/autoload.php');

header('Content-Type: application/json');

// Must be a POST from XHR
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
	http_response_code(405);
	print json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

// Admin only
$User->validate_session(false, true, false);
if ($user->admin !== '1') {
	http_response_code(403);
	print json_encode(['success' => false, 'error' => 'Administrator access required']);
	exit;
}

// Apply
$results = $Migration->apply_all();

// Log each migration result
foreach ($results as $r) {
	$Log->write("database", null, $user->t_id, $user->id, "migrate", false, ($r['success'] ? "Migration applied " : "Migration failed ") . $r['file']);
}

$all_ok = !in_array(false, array_column($results, 'success'), true);

print json_encode([
	'success' => $all_ok,
	'results' => $results,
]);
