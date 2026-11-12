<?php

/**
 *
 * PHP version detection report
 *
 * Probes hosts on http://host:80 and https://host:443 with a single GET / request
 * and detects PHP version from response headers only (passive):
 *   - X-Powered-By: PHP/x.y.z
 *   - Server: ... PHP/x.y.z
 *   - PHPSESSID cookie (PHP detected, version hidden)
 *
 * Detected versions are flagged against PHP branch support dates
 * (active / security / eol) and checked against first fixed release per branch
 * (8.5.12, 8.4.27, 8.3.36, 8.2.35).
 *
 * Targets are all tenant hosts (default) or hosts from a CSV file (--file).
 * Results are saved to the phpversions table, one record per tenant + hostname + port.
 * Subsequent scans update existing records. Unreachable hosts are not saved,
 * existing records of hosts that became unreachable are removed.
 *
 * Usage:
 * 	php phpversions/phpversions.php <tenant_id|tenant_href> [options]       scan and save
 * 	php phpversions/phpversions.php show <tenant_id|tenant_href> [options]  print saved results
 *
 * show - prints saved records from phpversions table (no scanning). Branch and patch
 *        status are re-evaluated from stored php_version, so they reflect current dates
 *        and fixed releases. Only --csv and --all apply.
 *
 * Options:
 * 	--file=PATH        scan hosts from CSV file instead of tenant hosts
 * 	                   format: hostname[,port[,scheme]] - one per line, header row optional
 * 	                   no port = 80 and 443; scheme defaults to http for 80, https otherwise
 * 	--csv              output CSV instead of table (summary goes to stderr)
 * 	--all              include hosts without PHP evidence and failed probes in output
 * 	--dry-run          do not save results to database
 * 	--timeout=N        connect timeout in seconds (default 5)
 * 	--concurrency=N    parallel requests (default 64)
 *
 * Tenant hosts in zones scanned by remote agents are skipped (not reachable
 * from here), ignored hosts are skipped.
 *
 */

# script can only be run from cli
if (php_sapi_name() != "cli") {
	http_response_code(404);
	exit;
}

# autoload classes
require (dirname(__FILE__)."/../functions/autoload.php");


/**
 * PHP branch support dates (https://www.php.net/supported-versions.php)
 * [active support until, security support until]
 * Update when new branches are released.
 */
$php_branches = [
	"5.6" => [null,         "2018-12-31"],
	"7.0" => [null,         "2019-01-10"],
	"7.1" => [null,         "2019-12-01"],
	"7.2" => [null,         "2020-11-30"],
	"7.3" => [null,         "2021-12-06"],
	"7.4" => [null,         "2022-11-28"],
	"8.0" => [null,         "2023-11-26"],
	"8.1" => ["2023-11-25", "2025-12-31"],
	"8.2" => ["2024-12-31", "2026-12-31"],
	"8.3" => ["2025-12-31", "2027-12-31"],
	"8.4" => ["2026-12-31", "2028-12-31"],
	"8.5" => ["2027-12-31", "2029-12-31"],
];

/**
 * First fixed release per branch.
 * Older branches have no fix (EOL), newer unlisted branches are not evaluated.
 */
$fixed_releases = [
	"8.2" => "8.2.35",
	"8.3" => "8.3.36",
	"8.4" => "8.4.27",
	"8.5" => "8.5.12",
];

/**
 * Default ports to probe => scheme
 */
$scan_ports = [80 => "http", 443 => "https"];


#
# arguments
#
$opts = [ "csv" => false, "all" => false, "dry_run" => false, "file" => null, "timeout" => 5, "concurrency" => 64 ];
$tenant_arg = null;
$mode       = "scan";

