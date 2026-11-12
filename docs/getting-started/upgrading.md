# Upgrading

php-ssl uses numbered SQL migration files to evolve the database schema incrementally. After every `git pull` that includes new migration files, you must apply any pending migrations.

---

## Upgrading to the `public/` directory layout

Web-facing files moved into `public/` and the cron entry point moved to `cron/cron.php`. When you pull this change on an existing installation:

1. **Change the web server document root** to `<app>/public` and add the `/route/` rewrite — see [Web Server Configuration](../operations/apache-nginx.md). nginx installations stop working until this is done. Apache installations keep working through the fallback `.htaccess` in the application root, but should still be switched.
2. **Update the crontab** to `/usr/bin/php <app>/cron/cron.php`. The old `cron.php` in the application root is a deprecated shim that forwards to the new location, so existing crontabs keep working in the meantime.
3. If PHP `open_basedir` is set, make sure it covers the application directory, not just `public/`.

---

## Migration files

Migrations live in `db/migrations/` and are named `NNNN_description.sql`, for example:

```
0001_add_changepass_to_users.sql
0002_add_test_to_users.sql
...
```

Applied migrations are tracked in the `migrations` database table (by filename). A migration is never applied twice.

---

## Checking for pending migrations

### In the UI

A warning banner is shown at the top of every page for admin users when unapplied migrations exist. It links directly to the migration management page.

### From the command line

```bash
php db/migrate.php status
```

Output:

```
DB version : 0023_add_testssl_table.sql
Latest     : 0030_logs_mediumtext.sql
Pending    : 7

Pending migrations:
  - 0024_add_notify_email_to_testssl.sql
  - 0025_cas_tenant_fk.sql
  ...
```

---

## Applying migrations

### From the command line (recommended)

```bash
php db/migrate.php
```

This applies all pending migrations in order and reports success or failure for each.

### From the UI

Admins can apply individual migrations from the **Database Migrations** page in the application.

---

## Keeping SCHEMA.sql in sync

`db/SCHEMA.sql` is a full dump of the schema and is used by the web installer. After any schema change — whether a new migration file or a direct alteration — regenerate it:

```bash
mysqldump --no-data --routines php-ssl > db/SCHEMA.sql
```

Commit both the migration file and the updated `SCHEMA.sql` together.

---

## Upgrade procedure summary

```bash
git pull
git submodule update --recursive
php db/migrate.php
```

If `cron/cron.php` is running, migrations are safe to apply while it runs — each migration is a discrete SQL statement and the cron scripts are tenant-scoped.
