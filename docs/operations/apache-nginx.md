# Web Server Configuration

php-ssl is a standard PHP application with no build step. **The web server's document root must be the `public/` directory** of the checkout. Everything else — `config/config.php`, `functions/`, `route/`, `db/` (including backups in `db/bkp/`), `cron/` — stays outside the web root and cannot be requested by URL.

```
/var/www/html/php-ssl/
├── public/          ← document root
│   ├── index.php    ← front controller for all pages
│   ├── route.php    ← dispatcher for /route/ajax/*, /route/modals/*, /route/login/login_check.php
│   ├── .htaccess    ← Apache rewrite rules
│   ├── css/  js/  favicon.*
├── cron/cron.php    ← cron entry point (CLI only)
├── config/          ← config.php, config.dist.php
├── route/  functions/  db/  …   ← not web-accessible
```

Two rewrites are required:

| Request | Handled by |
|---|---|
| `/route/…` (AJAX endpoints, modals, login check) | `public/route.php` — only `route/ajax/*`, `route/modals/*` and `route/login/login_check.php` are executed; anything else returns 404 |
| anything that is not an existing file in `public/` | `public/index.php` |

The application must be served at the **root of a (virtual) host**. Running under a sub-path such as `https://example.com/php-ssl/` is not supported, because asset and endpoint URLs (`/css/`, `/js/`, `/route/…`) are absolute.

---

## Apache

`mod_rewrite` is required. The rewrite rules ship in `public/.htaccess`.

```apache
<VirtualHost *:443>
    ServerName php-ssl.example.com
    DocumentRoot /var/www/html/php-ssl/public

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/php-ssl.crt
    SSLCertificateKeyFile /etc/ssl/private/php-ssl.key

    <Directory /var/www/html/php-ssl/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

If you cannot use `AllowOverride`, copy the rules from `public/.htaccess` into the `<Directory>` block.

**Fallback:** if the document root cannot be changed (e.g. shared hosting), the `.htaccess` in the application root maps every request into `public/`, so files outside it are still not served. Pointing `DocumentRoot` at `public/` is preferred.

---

## nginx

```nginx
server {
    listen 443 ssl;
    server_name php-ssl.example.com;

    ssl_certificate     /etc/ssl/certs/php-ssl.crt;
    ssl_certificate_key /etc/ssl/private/php-ssl.key;

    root  /var/www/html/php-ssl/public;
    index index.php;

    # AJAX / modal endpoints — route/ lives outside the web root
    location ^~ /route/ {
        rewrite ^ /route.php last;
    }

    location / {
        try_files $uri /index.php$is_args$args;
    }

    # Only the two front controllers are executed
    location ~ ^/(index|route)\.php$ {
        fastcgi_pass  unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include       fastcgi_params;
    }
    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(ht|git) {
        deny all;
    }
}
```

`public/route.php` reads the original path from `REQUEST_URI`, which the standard `fastcgi_params` file passes as `$request_uri` (unchanged by the internal rewrite).

---

## Security recommendations

- Serve over HTTPS only; redirect HTTP to HTTPS.
- Never point the document root at the application root — that would expose `config/config.php`, database backups in `db/bkp/` and the `.git` directory.
- Do not add PHP files to `public/` other than `index.php` and `route.php`. New endpoints belong in `route/ajax/` or `route/modals/`.
- If PHP `open_basedir` is set, it must include the application directory (the parent of `public/`), not just `public/`.
- The `install/` route is disabled by setting `$installed = true` in `config/config.php`. Consider also blocking it at the web server level after installation.
