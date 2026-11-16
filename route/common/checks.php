<?php

// PHP version check — shown to all logged-in users
if (PHP_MAJOR_VERSION < 8) {
    print "<div class='alert alert-danger' role='alert' style='margin-top:10px;'>";
    print "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-danger flex-shrink-0'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
    print "<strong>" . _("Unsupported PHP version:") . "</strong> ";
    print sprintf(_("PHP %s detected — php-ssl requires PHP 8.0 or later. Please upgrade your PHP installation."), PHP_VERSION);
    print "</div>";
}

// Dependency checks — composer packages (assets/) are resolved by the composer autoloader
$missing_dependencies = [];
$composer_packages = [
    'Net_DNS2'  => ['class' => 'NetDNS2\\Client',                'url' => 'https://github.com/mikepultz/netdns2'],
    'PHPMailer' => ['class' => 'PHPMailer\\PHPMailer\\PHPMailer', 'url' => 'https://github.com/PHPMailer/PHPMailer'],
];
foreach ($composer_packages as $name => $info) {
    if (!class_exists($info['class'])) {
        $missing_dependencies[$name] = $info['url'];
    }
}
// testssl.sh is a shell script (run via bash) — verify it exists
if (!is_readable(TestSSL::SCRIPT)) {
    $missing_dependencies['testssl.sh'] = 'https://github.com/testssl/testssl.sh';
}
// GoogleAuthenticator is bundled in the repository (functions/assets/), not installed by composer
if (!class_exists('PHPGangsta_GoogleAuthenticator', false)) {
    $ga_file = __DIR__ . '/../../functions/assets/GoogleAuthenticator/GoogleAuthenticator.php';
    if (is_readable($ga_file)) {
        require_once $ga_file;
    }
    if (!class_exists('PHPGangsta_GoogleAuthenticator', false)) {
        $missing_dependencies['GoogleAuthenticator'] = 'https://github.com/PHPGangsta/GoogleAuthenticator';
    }
}
if (!empty($missing_dependencies)) {
    print "<div class='alert alert-danger' role='alert' style='margin-top:10px;'>";
    print "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-danger flex-shrink-0 '><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
    print "<strong>" . _("Missing dependencies:") . "</strong> ";
    $links = [];
    foreach ($missing_dependencies as $name => $url) {
        $links[] = "<a href='" . htmlspecialchars($url) . "' target='_blank' rel='noreferrer'>" . htmlspecialchars($name) . "</a>";
    }
    print implode(', ', $links);
    print " <small class='text-muted'><code style='background: var(--tblr-bg-surface-dark);color:var(--tblr-light);padding:5px 4px'>composer install --no-dev</code></small></div>";
}

$Common->print_system_warnings();