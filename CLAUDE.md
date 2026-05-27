# CLAUDE.md

## Quick Context

- **Stack**: Bagisto 2.4.x · Laravel 12 · PHP 8.3+ · Vue 3 · Tailwind 3 · Vite 5
- **Shell**: PowerShell (Windows 11) — use PowerShell syntax in all terminal commands
- **After any change**: `php artisan optimize:clear` (config/routes/blade) · `php artisan view:clear` (blade ParseError)
- **Blade**: `@context` in JSON-LD → escape as `@@context` inside any `.blade.php` file
- **Locale**: only `en` is active — all other locale files deleted. Never add translations for other locales.
- **Git**: remote `https://github.com/maunujha/urbanflaky-bagisto-v1.git` · working branch `dev`

---

## Auto-Verify After Every Task

After completing ANY task, run these checks in order. Only report "Done" when all pass.

### 1. PHP Syntax Check (when a `.php` file changed)
```powershell
php -l {changed_file}
```
Skip for `.blade.php`, `.css`, `.js`, `.vue` — `view:cache` (step 2) catches Blade parse errors.

### 2. Clear & Compile (always)
```powershell
php artisan optimize:clear
php artisan view:cache
```
If `view:cache` fails → read the exact Blade error message, fix the offending file, re-run.

### 3. Route / Config Check (only if routes or config changed)
```powershell
php artisan route:list --path=api    # if API routes changed
php artisan route:list --path=shop   # if shop routes changed
php artisan config:show app          # if config files changed
```

### 4. Browser Verify (when any frontend, Blade, view, or CSS changed)
- Use the Playwright MCP to open `http://urbanflaky.test{relevant_page}`
- Take a screenshot
- Check: no 500 error, no broken layout, changed feature behaves as described
- If broken → fix and re-verify before reporting done
- Skip only for backend-only changes (controllers, models, listeners) with no UI surface

### 5. Test (when a test file exists for the touched class)
```powershell
vendor/bin/pest --filter={ClassName}
```

### Notes
- The `Stop` hook in `.claude/settings.json` already runs `php artisan view:clear` after each turn, so step 2's `optimize:clear` is the broader sweep.
- Playwright MCP is registered in `.mcp.json` (`npx -y @playwright/mcp@latest --headless`). First run downloads ~150 MB of Chromium.
- Don't skip step 4 if you touched anything visible. "Tests pass" ≠ "feature works in the browser."

---

## Store Identity

**Urbanflaky** — fashion e-commerce store by **Gabha Enterprise**, Dholpur, Rajasthan, India.
- Products: polo t-shirts, slim-fit casuals for men & women. Price range: ₹299–799.
- Local dev URL: `http://urbanflaky.test` | Admin: `http://urbanflaky.test/admin`
- Currency: INR | Timezone: Asia/Kolkata | DB: `my_bagisto_store`

## Environment

- **Laragon**: PHP 8.3 at `C:\laragon\bin\php\php-8.3.30`, Nginx config at `C:\laragon\etc\nginx\sites-enabled`

## Custom Integrations

### Shiprocket (auto-create shipment on order)
- Carrier: `packages/Webkul/Shipping/src/Carriers/Shiprocket.php`
- `app/Listeners/CreateShiprocketOrder.php` — triggered on `checkout.order.save.after`
- `app/Listeners/MarkOrderShipped.php` — marks order shipped via Shiprocket webhook
- Tracking shown in: `packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php`
- `.env` keys: `SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`, `SHIPROCKET_PICKUP_PINCODE`, `SHIPROCKET_PICKUP_LOCATION`, `SHIPROCKET_WEBHOOK_TOKEN`

### SMS Notifications (SmsAlert + DLT)
- Service: `packages/Webkul/Shop/src/Services/SmsAlertService.php` — note: in packages, not app/
- Listeners in `app/Listeners/`: `SendOrderSms`, `SendShipmentSms`, `SendRefundSms`, `SendRegistrationSms`, `SendCancellationSms`
- Registered in: `packages/Webkul/Shop/src/Providers/EventServiceProvider.php`
- `.env` keys: `SMSALERT_USERNAME`, `SMSALERT_APIKEY`, `SMSALERT_SENDER`, `SMSALERT_TEMPLATE_*`

### OTP Login
- `packages/Webkul/Shop/src/Http/Controllers/Customer/OtpController.php`
- `packages/Webkul/Shop/src/Http/Controllers/API/CheckoutOtpController.php`
- `app/Services/OtpCustomerService.php`
- `.env` keys: `OTP_EXPIRY_MINUTES`, `SMSALERT_TEMPLATE_OTP`

### Razorpay
- `.env` keys: `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`

### Custom CSS
- `public/css/urbanflaky.css` — store-specific styles

