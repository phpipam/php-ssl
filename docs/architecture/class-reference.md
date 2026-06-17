# Class Reference

All classes live in `functions/classes/`. They are instantiated in `functions/autoload.php` and available as globals in every route file.

## Inheritance

```
Validate
└── Common
    ├── SSL
    ├── User
    ├── URL
    ├── Config
    └── Zones
```

`Common` provides shared utilities (permalink generation, error handling, input validation wrappers). `Validate` provides low-level input sanitisation. All domain classes inherit both.

---

## Database_PDO (`class.PDO.php`)

All database access in the application goes through this class. Never use raw PDO directly.

**Key methods:**

| Method | Description |
|---|---|
| `getObjectQuery($sql, $params)` | Returns a single row as `stdClass`, or `null` |
| `getObjectsQuery($sql, $params)` | Returns an array of `stdClass` rows |
| `getValueQuery($sql, $params)` | Returns a single scalar value |
| `runQuery($sql, $params)` | Executes a non-SELECT query |
| `insertObject($table, $data)` | Inserts a row and returns the new ID |
| `updateObject($table, $data)` | Updates a row (must include `id` in `$data`) |
| `getObject($table, $id)` | Fetches a row by primary key |
| `lastInsertId()` | Returns the last auto-increment ID |

---

## SSL (`class.SSL.php`)

Core SSL scanner. Connects to hosts over TCP/TLS, extracts certificates, and updates the database.

**Key methods:**

| Method | Description |
|---|---|
| `fetch_website_certificate($host, $time, $tenant_id)` | Scan one host; returns cert array or `false` |
| `update_db_certificate($cert, $tenant_id, $zone_id, $time)` | Upsert cert into DB; returns cert ID |
| `assign_host_certificate($host, $ip, $port, $cert, $tls, $time, $user_id)` | Link cert to host, write log |
| `upsert_chain_cas($chain_pem, $tenant_id)` | Extract and store CA certs from a chain PEM |
| `process_certificate_chain($chain)` | Parse and validate a chain; returns structured array |
| `get_all_port_groups()` | Load all port groups (used to prime the port cache) |
| `resolve_ip($hostname)` | DNS-resolve a hostname to an IP |

**Scanning flow:**
1. Validate hostname and port group
2. For local agent: iterate ports, attempt `stream_socket_client` SSL connection
3. For remote agent: send to `Agent` class via HTTP API
4. `update_host_last_check()` is always called (success or failure)
5. On success: `update_db_certificate()` → `upsert_chain_cas()` → `assign_host_certificate()` (if cert changed)

---

## Certificates (`class.Certificates.php`)

Certificate CRUD and status logic.

**Key methods:**

| Method | Description |
|---|---|
| `parse_cert($pem)` | Parse PEM with `openssl_x509_parse`, add `custom_validDays`, `custom_validTo`, `custom_purposes` |
| `get_status($parsed, $text, $validate_domain, $domain)` | Returns `['code' => int, 'text' => string]` |
| `get_status_int($parsed, $validate_domain, $domain)` | Status code: 0=unknown, 1=expired, 2=expiring, 3=valid, 10=domain mismatch, 11=self-signed |
| `get_status_color($code)` | Tabler colour class name for a status code |
| `get_certificate_hosts($cert_id)` | All hosts assigned to a certificate |
| `get_expired($days, $after_days)` | Certificates expiring soon or recently expired |
| `is_issuer_ignored($aki, $tenant_id, $type)` | Whether a certificate's issuer is on the ignored list |

**Status code meanings:**

| Code | Meaning | Colour |
|---|---|---|
| 0 | Unknown (no cert / unparseable) | Grey |
| 1 | Expired | Red |
| 2 | Expiring soon (within threshold) | Orange |
| 3 | Valid | Green |
| 10 | Domain mismatch | Red |
| 11 | Self-signed | Orange |

---

## Zones (`class.Zones.php`)

Zone and host management.

**Key methods:**

| Method | Description |
|---|---|
| `get_all($tenant_href)` | All zones for a tenant (respects private zone rules) |
| `get_zone($tenant_href, $zone_name)` | Single zone by tenant+name |
| `get_zone_hosts($zone_id)` | All non-ignored hosts in a zone |
| `get_tenant_agents($tenant_id)` | All agents available to a tenant |
| `is_host_inside_domain($hostname, $zone)` | Validates that a hostname belongs to the zone domain |

