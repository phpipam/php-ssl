<?php

/**
 *
 * Class to work with certificates
 *
 *
 */
class Certificates extends Common
{

	/**
	 * Database holder
	 * @var Database_PDO
	 */
	private $Database;

	/**
	 * User details
	 * @var object
	 */
	private $user;

	/**
	 * Array of ignored issuers
	 * @var array
	 */
	private $ignored_issuers = [];



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 * @param  object $user
	 */
	public function __construct(Database_PDO $Database, object $user = NULL)
	{
		// Save database object
		$this->Database = $Database;
		// user
		if (is_object($user)) {
			$this->user = $user;
		}
	}

	/**
	 * Returns all certificates in database
	 * @method get_all
	 * @param  bool $orphaned_also
	 * @return array
	 */
	public function get_all($orphaned_also = false)
	{
		// fetch
		try {
			if (!$orphaned_also) {
				if (is_object($this->user) && $this->user->admin == "1") {
					$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zname from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id JOIN hosts as h ON h.c_id = c.id order by expires asc");
				}
				else {
					$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zname from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id JOIN hosts as h ON h.c_id = c.id and t.id = ? order by expires asc", [$this->user->t_id]);
				}
			}
			else {
				if (is_object($this->user) && $this->user->admin == "1") {
					$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zname from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id order by expires asc");
				}
				else {
					$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zname from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id and t.id = ? order by expires asc", [$this->user->t_id]);
				}
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex
		if (sizeof($certs) > 0) {
			$certs_new = [];
			foreach ($certs as $t) {
				$certs_new[$t->id] = $t;
			}
			$certs = $certs_new;
		}
		// return
		return $certs;
	}

	/**
	 * Returns expired and expired soon certificates
	 * @method get_expired
	 * @param  int $days
	 * @param  int $expired_days
	 * @param  bool $exclude_muted
	 * @return array
	 */
	public function get_expired($days = 30, $expired_days = 7, $exclude_muted = false)
	{
		// default to 30 and 7 if not set !
		if (!is_numeric($days))
			$days = 30;
		if (!is_numeric($expired_days))
			$expired_days = 7;

		// set from date
		$from_date = date("Y-m-d H:i:s", strtotime("+$days days")); // expire in next x days
		$expired_from_date = date("Y-m-d H:i:s", strtotime("-$expired_days days")); // expired in last y days
		// private zone filter
		$impersonating = isset($_SESSION['impersonate_original']);
		$pz_clause = $impersonating ? "and z.private_zone_uid is null" : "and (z.private_zone_uid is null or z.private_zone_uid = ".(int)$this->user->id.")";
		// mute filter
		$mute_clause = $exclude_muted ? "and (h.mute = 0 or h.id is null)" : "";
		// fetch
		try {
			// keep rows with a current host, plus host-less rows only for manually imported certs
			// (a host-less non-manual cert is just a superseded/replaced scan result, not a real orphan)
			$orphan_clause = "and (h.id is not null or c.is_manual = 1)";
			if ($this->user->admin == "1") {
				$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zone_name,z.private_zone_uid as private_zone_uid from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id LEFT JOIN hosts as h ON h.c_id = c.id where c.expires < ? and c.expires > ? $pz_clause $orphan_clause $mute_clause order by expires asc", [$from_date, $expired_from_date]);
			}
			else {
				$certs = $this->Database->getObjectsQuery("select *,c.id as id,z.name as zone_name,z.private_zone_uid as private_zone_uid from certificates as c JOIN zones as z ON c.z_id = z.id JOIN tenants as t ON z.t_id = t.id LEFT JOIN hosts as h ON h.c_id = c.id where t.id = ? and c.expires < ? and c.expires > ? $pz_clause $orphan_clause $mute_clause order by expires asc", [$this->user->t_id, $from_date, $expired_from_date]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex, collecting all hosts per cert
		if (sizeof($certs) > 0) {
			$certs_new = [];
			foreach ($certs as $t) {
				if (!isset($certs_new[$t->id])) {
					$certs_new[$t->id] = $t;
					$certs_new[$t->id]->hosts = [];
				}
				// LEFT JOIN: certs with no assigned host (e.g. manually imported) yield a null hostname
				if ($t->hostname !== null) {
					$certs_new[$t->id]->hosts[] = (object)['hostname' => $t->hostname, 'port' => $t->port];
				}
			}
			$certs = $certs_new;
		}
		// return
		return $certs;
	}

	/**
	 * Count how many certs will expire by zone
	 * @method count_expired_by_zone
	 * @param  int $zone_id
	 * @param  int $days
	 * @return int
	 */
	public function count_expired_by_zone($zone_id = 0, $days = 30)
	{
		// set from date
		$from_date = date("Y-m-d H:i:s", strtotime("+$days days"));
		// fetch
		try {
			$cnt = $this->Database->getObjectQuery("select count(distinct(c_id)) as cnt from hosts as h JOIN certificates as c ON h.c_id = c.id and h.z_id = ? and c.expires < ?", [$zone_id, $from_date]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $cnt->cnt;
	}

	/**
	 * Returns single certificate
	 * @method get_certificate
	 * @param  string $serial
	 * @param  array $href
	 * @return object
	 */
	public function get_certificate($serial = "", $href = "", $join = true)
	{
		// fetch
		try {
			// admins, ignore href
			if ($this->user->admin == "1") {
				if ($join)
					$cert = $this->Database->getObjectQuery("select *,c.id as id from certificates as c JOIN tenants as t ON c.t_id = t.id and c.serial = ?", [$serial]);
				else
					$cert = $this->Database->getObjectQuery("select * from certificates where serial = ?", [$serial]);
			}
			else {
				if ($join)
					$cert = $this->Database->getObjectQuery("select *,c.id as id from certificates as c JOIN tenants as t ON c.t_id = t.id and c.serial = ? and t.href = ?", [$serial, $href]);
				else
					$cert = $this->Database->getObjectQuery("select * from certificates where serial = ?", [$serial]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $cert;
	}

	/**
	 * All certificates for zone
	 * @method get_certificate_from_zone
	 * @param  string $serial
	 * @param  string $href
	 * @param  int $zone_id
	 * @return object|false
	 */
	public function get_certificate_from_zone($serial = "", $href = "", $zone_id = 0)
	{
		// fetch
		try {
			// admins, ignore href
			if ($this->user->admin == "1") {
				$cert = $this->Database->getObjectQuery("select *,c.id as id from certificates as c JOIN tenants as t ON c.t_id = t.id and c.z_id = ? and c.serial = ?", [$zone_id, $serial]);
			}
			else {
				$cert = $this->Database->getObjectQuery("select *,c.id as id from certificates as c JOIN tenants as t ON c.t_id = t.id and c.z_id = ? and c.serial = ? and t.href = ?", [$zone_id, $serial, $href]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $cert;
	}

	/**
	 * Get all hosts attached to certificate
	 * @method get_certificate_hosts
	 * @param  int $cert_id Certificate ID
	 * @return array Array of host objects with zone information
	 */
	public function get_certificate_hosts($cert_id = 0)
	{
		// fetch
		try {
			$hosts = $this->Database->getObjectsQuery("select * from hosts as h JOIN zones as z ON h.z_id = z.id and h.c_id = ? group by hostname", [$cert_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $hosts;
	}

	/**
	 * Parses received certificate
	 * @method parse_cert
	 * @param  string $certificate
	 * @return array
	 */
	public function parse_cert($certificate = "")
	{
		# parse
		$certificate_parsed = openssl_x509_parse($certificate);

		# false
		if ($certificate_parsed === false) {
			$certificate_parsed = [];
			$certificate_parsed['serialNumberHex'] = "/";
			$certificate_parsed['subject']['CN'] = "/";
			$certificate_parsed['issuer']['CN'] = "/";
			$certificate_parsed['custom_validTo'] = "/";
			$certificate_parsed['custom_validAllDays'] = "/";
			$certificate_parsed['custom_validDays'] = "/";
		}
		else {
			// validity
			$certificate_parsed['custom_validFrom'] = date("Y-m-d H:i:s", $certificate_parsed['validFrom_time_t']);
			$certificate_parsed['custom_validTo'] = date("Y-m-d H:i:s", $certificate_parsed['validTo_time_t']);
			$certificate_parsed['custom_validAllDays'] = round((strtotime(date("Y-m-d", $certificate_parsed['validTo_time_t'])) - strtotime(date("Y-m-d", $certificate_parsed['validFrom_time_t']))) / (60 * 60 * 24));
			$certificate_parsed['custom_validDays'] = round((strtotime(date("Y-m-d", $certificate_parsed['validTo_time_t'])) - time()) / (60 * 60 * 24));

			// purposes
			foreach ($certificate_parsed['purposes'] as $purp) {
				$certificate_parsed['custom_purposes'][$purp['2']] = $purp['0'] == 1 ? "Yes" : "No";
			}
		}

		// return
		return $certificate_parsed;
	}

	/**
	 * Get certificate status
	 * @method get_status
	 * @param  array|bool $cert_parsed Parsed certificate array or false
	 * @param  bool $text Whether to return text-only status
	 * @param  bool $validate_domain Whether to validate domain against certificate
	 * @param  string $domain Domain to validate
	 * @return array Array with 'code' (int) and 'text' (string) keys
	 */
	public function get_status($cert_parsed = false, $text = false, $validate_domain = false, $domain = "")
	{
		// get status from integer
		$status_int = $this->get_status_int($cert_parsed, $validate_domain, $domain);
		// return text
		$status_text = $this->get_status_text($status_int, $text);

		// prepare return array
		return [
			"code" => $status_int,
			"text" => $status_text
		];
	}

	/**
	 * Returns status integer from certificate
	 * @method get_status_int
	 * @param  array|bool $cert_parsed Parsed certificate array or false
	 * @param  bool $validate_domain Whether to validate domain against certificate
	 * @param  string $domain Domain to validate
	 * @return int Status code: 0=unknown, 1=expired, 2=expires soon, 3=valid, 10=domain mismatch, 11=self-signed
	 */
	public function get_status_int($cert_parsed = false, $validate_domain = false, $domain = "")
	{
		// set days
		$days = isset($cert_parsed['custom_validDays']) ? $cert_parsed['custom_validDays'] : 0;
		// set max days
		$max_days = $this->get_expired_day_range();

		// check date
		if (!is_numeric($days)) {
			return 0;
		} // unknown
		// check if cert or altnames are covering requested domain
		if ($validate_domain) {
			if ($this->validate_cert_domain_validity($domain, $cert_parsed) === false) {
				return 10;
			}
		}
		// check if certificate is self-signed (subject matches issuer)
		if ($this->is_self_signed($cert_parsed)) {
			return 11;
		}

		// result
		if ($days < 0) {
			return 1;
		} // expired
		if ($days <= $max_days) {
			return 2;
		} // expire soon
		if ($days > $max_days) {
			return 3;
		} // valid
		else {
			return 0;
		}
	}

	/**
	 * Returns Tabler color class name for a status code (e.g. 'red', 'orange', 'green', 'secondary')
	 * @method get_status_color
	 * @param  int $status_int
	 * @return string
	 */
	public function get_status_color($status_int = 0)
	{
		switch ($status_int) {
			case 1:  return 'red';
			case 2:  return 'orange';
			case 3:  return 'green';
			case 10: return 'red';
			case 11: return 'orange';
			default: return 'secondary';
		}
	}

	/**
	 * Returns translated status name for a status code
	 * @method get_status_label
	 * @param  int $status_int
	 * @return string
	 */
	public function get_status_label($status_int = 0)
	{
		switch ($status_int) {
			case 1:  return _("Expired");
			case 2:  return _("Expires soon");
			case 3:  return _("Valid");
			case 10: return _("Domain mismatch");
			case 11: return _("Self-signed");
			default: return _("Unknown");
		}
	}

	/**
	 * Has the current user enabled color-blind friendly status indicators?
	 * (profile → Layout → Accessibility; stored by the Layout class)
	 * Always false outside a web request (cron, mails).
	 * @method accessible_status
	 * @return bool
	 */
	public function accessible_status()
	{
		global $Layout;
		return isset($Layout) && $Layout instanceof Layout && $Layout->is_accessible_status();
	}

	/**
	 * Returns a status icon (Tabler outline icon, inherits text color) — a different
	 * shape per status so status does not depend on color alone.
	 * @method get_status_icon
	 * @param  int $status_int
	 * @return string  SVG markup (aria-hidden — the caller provides the label)
	 */
	public function get_status_icon($status_int = 0)
	{
		switch ($status_int) {
			// valid — circle-check
			case 3:  $paths = '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" />'; break;
			// expires soon — clock
			case 2:  $paths = '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" />'; break;
			// expired — circle-x
			case 1:  $paths = '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M10 10l4 4m0 -4l-4 4" />'; break;
			// domain mismatch — link-off
			case 10: $paths = '<path d="M9 15l3 -3m2 -2l1 -1" /><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" /><path d="M3 3l18 18" /><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" />'; break;
			// self-signed — alert-triangle
			case 11: $paths = '<path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />'; break;
			// unknown — help-circle
			default: $paths = '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 16v.01" /><path d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483" />';
		}
		return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon status-icon" aria-hidden="true" focusable="false"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>' . $paths . '</svg>';
	}

	/**
	 * Returns a compact status indicator for tables:
	 * a colored dot by default, or a colored per-status icon when the user
	 * enabled color-blind friendly status indicators.
	 * @method get_status_indicator
	 * @param  int $status_int
	 * @return string
	 */
	public function get_status_indicator($status_int = 0)
	{
		$label = htmlspecialchars($this->get_status_label($status_int), ENT_QUOTES);
		$color = $this->get_status_color($status_int);

		if ($this->accessible_status()) {
			return "<span class='status-indicator text-$color' role='img' aria-label='$label' title='$label'>" . $this->get_status_icon($status_int) . "</span>";
		}
		$bg = $status_int == 0 ? "" : " bg-$color";
		return "<span class='badge$bg' role='img' aria-label='$label' title='$label'></span>";
	}

	/**
	 * Returns certificate status in text - badge
	 * @method get_status_text
	 * @param  int $status_int
	 * @param  bool $text
	 * @return string
	 */
	public function get_status_text($status_int = 0, $text = false)
	{
		// span status
		$span_hidden = $text === true ? "" : "visually-hidden";
		$status_class = $text === true ? "" : "status";
		// color-blind friendly mode: per-status icon in front of the label
		$icon = $this->accessible_status() ? $this->get_status_icon($status_int) : "";

		// return
		if ($status_int == 10) {
			return "<span class='badge bg-red-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Domain mismatch") . "'>" . $icon . " <span class='$span_hidden'>" . _("Domain mismatch") . "</span></span> ";
		}
		if ($status_int == 11) {
			return "<span class='badge bg-orange-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Self-signed") . "'>" . $icon . " <span class='$span_hidden'>" . _("Self-signed") . "</span></span> ";
		}
		if ($status_int == 0) {
			return "<span class='badge bg-light-lt $status_class bg-light text-muted' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Unknown") . "'>" . $icon . " <span class='$span_hidden'>" . _("Unknown") . "</span></span> ";
		}
		if ($status_int == 1) {
			return "<span class='badge bg-red-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Expired") . "'>" . $icon . " <span class='$span_hidden'>" . _("Expired") . "</span></span> ";
		}
		if ($status_int == 2) {
			return "<span class='badge bg-orange-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Expires soon") . "'>" . $icon . " <span class='$span_hidden'>" . _("Expires soon") . "</span></span> ";
		}
		if ($status_int == 3) {
			return "<span class='badge bg-green-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _("Valid") . "'>" . $icon . " <span class='$span_hidden'>" . _("Valid") . "</span></span> ";
		}

		// default
		return "<span class='badge bg-green-lt $status_class' data-bs-toggle='tooltip' data-bs-placement='left' title='" . _($text) . "'>" . $icon . " <span class='$span_hidden'>" . _($text) . "</span></span> ";

	}

	/**
	 * Gets days when cert will be expired
	 * @method get_expired_day_range
	 * @return int
	 */
	private function get_expired_day_range()
	{
		global $user, $expired_days;
		return isset($user->days) ? $user->days : (isset($expired_days) ? $expired_days : 30);
	}

	/**
	 * Check if certificate is valid for specific domain
	 * @method validate_cert_domain_validity
	 * @param  string $domain
	 * @param  array $cert_parsed
	 * @return bool
	 */
	private function validate_cert_domain_validity($domain = "", $cert_parsed = [])
	{
		// valid flag
		$valid = false;
		// to lower
		$domain = strtolower($domain);

		// valid domains
		$valid_domains = [];
		// subject
		$valid_domains[] = $cert_parsed['subject']['CN'];
		// altnames
		$altnames = explode(",", $cert_parsed['extensions']['subjectAltName']);
		if (is_array($altnames)) {
			foreach ($altnames as $n) {
				$valid_domains[] = substr($n, strpos($n, ":") + 1);
			}
		}

		// unique
		$valid_domains = array_values(array_unique($valid_domains));

		// loop and check validity
		foreach ($valid_domains as $d) {
			// to lower, case insensitive
			$d = strtolower($d);
			// same ?
			if ($d == $domain) {
				return true;
			}
			// wildcard
			elseif (strpos($d, "*.") === 0) {
				$wd_domain = substr($d, "1");
				$found_host = str_replace($wd_domain, "", $domain);

				// check
				if (substr_count($found_host, ".") === 0 || "." . $found_host == $wd_domain) {
					return true;
				}
			}
		}

		// false
		return false;
	}

	/**
	 * Check if certificate is self-signed (subject and issuer are identical)
	 * @method is_self_signed
	 * @param  array|bool $cert_parsed
	 * @return bool
	 */
	private function is_self_signed($cert_parsed = false)
	{
		if (empty($cert_parsed['subject']) || empty($cert_parsed['issuer'])) {
			return false;
		}
		// Strip custom keys added by route files (e.g. CN_all) before comparing
		$standard_keys = ['C', 'ST', 'L', 'O', 'OU', 'CN', 'emailAddress', 'serialNumber'];
		$subject = array_intersect_key($cert_parsed['subject'], array_flip($standard_keys));
		$issuer  = array_intersect_key($cert_parsed['issuer'],  array_flip($standard_keys));
		return $subject === $issuer;
	}

	/**
	 * List of allowed certificate formats
	 * @method allowed_cert_formats
	 * @return array
	 */
	public function allowed_cert_formats()
	{
		return [
			"crt" => "PEM encoded public certificate (ASCII) - crt",
			"cer" => "DER encoded public certificate (binary) - cer"
		];
	}

	/**
	 * Check if certificate format is valid
	 * @method validate_cert_format
	 * @param  string $format
	 * @return bool
	 */
	public function validate_cert_format($format = "crt")
	{
		return array_key_exists($format, $this->allowed_cert_formats()) ? true : false;
	}

	/**
	 * Detects certificate format from content
	 * @method detect_cert_format
	 * @param  string $certificate Certificate content
	 * @return string|false Format identifier ('crt', 'cer', 'p12') or false if unknown
	 */
	private function detect_cert_format($certificate = "")
	{
		// crt / pem
		if ($this->is_format_ascii($certificate) !== false) {
			return "crt";
		}
		// cer / der
		elseif ($this->is_format_der($certificate) !== false) {
			return "cer";
		}
		// p12 / pfx
		elseif ($this->is_format_pfx($certificate) !== false) {
			return "p12";
		}
		// default
		else {
			return false;
		}
	}

	/**
	 *  Checks if format is PEM encoded ASCII
	 *
	 *  PEM encoded (ASCII encoding), x509 certificates. ---- BEGIN CERTIFICATE -----
	 *  The .pem file can include the server certificate, the intermediate certificate and the private key in a single file.
	 *  The server certificate and intermediate certificate can also be in a separate .crt or .cer file. The private key can be in a .key file.
	 *
	 *
	 * @method is_format_ascii
	 * @param  string $certificate
	 * @return bool
	 */
	private function is_format_ascii($certificate = "")
	{
		// for now we only return crt, maybe it can be extended in future in case we have pkeys also :)
		return openssl_x509_parse($certificate) == false ? false : "crt";


	//$cert_res_pri = openssl_pkey_get_private($certificate, "");
	//$cert_res_pub = openssl_x509_read($certificate);
	}

	/**
	 *  Checks if format is DER
	 *
	 *  The DER certificates are in binary form, contained in .der or .cer files. These certificates are mainly used in Java-based web servers.
	 *
	 *
	 * @method is_format_der
	 * @param  string $certificate
	 * @return bool
	 */
	private function is_format_der($certificate = "")
	{
		// get PEM pubkey
		if (openssl_x509_export($certificate, $exported_pub) === false) {
			return false;
		}
		else {
			return true;
		// remove BEGIN / END Certificate
		//$exported_pub = str_replace("-----BEGIN CERTIFICATE-----" . PHP_EOL, "", $exported_pub);
		//$exported_pub = str_replace("-----END CERTIFICATE-----" . PHP_EOL, "", $exported_pub);

		//$content = wordwrap(base64_decode($exported_pub), 64, "\r\n", true);
		}
	}


	/**
	 * Checks for p12 / PFX format
	 *
	 *  The PKCS#12 certificates are in binary form, contained in .pfx or .p12 files.
	 *  The PKCS#12 can store the server certificate, the intermediate certificate and the private key in a single .pfx file with password protection.
	 *
	 *
	 * @method is_format_pfx
	 * @param  string $certificate
	 * @return bool
	 */
	private function is_format_pfx($certificate = "")
	{
		return false;

	// parse
	//openssl_pkey_export($cert_res_pri, $exported_pri, $_GET['key']);
	//openssl_x509_export($cert_res_pub, $exported_pub); // binary to ascii (PEM)
	// export to p12
	//openssl_pkcs12_export($cert_res_pub, $content, $cert_res_pri, $_GET['key']);
	}

	/**
	 * Reformat certificate to different format
	 *
	 * @method cert_reformat
	 * @param  string $curr_cert_content Current certificate content
	 * @param  string $req_format Requested format ('crt' or 'cer')
	 * @return string|false Reformatted certificate content or false on error
	 */
	public function cert_reformat($curr_cert_content = "", $req_format = "crt")
	{
		// validate format
		if (!$this->validate_cert_format($req_format)) {
			return false;
		}
		// detect current format
		$curr_format = $this->detect_cert_format($curr_cert_content);

		// if same return
		if ($curr_format == $req_format) {
			return $curr_cert_content;
		}
		// reformat
		else {
			return $this->reformat_certificate($curr_cert_content, $curr_format, $req_format);
		}
	}

	/**
	 * Reformats certificate to new format
	 *
	 * @method reformat_certificate
	 * @param  string $certificate Certificate content
	 * @param  string $curr_format Current format ('crt', 'cer', or 'p12')
	 * @param  string $req_format Requested format ('crt', 'cer', or 'p12')
	 * @return string|false Reformatted certificate or false (currently not implemented)
	 */
	private function reformat_certificate($certificate = "", $curr_format = "crt", $req_format = "crt")
	{
		return false;
	}




	/**
	 * Returns full list of all ignored issuers
	 * @method get_all_ignored_issuers
	 * @param  int|null $t_id Tenant ID (null to use current user's tenant)
	 * @return array Array of ignored issuer objects
	 */
	public function get_all_ignored_issuers($t_id = NULL)
	{
		// set tenant
		$t_id = is_null($t_id) ? $this->user->t_id : $t_id;

		// fetch from cas table — only rows with at least one notification flag set
		try {
			if (!isset($this->user) || $this->user === null || $this->user->admin == "1") {
				$ignored = $this->Database->getObjectsQuery(
					"SELECT * FROM cas WHERE ski IS NOT NULL AND (ignore_updates = 1 OR ignore_expiry = 1)"
				);
			}
			else {
				$ignored = $this->Database->getObjectsQuery(
					"SELECT * FROM cas WHERE ski IS NOT NULL AND t_id = ? AND (ignore_updates = 1 OR ignore_expiry = 1)",
					[$t_id]
				);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// save for later checks to avoid multiple db checks
		if (sizeof($ignored) > 0) {
			foreach ($ignored as $i) {
				$this->ignored_issuers[$i->t_id][$i->ski] = $i;
			}
		}

		// return
		return $ignored;
	}

	/**
	 * Checks if specific issuer is ignored
	 * @method is_issuer_ignored
	 * @param  string $ski
	 * @param  int $t_id
	 * @param  string|null $type  'update'|'expired' (mapped to cas columns ignore_updates|ignore_expiry)
	 * @return bool
	 */
	public function is_issuer_ignored($ski = "", $t_id = 0, $type = null)
	{
		if (array_key_exists($t_id, $this->ignored_issuers)) {
			if (array_key_exists(trim($ski), $this->ignored_issuers[$t_id])) {
				if ($type === null) {
					return true;
				}
				// map legacy type names to cas column names
				$col = ($type === 'update') ? 'ignore_updates' : (($type === 'expired') ? 'ignore_expiry' : $type);
				return (bool)$this->ignored_issuers[$t_id][trim($ski)]->$col;
			}
		}
		// not found - default
		return false;
	}


	// ── Private key encryption ────────────────────────────────────────────────

	/**
	 * Derives a 32-byte AES key from the per-tenant config string.
	 * Returns null if no key is configured for the given tenant.
	 */
	private function pkey_get_enc_key(int $t_id): ?string
	{
		global $private_key_encryption_key;
		if (empty($private_key_encryption_key[$t_id])) {
			return null;
		}
		return hash('sha256', $private_key_encryption_key[$t_id], true);
	}

	/**
	 * Encrypts a PEM private key string with AES-256-GCM.
	 * Stored format: base64( iv[12] . tag[16] . ciphertext )
	 * Returns null if tenant has no encryption key configured.
	 */
	public function pkey_encrypt(string $pem, int $t_id): ?string
	{
		$key = $this->pkey_get_enc_key($t_id);
		if ($key === null) {
			return null;
		}
		$iv  = random_bytes(12);
		$tag = '';
		$ct  = openssl_encrypt($pem, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
		if ($ct === false) {
			return null;
		}
		return base64_encode($iv . $tag . $ct);
	}

	/**
	 * Decrypts a value produced by pkey_encrypt().
	 * Returns false on failure (wrong key, tampered data, etc.).
	 */
	public function pkey_decrypt(string $stored, int $t_id)
	{
		$key = $this->pkey_get_enc_key($t_id);
		if ($key === null) {
			return false;
		}
		$raw = base64_decode($stored, true);
		if ($raw === false || strlen($raw) < 29) {
			return false;
		}
		$iv  = substr($raw, 0, 12);
		$tag = substr($raw, 12, 16);
		$ct  = substr($raw, 28);
		return openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
	}

	/**
	 * Checks whether a PEM private key matches the public key in a PEM certificate.
	 */
	public function pkey_matches_cert(string $private_key_pem, string $cert_pem, ?string $passphrase = null): bool
	{
		$priv = @openssl_pkey_get_private($private_key_pem, $passphrase ?? '');
		if (!$priv) {
			return false;
		}
		$pub_from_priv = openssl_pkey_get_details($priv)['key'] ?? '';
		$pub_from_cert = openssl_pkey_get_details(@openssl_pkey_get_public($cert_pem))['key'] ?? '';
		return !empty($pub_from_priv) && $pub_from_priv === $pub_from_cert;
	}
}