foreach (array_slice($argv, 1) as $arg) {
	if     ($arg == "--csv")                                  { $opts['csv'] = true; }
	elseif ($arg == "--all")                                  { $opts['all'] = true; }
	elseif ($arg == "--dry-run")                              { $opts['dry_run'] = true; }
	elseif (preg_match('/^--file=(.+)$/', $arg, $m))          { $opts['file'] = $m[1]; }
	elseif (preg_match('/^--timeout=(\d+)$/', $arg, $m))      { $opts['timeout'] = max(1, (int) $m[1]); }
	elseif (preg_match('/^--concurrency=(\d+)$/', $arg, $m))  { $opts['concurrency'] = max(1, (int) $m[1]); }
	elseif ($arg == "--help" || $arg == "-h")                 { usage(0); }
	elseif ($arg == "show" && $mode == "scan" && $tenant_arg === null) { $mode = "show"; }
	elseif ($tenant_arg === null && $arg[0] != "-")           { $tenant_arg = $arg; }
	else                                                      { fwrite(STDERR, "Unknown argument: $arg\n"); usage(1); }
}
if ($tenant_arg === null) {
	usage(1);
}

# tenant - by id or href
$tenant = $Database->getObjectQuery("select * from tenants where id = ? or href = ? limit 1", [is_numeric($tenant_arg) ? (int) $tenant_arg : 0, $tenant_arg]);
if (!$tenant) {
	fwrite(STDERR, "Tenant '$tenant_arg' not found.\n");
	exit(1);
}


#
# show saved results
#
if ($mode == "show") {
	$saved_rows = $Database->getObjectsQuery("select * from phpversions where t_id = ? order by hostname asc, port asc", [$tenant->id]);

	$records = [];
	foreach ($saved_rows as $r) {
		$record = array_map(fn($v) => $v ?? "", (array) $r);
		// re-evaluate against current dates / fixed releases
		if ($record['php_version'] !== "") {
			$branch = branch_status($record['php_version'], $php_branches);
			$patch  = patch_status($record['php_version'], $fixed_releases);
			$record['branch_status']   = $branch['status'];
			$record['supported_until'] = $branch['until'];
			$record['patch_status']    = $patch['status'];
			$record['fixed']           = $patch['fixed'];
		}
		$records[] = $record;
	}

	$summary_text  = sprintf("Tenant: %s [%s] - %d saved records\n", $tenant->name, $tenant->href, sizeof($records));
	$summary_text .= summary_text($records);

	// rows with PHP evidence
	print_report($opts['all'] ? $records : array_filter($records, fn($r) => $r['source'] !== ""), $opts, $summary_text, ["last_scan"]);
	exit(0);
}


#
# build targets (hostname:port), deduplicated
#
$targets       = [];
$skipped_agent = 0;
$skipped_csv   = 0;

if ($opts['file'] !== null) {
	$input = "csv";
	$fh = @fopen($opts['file'], "r");
	if (!$fh) {
		fwrite(STDERR, "Cannot open file '{$opts['file']}'.\n");
		exit(1);
	}
	while (($line = fgetcsv($fh, 0, ",", "\"", "")) !== false) {
		$hostname = trim($line[0] ?? "");
		// skip empty lines, comments and header
		if ($hostname === "" || $hostname[0] == "#" || strtolower($hostname) == "hostname") {
			continue;
		}
		$port   = trim($line[1] ?? "");
		$scheme = strtolower(trim($line[2] ?? ""));

		if (!valid_hostname($hostname) || ($port !== "" && !(ctype_digit($port) && $port > 0 && $port < 65536)) || ($scheme !== "" && !in_array($scheme, ["http", "https"]))) {
			fwrite(STDERR, "Skipping invalid CSV line: ".implode(",", $line)."\n");
			$skipped_csv++;
			continue;
		}

		$ports = $port === "" ? $scan_ports : [(int) $port => $scheme ?: ($port == 80 ? "http" : "https")];
		foreach ($ports as $p => $s) {
			add_target($targets, $hostname, $p, $s, null);
		}
	}
	fclose($fh);
}
else {
	$input = "hosts";
	$hosts = $Database->getObjectsQuery(
		"select h.hostname, h.ip, z.agent_id, a.atype
		   from hosts as h
		   join zones as z on z.id = h.z_id
		   left join agents as a on a.id = z.agent_id
		  where z.t_id = ? and h.ignore = 0
		  order by h.hostname asc",
		[$tenant->id]
	);
	foreach ($hosts as $h) {
		// remote agent zones are scanned remotely - not reachable from this server
		if (!(is_null($h->agent_id) || $h->agent_id == 1 || $h->atype == "local")) {
			$skipped_agent++;
			continue;
		}
		foreach ($scan_ports as $p => $s) {
			add_target($targets, $h->hostname, $p, $s, $h->ip);
		}
	}
}

