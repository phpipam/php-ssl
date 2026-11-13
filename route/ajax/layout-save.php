<?php

#
# Layout / theme settings AJAX endpoint (profile → Layout tab)
#
# POST: key, value, csrf_token  — save one option (validated against Layout::OPTIONS)
#       reset=1, csrf_token     — clear all options (back to defaults)
#
# Storage is handled by the Layout class (users.layout column).
#

# autoload
require('../../functions/autoload.php');
# validate session (not a popup)
$User->validate_session (false, false, true);

header('Content-Type: application/json');

# CSRF
$submitted = $_POST['csrf_token'] ?? '';
$expected  = $_SESSION['csrf_token'] ?? '';
if (empty($expected) || !is_string($submitted) || !hash_equals($expected, $submitted)) {
	print json_encode(['error' => _("Invalid or missing CSRF token.")]);
	exit;
}

# reset
if (!empty($_POST['reset'])) {
	$Layout->reset();
	print json_encode(['success' => true]);
	exit;
}

# save single option
$key   = is_string($_POST['key'] ?? null)   ? $_POST['key']   : '';
$value = is_string($_POST['value'] ?? null) ? $_POST['value'] : '';

if (!$Layout->set($key, $value)) {
	print json_encode(['error' => _("Invalid layout setting.")]);
	exit;
}

print json_encode(['success' => true, 'key' => $key, 'value' => $value]);
