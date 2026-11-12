## php-ssl :: php certificate scanner

php-ssl is a PHP 8+ SSL/TLS certificate monitoring web application. It scans predefined hostnames
for certificate changes and provides visibility into certificates used for specific domains or
hostnames. It supports DNS zone transfers (AXFR) to automatically discover and track hosts,
remote scanning agents for environments with restricted DNS resolution, and email notifications
for certificate changes and upcoming expirations.

## Main features

- **Multi-tenant** support with full tenant isolation
- **Remote scanning agents** for environments with limited DNS resolution
- **DNS zone transfer (AXFR)** — automatically imports hostnames from DNS servers
- **Parallel scanning** — multi-process certificate scanning via `pcntl_fork`
- **Certificate change notifications** — email alerts when a certificate changes (cron)
- **Certificate expiry notifications** — daily alerts for certificates nearing expiration (cron)
- **On-demand certificate fetch** from any hostname
- **Certificate details** page with chain validation
- **Per-host notification and ownership** settings
- **Ignored issuers** — suppress notifications for specific certificate issuers per tenant
- **Active Directory / LDAP** user synchronization
- **Per-tenant configuration** overrides (expiry thresholds, mail settings, etc.)
- **passKey** passwordless authentication
- **Audit logging** — full change history for all managed objects
- **Search** across certificates, zones, and hosts
- **Dark / light theme** toggle
- **Database backup** with configurable retention


## Screenshots

<table>
  <tr>
    <td><img src="css/images/Dashboard.png"></td>
    <td><img src="css/images/Tenants.png"></td>
    <td><img src="css/images/Zones.png"></td>
  </tr>
  <tr>
    <td><img src="css/images/Certificates.png"></td>
    <td><img src="css/images/Certificate_details.png"></td>
    <td><img src="css/images/Certificate_details_2.png"></td>
  </tr>
  <tr>
    <td><img src="css/images/AXFR.png"></td>
    <td><img src="css/images/AXFR-test.png"></td>
    <td><img src="css/images/Agents.png"></td>
  </tr>
</table>


## Requirements

- Linux/Unix distribution
- Apache or nginx web server
- PHP 8.0+
- MySQL database server
- PHP extensions: `curl`, `gettext`, `openssl`, `pcntl`, `PDO`, `pdo_mysql`, `session`

## Installation

Clone the repository (including submodules for Net_DNS2 and PHPMailer):
```
cd /var/www/html/
git clone --recursive https://github.com/phpipam/php-ssl.git php-ssl
```

Copy and edit the config file:
```
cp config.dist.php config.php
# Edit config.php: set DB credentials and mail settings
```

Point the web server's **document root at the `public/` directory** (e.g. `/var/www/html/php-ssl/public`)
and configure the rewrites — see [Web Server Configuration](docs/operations/apache-nginx.md). Only `public/`
is web-accessible; application code, configuration and database backups stay outside the web root.

Open the web installer in your browser and follow the steps:
```
https://<your-server>/install/
```

The installer will create the database, application user, and import the schema automatically.
Once installation is complete, set `$installed = true` in `config.php` to disable the installer.

## Cronjob

Add the following to your crontab for automated certificate scanning and notifications:
```cron
# php-ssl cronjobs
*/5 * * * * /usr/bin/php /var/www/html/php-ssl/cron/cron.php
```

> Upgrading an existing installation? The cron entry point moved from `cron.php` to `cron/cron.php` and the
> web root moved to `public/` — see [Upgrading](docs/getting-started/upgrading.md).

Cron job schedules are managed per-tenant in the application UI (Scanning → Cron). The system
crontab entry only triggers `cron/cron.php` every 5 minutes; the application checks its internal
schedules to decide which scripts to run.

To run a cron script manually:
```
php cron/cron.php <tenant_id> <script_name>
# e.g.: php cron/cron.php 1 update_certificates
```

Available cron scripts: `update_certificates`, `axfr_transfer`, `expired_certificates`,
`remove_orphaned`, `backup`.