if (!$opts['csv']) {
	print "Tenant: {$tenant->name} [{$tenant->href}] - ".sizeof($targets)." host:port targets from ".($input == "csv" ? "file {$opts['file']}" : "tenant hosts");
	print $skipped_agent > 0 ? ", $skipped_agent hosts skipped (remote agent)" : "";
	print $skipped_csv > 0   ? ", $skipped_csv invalid CSV lines skipped" : "";
	print "\n\n";
}


#
# probe
#
$results = probe_targets(array_values($targets), $opts['timeout'], $opts['concurrency']);


#
# evaluate
#
$records = [];

foreach ($results as $r) {
	$d = detect_php($r['headers']);

	if ($r['error'] !== null)  { $result = "unreachable"; }
	elseif ($d === null)       { $result = "no php"; }
	else                       { $result = "php"; }

	$branch = ["status" => "", "until" => ""];
	$patch  = ["status" => "", "fixed" => ""];
	if ($d !== null) {
		if ($d['version'] === null) { $branch['status'] = "hidden"; }
		else                        { $branch = branch_status($d['version'], $php_branches); }
		$patch = patch_status($d['version'], $fixed_releases);
	}

	$records[] = [
		"ip"              => $r['ip'] ?: ($r['target']['ip'] ?? ""),
		"hostname"        => $r['target']['hostname'],
		"port"            => $r['target']['port'],
		"scheme"          => $r['target']['scheme'],
		"result"          => $result,
		"php_version"     => $d['version'] ?? "",
		"branch_status"   => $branch['status'],
		"supported_until" => $branch['until'],
		"patch_status"    => $patch['status'],
		"fixed"           => $patch['fixed'],
		"source"          => $d['source'] ?? "",
		"x_powered_by"    => mb_substr(implode("; ", $r['headers']['x-powered-by'] ?? []), 0, 255),
		"server"          => mb_substr(implode("; ", $r['headers']['server'] ?? []), 0, 255),
		"http_code"       => $r['http_code'] ?: "",
		"error"           => mb_substr($r['error'] ?? "", 0, 255),
	];
}


#
# save
#
$saved = $opts['dry_run'] ? null : save_records($Database, (int) $tenant->id, $input, $records);


#
# output
#
$summary_text  = summary_text($records);
$summary_text .= $saved === null ? "Dry run - results not saved.\n" : "Saved to phpversions: {$saved['inserted']} new, {$saved['updated']} updated, {$saved['removed']} removed (unreachable).\n";

print_report($opts['all'] ? $records : array_filter($records, fn($r) => $r['result'] == "php"), $opts, $summary_text);

exit(0);




/**
 * Print usage and exit
 *
 * @param int $code
 * @return never
 */
function usage($code = 0)
{
	$out = $code == 0 ? STDOUT : STDERR;
	fwrite($out, "Usage: php phpversions/phpversions.php <tenant_id|tenant_href> [--file=hosts.csv] [--csv] [--all] [--dry-run] [--timeout=N] [--concurrency=N]\n");
	fwrite($out, "       php phpversions/phpversions.php show <tenant_id|tenant_href> [--csv] [--all]\n");
	exit($code);
}

/**
 * Validate hostname or IP address from CSV input.
 *
 * @param string $hostname
 * @return bool
 */
function valid_hostname(string $hostname): bool
{
	return filter_var($hostname, FILTER_VALIDATE_IP) !== false
		|| filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
}

/**
 * Add target to list, deduplicated by hostname:port.
 *
 * @param array $targets
 * @param string $hostname
 * @param int $port
 * @param string $scheme
 * @param string|null $ip   known IP (fallback if probe fails)
 * @return void
 */
