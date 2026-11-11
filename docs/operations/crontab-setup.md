# Crontab Setup

php-ssl uses a two-level scheduling system:

1. **System crontab** — triggers `cron.php` every 5 minutes
2. **In-app schedules** — per-tenant cron schedules stored in the `cron` DB table, checked by `cron.php` on each run

---

## System crontab entry

Add one entry to the system crontab (as the web server user or a dedicated service account):

```cron
*/5 * * * * /usr/bin/php /var/www/html/php-ssl/cron.php
```

This is the only system-level entry needed. All scheduling granularity beyond "every 5 minutes" is handled by the in-app schedules.

---

## In-app schedules

Schedules are managed per-tenant under **Scanning → Cron** in the UI. Each row in the `cron` table has standard cron fields (`minute`, `hour`, `day`, `month`, `weekday`) plus a `script` name.

Default schedules seeded at install time for tenant 1:

| Script | Default schedule | Purpose |
|---|---|---|
| `update_certificates` | Every 30 minutes | Scan all hosts for certificate changes |
| `expired_certificates` | Daily at 08:00 | Send expiry notification emails |
| `remove_orphaned` | Daily at 02:15 | Remove certificates no longer assigned to any host |
| `axfr_transfer` | Daily at 03:00 | Perform DNS zone transfers (AXFR) |
| `backup` | Daily at 01:15 | Create a database backup |

**Note:** `testssl_scan` is not in this table. It runs on every `cron.php` invocation regardless of schedule, picks up any scans with status `Requested`, and executes testssl.sh.

### Force flag

Each schedule row has a `force` column. Setting it to `1` causes the script to run on the next cron execution regardless of whether the schedule time has been reached, then resets to `0`.

---

## Manual execution

Run any cron script directly from the command line:

```bash
php cron.php <tenant_id> <script_name>
```

Examples:

```bash
php cron.php 1 update_certificates
php cron.php 1 expired_certificates
php cron.php 1 axfr_transfer
php cron.php 1 remove_orphaned
php cron.php 1 backup
```

All cron scripts are CLI-only and will exit with an error if called from a web request.

---

## Parallelism

`update_certificates` uses `pcntl_fork` to scan hosts in parallel. The maximum number of concurrent processes is controlled by the `scanMaxThreads` config setting (default: 64, configurable per tenant). The `pcntl` PHP extension must be installed and enabled.

---

## FreeBSD note

testssl.sh on FreeBSD requires `fdescfs` mounted at `/dev/fd`:

```
fdescfs   /dev/fd   fdescfs   rw   0   0
```

Add this to `/etc/fstab` and mount before running scans.
