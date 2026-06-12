# Urbanflaky — Production Deployment Guide

Checklist distilled from the production-readiness audit. Work top to bottom on
every fresh deploy target; the **Per-release** section is what CI/CD or a
deploy script repeats on each release.

---

## 1. Server prerequisites

- PHP 8.3+ with extensions: `gd`, `intl`, `mbstring`, `pdo_mysql`, `curl`,
  `openssl`, **`redis` (phpredis)** — phpredis is required because
  `REDIS_CLIENT=phpredis`; predis (pure PHP) is measurably slower.
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

1. Copy `.env.production.example` → `.env`, fill **every** blank.
2. `php artisan key:generate` — never reuse the dev APP_KEY.
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

```nginx
server {
    listen 443 ssl http2;
    server_name urbanflaky.in www.urbanflaky.in;
    root /var/www/urbanflaky/public;
    index index.php;

    ssl_protocols TLSv1.2 TLSv1.3;          # never TLSv1/1.1
    autoindex off;                           # dev config has this ON — do not copy

    client_max_body_size 20m;

    location / { try_files $uri $uri/ /index.php$is_args$args; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Static asset cache (Vite output is content-hashed)
    location ~* \.(css|js|woff2?|jpg|jpeg|png|webp|svg|ico)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\. { deny all; }             # blocks .env, .git, .ht*
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
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan event:cache
php artisan view:clear                   # NOT view:cache (Windows dev habit carries no benefit here either; lazy-compile is fine)
```

Smoke-check before pointing DNS:

```bash
curl -sI https://urbanflaky.in            | head -1   # 200
curl -sI https://urbanflaky.in/sitemap.xml| head -1   # 200, application/xml
curl -s  https://urbanflaky.in/robots.txt | head -3
curl -sI https://urbanflaky.in/admin/login| head -1   # 200
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
sudo supervisorctl restart urbanflaky-worker:
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
- `composer audit` in CI on every release; treat new advisories as blockers.
