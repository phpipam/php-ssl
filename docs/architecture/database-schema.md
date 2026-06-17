# Database Schema

## Table hierarchy

```
tenants
└── zones (t_id)
    └── hosts (z_id)
        └── certificates (z_id, t_id)
users (t_id)
agents (t_id)
cas (t_id)
csrs (t_id)
testssl (tenant_id)
nmap_scans (tenant_id, zone_id)
ssl_port_groups (t_id)
cron (t_id)
config (t_id)
pkey ← referenced by certificates, cas, csrs
passkeys (user_id)
domains (t_id)
translations
migrations
logs
```

---

## Core tables

### `tenants`

The root of all tenant-scoped data.

| Column | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `name` | varchar(255) | Display name |
| `href` | varchar(255) | URL slug — used in all application URLs |
| `description` | text | Optional |
| `active` | tinyint | 1 = active |
| `admin` | tinyint | 1 = this is the admin tenant |
| `recipients` | text | Semicolon-separated email list for change notifications |
| `mail_style` | enum | `list` or `table` — email format for change notifications |
| `remove_orphaned` | tinyint | Whether the remove_orphaned cron script runs for this tenant |
| `log_retention` | int | Days to keep audit log entries |
| `lang_id` | int FK | Default language for the tenant (→ `translations`) |

### `zones`

A zone groups hosts for scanning. Can be a manual list or a DNS zone (AXFR).

| Column | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `t_id` | int FK | Tenant |
| `name` | varchar(255) | Zone name / domain |
| `type` | enum | `local` (manual) or `axfr` (DNS zone transfer) |
| `agent_id` | int FK | Scanning agent to use (→ `agents`) |
| `is_domain` | tinyint | If 1, zone name is appended to each hostname on add |
| `ignore` | tinyint | If 1, zone is excluded from all scans |
| `private_zone_uid` | int FK | NULL = public; user ID = private, owner-only (→ `users`) |
| `dns` | varchar | DNS server for AXFR |
| `tsig_name`, `tsig` | varchar | TSIG key for authenticated zone transfers |
| `record_types` | varchar | Comma-separated DNS record types to import via AXFR |
| `delete_records` | tinyint | If 1, AXFR removes hosts no longer in DNS |
| `check_ip` | tinyint | If 1, AXFR also imports A record IP addresses |
| `regex_include`, `regex_exclude` | text | Patterns to filter AXFR hostnames |

### `hosts`

One row per monitored hostname.

| Column | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `z_id` | int FK | Zone |
| `c_id` | int FK | Current certificate (→ `certificates`) |
| `c_id_old` | int FK | Previous certificate — used for change detection |
| `pg_id` | int FK | Port group (→ `ssl_port_groups`) |
| `hostname` | varchar(255) | The hostname or IP to scan |
| `ip` | varchar(15) | Last resolved IP address |
| `port` | int | Port where the current certificate was found |
| `tls_version` | varchar | TLS version from the last successful scan |
| `ignore` | tinyint | If 1, host is excluded from all scans |
| `mute` | tinyint | If 1, no change notifications are sent for this host |
| `h_recipients` | text | Semicolon-separated per-host notification email overrides |
| `last_check` | timestamp | Last scan attempt (success or failure) |
| `last_change` | timestamp | Last time the certificate changed |

### `certificates`

Stores discovered certificate PEM data. Each unique serial+zone combination is one row.

| Column | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `z_id` | int FK | Zone |
| `t_id` | int FK | Tenant |
| `serial` | varchar(255) | Certificate serial number |
| `certificate` | text | PEM-encoded leaf certificate |
| `chain` | text | PEM-encoded intermediate chain (leaf first) |
| `expires` | datetime | Certificate expiry date |
| `aki` | varchar(255) | Authority Key Identifier — links to signing CA's SKI |
| `is_manual` | tinyint | 1 = manually imported, excluded from orphan cleanup |
| `pkey_id` | int FK | Associated private key, if stored (→ `pkey`) |

**Unique constraint:** `(z_id, serial)` — a given serial can appear in multiple zones (different tenants) but only once per zone.

---

## Supporting tables

### `cas` — Certificate Authorities

CAs discovered from scanned certificate chains, or manually imported.

| Column | Notes |
|---|---|
| `ski` | Subject Key Identifier — used to match against `certificates.aki` |
| `parent_ca_id` | Self-referencing FK for hierarchical chain display |
| `source` | `auto` (discovered from scan) or `manual` (imported) |
| `ignore_updates` | If 1, the cert is not updated when a new version is discovered |
| `ignore_expiry` | If 1, the expiry notification cron skips this CA |
| `pkey_id` | If set, this CA has a stored private key and can sign CSRs |

### `csrs` — Certificate Signing Requests

| Column | Notes |
|---|---|
| `status` | `pending`, `submitted`, or `signed` |
| `source` | `internal` (key generated here) or `external` (CSR imported) |
| `pkey_id` | Private key used to generate the CSR |
| `cert_id` | Resulting certificate after signing |
| `renewed_by` | Points to a newer CSR if this one was renewed |

### `pkey` — Private keys

Single-column table (`private_key_enc`). The encrypted private key is stored as a base64-encoded AES-256-GCM ciphertext. The encryption key comes from `$private_key_encryption_key[t_id]` in `config.php`. Referenced by `certificates`, `cas`, and `csrs`.

### `ssl_port_groups`

Named groups of ports to scan (e.g. `pg_ssl` = `443,8443`). Assigned per zone/host.

### `agents`

Remote scanning agents. The built-in local agent (`id=1`, `is_global=1`) scans from the server itself. Additional agents communicate via HTTP API.

### `cron`

Per-tenant cron schedules. Standard cron fields plus `script` (the PHP cron script name) and `force` (run on next tick regardless of schedule).

### `config`

Key/value pairs scoped to a `t_id`. Override any setting from `config.php` for a specific tenant.

### `testssl`

One row per testssl.sh scan request.

| Column | Notes |
|---|---|
| `hash` | 64-character hex — used in public report URLs |
| `status` | `Requested`, `Scanning`, `Completed`, `Cancelled`, `Error` |
| `json_result` | Raw JSON output from testssl.sh |
| `notify_email` | If set, an email is sent on completion |

### `logs`

Audit trail for all object changes.

| Column | Notes |
|---|---|
| `object` | Table name (e.g. `hosts`, `zones`, `certificates`) |
| `object_id` | Row ID in the source table |
| `action` | e.g. `add`, `refresh`, `delete` |
| `json_object_old` | Full object JSON before the change (requires `$log_object = true`) |
| `json_object_new` | Full object JSON after the change |

### `migrations`

Tracks applied migration filenames. Prevents re-applying migrations after git pulls.

### `passkeys`

WebAuthn credentials per user. Stores the public key, credential ID, and sign count.

### `domains`

Active Directory / LDAP configuration per tenant for user synchronisation.

### `translations`

Available UI languages. Each row maps a language name to a gettext locale code.

### `nmap_scans`

One row per nmap host-discovery scan request.
