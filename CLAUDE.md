# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**php-ssl** (v0.9.0) is a PHP 8.0+ SSL/TLS certificate monitoring web application. It scans predefined hostnames for certificate changes, supports DNS zone transfers (AXFR) to auto-discover hosts, remote scanning agents, and sends email notifications for changes and expirations. Multi-tenant architecture with full tenant isolation.

Key features: certificate monitoring, CA management, CSR generation/signing, testssl.sh integration, WebAuthn/Passkey authentication, Active Directory sync, multi-language (EN/SL/DE), database migrations UI, private key management, public scan report sharing.

## Setup

**Configuration:**
```bash
cp config/config.dist.php config/config.php
# Edit config/config.php with DB credentials and mail settings
```

**Web server:** the document root must be `public/` (served at the root of a vhost). See `docs/operations/apache-nginx.md`.

**Database:**
```bash
mysql -u root -p -e "CREATE DATABASE \`php-ssl\`; CREATE USER 'phpssladmin'@'localhost' IDENTIFIED BY 'phpssladmin'; GRANT ALL ON \`php-ssl\`.* TO 'phpssladmin'@'localhost';"
mysql -u root -p php-ssl < db/SCHEMA.sql
```

**Git submodules** (Net_DNS2, PHPMailer in `functions/assets/`; testssl.sh in `functions/testSSL/`):
```bash
git submodule update --init --recursive
```

**Cron:**
```
*/5 * * * * /usr/bin/php /var/www/html/php-ssl/cron/cron.php
```

**Run cron script manually:**
```bash
php cron/cron.php <tenant_id> <script_name>
# e.g.: php cron/cron.php 1 update_certificates
```

**PHP required:** 8.0 or later (server currently runs PHP 8.3)

**PHP extensions required:** `curl`, `gettext`, `openssl`, `pcntl`, `PDO`, `pdo_mysql`, `session`

There is no build system — this is a traditional PHP app deployed directly; only `public/` is the web root.

## Architecture

### Directory Layout

```
public/       ← web document root (only web-accessible directory)
  index.php     front controller for pages
  route.php     dispatcher for /route/ajax/*, /route/modals/*, /route/login/login_check.php
  .htaccess     rewrite rules (/route/* → route.php, everything else → index.php)
  css/ js/ favicon*
cron/cron.php ← cron entry point (CLI only)
route/        ← page templates, AJAX endpoints, modals (NOT web-accessible)
functions/    ← autoload, classes, cron job scripts, locale, submodules
db/           ← SCHEMA.sql, migrations, backups (db/bkp)
config/       ← config.php (local, gitignored — credentials), config.dist.php (template)
version.php
```

Code under `public/` must reference the rest of the app via `__DIR__ . '/../...'`. Never add PHP files to `public/` other than the two front controllers.

### Request Flow

```
HTTP Request → public/index.php
  → functions/autoload.php   (instantiates all classes)
  → Session/user validation
  → route/content.php        (URL routing)
  → route/{feature}/index.php (feature handler)
  → HTML response using Tabler UI
```

```
HTTP Request /route/(ajax|modals)/… → public/route.php
  → allowlist + realpath check (anything else → 404)
  → chdir() to the endpoint's directory, then require it
  → endpoint requires functions/autoload.php itself (relative path, e.g. '../../functions/autoload.php')
```

Special top-level slugs handled before tenant routing in `public/index.php`: `login`, `install`, `report` (public testSSL report at `/report/<hash>/`).

### URL Structure

URLs follow the pattern `/{tenant_href}/{route}/{app}/{id1}`. The `tenant` segment is the tenant's `href` slug (not its numeric ID). Parsed by `class.URL.php` into `$_params` with keys: `tenant`, `route`, `app`, `id1`. Valid routes are defined in `$url_items` in `functions/config.menu.php`. `route/content.php` dispatches to `route/{route}/index.php`.

### Key Classes (`functions/classes/`)

