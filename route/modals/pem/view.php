<?php

/**
 * Modal: show PEM-encoded material for a CA, CSR or certificate.
 *
 * GET: one of
 *   ca_id   — CA certificate (+ private key)
 *   csr_id  — signed certificate, CSR (+ private key)
 *   cert_id — certificate (+ private key); with part=chain — each certificate of the stored chain
 *
 * Whatever is present is shown. The private key is only decrypted and shown
 * for admins / permission level 3+, matching the .key download endpoints.
 * Access scoping mirrors the corresponding download endpoints.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$ca_id   = (int)($_GET['ca_id']   ?? 0);
$csr_id  = (int)($_GET['csr_id']  ?? 0);
$cert_id = (int)($_GET['cert_id'] ?? 0);
$part    = ($_GET['part'] ?? '') === 'chain' ? 'chain' : 'certificate';

$is_admin  = $user->admin == "1";
$t_id      = (int)$user->t_id;
$can_pkey  = $is_admin || (int)$user->permission >= 3;
$title     = _("Show PEM");
$blocks    = [];   // [label, pem|null, notice|null]
$error     = null;

/**
 * Fetch a certificate row with tenant scoping.
 * Certificates in private zones are only returned to the zone creator, and never during impersonation.
 */
$fetch_cert = function (int $id) use ($Database, $is_admin, $t_id, $user) {
	$cert = $is_admin
		? $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$id])
		: $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$id, $t_id]);
	if ($cert) {
		$zone = $Database->getObjectQuery("SELECT private_zone_uid FROM zones WHERE id = ?", [(int)$cert->z_id]);
		if ($zone && !empty($zone->private_zone_uid)
			&& ($zone->private_zone_uid != $user->id || isset($_SESSION['impersonate_original']))) {
			return null;
		}
	}
	return $cert;
};

/** Append a private key block for $pkey_id (owned by tenant $owner_t_id), if a key is stored. */
$add_pkey = function ($pkey_id, int $owner_t_id) use (&$blocks, $Database, $Certificates, $can_pkey) {
	if (empty($pkey_id)) { return; }
	$row = $Database->getObjectQuery("SELECT private_key_enc FROM pkey WHERE id = ?", [(int)$pkey_id]);
	if (!$row || empty($row->private_key_enc)) { return; }
	if (!$can_pkey) {
		$blocks[] = [_("Private key"), null, _("Insufficient permissions")];
		return;
	}
	$pem = $Certificates->pkey_decrypt($row->private_key_enc, $owner_t_id);
	$blocks[] = ($pem === false || empty($pem))
		? [_("Private key"), null, _("Failed to decrypt private key.")]
		: [_("Private key"), $pem, null];
};

if ($ca_id > 0) {
	$ca = $is_admin
		? $Database->getObjectQuery("SELECT * FROM cas WHERE id = ?", [$ca_id])
		: $Database->getObjectQuery("SELECT * FROM cas WHERE id = ? AND t_id = ?", [$ca_id, $t_id]);
	if (!$ca) {
		$error = _("CA not found.");
	} else {
		$title = htmlspecialchars($ca->name);
		if (!empty($ca->certificate)) {
			$blocks[] = [_("Certificate"), $ca->certificate, null];
		}
		$add_pkey($ca->pkey_id, (int)$ca->t_id);
	}
}
elseif ($csr_id > 0) {
	$csr = $is_admin
		? $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id])
		: $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $t_id]);
	if (!$csr) {
		$error = _("CSR not found.");
	} else {
		$title = htmlspecialchars($csr->cn);
		if (!empty($csr->cert_id)) {
			$cert = $fetch_cert((int)$csr->cert_id);
			if ($cert && !empty($cert->certificate)) {
				$blocks[] = [_("Certificate"), $cert->certificate, null];
			}
		}
		if (!empty($csr->csr_pem)) {
			$blocks[] = [_("Certificate Signing Request"), $csr->csr_pem, null];
		}
		$add_pkey($csr->pkey_id, (int)$csr->t_id);
	}
}
elseif ($cert_id > 0) {
	$cert = $fetch_cert($cert_id);
	if (!$cert || empty($cert->certificate)) {
		$error = _("Certificate not found.");
	} else {
		$parsed   = @openssl_x509_parse($cert->certificate);
		$cn       = $parsed['subject']['CN'] ?? ('cert_' . $cert_id);
		$title    = htmlspecialchars($cn);
		if ($part === 'chain') {
			$title .= " — " . _("Chain");
			preg_match_all('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', (string)$cert->chain, $m);
			foreach ($m[0] as $n => $chain_pem) {
				$p        = @openssl_x509_parse($chain_pem);
				$label    = ($n + 1) . ". " . htmlspecialchars($p['subject']['CN'] ?? $p['subject']['O'] ?? _("Certificate"));
				$blocks[] = [$label, $chain_pem, null];
			}
		} else {
			$blocks[] = [_("Certificate"), $cert->certificate, null];
			$add_pkey($cert->pkey_id ?? null, (int)$cert->t_id);
		}
	}
}
else {
	$error = _("Invalid request.");
}

if ($error === null && empty($blocks)) {
	$error = _("No PEM data available.");
}

if ($error !== null) {
	$Modal->modal_print(_("Show PEM"), "<div class='alert alert-danger'>" . $error . "</div>", "", "", false, "danger");
	exit;
}

$copy_icon = "<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z' /><path d='M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1' /></svg>";

$content = "";
foreach ($blocks as $i => [$label, $pem, $notice]) {
	if ($pem === null) {
		$content .= "<div class='mb-3'><small class='text-muted text-uppercase fw-bold d-block mb-1'>" . $label . "</small>";
		$content .= "<div class='alert alert-warning py-2 mb-0'>" . $notice . "</div></div>";
		continue;
	}
	$pem   = trim($pem) . "\n";
	$rows  = min(30, max(6, substr_count($pem, "\n") + 1));
	$content .= "<div class='mb-3'>";
	$content .= "<div class='d-flex align-items-center justify-content-between mb-1'>";
	$content .= "<small class='text-muted text-uppercase fw-bold'>" . $label . "</small>";
	$content .= "<button type='button' class='btn btn-sm bg-info-lt text-info pem-copy' data-target='pem-block-{$i}'>{$copy_icon} <span>" . _("Copy") . "</span></button>";
	$content .= "</div>";
	$content .= "<textarea id='pem-block-{$i}' class='form-control font-monospace' style='font-size:11px' rows='{$rows}' readonly spellcheck='false'>" . htmlspecialchars($pem) . "</textarea>";
	$content .= "</div>";
}

$Modal->modal_print($title, $content, "", "", false, "info");
?>
<script>
$('.pem-copy').on('click', function () {
	var $btn  = $(this);
	var el    = document.getElementById($btn.data('target'));
	var $lbl  = $btn.find('span');
	var orig  = $lbl.text();
	var done  = function () {
		$lbl.text(<?php print json_encode(_("Copied!")); ?>);
		setTimeout(function () { $lbl.text(orig); }, 1500);
	};
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(el.value).then(done);
	} else {
		el.select();
		document.execCommand('copy');
		done();
	}
});
</script>
