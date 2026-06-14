# CLAUDE.md

## Quick Context

- **Stack**: Bagisto 2.4.x · Laravel 12 · PHP 8.3+ · Vue 3 · Tailwind 3 · Vite 5
- **Shell**: PowerShell (Windows 11) — use PowerShell syntax in all terminal commands
- **After any change**: `php artisan optimize:clear` · blade ParseError → `php artisan view:clear`
- **Blade**: `@context` in JSON-LD → escape as `@@context` inside any `.blade.php` file · Vue `@error` handlers → write `v-on:error` (`@error` is a Blade directive and breaks compilation)
- **Locale**: only `en` is active — never add translations for other locales
- **Git**: remote `https://github.com/maunujha/urbanflaky-bagisto-v1.git` · branch `dev`

---

## Store Identity

**Urbanflaky** — fashion e-commerce by **Gabha Enterprise**, Dholpur, Rajasthan, India.

- Products: polo t-shirts, slim-fit casuals for men & women · Price range: ₹299–799
- Local dev URL: `http://urbanflaky.test` · Admin: `http://urbanflaky.test/admin`
- Currency: INR · Timezone: Asia/Kolkata · DB: `my_bagisto_store`
- Laragon: PHP 8.3 at `C:\laragon\bin\php\php-8.3.30` · Nginx: `C:\laragon\etc\nginx\sites-enabled`

---

## Auto-Verify After Every Task

- Changed `.php` (not blade/css/js/vue) → `php -l {file}`
- Always → `php artisan optimize:clear`
- Blade changed → `php artisan view:clear` — **never `view:cache`** (Windows rename lock → 500s)
- Routes/config changed → `php artisan route:list --path=shop` / `config:show app`
- Visible change → `npm run build`; user confirms in browser (no Playwright/screenshots)
- Test file exists for touched class → `vendor/bin/pest --filter={ClassName}`
- On failure: read the exact error, fix the offending file, re-run until clean