function add_target(array &$targets, string $hostname, int $port, string $scheme, ?string $ip): void
{
	$key = strtolower($hostname).":".$port;
	if (!isset($targets[$key])) {
		$targets[$key] = ["hostname" => $hostname, "port" => $port, "scheme" => $scheme, "ip" => $ip];
	}
}

/**
 * Probe targets in parallel via curl_multi. Only response headers are read,
 * body transfer is aborted as soon as it starts.
 *
 * @param array $targets    [["hostname"=>..., "port"=>..., "scheme"=>http|https, "ip"=>?string], ...]
 * @param int $timeout
 * @param int $concurrency
 * @return array            [["target"=>..., "headers"=>[name=>[values]], "http_code"=>int, "ip"=>string, "error"=>?string], ...]
 */
function probe_targets(array $targets, int $timeout, int $concurrency): array
{
	$results = [];
	$mh      = curl_multi_init();
	$active  = [];
	$next    = 0;

	$add = function ($i) use (&$targets, &$results, &$active, $mh, $timeout) {
		$t    = $targets[$i];
		$host = filter_var($t['hostname'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[".$t['hostname']."]" : $t['hostname'];

		$results[$i] = ["target" => $t, "headers" => [], "http_code" => 0, "ip" => "", "error" => null];

		$ch = curl_init($t['scheme']."://".$host.":".$t['port']."/");
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT        => $timeout * 2,
			CURLOPT_USERAGENT      => "php-ssl php-version-check",
			CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$results, $i) {
				// new response (e.g. 100 Continue) - reset headers
				if (stripos($line, "HTTP/") === 0) {
					$results[$i]['headers'] = [];
				}
				elseif (strpos($line, ":") !== false) {
					[$name, $value] = explode(":", $line, 2);
					$results[$i]['headers'][strtolower(trim($name))][] = trim($value);
				}
				return strlen($line);
			},
			// headers are all we need - abort on first body chunk
			CURLOPT_WRITEFUNCTION  => fn($ch, $data) => 0,
		]);
		curl_multi_add_handle($mh, $ch);
		$active[(int) $ch] = $i;
	};

	// fill initial window
	while ($next < sizeof($targets) && sizeof($active) < $concurrency) {
		$add($next++);
	}

	do {
		curl_multi_exec($mh, $running);
		curl_multi_select($mh, 1.0);

		while ($info = curl_multi_info_read($mh)) {
			$ch = $info['handle'];
			$i  = $active[(int) $ch];

			$results[$i]['http_code'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			$results[$i]['ip']        = (string) curl_getinfo($ch, CURLINFO_PRIMARY_IP);
			// CURLE_WRITE_ERROR is our own body abort - headers were received
			if ($info['result'] !== CURLE_OK && !($info['result'] === CURLE_WRITE_ERROR && $results[$i]['http_code'] > 0)) {
				$results[$i]['error'] = curl_error($ch) ?: curl_strerror($info['result']);
			}

			curl_multi_remove_handle($mh, $ch);
			curl_close($ch);
			unset($active[(int) $ch]);

			// refill window
			if ($next < sizeof($targets)) {
				$add($next++);
			}
		}
	} while ($running > 0 || !empty($active));

	curl_multi_close($mh);
	ksort($results);

	return $results;
}

/**
 * Detect PHP from response headers.
 *
 * @param array $headers    [lowercase name => [values]]
 * @return array|null       ["version" => ?string, "source" => string] or null if no PHP evidence
 */
function detect_php(array $headers): ?array
{
	$fallback = null;

	foreach (["x-powered-by", "server"] as $name) {
		foreach ($headers[$name] ?? [] as $value) {
			if (preg_match('/\bPHP\/(\d+\.\d+(?:\.\d+)?)/i', $value, $m)) {
				return ["version" => $m[1], "source" => $name];
			}
			if ($fallback === null && preg_match('/\bPHP\b/i', $value)) {
				$fallback = ["version" => null, "source" => $name];
			}
		}
	}
	if ($fallback !== null) {
		return $fallback;
	}

	foreach ($headers['set-cookie'] ?? [] as $value) {
		if (stripos($value, "PHPSESSID=") === 0) {
			return ["version" => null, "source" => "cookie"];
		}
	}

	return null;
}

