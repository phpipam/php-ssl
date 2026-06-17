# Configuration

php-ssl uses a two-layer configuration system:

1. **`config.php`** — global defaults, loaded on every request
2. **`config` database table** — per-tenant overrides managed through the UI (Settings menu)

When both are set, the database value takes precedence for the tenant in question.

---

## config.php Reference

Copy `config.dist.php` to `config.php` to get started. Never commit `config.php` to version control — it contains credentials.

### Database

```php
$db['host'] = "127.0.0.1";
$db['user'] = "phpssladmin";
$db['pass'] = "phpssladmin";
$db['name'] = "php-ssl";
$db['port'] = 3306;
$db['ssl']  = false;   // set true to require TLS for the DB connection
```

### Application state

```php
$installed = false;   // set true after running the web installer
$debugging = false;   // set true to display PHP errors on-screen
```

### Base path

Only needed when the application is **not** at the web root:

```php
define('BASE', '/php-ssl');   // the URL prefix, no trailing slash
```

Also update `RewriteBase` in `.htaccess` to match. Note: CSS/JS paths are hardcoded as absolute paths (`/css/`, `/js/`), so the web server must be configured to serve them regardless of where the app lives.

### Certificate expiry thresholds

```php
$expired_days       = 20;   // warn N days before expiry (cron notifications)
$expired_after_days = 7;    // continue reporting N days after expiry
```

These are the system-wide defaults. Users can set a personal value in their profile, and tenants can override via the `config` DB table. The user setting takes precedence over the tenant setting.

### Audit logging

```php
$log_object = true;   // store full object JSON in logs.json_object_old/new
```

Setting this to `false` reduces database growth but disables some features: the private-zone log filter (for deleted-zone records) relies on JSON stored in this column.

### Backup retention

```php
$backup_retention_period = 30;   // days to keep database backups
```

### Session

```php
$phpsessname = "phpssl";   // PHP session name; change for added security
```

### Mail (SMTP)

```php
$mail_settings->mtype   = "smtp";
$mail_settings->msecure = "tls";       // "tls", "ssl", or "" for none
$mail_settings->mauth   = "no";        // "yes" to use SMTP authentication
$mail_settings->mserver = "127.0.0.1";
$mail_settings->mport   = 25;
$mail_settings->muser   = "";
$mail_settings->mpass   = "";
```

### Mail sender identity

```php
$mail_sender_settings->mail_from = "SSL Certificate check";
$mail_sender_settings->mail_addr = "noreply@mydomain.com";
$mail_sender_settings->email     = "php-ssl@mydomain.com";   // shown in mail footer
$mail_sender_settings->www       = "https://mywebsite.com";  // shown in mail footer
$mail_sender_settings->bcc       = "";                       // always-BCC address
$mail_sender_settings->url       = "myurl";
```

### WebAuthn / Passkeys

These are only required when running behind a reverse proxy that terminates TLS, because PHP cannot auto-detect the correct public origin in that case:

```php
$webauthn_origin = "https://php-ssl.example.com";   // full public origin
$webauthn_rpid   = "php-ssl.example.com";           // hostname only, no scheme
```

Leave both empty to auto-detect from the HTTP request.

### nmap

```php
$nmap_path = "/usr/bin/nmap";   // path to the nmap binary
```

The web server user must have execute permission on this binary.

### Private key encryption

Private keys stored in the database are encrypted with AES-256-GCM. Configure one encryption secret per tenant:

```php
$private_key_encryption_key[1] = 'change-me-to-a-long-random-secret';
$private_key_encryption_key[2] = 'another-secret-for-tenant-2';
```

Use a different, long random string for each tenant. Keep `config.php` outside version control.

---

## Per-Tenant Database Overrides

Settings in the `config` table (key/value pairs scoped to a `t_id`) override the corresponding `config.php` values for that tenant. Managed via **Settings** in the tenant UI.

Overridable settings include: `expired_days`, `expired_after_days`, `scanMaxThreads`, mail settings, and others.
