# Performance — Production Deploy Checklist

Code-level optimizations are already committed (see git history). Everything below
**cannot be done in the local Windows/Laragon setup** and must be applied on the
production server (urbanflaky.in) at deploy time.

---

## 1. Enable Full-Page Cache (single biggest win)

Production `.env`:

```env
RESPONSE_CACHE_ENABLED=true
RESPONSE_CACHE_DRIVER=redis
```

- Guest GET pages (home, category, PDP, CMS, search) are then served straight
  from Redis — no Laravel boot for the page body.
- Invalidation is automatic: `Webkul\FPC` listeners flush on product, category,
  CMS page, order (stock), review, theme-customization and config saves.
- Manual flush when needed: `php artisan responsecache:clear`
- **Keep it `false` locally** — `php artisan optimize:clear` does NOT clear the
  response cache, so every local Blade edit would look stale.

## 2. Run a real queue worker

Production `.env`:

```env
QUEUE_CONNECTION=redis
```

Supervisor program:

```ini
[program:urbanflaky-worker]
command=php /var/www/urbanflaky/artisan queue:work redis --tries=3 --backoff=10 --max-time=3600
numprocs=2
autostart=true
autorestart=true
user=www-data
```

Why: the RewardCoins listeners (`AwardCoinsOnOrder`, `ConfirmCoinsOnDelivery`,
`ReverseCoinsOnCancellation`) implement `ShouldQueue`, but with the current
`QUEUE_CONNECTION=sync` they execute **inside the checkout request**, adding
their DB work to the customer's place-order latency. Locally `sync` stays (no
worker running on the dev machine).

## 3. Install phpredis (replace predis)

```bash
sudo apt install php8.3-redis   # or: pecl install redis
```

```env
REDIS_CLIENT=phpredis
```

predis is pure PHP; the phpredis C extension is significantly faster for the
session + cache + FPC traffic that now all flows through Redis. The local
Laragon PHP has no redis extension, so local stays on predis.

## 4. Deploy-script framework caches

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache        # safe on Linux; never run on local Windows (file-lock 500s)
cd packages/Webkul/Shop && npm ci && npm run build
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan view:cache
```

## 5. OPcache (production php.ini / fpm pool)

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0     ; code never re-statted — reload php-fpm on deploy
realpath_cache_size=4096K
realpath_cache_ttl=600
```

Local Laragon already runs OPcache with `validate_timestamps=1` (correct for dev — leave it).

## 6. Nginx

```nginx
# Hashed Vite build output — immutable
location ~* ^/themes/.+/build/ {
    expires max;
    add_header Cache-Control "public, immutable";
    access_log off;
}

# Uploaded media (product images, theme banners, blog images)
location /storage/ {
    expires 30d;
    add_header Cache-Control "public";
    access_log off;
}

gzip on;
gzip_types text/css application/javascript application/json image/svg+xml;
gzip_min_length 1024;
# brotli on; brotli_types ...;   # if the brotli module is available
http2 on;
```

## 7. Cron (required, not just performance)

```cron
* * * * * php /var/www/urbanflaky/artisan schedule:run >> /dev/null 2>&1
```

Runs Bagisto housekeeping + RewardCoins expiry. Without it, scheduled jobs
silently never run in production.

## 8. Final production .env sanity

```env
APP_ENV=production
APP_DEBUG=false
```

---

## Code optimizations already applied (reference)

| Change | File |
|---|---|
| Footer CMS-pages query cached 1h per locale | `packages/Webkul/Shop/.../layouts/footer/index.blade.php` |
| Home "From the Journal" query cached 1h, flushed on any blog save/delete | `Gabha\Blog` HomeBlogComposer + Blog model |
| Shiprocket pincode serviceability cached 4h per pincode | `app/Http/Controllers/DeliveryCheckController.php` |
| Hero LCP image `<link rel="preload">` (mobile + desktop) | `packages/Webkul/Shop/.../home/index.blade.php` |

Already verified healthy, no action needed: custom-table indexes (Blog/FAQ/RewardCoins),
product-card lazy-loading, Vite manualChunks + esbuild minify, Tailwind content globs,
Redis session/cache DB separation (0/1), Spatie ResponseCache CSRF replacer.
