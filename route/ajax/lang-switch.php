<?php

#
# Language switch AJAX endpoint
#
# POST: lang_id (int) — ID from translations table, or 0/empty to reset to default
#
# Saves the chosen language to $_SESSION['lang_id'] immediately (current request).
# Optionally saves it to users.lang_id in the DB if ?persist=1 is sent.
#

# autoload
require('../../functions/autoload.php');
# validate session (not a popup)
$User->validate_session (false, false, true);

header('Content-Type: application/json');

# validate lang_id
$lang_id = isset($_POST['lang_id']) ? (int)$_POST['lang_id'] : 0;

if ($lang_id > 0) {
	# verify it exists and is enabled
	try {
		$tr = $Database->getObjectQuery ("SELECT id, name, native_name, locale_code, lang_code, flag FROM translations WHERE id = ? AND enabled = 1", [$lang_id]);
	} catch (Exception $e) {
		print json_encode(['error' => _("Invalid language.")]);
		exit;
	}
	if (!$tr) {
		print json_encode(['error' => _("Invalid language.")]);
		exit;
	}
	$_SESSION['lang_id'] = $lang_id;
} else {
	# reset to default (English)
	unset($_SESSION['lang_id']);
	$tr = null;
}

# optionally persist to DB
if (!empty($_POST['persist']) && $user !== null) {
	try {
		$Database->updateObject("users", ['id' => $user->id, 'lang_id' => ($lang_id > 0 ? $lang_id : null)]);
	} catch (Exception $e) { /* non-fatal */ }
}

print json_encode(['success' => true, 'lang_id' => $lang_id]);
