<?php

#
# Assign certificate to host
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

$title = _("Assign certificate");

# validate host_id
if(!$Common->validate_int($_GET['host_id'])) {
	$content   = [$Result->show("danger", _("Invalid host"), false, false, true)];
	$btn_text  = "";
}
else {
	$host   = $Database->getObject("hosts", $_GET['host_id']);
	$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

	if(is_null($host) || is_null($tenant)) {
		$content  = [$Result->show("danger", _("Invalid host or tenant"), false, false, true)];
		$btn_text = "";
	}
	else {
		# fetch all certificates for this tenant
		$certs = $Database->getObjectsQuery(
			"SELECT c.id, c.serial, c.expires, c.is_manual, c.certificate, c.z_id, z.name as zone_name
			 FROM certificates c JOIN zones z ON c.z_id = z.id
			 WHERE c.t_id = :t_id ORDER BY c.expires DESC",
			['t_id' => $tenant->id]
		);

		// Score each cert: zone match (+2), hostname/SAN match (+4)
		$hostname = strtolower($host->hostname);
		function cert_matches_hostname(string $hostname, array $parsed): bool {
			$names = [];
			if (!empty($parsed['subject']['CN'])) {
				$names[] = strtolower($parsed['subject']['CN']);
			}
			$san_raw = $parsed['extensions']['subjectAltName'] ?? '';
			foreach (explode(',', $san_raw) as $part) {
				$part = trim($part);
				if (strpos($part, 'DNS:') === 0) $names[] = strtolower(substr($part, 4));
			}
			foreach ($names as $name) {
				if ($name === $hostname) return true;
				if (strpos($name, '*.') === 0) {
					$wildcard_base = substr($name, 2);
					$host_base     = substr($hostname, strpos($hostname, '.') + 1);
					if ($wildcard_base === $host_base) return true;
				}
			}
			return false;
		}

		$scored = [];
		foreach ($certs as $c) {
			$parsed = !empty($c->certificate) ? openssl_x509_parse($c->certificate) : false;
			$score  = 0;
			if ((int)$c->z_id === (int)$host->z_id)                    $score += 2;
			if ($parsed && cert_matches_hostname($hostname, $parsed))   $score += 4;
			$scored[] = ['cert' => $c, 'parsed' => $parsed, 'score' => $score];
		}
		usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

		$content   = [];
		$content[] = "<form id='modal-form'>";
		$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
		$content[] = "<input type='hidden' name='tenant' value='" . htmlspecialchars($_GET['tenant']) . "'>";
		$content[] = "<input type='hidden' name='host_id' value='" . $host->id . "'>";

		$content[] = "<table class='table table-condensed table-borderless align-middle table-sm'>";

		$content[] = "<tr>";
		$content[] = "	<th style='width:120px;'>" . _("Host") . "</th>";
		$content[] = "	<td><b>" . htmlspecialchars($host->hostname) . "</b></td>";
		$content[] = "</tr>";

		$content[] = "<tr>";
		$content[] = "	<th>" . _("Certificate") . "</th>";
		$content[] = "	<td>";

		if (sizeof($certs) == 0) {
			$content[] = "<span class='text-muted'>" . _("No certificates available for this tenant.") . "</span>";
			$btn_text  = "";
		}
		else {
			$content[] = "<select name='certificate_id' class='form-select form-select-sm'>";

			// Recommended group first
			$recommended_rows = array_filter($scored, fn($r) => $r['score'] >= 4);
			$other_rows       = array_filter($scored, fn($r) => $r['score'] < 4);

			if (!empty($recommended_rows)) {
				$content[] = "<optgroup label='\u{2605} " . _("Recommended") . "'>";
				foreach ($recommended_rows as $row) {
					$c           = $row['cert'];
					$parsed      = $row['parsed'];
					$cn          = ($parsed && !empty($parsed['subject']['CN'])) ? $parsed['subject']['CN'] : $c->serial;
					$expires_fmt = $c->expires ? date("Y-m-d", strtotime($c->expires)) : "/";
					$content[]   = "<option value='" . $c->id . "'>" . htmlspecialchars($cn) . " &mdash; exp: " . $expires_fmt . "</option>";
				}
				$content[] = "</optgroup>";
			}

			// Remaining certs grouped by zone
			$by_zone = [];
			foreach ($other_rows as $row) {
				$by_zone[$row['cert']->zone_name][] = $row;
			}
			foreach ($by_zone as $zone_name => $rows) {
				$content[] = "<optgroup label='" . htmlspecialchars($zone_name) . "'>";
				foreach ($rows as $row) {
					$c           = $row['cert'];
					$parsed      = $row['parsed'];
					$cn          = ($parsed && !empty($parsed['subject']['CN'])) ? $parsed['subject']['CN'] : $c->serial;
					$expires_fmt = $c->expires ? date("Y-m-d", strtotime($c->expires)) : "/";
					$content[]   = "<option value='" . $c->id . "'>" . htmlspecialchars($cn) . " &mdash; exp: " . $expires_fmt . "</option>";
				}
				$content[] = "</optgroup>";
			}

			$content[] = "</select>";
			$btn_text  = _("Assign certificate");
		}

		$content[] = "	</td>";
		$content[] = "</tr>";
		$content[] = "</table>";
		$content[] = "</form>";
	}
}

# print modal
$Modal->modal_print ($title, implode("\n", $content), isset($btn_text) ? $btn_text : "", "/route/modals/certificates/assign-submit.php", false, "success");