| Class | Role |
|-------|------|
| `class.PDO.php` | Database abstraction layer — all DB access goes through this |
| `class.SSL.php` | Core SSL scanning: connects to hosts, retrieves certificates |
| `class.Certificates.php` | Certificate CRUD, change detection logic |
| `class.Zones.php` | DNS zone and host management |
| `class.User.php` | Session-based authentication, role checks, permission validation, impersonation |
| `class.Tenants.php` | Multi-tenant isolation |
| `class.AXFR.php` | DNS zone transfer (uses Net_DNS2 submodule) |
| `class.Agent.php` | Remote scanning agent communication |
| `class.Cron.php` | Cronjob orchestration — reads schedule from `cron` DB table |
| `class.Thread.php` | Multi-process scanning via `pcntl_fork` |
| `class.Mail.php` | Email notifications via PHPMailer submodule |
| `class.Log.php` | Audit logging to the `logs` table |
| `class.Common.php` | Shared utility methods (base class for URL, User, Config, Zones) |
| `class.Validate.php` | Input validation |
| `class.Result.php` | JSON response formatting for AJAX endpoints and HTML alert rendering |
| `class.Modal.php` | Renders Bootstrap modal HTML (header/body/footer/action JS) |
| `class.Config.php` | Reads per-tenant config overrides from the `config` DB table |
| `class.ADsync.php` | Active Directory LDAP user synchronization |
| `class.Migration.php` | Tracks and applies incremental DB migrations |
| `class.testssl.php` | testssl.sh integration: scan requests, cron execution, JSON parsing, export, email notification |
| `class.WebAuthn.php` | WebAuthn/Passkey authentication (ES256 and RS256) |
| `class.Layout.php` | Per-user Tabler UI layout/theme preferences (stored in `users.layout`), renders `<html>` `data-bs-*` attributes |

### Route Structure (`route/`)

Each top-level feature has a directory matching its name.

**User-facing routes:** `dashboard`, `zones`, `certificates`, `cas` (CA management), `ca-certificates` (CA cert browser), `csrs` (CSR management), `scanning` (sub-pages: `agents`, `portgroups`, `cron`), `logs`, `users`, `tenants`, `user`, `search`, `fetch`, `transform`, `ignored`, `testssl`, `domains`, `validate`

**Special routes (no auth):** `login`, `install`, `report` (public testSSL report — standalone HTML page at `/report/<hash>/`)

AJAX endpoints (return JSON via `$Result`) live under `route/ajax/`. Some have subdirectories: `route/ajax/ca/`, `route/ajax/csr/`, `route/ajax/passkey/`. Modal dialogs (return HTML fragments) live under `route/modals/{feature}/`. These are the **only** files reachable by URL (via `public/route.php`), together with `route/login/login_check.php`; an endpoint placed anywhere else under `route/` will 404. File names must match `[a-z0-9/_-]+\.php`. The standard modal pattern is:
- `route/modals/{feature}/edit.php` — renders the form HTML
- `route/modals/{feature}/edit-submit.php` — processes POST and returns JSON result

Modals are loaded asynchronously: `data-bs-toggle="modal"` with an `href` triggers `$('.modal-content').load(href)` in `public/js/magic.js`.

**Shared page components** live in `route/common/`:
- `checks.php` — system health alerts shown on every page (unapplied migrations, missing git submodules)
- `header.php` — top navigation bar HTML
- `header-notifications.php` — notification bell/badge
- `left-menu.php` — sidebar menu generation

### Cron Scripts (`functions/cron/`)

- `update_certificates.php` — scans all hosts for new/changed certs, sends change notifications, uses `pcntl` forking for parallelism
- `axfr_transfer.php` — performs DNS AXFR zone transfers, auto-adds/removes hosts
- `expired_certificates.php` — identifies expiring certs, sends notifications
- `remove_orphaned.php` — cleans up orphaned certificate records
- `testssl_scan.php` — picks up `Requested` testssl scans and runs testssl.sh; **always executed on every `cron/cron.php` run for all tenants** (not scheduled via `cron` DB table)
- `backup.php` — backup operations

Cron schedules are stored per-tenant in the `cron` DB table (not just the system crontab). The system crontab only triggers `cron/cron.php` every 5 minutes; `Cron` class checks DB schedules to decide which scripts to run. Exception: `testssl_scan` always runs — it is injected directly in `Cron::execute_cronjobs()` outside the schedule loop.