/**
 * Get support status for a PHP version.
 *
 * @param string $version
 * @param array $branches
 * @return array            ["status" => active|security|eol|unknown, "until" => Y-m-d or ""]
 */
function branch_status(string $version, array $branches): array
{
	$parts  = explode(".", $version);
	$branch = $parts[0].".".($parts[1] ?? "0");
	$today  = date("Y-m-d");

	if (!isset($branches[$branch])) {
		// anything older than the oldest listed branch is long dead
		return version_compare($branch, array_key_first($branches), "<") ? ["status" => "eol", "until" => ""] : ["status" => "unknown", "until" => ""];
	}

	[$active_until, $security_until] = $branches[$branch];

	if ($active_until !== null && $today <= $active_until) { return ["status" => "active",   "until" => $security_until]; }
	if ($today <= $security_until)                         { return ["status" => "security", "until" => $security_until]; }
	return ["status" => "eol", "until" => $security_until];
}

/**
 * Check PHP version against first fixed release of its branch.
 *
 * @param string|null $version
 * @param array $fixed      [branch => first fixed version]
 * @return array            ["status" => vulnerable|patched|no fix|unknown, "fixed" => fixed version for branch or ""]
 *                          no fix  = branch older than any fixed branch (EOL)
 *                          unknown = version hidden / incomplete / unlisted newer branch
 */
function patch_status(?string $version, array $fixed): array
{
	if ($version === null) {
		return ["status" => "unknown", "fixed" => ""];
	}

	$parts  = explode(".", $version);
	$branch = $parts[0].".".($parts[1] ?? "0");

	if (isset($fixed[$branch])) {
		// patch level is required to compare
		if (sizeof($parts) < 3) {
			return ["status" => "unknown", "fixed" => $fixed[$branch]];
		}
		return ["status" => version_compare($version, $fixed[$branch], "<") ? "vulnerable" : "patched", "fixed" => $fixed[$branch]];
	}
	return ["status" => version_compare($branch, array_key_first($fixed), "<") ? "no fix" : "unknown", "fixed" => ""];
}

/**
 * Save scan records to phpversions table (insert or update by tenant + hostname + port).
 * Unreachable hosts are not saved, existing records for them are removed.
 *
 * @param Database_PDO $Database
 * @param int $tenant_id
 * @param string $input     hosts|csv
 * @param array $records
 * @return array            ["inserted" => int, "updated" => int, "removed" => int]
 */
function save_records(Database_PDO $Database, int $tenant_id, string $input, array $records): array
{
	$now   = date("Y-m-d H:i:s");
	$count = ["inserted" => 0, "updated" => 0, "removed" => 0];

	$insert_cols = ["t_id", "hostname", "port", "scheme", "ip", "input", "result", "php_version", "branch_status", "supported_until", "patch_status", "fixed", "source", "x_powered_by", "server", "http_code", "error", "created", "last_scan", "last_change"];
	$insert_sql  = "insert into phpversions (`".implode("`,`", $insert_cols)."`) values (".implode(",", array_fill(0, sizeof($insert_cols), "?")).")";

	foreach ($records as $r) {
		$values = [
			"scheme"          => $r['scheme'],
			"ip"              => $r['ip'] ?: null,
			"input"           => $input,
			"result"          => $r['result'],
			"php_version"     => $r['php_version'] ?: null,
			"branch_status"   => $r['branch_status'] ?: null,
			"supported_until" => $r['supported_until'] ?: null,
			"patch_status"    => $r['patch_status'] ?: null,
			"fixed"           => $r['fixed'] ?: null,
			"source"          => $r['source'] ?: null,
			"x_powered_by"    => $r['x_powered_by'] ?: null,
			"server"          => $r['server'] ?: null,
			"http_code"       => $r['http_code'] ?: null,
			"error"           => $r['error'] ?: null,
		];

		$existing = $Database->getObjectQuery("select * from phpversions where t_id = ? and hostname = ? and port = ? limit 1", [$tenant_id, $r['hostname'], $r['port']]);

		// unreachable - do not store, remove existing record
		if ($r['result'] == "unreachable") {
			if ($existing) {
				$Database->runQuery("delete from phpversions where id = ?", [$existing->id]);
				$count['removed']++;
			}
			continue;
		}

		if (!$existing) {
			$Database->runQuery($insert_sql, array_values(array_merge(
				["t_id" => $tenant_id, "hostname" => $r['hostname'], "port" => $r['port']],
				$values,
				["created" => $now, "last_scan" => $now, "last_change" => $r['result'] == "php" ? $now : null]
			)));
			$count['inserted']++;
			continue;
		}

		// detection changed
		if ($existing->result != $r['result'] || $existing->php_version != $values['php_version'] || $existing->source != $values['source']) {
			$values['last_change'] = $now;
		}
		$values['last_scan'] = $now;

		$set = implode(", ", array_map(fn($c) => "`$c` = ?", array_keys($values)));
		$Database->runQuery("update phpversions set $set where id = ?", [...array_values($values), $existing->id]);
		$count['updated']++;
	}

	return $count;
}