### Category Page (sidebar filters + top toolbar — `uf-cat-filters` / `uf-cat-toolbar`)
- Sidebar filter template: `packages/Webkul/Shop/src/Resources/views/categories/filters.blade.php`
- Top sort/limit/mode toolbar: `packages/Webkul/Shop/src/Resources/views/categories/toolbar.blade.php`
- Outer layout (wraps both): `packages/Webkul/Shop/src/Resources/views/categories/view.blade.php`
- Styling: Tailwind classes inline (dark + accent via helpers); custom CSS hooks in `public/css/urbanflaky.css` only for: pseudo-element checkbox, Bagisto dropdown internals, `.secondary-button` "Load more"
- Accent helper classes (defined in urbanflaky.css): `uf-text-accent`, `uf-bg-accent`, `uf-border-accent`, `uf-bg-accent-soft`, `uf-ring-accent`, `uf-text-accent-hover`, `uf-bg-accent-hover`
- `uf-filters-title` adds the small lime indicator before the "Filters" heading

### Product Card (dark premium redesign — `uf-product-card`)
- Template + Vue script: `packages/Webkul/Shop/src/Resources/views/components/products/card.blade.php`
- Styles (search for `/* ── Product Card`): `public/css/urbanflaky.css`
- API resource (variants + `variant_images` map for color-swap): `packages/Webkul/Shop/src/Http/Resources/ProductResource.php`
- Used by carousel: `packages/Webkul/Shop/src/Resources/views/components/products/carousel.blade.php`
- CSS classes: `.uf-product-card`, `.uf-img-wrap`, `.uf-overlay`, `.uf-badge` (`-new`/`-sale`), `.uf-card-icons` / `.uf-icon-btn` / `.uf-quick-view`, `.uf-hover-panel`, `.uf-swatch-row` / `.uf-color-dot`, `.uf-size-row` / `.uf-size-pill`, `.uf-cta-row` / `.uf-btn-atc` / `.uf-btn-buy`, `.uf-card-content` / `.uf-card-name` / `.uf-card-price`, `.uf-mobile-cart`, `.uf-delivery-strip`
- Hover panel (swatches + CTAs) shows only on desktop ≥1180px; mobile shows the inline `+ Add` pill
- Color-swatch click → swaps `currentImage` from `product.variant_images[optionId]` (set in `selectAttribute()`)

## Packages Actively Modified

- `packages/Webkul/Shop/` — storefront views, SMS service, OTP, SEO
- `packages/Webkul/Shipping/` — Shiprocket carrier
- `packages/Webkul/Sales/` — order/invoice/refund logic
- `packages/Webkul/Admin/` — admin panel customisations
- `app/Listeners/` — Shiprocket + SMS event handlers
- `app/Services/` — OTP service

## Architecture

### Modular Package System

All core functionality lives in `packages/Webkul/` (~42 packages). Each package is self-contained with its own models, controllers, routes, views, migrations, and service providers.

**Dual registration** — each package registers in two places:
1. `bootstrap/providers.php` — ServiceProvider (routes, views, events, config)
2. `config/concord.php` — ModuleServiceProvider (Konekt Concord model/enum registration)

### Key Design Patterns

- **Repository Pattern**: All DB access via repositories. Interfaces in `Contracts/`, implementations in `Repositories/`. Never query models directly in controllers.
- **Proxy Pattern**: Models have Proxy classes (`ProductProxy`, etc.) — always reference proxies when type-hinting across packages.
- **Event-Driven**: Extend via listeners, not by modifying core packages.

### Frontend Assets

Independent Vite builds — run `npm install` + `npm run dev`/`npm run build` from within the package:
- **Shop**: `packages/Webkul/Shop/` → `public/themes/shop/default/build/`
- **Admin**: `packages/Webkul/Admin/` → `public/themes/admin/default/build/`

## Common Commands

```bash
composer install               # Install PHP dependencies
php artisan optimize:clear     # Clear all caches — run after ANY config/route/blade change
php artisan view:clear         # Clear compiled Blade (fixes ParseError)
php artisan serve              # Start PHP dev server
vendor/bin/pest                # Run all tests
vendor/bin/pint                # Fix PHP code style
```

## Patterns

### Add a new SMS listener

1. Create `app/Listeners/SendXxxSms.php` — copy structure from `app/Listeners/SendOrderSms.php`
2. Inject and call `SmsAlertService` (`packages/Webkul/Shop/src/Services/SmsAlertService.php`)
3. Add a DLT template ID to `.env`: `SMSALERT_TEMPLATE_XXX=...`
4. Read it in `SmsAlertService` or pass it directly when calling `send()`
5. Register the listener in `packages/Webkul/Shop/src/Providers/EventServiceProvider.php` under `$listen`

### Add a new Shop API controller + route

1. Create controller in `packages/Webkul/Shop/src/Http/Controllers/API/XxxController.php`
   - Extend `\Webkul\Shop\Http\Controllers\Controller`
2. Add the route to `packages/Webkul/Shop/src/Routes/api.php`
3. Run `php artisan optimize:clear`

### Repository pattern usage

Always resolve repositories via the constructor or `app()` — never instantiate or query Eloquent models directly in controllers or listeners. Example:

```php
// correct
public function __construct(protected OrderRepository $orderRepository) {}
$order = $this->orderRepository->find($id);

// wrong — never do this
$order = Order::find($id);
```

## Do Not

- **Never query Eloquent models directly** — always go through the repository (`OrderRepository`, `ProductRepository`, etc.)
- **Never add non-`en` locale files** — all other locales have been intentionally removed from this project
- **Never modify `vendor/`** — changes there are wiped on `composer install`. Always extend or override inside `packages/Webkul/` or `app/`
