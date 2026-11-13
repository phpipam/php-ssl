# Architecture Overview

## Directory layout

```
public/              ← web document root (the only web-accessible directory)
├── index.php        ← front controller for pages
├── route.php        ← dispatcher for /route/ajax/*, /route/modals/*, /route/login/login_check.php
├── .htaccess        ← Apache rewrites (/route/* → route.php, everything else → index.php)
└── css/  js/  favicon.*
cron/cron.php        ← cron entry point (CLI only)
route/               ← page templates, AJAX endpoints, modals (not web-accessible)
functions/           ← autoload, classes, cron job scripts, locale, submodules
db/                  ← SCHEMA.sql, migrations, backups
config/              ← config.php (local, gitignored), config.dist.php (template)
version.php
```

Code in `public/` reaches the rest of the application via `__DIR__ . '/../…'`. Only `index.php` and `route.php` belong in `public/`.

## Request flow

```
Browser request
    │
    ▼
public/index.php
    ├── Loads config/config.php
    ├── require functions/autoload.php
    │       ├── Instantiates all classes (Database_PDO, URL, User, SSL, …)
    │       ├── Parses URL → $_params
    │       └── Initialises gettext locale
    │
    ├── Special top-level slugs (no tenant routing):
    │       login/      → route/login/index.php
    │       install/    → route/install/index.php
    │       report/<hash>/  → public testSSL report (no auth)
    │
    └── Authenticated request
            ├── Session validation ($User->validate_session())
            └── route/content.php
                    └── route/{route}/index.php
```

The HTML shell (head, nav, sidebar) is rendered in `public/index.php`. The `<main>` content area is populated by the included route file. Modal dialogs and AJAX data endpoints are loaded asynchronously by the browser.

### Endpoint requests (`/route/…`)

```
Browser request /route/ajax/… | /route/modals/… | /route/login/login_check.php
    │
    ▼
public/route.php
    ├── Allowlist check: (ajax|modals)/[a-z0-9/_-]+\.php or login/login_check.php
    ├── realpath() must stay inside route/  — otherwise 404
    ├── chdir() to the endpoint's directory
    └── require the endpoint
            └── endpoint requires functions/autoload.php itself (relative path)
```

Files elsewhere under `route/` (page templates, shared includes) cannot be requested directly — they return 404.

---

## URL structure

URLs follow the pattern:

```
/{tenant}/{route}/{app}/{id1}/
```

| Segment | Key | Description |
|---|---|---|
| `tenant` | `$_params['tenant']` | The tenant's `href` slug (not its numeric ID) |
| `route` | `$_params['route']` | Feature area (e.g. `zones`, `certificates`, `testssl`) |
| `app` | `$_params['app']` | Sub-resource (e.g. zone name, certificate serial) |
| `id1` | `$_params['id1']` | Further qualifier (e.g. hostname within a zone) |

Parsing is done by `class.URL.php`. Valid routes are defined in `$url_items` in `functions/config.menu.php`. Requests to `route/` paths are handled directly by the browser (AJAX/modal loads) and are excluded from tenant routing.

### Examples

| URL | What it shows |
|---|---|
| `/admins/zones/` | Zone list for the `admins` tenant |
| `/admins/zones/example.com/` | Host list for the `example.com` zone |
| `/admins/zones/example.com/web01.example.com/` | Host detail page |
| `/admins/certificates/example.com/0x1234ABCD/` | Certificate detail page |
| `/admins/testssl/abc123.../` | testssl scan result |
| `/report/abc123.../` | Public testssl report (no login required) |

---

## Autoloader and globals

`functions/autoload.php` runs on every web request and makes the following variables available globally in all route files and modal handlers:

| Variable | Type | Description |
|---|---|---|
| `$Database` | `Database_PDO` | Database abstraction — all DB access goes through this |
| `$User` | `User` | Auth and session management |
| `$user` | `stdClass` | Current user row (`$user->t_id`, `$user->admin`, `$user->id`) |
| `$_params` | `array` | Parsed URL: `tenant`, `route`, `app`, `id1` |
| `$SSL` | `SSL` | SSL scanner |
| `$Certificates` | `Certificates` | Certificate CRUD |
| `$Zones` | `Zones` | Zone and host management |
| `$Tenants` | `Tenants` | Tenant management |
| `$Log` | `Log` | Audit logging |
| `$Modal` | `Modal` | Modal HTML builder |
| `$Result` | `Result` | JSON/alert response formatter |
| `$Config` | `Config` | Per-tenant config overrides |
| `$Cron` | `Cron` | Cron schedule management |
| `$testssl_available` | `bool` | Whether `functions/testSSL/testssl.sh` exists |

`TestSSL` is **not** a global — instantiate it per-request with `new TestSSL($Database)`.

---

## Route structure

```
route/
├── content.php          ← dispatcher: includes route/{route}/index.php
├── {feature}/
│   └── index.php        ← feature page
├── ajax/
│   └── *.php            ← JSON endpoints for Bootstrap Table (server-side pagination)
│   ├── ca/              ← CA-specific AJAX (download, delete, toggle flags)
│   ├── csr/             ← CSR-specific AJAX
│   └── passkey/         ← WebAuthn registration/auth
├── modals/
│   └── {feature}/
│       ├── edit.php          ← renders modal form HTML
│       └── edit-submit.php   ← processes POST, returns JSON result
└── common/
    ├── checks.php            ← system health alerts (shown on every page)
    ├── header.php            ← top navigation bar
    ├── header-notifications.php
    └── left-menu.php         ← sidebar menu
```

### Modal pattern

Modals are loaded asynchronously. A link with `data-bs-toggle="modal"` and an `href` triggers `$('.modal-content').load(href)` in `public/js/magic.js`. Two modal sizes are available:

- `#modal1` — standard width (default)
- `#modal2` — extra-large (`modal-xl`); trigger with `data-bs-target="#modal2"`

---

## Frontend stack

| Library | Version | Purpose |
|---|---|---|
| Tabler | 1.4.0 | Bootstrap-based admin UI |
| Bootstrap | (bundled with Tabler) | Layout and components |
| jQuery | 3.6.0 | DOM manipulation and AJAX |
| Bootstrap Table | 1.26.0 | Server-side paginated tables |
| Tippy.js | — | Tooltips |

All libraries are bundled locally in `public/js/` and `public/css/` — no CDN dependency.

Custom JavaScript lives in `public/js/magic.js`. The dark/light theme toggle is stored in `$_SESSION['theme']`.