/**
 * Build summary line from records.
 *
 * @param array $records
 * @return string
 */
function summary_text(array $records): string
{
	$count = fn($field, $value) => sizeof(array_filter($records, fn($r) => $r[$field] === $value));

	return sprintf(
		"Total %d, unreachable %d, PHP detected %d (active %d, security-only %d, EOL %d, unknown branch %d, version hidden %d), vulnerable %d\n",
		sizeof($records), $count("result", "unreachable"), $count("result", "php"),
		$count("branch_status", "active"), $count("branch_status", "security"), $count("branch_status", "eol"), $count("branch_status", "unknown"), $count("branch_status", "hidden"),
		$count("patch_status", "vulnerable") + $count("patch_status", "no fix")
	);
}

/**
 * Print rows as table or CSV (--csv), followed by summary.
 *
 * @param array $rows
 * @param array $opts
 * @param string $summary_text
 * @param array $extra_columns  additional columns appended to output
 * @return void
 */
function print_report(array $rows, array $opts, string $summary_text, array $extra_columns = []): void
{
	if ($opts['csv']) {
		$columns = ["ip", "hostname", "port", "result", "php_version", "branch_status", "supported_until", "patch_status", "fixed", "source", "x_powered_by", "server", "http_code", "error", ...$extra_columns];
		fputcsv(STDOUT, $columns, ",", "\"", "");
		foreach ($rows as $row) {
			fputcsv(STDOUT, array_map(fn($c) => $row[$c], $columns), ",", "\"", "");
		}
		fwrite(STDERR, $summary_text);
	}
	else {
		$columns = ["ip", "hostname", "port", "result", "php_version", "branch_status", "supported_until", "patch_status", "fixed", "source", "x_powered_by", "server", ...$extra_columns];
		if ($opts['all']) { $columns[] = "error"; }
		print_table($rows, $columns);
		print "\n".$summary_text;
	}
}

/**
 * Print rows as plain text table.
 *
 * @param array $rows
 * @param array $columns
 * @return void
 */
function print_table(array $rows, array $columns): void
{
	if (empty($rows)) {
		print "No PHP detected.\n";
		return;
	}

	$max_width = 50;
	$widths    = [];
	foreach ($columns as $c) {
		$widths[$c] = strlen($c);
		foreach ($rows as $r) {
			$widths[$c] = min($max_width, max($widths[$c], mb_strlen((string) $r[$c])));
		}
	}

	$line = function ($values) use ($columns, $widths) {
		$out = [];
		foreach ($columns as $c) {
			$v = (string) $values[$c];
			if (mb_strlen($v) > $widths[$c]) { $v = mb_substr($v, 0, $widths[$c] - 1)."~"; }
			$out[] = $v.str_repeat(" ", $widths[$c] - mb_strlen($v));
		}
		return rtrim(implode("  ", $out))."\n";
	};

	print $line(array_combine($columns, $columns));
	print $line(array_combine($columns, array_map(fn($c) => str_repeat("-", $widths[$c]), $columns)));
	foreach ($rows as $r) {
		print $line($r);
	}
}
