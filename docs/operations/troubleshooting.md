# Troubleshooting

## Installation

### "Config file missing" on first load

`config.php` does not exist. Copy `config.dist.php` to `config.php` and fill in your database credentials before loading the app.

### Installer redirects to itself repeatedly

The installer checks the DB connection. Verify:
- The DB host, user, password, and database name in `config.php` are correct
- The MySQL/MariaDB server is running and reachable from the web server
- The DB user has `ALL` privileges on the database

### Blank page or HTTP 500 after install

Enable debugging temporarily to see the error:

```php
$debugging = true;
```

Remember to set it back to `false` afterwards.

---

## Git submodules

The **System Health** banner (visible to all logged-in users) shows a warning if any of the following are missing:

| File | Submodule |
|---|---|
| `functions/assets/Net_DNS2/Net/DNS2.php` | Net_DNS2 |
| `functions/assets/PHPMailer/src/PHPMailer.php` | PHPMailer |
| `functions/testSSL/testssl.sh` | testssl.sh |

Fix with:

```bash
git submodule update --init --recursive
```

---

## Scanning

### "Threading is required for scanning certificates"

The `pcntl` PHP extension is missing or disabled. Install it:

```bash
# Debian/Ubuntu
apt install php8.3-pcntl

# RHEL/CentOS
yum install php-process
```

Verify it is loaded:

```bash
php -m | grep pcntl
```

### Hosts show "Unresolved" IP

The web server user cannot resolve DNS for the hostname. Check:
- `/etc/resolv.conf` is correctly configured
- The hostname is reachable from the server
- If the host is internal, consider using a [remote scanning agent](../features/agents.md)

### Certificate fetch fails with SSL error

Common causes:
- The server is only reachable on a non-standard port — check the port group assignment for the zone
- A firewall blocks outbound connections on port 443 from the web server
- The remote server requires SNI and the PHP `openssl` extension version is old

### Cron doesn't run

1. Confirm the system crontab entry exists: `crontab -l`
2. Check the cron log: `grep CRON /var/log/syslog`
3. Run manually to see output: `php /var/www/html/php-ssl/cron.php`
4. Confirm the in-app schedule exists under **Scanning → Cron** for your tenant

---

## Database migrations

### Warning banner: "There are unapplied database migrations"

Apply pending migrations:

```bash
php db/migrate.php
```

Or apply them individually through the admin UI.

### Migration fails with a foreign key error

This usually means the schema is in a state inconsistent with the migration, often from a partial previous run. Check the error message for the table and column involved, then inspect the current schema:

```bash
mysql -u phpssladmin -p php-ssl -e "DESCRIBE <table>;"
```

---

## testssl.sh

### Scans stay in "Requested" state

- Confirm `functions/testSSL/testssl.sh` exists (submodule initialised)
- Confirm `cron.php` is running — testssl scans are picked up on every cron run
- Check for errors in the scan record via the testssl detail page

### testssl.sh fails on FreeBSD

Mount `fdescfs`:

```
fdescfs   /dev/fd   fdescfs   rw   0   0
```

Add to `/etc/fstab` and run `mount /dev/fd`.

---

## Email notifications

### No emails sent after certificate change

1. Verify SMTP settings in `config.php` (or the tenant's config overrides)
2. Check that the tenant has at least one recipient configured under **Tenants → Edit**
3. Run `update_certificates` manually and check for mail-related PHP errors: `php cron.php 1 update_certificates`
4. Confirm the host is not muted (mute icon in the zone host list)

---

## PHP version

A runtime warning is shown to all users if PHP < 8.0 is detected. The server requires PHP 8.0+; PHP 8.3 is the recommended version. Check your active version:

```bash
php --version
```

If multiple PHP versions are installed, ensure the CLI version matches the web server version.
