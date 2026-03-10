<?php

/**
 *
 * Class for common functions
 *
 */
class Common extends Validate
{

	/**
	 * Check if config exists
	 * @method config_exists
	 * @return bool
	 */
	public function config_exists()
	{
		return file_exists(dirname(__FILE__) . "/../../config.php") ? true : false;
	}

	/**
	 * Prints a Tabler breadcrumb nav based on the current $_params.
	 * Last (active) item is not clickable; all preceding items are linked.
	 *
	 * URL structure: /{tenant}/{route}/{app}/{id1}/
	 *
	 * @method print_breadcrumbs
	 * @return void
	 */
	public function print_breadcrumbs(): void
	{
		global $_params;

		$tenant = $_params['tenant'] ?? '';
		$route  = $_params['route']  ?? 'dashboard';
		$app    = (isset($_params['app'])  && strlen($_params['app'])  > 0) ? $_params['app']  : null;
		$id1    = (isset($_params['id1'])  && strlen($_params['id1'])  > 0) ? $_params['id1']  : null;

		// Human-readable route names
		$route_labels = [
			'dashboard'    => _('Dashboard'),
			'zones'        => _('Zones'),
			'certificates' => _('Certificates'),
			'scanning'     => _('Scanning'),
			'logs'         => _('Logs'),
			'users'        => _('Users'),
			'tenants'      => _('Tenants'),
			'user'         => _('User'),
			'search'       => _('Search'),
			'fetch'        => _('Fetch'),
			'transform'    => _('Transform'),
			'ignored'      => _('Ignored issuers'),
		];

		// Human-readable app names for specific route/app combinations
		$sub_labels = [
			'scanning' => ['agents' => _('Scan agents'), 'portgroups' => _('Port groups'), 'cron' => _('Cron jobs')],
			'user'     => ['profile' => _('Profile')],
		];

		// Build items: [label, url]  — url===null means active/last (not clickable)
		$items = [];

		if ($route === 'dashboard') {
			$items[] = [null];
		} else {
			$items[] = [_(''), "/{$tenant}/dashboard/"];

			$route_label = $route_labels[$route] ?? ucfirst($route);

			if ($app === null) {
				$items[] = [$route_label, null];
			} else {
				$items[] = [$route_label, "/{$tenant}/{$route}/"];

				$app_label = isset($sub_labels[$route][$app])
					? $sub_labels[$route][$app]
					: htmlspecialchars($app, ENT_QUOTES, 'UTF-8');

				if ($id1 === null) {
					$items[] = [$app_label, null];
				} else {
					$items[] = [$app_label, "/{$tenant}/{$route}/{$app}/"];
					$items[] = [htmlspecialchars($id1, ENT_QUOTES, 'UTF-8'), null];
				}
			}
		}

		// Render — right-aligned, links in text-secondary, active item unstyled
		$html = "<ol class='breadcrumb justify-content-end' aria-label='breadcrumbs'>\n";
		foreach ($items as [$label, $url]) {
			if ($url !== null) {
				$html .= "  <li class='breadcrumb-item'><a class='text-secondary' href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'>{$label}</a></li>\n";
			} else {
				$html .= "  <li class='breadcrumb-item active' aria-current='page'>{$label}</li>\n";
			}
		}
		$html .= "</ol>\n";

		print $html;
	}
}




/**
 *
 *
 * Global functions
 *
 *
 *
 */





/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return string|bool
 */
function php_feature_missing($required_extensions = null, $required_functions = null)
{
	if (is_array($required_extensions)) {
		foreach ($required_extensions as $ext) {
			if (extension_loaded($ext))
				continue;

			return _('Required PHP extension not installed: ') . $ext;
		}
	}

	if (is_array($required_functions)) {
		foreach ($required_functions as $function) {
			if (function_exists($function))
				continue;

			$ini_path = trim(php_ini_loaded_file());
			$disabled_functions = ini_get('disable_functions');
			if (is_string($disabled_functions) && in_array($function, explode(';', $disabled_functions)))
				return _('Required function disabled') . " : $ini_path, disable_functions=$function";

			return _('Required function not found: ') . $function . '()';
		}
	}

	return false;
}


/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return string|bool
 */
function php_feature_missing_all($required_extensions = null, $required_functions = null)
{

	$errors = [];

	if (is_array($required_extensions)) {
		foreach ($required_extensions as $ext) {
			if (extension_loaded($ext))
				continue;

			$errors[] = $ext;
		}
	}

	return $errors;
}


/**
 * Returns a short purpose description for a known PHP extension used by this project.
 *
 * @param  string $extension  Extension name (e.g. 'curl', 'openssl')
 * @return string|null        Purpose string, or null if extension is unknown
 */
function php_extension_purpose(string $extension): ?string
{
	$purposes = [
		'curl'      => 'Remote agent communication via HTTP API calls',
		'openssl'   => 'SSL/TLS certificate scanning, parsing and fingerprinting',
		'pcntl'     => 'Multi-process forking for parallel host scanning',
		'posix'     => 'Process management (PID, signals, FIFOs) companion to pcntl',
		'pdo'       => 'Database abstraction layer (prepared statements, transactions)',
		'pdo_mysql' => 'MySQL driver for PDO database connectivity',
		'session'   => 'User authentication sessions and theme preference storage',
		'hash'      => 'Password hashing (SHA-512) and CSRF token generation',
		'gettext'   => 'Internationalisation — translates UI strings via _()',
	];

	return $purposes[strtolower($extension)] ?? null;
}


/**
 * Cronjob helpr function for scanning via forked process
 *
 * @method scan_host
 * @param  object $host
 * @param  datetime $execution_time
 * @param  int $tenant_id
 * @return void
 */
function scan_host($host, $execution_time, $tenant_id)
{
	# load classes
	$Database = new Database_PDO();
	$SSL = new SSL($Database);

	// try to fetch cert
	$host_certificate = $SSL->fetch_website_certificate($host, $execution_time, $tenant_id);

	// update cert if fopund
	if ($host_certificate !== false) {
		$cert_id = $SSL->update_db_certificate($host_certificate, $host->t_id, $host->z_id, $execution_time);
		// get IP if not set from remote agent
		$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
		// if Id of certificate changed
		if($host->c_id!=$cert_id) {
			// get new cert
			$certificate = $Database->getObject ("certificates", $cert_id);
			// assign
			$SSL->assign_host_certificate ($host, $ip, $host_certificate['port'], $certificate, $host_certificate['tls_proto'], $execution_time, null);
		}
	}
	// dummy return
	exit(1);
}
