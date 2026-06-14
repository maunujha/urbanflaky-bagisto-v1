# Urbanflaky — Production Deployment Guide

Checklist distilled from the production-readiness audit. Work top to bottom on
every fresh deploy target; the **Per-release** section is what CI/CD or a
deploy script repeats on each release.

---

## 1. Server prerequisites

- PHP 8.3+ with extensions: `gd`, `intl`, `mbstring`, `pdo_mysql`, `curl`,
  `openssl`, **`redis` (phpredis)** — phpredis is required because
  `REDIS_CLIENT=phpredis`; predis (pure PHP) is measurably slower.
- GD must be compiled **with WebP support** (`php -r "var_dump(gd_info()['WebP Support']);"`
  must print `true`) — every image upload is converted to WebP at quality 85.
- MySQL 8, Redis 7, Nginx, Composer 2, Node 20 (build only).
- OPcache enabled and tuned for deploys:

  ```ini
  ; /etc/php/8.3/fpm/conf.d/10-opcache.ini
  opcache.enable=1
  opcache.memory_consumption=256
  opcache.max_accelerated_files=40000
  opcache.validate_timestamps=0   ; restart php-fpm on each deploy instead
  ```

## 2. Environment

1. Copy `.env.production.example` → `.env`, fill **every** blank. **Never copy your dev
   `.env`** — it ships `REDIS_CLIENT=predis` (predis is not installed → every Redis call
   dies), a stale `REDIS_PASSWORD` (prod Redis has no auth → `NOAUTH`/`ERR` failures), and
   `CACHE_DRIVER` (Laravel 12 only reads `CACHE_STORE`). All three silently break
   cache/session/queue while the app still boots.
2. `php artisan key:generate` — never reuse the dev APP_KEY. **Exception:** if the prod DB
   was migrated from another install, keep that install's `APP_KEY` or encrypted columns
   become unreadable.
3. Non-negotiables (each one was an audit finding):
   - `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`
   - `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
   - `QUEUE_CONNECTION=redis` (+ worker, §4)
   - `RESPONSE_CACHE_ENABLED=true`
   - `SENTRY_SEND_DEFAULT_PII=false`
   - `TRUSTED_PROXIES` = real proxy IPs only, never `*`; empty if no proxy
4. Razorpay **live** keys: Admin → Configure → Payment Methods → Razorpay,
   sandbox OFF. Add a webhook in the Razorpay dashboard pointing to
   `https://urbanflaky.in/razorpay/payment/webhook`, subscribe to
   `payment.captured`, and put its secret in `RAZORPAY_WEBHOOK_SECRET`.
5. Shiprocket webhook: panel → Settings → Webhooks →
   `https://urbanflaky.in/webhooks/tracking`, header `x-api-key` =
   `SHIPROCKET_WEBHOOK_TOKEN` (endpoint rejects everything if unset).

## 3. Nginx vhost (hardening that dev does not have)

**Copy the committed file — do not hand-roll it:** `deploy/nginx/urbanflaky.conf` is the
canonical HTTP base. `sudo cp` it into `sites-available`, enable it, remove `default`, then
`sudo certbot --nginx -d urbanflaky.in -d www.urbanflaky.in --redirect` rewrites it to add
the `listen 443 ssl http2` block + the 80→443 redirect. The resulting vhost:

```nginx
server {
    listen 443 ssl http2;                    # nginx ≥1.25.1: use a separate `http2 on;`
    server_name urbanflaky.in www.urbanflaky.in;
    root /var/www/urbanflaky/public;
    index index.php;

    ssl_protocols TLSv1.2 TLSv1.3;          # never TLSv1/1.1
    autoindex off;                           # dev config has this ON — do not copy

    client_max_body_size 20m;

    # Bagisto on-the-fly image resizer (Webkul\ImageCache). MUST be ABOVE the static
    # regex — /cache/{size}/product/*.webp|jpg are DYNAMIC Laravel routes, not files.
    # Omit it and every product image 404s while /storage originals still load fine.
    location ^~ /cache/ { try_files $uri /index.php$is_args$args; }

    location / { try_files $uri $uri/ /index.php$is_args$args; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Static asset cache (Vite output is content-hashed; /storage originals served here too)
    location ~* \.(css|js|woff2?|jpg|jpeg|png|webp|svg|ico)$ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\.(?!well-known).* { deny all; }   # block .env/.git/.ht*, allow ACME
}

server {                                      # force HTTPS
    listen 80;
    server_name urbanflaky.in www.urbanflaky.in;
    return 301 https://urbanflaky.in$request_uri;
}
```

