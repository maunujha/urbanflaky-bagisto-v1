# Shop Package

- SMS service: `src/Services/SmsAlertService.php` — NOT in app/
- Events registered: `src/Providers/EventServiceProvider.php`
- OTP: `src/Http/Controllers/Customer/OtpController.php` + `src/Http/Controllers/API/CheckoutOtpController.php`
- Views: `src/Resources/views/`
- Routes: `src/Routes/web.php`, `api.php`, `checkout-routes.php`, `customer-routes.php`, `store-front-routes.php`
- Vite build runs from THIS directory → `public/themes/shop/default/build/`
- Custom CSS hooks only in: `src/Resources/assets/css/urbanflaky.css` (a Vite input → built to hashed `assets/urbanflaky-*.css`; loaded via `@bagistoVite` in the layout). Run `npm run build` after editing it.
- Accent classes: `uf-text-accent`, `uf-bg-accent`, `uf-border-accent`, `uf-bg-accent-soft`, `uf-ring-accent`, `uf-bg-accent-hover`, `uf-text-accent-hover`
- After view change: `php artisan view:clear`
