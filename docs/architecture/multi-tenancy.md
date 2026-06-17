# Multi-Tenancy

php-ssl is a multi-tenant application. Every piece of user data is scoped to a tenant, and tenants are fully isolated from one another.

---

## Tenant identification

Tenants are identified in URLs by their `href` slug (e.g. `/admins/`), not their numeric `id`. The slug is set when the tenant is created and used to construct all links within the application.

The `tenants` table is the top of the ownership hierarchy:

```
tenants → zones → hosts → certificates
```

All primary tables (`zones`, `hosts`, `certificates`, `users`, `agents`, `cas`, `csrs`, `testssl`) carry a `t_id` (tenant ID) column. All queries are scoped to `$user->t_id` unless the user is an admin.

---

## User roles

User permission levels are stored in `users.permission`:

| Level | Name | Can do |
|---|---|---|
| 0 | No access | Read-only with restricted views |
| 1 | Read | View certificates and zones |
| 2 | Write | Add hosts, trigger manual scans |
| 3 | Admin | Edit zones, import, delete |

The `users.admin` flag (separate from `permission`) grants system-wide admin access: the user can see all tenants, manage other tenants' data, and access the Tenants menu.

### Admin capabilities

- View and manage all tenants
- Impersonate any user (see below)
- Apply database migrations
- See the system health banner warnings

---

## Private zones

A zone can be marked as private at creation time. The `zones.private_zone_uid` column controls visibility:

| Value | Meaning |
|---|---|
| `NULL` | Public zone — visible to all users in the tenant (and admins) |
| `user.id` | Private zone — visible only to the creating user |

**Key rules:**
- Admins **cannot** see private zones belonging to other users
- The zones list shows a note when hidden private zones exist in the tenant
- Cron scripts scan private zones but send notifications only to the zone creator — tenant-wide recipients are never BCC'd on private-zone changes
- If `$log_object = false` in `config.php`, private zone filtering in the logs page for deleted-zone records will not work correctly

**Where private-zone filtering is applied:**
- `Zones::get_all()`
- `Zones::search_zone_hosts()`
- `Certificates::get_expired()`
- `route/ajax/zone-hosts.php`
- `route/ajax/certificates.php`
- `route/ajax/logs.php`

---

## Admin impersonation

Admins can impersonate any user. When impersonating, `$_SESSION['impersonate_original']` is set to the admin's original username.

**Impersonation blocks all private zone access** — even the impersonated user's own private zones are hidden while the session is impersonated. This prevents admins from using impersonation to read private data.

Check the flag anywhere sensitive access control decisions are made:

```php
if (isset($_SESSION['impersonate_original'])) {
    // block private zone access
}
```

Stopping impersonation clears this key.

---

## Tenant configuration overrides

The `config` table stores per-tenant key/value overrides for settings defined in `config.php`. The `Config` class reads these and merges them over the global defaults at runtime, so `$expired_days`, mail settings, `scanMaxThreads`, and other values can differ per tenant.

The resolution order for user-facing expiry thresholds is:
1. User's personal setting (`users.days`)
2. Tenant config override (`config` table)
3. Global default (`config.php`)
