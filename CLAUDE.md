# CLAUDE.md

## Quick Context

- **Stack**: Bagisto 2.4.x · Laravel 12 · PHP 8.3+ · Vue 3 · Tailwind 3 · Vite 5
- **Shell**: PowerShell (Windows 11) — use PowerShell syntax in all terminal commands
- **After any change**: `php artisan optimize:clear` · blade ParseError → `php artisan view:clear`
- **Blade**: `@context` in JSON-LD → escape as `@@context` inside any `.blade.php` file
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

### Always (every task)

```powershell
php -l {changed_file}        # skip for .blade.php / .css / .js / .vue
php artisan optimize:clear
```

### Only if a Blade file changed

```powershell
php artisan view:cache
```

If fails → read exact error, fix offending file, re-run. Do not continue until clean.

### Only if routes or config changed

```powershell
php artisan route:list --path=api     # API routes
php artisan route:list --path=shop    # shop routes
php artisan config:show app           # config files
```

### Browser testing — DISABLED

- Do NOT use Playwright / browser automation to verify changes. Do not take screenshots.
- For visible changes, rebuild (`npm run build`) + clear caches and rely on a clean build / HTTP 200; the user will visually confirm in their own browser.

### Tests — only if a test file exists for the touched class

```powershell
vendor/bin/pest --filter={ClassName}
```
