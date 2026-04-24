<?php

#
# Import certificate - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant (false, true);

# strip tags - exclude certificate field (PEM contains newlines/slashes)
$_POST_safe = $User->strip_input_tags ($_POST);
$_POST['tenant']   = $_POST_safe['tenant'];
$_POST['zone_id']  = $_POST_safe['zone_id'];

# validate zone_id
if(!$Common->validate_int($_POST['zone_id']))
$Result->show("danger", _("Invalid zone") . ".", true, false, false, false);

# fetch zone
$zone = $Zones->get_zone_raw ($_POST['zone_id']);
if(is_null($zone))
$Result->show("danger", _("Invalid zone") . ".", true, false, false, false);

# fetch tenant
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
if(is_null($tenant))
$Result->show("danger", _("Invalid tenant") . ".", true, false, false, false);

# validate zone belongs to tenant
if($zone->t_id != $tenant->id)
$Result->show("danger", _("Access denied") . ".", true, false, false, false);

# validate certificate input
$cert_pem       = trim($_POST['certificate']);
$pkey_pem       = trim($_POST['pkey_pem']       ?? '');
$pkey_passphrase = $_POST['pkey_passphrase']    ?? '';
if(strlen($cert_pem) == 0)
$Result->show("danger", _("Certificate is required") . ".", true, false, false, false);

# parse certificate
$cert_parsed = openssl_x509_parse($cert_pem);
if($cert_parsed === false)
$Result->show("danger", _("Cannot parse certificate. Please provide a valid PEM-encoded certificate."), true, false, false, false);

# extract serial and expiry
$serial  = $cert_parsed['serialNumber'];
$expires = date("Y-m-d H:i:s", $cert_parsed['validTo_time_t']);

# check for duplicate (zone_id + serial is unique)
$existing = $Database->getObjectQuery("SELECT id FROM certificates WHERE z_id = :z_id AND serial = :serial", ['z_id' => $zone->id, 'serial' => $serial]);
if(!is_null($existing))
$Result->show("danger", _("A certificate with this serial number already exists in this zone."), true, false, false, false);

# validate and store private key if provided
$pkey_id = null;
if (!empty($pkey_pem)) {
	global $private_key_encryption_key;
	if (empty($private_key_encryption_key[$tenant->id])) {
		$Result->show("danger", _("Private key encryption is not configured for this tenant."), true, false, false, false);
	}
	$pkey_res = openssl_pkey_get_private($pkey_pem, $pkey_passphrase);
	if ($pkey_res === false) {
		$Result->show("danger", _("Cannot parse private key. Check the passphrase if encrypted."), true, false, false, false);
	}
	if (!$Certificates->pkey_matches_cert($pkey_pem, $cert_pem, $pkey_passphrase ?: null)) {
		$Result->show("danger", _("Private key does not match the certificate."), true, false, false, false);
	}
	$encrypted = $Certificates->pkey_encrypt($pkey_pem, $tenant->id);
	if ($encrypted === null) {
		$Result->show("danger", _("Private key encryption failed."), true, false, false, false);
	}
	$Database->runQuery("INSERT INTO pkey (private_key_enc) VALUES (?)", [$encrypted]);
	$pkey_id = $Database->lastInsertId();
}

# insert certificate
try {
	$insert = [
		"z_id"        => $zone->id,
		"t_id"        => $tenant->id,
		"serial"      => $serial,
		"certificate" => $cert_pem,
		"expires"     => $expires,
		"is_manual"   => 1,
	];
	if ($pkey_id !== null) $insert['pkey_id'] = $pkey_id;
	$new_id = $Database->insertObject("certificates", $insert);
	# refetch for logging
	$new_cert = $Database->getObject("certificates", $new_id);
	# Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("certificates", $new_id, $tenant->id, $user->id, "add", true, "Certificate serial " . $serial . " manually imported into zone " . $zone->name, NULL, json_encode(["certificates" => ["0" => $new_cert]]));
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

$Result->show("success", _("Certificate imported successfully."), false, false, false, true);