## 4. Workers and scheduler (REQUIRED — checkout depends on them)

All SMS, Shiprocket order creation and reward-coin listeners are queued.
Without a worker they silently never run; with `sync` they slow every checkout.

```ini
; /etc/supervisor/conf.d/urbanflaky-worker.conf
[program:urbanflaky-worker]
command=php /var/www/urbanflaky/artisan queue:work redis --tries=3 --backoff=10 --max-time=3600
numprocs=2
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/urbanflaky-worker.log
```

Scheduler cron (AWB sync 30min, coin expiry daily, trending-search reset):

```cron
* * * * * cd /var/www/urbanflaky && php artisan schedule:run >> /dev/null 2>&1
```

## 5. First deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build                  # repeat inside packages/Webkul/Shop and Admin if themes changed
php artisan storage:link

# Media (storage/app/public — product/category/lookbook/tinymce images, ~84M) is GITIGNORED.
# It is NOT in the repo and NOT in migrations — copy it from a working install or every
# product image 404s. From the source machine (Git Bash works on Windows; no rsync needed):
#   tar czf - -C storage/app/public . | ssh deploy@SERVER 'tar xzf - -C /var/www/urbanflaky/storage/app/public'
# Then on the server, match the rest of storage:
#   sudo chown -R deploy:www-data storage/app/public && sudo chmod -R 775 storage/app/public \
#     && sudo find storage/app/public -type d -exec chmod 2775 {} +

php artisan migrate --force
php artisan webp:convert --fallbacks   # WebP siblings for any JPG/PNG + JPEG fallbacks for WebP-only images
php artisan config:cache && php artisan route:cache && php artisan event:cache
php artisan view:clear                   # NOT view:cache (Windows dev habit carries no benefit here either; lazy-compile is fine)
```

Smoke-check before pointing DNS:

```bash
curl -sI https://urbanflaky.in            | head -1   # 200
curl -sI https://urbanflaky.in/sitemap.xml| head -1   # 200, application/xml
curl -s  https://urbanflaky.in/robots.txt | head -3
curl -sI https://urbanflaky.in/admin/login| head -1   # 200

# Product-image pipeline: a RESIZED variant must be 200 image/webp, NOT 404. This catches
# both a missing storage/app/public copy AND a missing `location ^~ /cache/` nginx rule.
P=$(php artisan tinker --execute="echo DB::table('product_images')->value('path');")
curl -sI "https://urbanflaky.in/cache/medium/$P" | head -1   # 200, content-type image/webp
```

## 6. Per-release script

```bash
cd /var/www/urbanflaky
php artisan down --retry=30
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan event:cache
php artisan responsecache:clear
sudo supervisorctl restart urbanflaky-worker:*
sudo systemctl restart php8.3-fpm        # because opcache.validate_timestamps=0
php artisan up
```

## 7. Post-launch (first week)

- Submit `https://urbanflaky.in/sitemap.xml` in Google Search Console.
- Reconcile Razorpay dashboard payments against orders daily until the
  webhook has proven itself (look for `Razorpay webhook: recovered order`
  in the logs).
- Watch Sentry for new exceptions; watch `storage/logs` for
  `Shiprocket webhook: invalid token` (means panel token mismatch).
- Verify SMS templates fire (place a ₹1 test order end-to-end, then refund).
- Run Lighthouse on `/`, a category and a product page; the heavy work
  (fonts, og-image, hero priority, response cache) is done — scores should
  be green on mobile for everything except JS execution time.

## 8. Standing rules

- Never edit `.env` casually: `config:cache` means changes need re-caching.
- Rotate `SHIPROCKET_PASSWORD`, `SMSALERT_APIKEY`, `MAIL_PASSWORD`,
  Razorpay secrets ~quarterly and on any team change.
- Database backups: nightly `mysqldump` + binlog, tested restore monthly.
- Media backups: `storage/app/public` is **not in git** — back it up (rsync/tar) nightly
  alongside the DB, or uploaded product images are unrecoverable after a rebuild.
- `composer audit` in CI on every release; treat new advisories as blockers.