### Multi-Tenancy

All primary tables (`zones`, `hosts`, `certificates`, `users`, `agents`, `testssl`, `cas`, `csrs`) have a `tenant_id` column. The `$user->tenant_id` on the session is used to scope all queries. Admins (`$user->admin == "1"`) can see the Tenants menu and manage all tenants. The `tenant` URL segment is the tenant's `href` slug.

### Database Schema (key tables)

`tenants` → `zones` → `hosts` → `certificates` (hierarchical ownership). `users` are scoped to tenants. `agents` handle remote scanning. `ssl_port_groups` define which ports to scan per zone.

**testssl** — scan requests and results: `id`, `tenant_id`, `user_id`, `hostname`, `port`, `rating`, `status` (enum: Requested/Scanning/Completed/Cancelled/Error), `requested`, `started`, `completed`, `hash` (64-char hex, for public sharing), `notify_email`, `json_result`, `error_message`. URL uses hash (not id): `/{tenant}/testssl/{hash}/`. Public page: `/report/{hash}/`.

**cas** — Certificate Authorities: `id`, `t_id`, `name`, `certificate`, `pkey_id`, `parent_ca_id`, `subject`, `expires`, `created`, `ski`, `source`, `ignore_updates`, `ignore_expiry`. Used for CSR signing and certificate chain building.

**csrs** — Certificate Signing Requests: `id`, `t_id`, `cn`, `sans`, `key_algo`, `key_size`, country/state/locality/org/ou/email fields, `status`, `source`, `csr_pem`, `extensions`, `pkey_id`, `cert_id`, `renewed_by`, `created`.

**csr_templates** — reusable CSR profile templates with `extensions`.

**pkey** — private keys stored separately so certificates sharing the same key can be linked. Also used by CAs and CSRs.

**passkeys** — WebAuthn credentials (public key, credential ID, sign count) per user.

**domains** — Active Directory domain configurations per tenant.

**migrations** — tracks applied migration filenames to detect unapplied changes.

