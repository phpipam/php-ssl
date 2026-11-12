<?php

/**
 * Dispatcher for directly-requested endpoints under /route/.
 *
 * The route/ directory lives outside the web root. Browser requests to
 * /route/ajax/*, /route/modals/* and /route/login/login_check.php are
 * rewritten here (see .htaccess), validated against an allowlist and the
 * real path, and then executed in the global scope exactly as if they had
 * been requested directly.
 *
 * Any other /route/ path returns 404, so page templates, shared includes
 * and other non-endpoint files under route/ cannot be executed by URL.
 */

$__route_base = realpath(__DIR__ . '/../route');
$__route_path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$__route_rel  = str_starts_with($__route_path, '/route/') ? substr($__route_path, strlen('/route/')) : '';

// Allowlist: endpoint directories only. No dots apart from the .php
// extension, so ".." traversal cannot match.
$__route_ok = $__route_base !== false
           && preg_match('#^((ajax|modals)/[a-z0-9/_-]+\.php|login/login_check\.php)$#i', $__route_rel) === 1;

if ($__route_ok) {
	$__route_file = realpath($__route_base . '/' . $__route_rel);
	$__route_ok   = $__route_file !== false
	             && is_file($__route_file)
	             && str_starts_with($__route_file, $__route_base . DIRECTORY_SEPARATOR);
}

if (!$__route_ok) {
	http_response_code(404);
	exit;
}

// Endpoints use paths relative to their own directory, as they did when
// the web server executed them directly.
chdir(dirname($__route_file));

$__route_target = $__route_file;
unset($__route_base, $__route_path, $__route_rel, $__route_ok, $__route_file);

require $__route_target;
