# Installation

## Requirements

| Component | Minimum |
|---|---|
| OS | Linux or Unix-like |
| Web server | Apache 2.4+ or nginx |
| PHP | 8.0 (8.3 recommended) |
| Database | MySQL 5.7+ or MariaDB 10.3+ |

**Required PHP extensions:** `curl`, `gettext`, `openssl`, `pcntl`, `PDO`, `pdo_mysql`, `session`

**Optional:**
- `nmap` binary — for network host discovery scans
- `testssl.sh` — for deep TLS analysis (configured as a git submodule)

---

## 1. Clone the repository

Clone with submodules. The `functions/assets/` and `functions/testSSL/` directories are git submodules (Net_DNS2, PHPMailer, testssl.sh).

```bash
cd /var/www/html/
git clone --recursive https://github.com/phpipam/php-ssl.git php-ssl
```

If you already cloned without `--recursive`, initialise the submodules afterwards:

```bash
cd php-ssl
git submodule update --init --recursive
```

---

## 2. Configure the application

Copy the example config file and edit it:

```bash
cp config/config.dist.php config/config.php
```

At minimum, update the database credentials. See [Configuration](configuration.md) for the full reference.

---

## 3. Create the database

```sql
CREATE DATABASE `php-ssl`;
CREATE USER 'phpssladmin'@'localhost' IDENTIFIED BY 'phpssladmin';
GRANT ALL ON `php-ssl`.* TO 'phpssladmin'@'localhost';
```

---

## 4. Configure your web server

Point the document root at the `public/` directory of the checkout (e.g. `/var/www/html/php-ssl/public`) and set up the rewrites. See [Web Server Configuration](../operations/apache-nginx.md).

---

## 5. Run the web installer

Open the installer in your browser:

```
https://<your-server>/install/
```

The installer will:
1. Verify the database connection
2. Import `db/SCHEMA.sql` (creates all tables and seeds default data)
3. Create the initial admin user

Once complete, set the installed flag in `config/config.php` to disable the installer:

```php
$installed = true;
```

> **Important:** Leaving `$installed = false` after a successful install allows anyone to re-run the installer and overwrite your database.

---

## 6. Seed the cron schedule (if not done by installer)

The installer seeds default cron schedules for tenant 1. If you need to re-add them manually:

```sql
INSERT INTO `cron` (`t_id`, `minute`, `hour`, `day`, `month`, `weekday`, `script`)
VALUES
  (1, '*/30', '*', '*', '*', '*', 'update_certificates'),
  (1, '15',   '2', '*', '*', '*', 'remove_orphaned'),
  (1, '0',    '8', '*', '*', '*', 'expired_certificates'),
  (1, '0',    '3', '*', '*', '*', 'axfr_transfer'),
  (1, '15',   '1', '*', '*', '*', 'backup');
```

---

## 7. Configure the system crontab

Add a single crontab entry to drive all scheduled jobs:

```cron
*/5 * * * * /usr/bin/php /var/www/html/php-ssl/cron/cron.php
```

This runs every 5 minutes. The application checks its internal per-tenant schedules (stored in the `cron` DB table) to decide which scripts to actually execute. See [Crontab Setup](../operations/crontab-setup.md) for details.

---

## Default login

| Field | Value |
|---|---|
| Email | `admin` |
| Password | `admin` |

You will be prompted to change the password on first login.
