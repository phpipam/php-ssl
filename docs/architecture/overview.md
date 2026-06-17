# Architecture Overview

## Request flow

```
Browser request
    │
    ▼
index.php
    ├── Loads config.php
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

The HTML shell (head, nav, sidebar) is rendered in `index.php`. The `<main>` content area is populated by the included route file. Modal dialogs and AJAX data endpoints are loaded asynchronously by the browser.

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

Modals are loaded asynchronously. A link with `data-bs-toggle="modal"` and an `href` triggers `$('.modal-content').load(href)` in `js/magic.js`. Two modal sizes are available:

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

All libraries are bundled locally in `js/` and `css/` — no CDN dependency.

Custom JavaScript lives in `js/magic.js`. The dark/light theme toggle is stored in `$_SESSION['theme']`.
