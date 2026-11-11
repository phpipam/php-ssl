# Web Server Configuration

php-ssl is a standard PHP application with no build step. Deploy it to a directory served by Apache or nginx and configure URL rewriting so all requests pass through `index.php`.

---

## Apache

### Virtual host example (app at web root)

```apache
<VirtualHost *:443>
    ServerName php-ssl.example.com
    DocumentRoot /var/www/html/php-ssl

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/php-ssl.crt
    SSLCertificateKeyFile /etc/ssl/private/php-ssl.key

    <Directory /var/www/html/php-ssl>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

The repository includes an `.htaccess` file that enables `mod_rewrite` and routes all requests to `index.php`. Ensure `AllowOverride All` is set, or copy the rewrite rules directly into your virtual host config.

### Running at a sub-path

If the app is **not** at the web root (e.g. `https://example.com/php-ssl/`):

1. Set the `BASE` constant in `config.php`:

    ```php
    define('BASE', '/php-ssl');
    ```

2. Update `RewriteBase` in `.htaccess`:

    ```apache
    RewriteBase /php-ssl
    ```

3. Configure the web server to serve the CSS/JS assets from their absolute paths. The templates hardcode `/css/` and `/js/` (not relative to `BASE`), so the server must map those paths:

    ```apache
    Alias /css /var/www/html/php-ssl/css
    Alias /js  /var/www/html/php-ssl/js
    ```

---

## nginx

### Server block example (app at web root)

```nginx
server {
    listen 443 ssl;
    server_name php-ssl.example.com;

    ssl_certificate     /etc/ssl/certs/php-ssl.crt;
    ssl_certificate_key /etc/ssl/private/php-ssl.key;

    root  /var/www/html/php-ssl;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass  unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include       fastcgi_params;
    }

    # Deny direct access to sensitive files
    location ~ /\.(ht|git) {
        deny all;
    }
}
```

### Running at a sub-path with nginx

```nginx
location /php-ssl/ {
    alias /var/www/html/php-ssl/;
    try_files $uri $uri/ /php-ssl/index.php?$query_string;

    location ~ \.php$ {
        fastcgi_pass  unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/html/php-ssl$fastcgi_script_name;
        include       fastcgi_params;
    }
}

# Serve hardcoded absolute asset paths
location /css/ { alias /var/www/html/php-ssl/css/; }
location /js/  { alias /var/www/html/php-ssl/js/; }
```

Set `define('BASE', '/php-ssl');` in `config.php`.

---

## Security recommendations

- Serve over HTTPS only; redirect HTTP to HTTPS.
- Deny direct browser access to `config.php`, `functions/`, `db/`, and `cron.php`:

    **Apache:**
    ```apache
    <FilesMatch "^(config\.php|cron\.php)$">
        Require all denied
    </FilesMatch>
    <DirectoryMatch "/(functions|db)/">
        Require all denied
    </DirectoryMatch>
    ```

    **nginx:**
    ```nginx
    location ~ ^/(config\.php|cron\.php)$ { deny all; }
    location ~ ^/(functions|db)/          { deny all; }
    ```

- The `install/` route is disabled by setting `$installed = true` in `config.php`. Consider also blocking it at the web server level after installation.