---

## User (`class.User.php`)

Session-based authentication, role checking, and permission validation.

**Key methods:**

| Method | Description |
|---|---|
| `validate_session($redirect, $json, $ajax)` | Require a valid session; redirect or return JSON error if not |
| `validate_user_permissions($level, $die)` | Require minimum permission level |
| `validate_tenant($die, $json)` | Verify the URL tenant matches the user's tenant (or admin) |
| `get_current_user()` | Returns the current `$user` object |
| `create_csrf_token()` | Generate a CSRF token for a form |
| `validate_csrf_token()` | Validate a submitted CSRF token; die on failure |
| `strip_input_tags($array)` | Strip HTML tags from all values in `$_GET` / `$_POST` |

---

## Tenants (`class.Tenants.php`)

Tenant CRUD.

**Key methods:** `get_all()`, `get_tenant_by_href($href)`, `get_tenant_by_id($id)`

---

## Log (`class.Log.php`)

Audit logging. All changes to zones, hosts, certificates, users, and CAs are written here.

**Key method:**

```php
$Log->write($object, $object_id, $tenant_id, $user_id, $action, $public, $text, $old_json, $new_json);
```

The `$old_json` / `$new_json` parameters are only stored when `$log_object = true` in `config.php`.

---

## Config (`class.Config.php`)

Reads per-tenant overrides from the `config` DB table and merges them over the global `config.php` defaults.

**Key method:** `get_config($tenant_id)` — returns an associative array of all config values for the tenant.

---

## Cron (`class.Cron.php`)

Reads the `cron` DB table and decides which scripts to execute on a given run.

**Key method:** `execute_cronjobs($tenant_id)` — checks schedules, runs due scripts (and always runs `testssl_scan` regardless of schedule), updates `last_executed`.

---

## Mail (`class.Mail.php`)

Thin wrapper around PHPMailer. Used by the cron scripts to send certificate change and expiry notifications.

---

## Modal (`class.Modal.php`)

Renders Bootstrap modal HTML (header, body, footer, action button JavaScript).

**Key method:** `modal_print($title, $content, $btn_text, $submit_url, $close, $header_class)`

---

## Result (`class.Result.php`)

Formats AJAX JSON responses and HTML alert banners.

**Key methods:**

| Method | Description |
|---|---|
| `show($type, $message, $die, $return, $inline, $br)` | Render a Bootstrap alert div |
| `result_json($status, $message, $data)` | Output a JSON response and exit |

---

## AXFR (`class.AXFR.php`)

DNS zone transfer client. Uses the Net_DNS2 submodule to perform AXFR requests and extract hostnames.

---

## Agent (`class.Agent.php`)

HTTP client for remote scanning agents. Sends hostname+ports to a remote agent's API and returns the result.

---

## Thread / ScanThread (`class.Thread.php`)

`pcntl_fork`-based threading for parallel certificate scanning. Used exclusively by the `update_certificates` cron script.

---

## TestSSL (`class.testssl.php`)

testssl.sh integration. **Not a global** — instantiate per-request: `new TestSSL($Database)`.

**Key methods:** `run_pending()`, `parse_result($json)`, `send_completion_email($scan)`, `get_latest_by_hostnames($hostnames, $tenant_id, $admin)`

---

## Migration (`class.Migration.php`)

Tracks and applies incremental DB migrations from `db/migrations/`.

**Key methods:** `get_pending()`, `apply_all()`, `get_current_version()`, `get_latest_version()`

---

## WebAuthn (`class.WebAuthn.php`)

WebAuthn/Passkey credential verification. Implements ES256 and RS256.

---

## ADsync (`class.ADsync.php`)

Active Directory LDAP user synchronisation.

---

## Common (`class.Common.php`)

Base class for `SSL`, `User`, `URL`, `Config`, and `Zones`. Provides:
- `validate_hostname()`, `validate_ip()`, `validate_mail()`, `validate_int()`
- `print_breadcrumbs()`
- `save_error()`, `result_die()`
- `scan_host()` — the forked-process worker function used by `update_certificates`

---

## Validate (`class.Validate.php`)

Base class for `Common`. Low-level input sanitisation and type checking.