Key `zones` columns: `private_zone_uid` (NULL = public; non-NULL = private, owner's `users.id`).

Key `hosts` columns: `c_id` (current certificate FK), `c_id_old` (previous certificate FK — used for change detection), `ignore`, `mute`, `h_recipients` (per-host notification overrides).

The `config` table stores per-tenant configuration overrides for settings defined in `config/config.php`.

### Dual Configuration System

`config/config.php` provides global defaults. The `Config` class reads the `config` DB table and composes per-tenant overrides. This means some settings (like `$expired_days`) can differ per tenant at runtime, even though `config/config.php` defines the baseline.

### Global Variables in Route Files

After `functions/autoload.php` runs, all route files and modal handlers have these globals in scope:

| Variable | Type | Description |
|----------|------|-------------|
| `$Database` | `Database_PDO` | DB abstraction layer |
| `$User` | `User` | Auth class; call `$User->validate_session()` to require login |
| `$user` | `stdClass` | Current user row (`$user->t_id`, `$user->admin`, `$user->id`) |
| `$_params` | `array` | Parsed URL: keys `tenant`, `route`, `app`, `id1` |
| `$SSL` | `SSL` | SSL scanner |
| `$Certificates` | `Certificates` | Certificate CRUD |
| `$Zones` | `Zones` | Zone/host management |
| `$Tenants` | `Tenants` | Tenant management |
| `$Log` | `Log` | Audit logging |
| `$Modal` | `Modal` | Modal HTML builder |
| `$Result` | `Result` | JSON/alert response formatter |
| `$Config` | `Config` | Per-tenant config overrides |
| `$Cron` | `Cron` | Cron orchestration |
| `$testssl_available` | `bool` | Whether `functions/testSSL/testssl.sh` exists on disk |

Note: `TestSSL` is **not** a global — instantiate per-request with `new TestSSL($Database)`.

### Class Inheritance

`Validate` ← `Common` ← `SSL`, `User`, `URL`, `Config`, `Zones`

All domain classes inherit shared utilities from `Common` (permalink generation, error handling, etc.), which itself extends `Validate` (input sanitization).

### Frontend

Tabler 1.7.5 (Bootstrap-based admin UI) + jQuery 3.6.0 + Bootstrap-table 1.26.0. All JS/CSS libraries are bundled locally in `public/js/` and `public/css/`. Custom JS is in `public/js/magic.js`.

**Layout / theme settings** (profile → Layout tab, `/{tenant}/profile/layout/`): `class.Layout.php` (global `$Layout`) holds the Tabler theme options (color scheme, accent, theme base, radius, navbar/sidebar navigation, container width, sticky navbar, navigation/offcanvas style, sidebar folding), validates them and renders them as `data-bs-*` attributes on `<html>` via `$Layout->html_attributes()`. Stored as JSON in `users.layout` (migration `0031_users_layout.sql`; only explicitly chosen values, NULL = defaults). While impersonating, changes go to `$_SESSION['layout_impersonate']` only; if the column is missing (migration not applied) or nobody is logged in, `$_SESSION['layout']` is used. Saved via `route/ajax/layout-save.php`. The header light/dark toggle sets the `scheme` option. Page structure in `public/index.php` follows Tabler's `.page` layout: sidebar (`route/common/left-menu.php`, vertical mode, uses `data-bs-navbar-position="combined"` so the top header stays visible) or horizontal top menu (`route/common/top-menu.php`), then `route/common/header.php`, then `.page-wrapper > .page-body`, which wraps all page content (route pages must not add their own `.page-body`). The logo is shared in `route/common/logo.php` (both light/dark variants, switched by CSS).

**Accessibility — color-blind friendly status indicators** (Layout tab → Accessibility, option `status-style`: `default` | `accessible`, rendered as `data-bs-status-style` on `<html>`). When `accessible`:
- `public/css/style.css` remaps `--tblr-green/success` → blue, `--tblr-red/danger` → vermillion, `--tblr-orange` → amber (Okabe–Ito palette) app-wide.
- Certificate status is shown as a different icon per status instead of a color-only dot. Use `$Certificates->get_status_indicator($code)` for compact table indicators (dot or icon) and `get_status_text()` for labelled badges (adds the icon automatically); `get_status_icon()`, `get_status_label()`, `get_status_color()` are the building blocks. Do not hand-build colored status dots — they bypass this setting.

**CSS/JS paths** and all `/route/...` URLs are hardcoded as absolute paths (`/css/`, `/js/`) — the app must be served at the root of a host (no subdirectory installs).

**Two modal sizes** are available globally:
- `#modal1` — standard width (default when no `data-bs-target` is set)
- `#modal2` — extra-large (`modal-xl`) for wide content; trigger with `data-bs-target="#modal2"`

**AJAX data endpoints** in `route/ajax/` serve JSON rows for Bootstrap Table's server-side pagination (e.g., `route/ajax/certificates.php`, `route/ajax/zone-hosts.php`, `route/ajax/logs.php`). Add new bootstrap-table AJAX sources here.

### testSSL Integration

- `class.testssl.php` handles all DB operations, scan execution, JSON parsing, export, and email
- Scans run via `proc_open()` with an argument array (no shell — avoids injection)
- `run_pending()` is called from `functions/cron/testssl_scan.php` on every cron run
- JSON output is parsed by `parse_result()` into display sections; rating extracted from `overall_grade` id
- Public unauthenticated report at `/report/<hash>/` — handled in `index.php` before tenant routing
- `route/ajax/testssl-export-public.php` — unauthenticated JSON/CSV download by hash
- `route/ajax/testssl-export.php` — authenticated download by scan id
- On completion, if `notify_email` is set, `send_completion_email()` sends scan summary via `mailer`
- FreeBSD requires `fdescfs` mounted at `/dev/fd` for testssl.sh to work

### CA and CSR Management

- CAs are stored in the `cas` table, support hierarchical chains via `parent_ca_id`
- CSRs are stored in the `csrs` table; can be signed by internal CAs or exported for external signing
- Private keys in `pkey` table are shared across certificates, CAs, and CSRs by `pkey_id` FK
- `route/ajax/ca/` and `route/ajax/csr/` subdirectories contain feature-specific AJAX endpoints
- CSR templates (`csr_templates` table) allow saving reusable subject/extension profiles

### WebAuthn / Passkeys

- `class.WebAuthn.php` implements ES256 and RS256 credential verification
- Passkeys stored in `passkeys` table per user
- AJAX endpoints: `route/ajax/passkey/` (register, challenge, auth, delete)
- Users can have `force_passkey` flag set by admin to require passkey login

### Private Zones

Zones can be marked private at creation time (`private_zone_uid` column on `zones` table). Rules:
- `private_zone_uid IS NULL` — public zone, visible to all users in the tenant (and admins)
- `private_zone_uid = user.id` — private zone, visible only to its creator
- Admins **cannot** see private zones belonging to other users; the zones page shows a note if hidden private zones exist
- Impersonation (`$_SESSION['impersonate_original']` is set) blocks all private zone access — even the impersonated user's own private zones are hidden
- Cron scripts still scan private zones but send notifications only to the zone creator (no tenant-wide recipients, no BCC leakage)
- Private zone filtering is applied consistently in: `Zones::get_all()`, `Zones::search_zone_hosts()`, `Certificates::get_expired()`, `route/ajax/zone-hosts.php`, `route/ajax/certificates.php`, `route/ajax/logs.php`
- Logs filter uses both a live-table subquery (for existing records) and `JSON_EXTRACT(json_object_old, '$.hosts.0.z_id')` fallback (for deleted records, relies on `$log_object = true`)
- Access checks in `route/zones/zone/index.php`, `route/certificates/certificate.php`, and `route/modals/zones/edit-submit.php` set the zone/cert to `null` if access is denied

### Admin Impersonation

When an admin impersonates another user, `$_SESSION['impersonate_original']` is set to the admin's original username. Check this flag anywhere private-zone or sensitive access control decisions are made. Stopping impersonation clears this key.

### Database Schema Management

Incremental migrations live in `db/migrations/` (numbered `NNNN_description.sql`). Applied migrations are tracked in the `migrations` DB table. The UI shows a warning banner (via `route/common/checks.php`) when unapplied migrations exist. Always keep `db/SCHEMA.sql` (full dump) in sync after any schema change.

### System Health Checks (`route/common/checks.php`)

Included on every authenticated page. Checks:
- **Unapplied migrations** (admin only) — compares `db/migrations/*.sql` filenames against `migrations` table
- **Missing git submodules** (all users) — checks `functions/assets/Net_DNS2/Net/DNS2.php`, `functions/assets/PHPMailer/src/PHPMailer.php`, `functions/testSSL/testssl.sh`

### Configuration (`config/config.php`)

Key settings beyond DB credentials:
- `$expired_days` — days before expiry to warn (default 20)
- `$expired_after_days` — days post-expiry to still report (default 7)
- `$log_object` — whether to store full object JSON in `logs.json_object_old/new` (default `true`; required for private zone log filtering of deleted records)
- `$mail_settings` / `$mail_sender_settings` — SMTP configuration

## Coding Conventions

### PHP Version Compatibility

The server runs **PHP 8.3**. PHP 8.0+ syntax is fully supported and preferred:
- `match` expressions, `str_starts_with()`, `str_ends_with()`, `str_contains()`
- Named arguments, union types, nullsafe operator (`?->`)
- Enums (PHP 8.1+), readonly properties (PHP 8.1+), fibers (PHP 8.1+)
- A runtime warning banner is shown to all users if PHP < 8.0 is detected (via `route/common/checks.php`)

### Translations

All user-visible strings in PHP templates and route files **must** be wrapped in `_()` so they are picked up by gettext and can be translated. This applies to every `print`, `echo`, button label, placeholder, alert text, table header, and tooltip. Never output a bare English string directly.

```php
// correct
print _("Save");
echo _("No records found.");
<input placeholder="<?php print _('Search...'); ?>">

// wrong — not translatable
print "Save";
echo "No records found.";
```

When adding new strings, also add the corresponding `msgid` / `msgstr` entries to both translation files:
- `functions/locale/sl_SI.UTF-8/LC_MESSAGES/messages.po`
- `functions/locale/de_DE.UTF-8/LC_MESSAGES/messages.po`

After editing `.po` files, recompile on the server with `msgfmt messages.po -o messages.mo`.
