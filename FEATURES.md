# Urbanflaky — Feature & Functionality Document

> **Store:** Urbanflaky — fashion e-commerce by Gabha Enterprise, Dholpur, Rajasthan, India
> **Products:** Polo t-shirts, slim-fit casuals for men & women · Price range ₹299–799
> **Last reviewed:** 2026-05-30

---

## 1. Platform & Tech Stack

| Layer | Technology |
|-------|-----------|
| E-commerce engine | **Bagisto 2.4.x** (Laravel package suite) |
| Framework | Laravel 12 · PHP 8.3+ |
| Frontend | Vue 3 · Tailwind CSS 3 · Vite 5 · Blade |
| Database | MySQL (`my_bagisto_store`) |
| Search | Elasticsearch 8 (available) |
| Currency / Locale | INR · Asia/Kolkata · English-only (`en`) |
| Local dev | Laragon · `http://urbanflaky.test` · Admin `/admin` |
| Hosting deps | Redis/Predis, Laravel Octane (available) |

The store is a **customized Bagisto build** — it ships with the full Bagisto feature set plus a layer of custom features built specifically for Urbanflaky (an India-focused D2C fashion store). This document separates the two so you can see what's stock vs. what's bespoke.

---

## 2. Custom Features Built for Urbanflaky

These are the features developed on top of stock Bagisto (derived from the project's commit history and custom code).

### 2.1 Checkout & Orders
- **Mobile OTP verification at checkout** — Step 1 of checkout requires phone OTP verification (`CheckoutOtpController`, `OtpCustomerService`). Includes a "change phone number" flow for logged-in users.
- **Buy Now** — One-click buy button on product pages with dynamic price + variant-aware disabled state (bypasses cart).
- **Sticky Add-to-Cart bar** on the product page (PDP) that follows scroll, with scroll-to-variant prompting when a variant isn't selected.
- **Guest checkout OTP fix** — channel-aware OTP lookup so guest customers can verify.
- **One-page checkout** (Bagisto core, customized) with corrected shipping/payment method display and live grand-total updates.

### 2.2 Payments
- **Razorpay** payment gateway integration (`Webkul\Razorpay` package + custom branch work) — India's primary payment provider (UPI, cards, netbanking, wallets).
- PayU, PayPal, Stripe packages are present (available, may not be active).

### 2.3 Shipping & Delivery (Shiprocket)
- **Real-time shipping rates** via Shiprocket serviceability API at checkout.
- **Pincode delivery checker on PDP** — customer enters pincode, sees if/when delivery is available (`DeliveryCheckController`, `check-delivery` route).
- **Auto-shipment creation** — a Shiprocket order is created automatically when an order is placed (`CreateShiprocketOrder` listener, `ShiprocketOrder` model).
- **AWB tracking on order detail page** — customers see live tracking/AWB info.
- **Shiprocket webhook** receiver (`ShiprocketWebhookController`) — updates order/shipment status; token-verified, CSRF-exempt.
- **AWB sync command** (`SyncShiprocketAwb`) — scheduled job to keep tracking numbers fresh.
- Weight handling fix (grams → kg) and correct billing-address/state mapping.

### 2.4 SMS Notifications (SMSAlert + DLT)
- **Full transactional SMS system** with 14 DLT-approved templates wired via `SmsAlertService`. Events covered:
  - OTP, Welcome, Registration
  - Order placed, shipped, delivered, cancelled, refunded
  - Refund processed, Abandoned cart
  - Admin alerts: new inquiry, new signup, order-status change, new order
- Listeners: `SendOrderSms`, `SendShipmentSms`, `SendCancellationSms`, `SendRefundSms`, `SendRegistrationSms`, `MarkOrderShipped`.
- Order-confirmation SMS fires on order placement.

### 2.5 Search Experience
- **Instant search autocomplete** — type-ahead results dropdown (`AutocompleteController`), capped at 4 results mobile / 8 desktop, 500px-wide dropdown.
- **Trending searches** — shows popular search terms in the search dropdown (`TrendingSearchController`); reset on a schedule via `ResetTrendingSearches` command.

### 2.6 Product Page (PDP) Enhancements
- **Recently Viewed Products** section (JS-injected, client-side tracked).
- **Variant image gallery switching** — gallery updates when a variant is selected.
- **Low-stock urgency badge** ("Only X left") to drive conversions.
- **Social share buttons** — Facebook, WhatsApp, copy-link.
- **Shipping & returns info partial** on PDP.
- Variant products **hidden from admin product listing** to reduce clutter.

### 2.7 Storefront / Theme (D2C fashion redesign)
- **Redesigned product cards** in a D2C fashion style with corrected variant logic and a ratings badge wrapper.
- **Custom theme layer** via `urbanflaky.css` (Vite-built) with accent utility classes (`uf-text-accent`, `uf-bg-accent`, etc.).
- **Dark category page** styling.
- Custom logos, favicon, OG image, brand imagery.

### 2.8 SEO
- **Structured data (JSON-LD)** for products, categories, home.
- **Meta tags + Open Graph** tags across storefront; duplicate-meta-tag bug fixed on PDP/category/home.
- Brand images wired into shop layout.
- Sitemap support (`spatie/laravel-sitemap` + Bagisto sitemap).

### 2.9 Security & Reliability
- **Google reCAPTCHA v3** on all public forms.
- **Rate limiting** on API (`throttle:api`), OTP (`throttle:api-otp`, strictest to protect SMS cost), and auth (`throttle:api-auth`, anti credential-stuffing) endpoints.
- **Sentry** error monitoring integrated.
- **Google social login** (`GoogleController` + `Webkul\SocialLogin`).

### 2.10 CMS
- Seeded CMS page(s) (e.g. shipping/returns policy content).

---

## 3. Stock Bagisto Features (Available Out of the Box)

These ship with Bagisto and are available even if not all are actively used/styled yet.

### Catalog
- Product types: **Simple, Configurable (variants), Grouped, Bundle, Virtual, Downloadable, Booking**
- Categories (nested), Attributes & Attribute Families
- Product reviews & ratings, Product comparison, Wishlist
- Inventory management with multiple inventory sources
- Catalog price rules

### Customer
- Registration / login / forgot-password / reset
- Customer groups, Address book, Order history
- GDPR data requests, RMA (returns) module
- Downloadable-product access

### Cart & Checkout
- Cart price rules (coupons / promotions)
- One-page checkout, Multiple shipping & payment methods
- Tax categories & rates

### Sales
- Orders, Invoices, Shipments, Refunds, Transactions
- Order status workflow

### Marketing
- Email templates & campaigns, Newsletter subscriptions
- Promotions (cart rules / catalog rules with coupons)
- Search SEO: URL rewrites, search terms, search synonyms, sitemap

### Admin
- Role-based access control (ACL) for admin users
- **Dashboard & reporting** — sales, customers, products, cart analytics
- DataGrids with saved filters, Data import/export (CSV/Excel)
- Multi-channel, multi-currency, multi-locale (currently only `en` active)
- Theme customization, CMS pages, Cache management
- **MagicAI** (AI content) module present, Notifications

### Tech / Infra
- REST-style storefront API (cart, categories, products, reviews, compare, wishlist, addresses)
- Full Page Cache (FPC) + response caching
- Elasticsearch indexing, Image caching
- Queue/jobs, Two-factor auth (`pragmarx/google2fa`)

---

## 4. Integrations Summary

| Integration | Purpose | Status |
|-------------|---------|--------|
| **Razorpay** | Payments (UPI/cards/netbanking) | Active (India) |
| **Shiprocket** | Shipping rates, label, tracking, webhook | Active |
| **SMSAlert (DLT)** | Transactional SMS (14 templates) | Active |
| **Google reCAPTCHA v3** | Bot protection on forms | Active |
| **Sentry** | Error monitoring | Active |
| **Google OAuth** | Social login | Active |
| PayU / PayPal / Stripe | Alternative payments | Available |
| Elasticsearch | Search backend | Available |

---

## 5. Suggested Roadmap / Features to Plan Next

> A starting menu of common D2C/fashion enhancements to consider. Reprioritize to your business goals.

### Conversion & Merchandising
- [ ] Size chart / size guide modal per product
- [ ] Back-in-stock email/SMS notifications
- [ ] Product bundles / "Complete the look" cross-sell blocks
- [ ] Reviews with photo uploads + verified-buyer badge
- [ ] Wishlist sharing / move-to-cart reminders

### Marketing & Retention
- [ ] **Abandoned cart recovery** (SMS template already exists — wire the automation)
- [ ] Coupon/first-order discount popup, referral program
- [ ] WhatsApp order updates (in addition to SMS)
- [ ] Loyalty / rewards points
- [ ] Email automation flows (welcome, win-back)

### Checkout & Payments
- [ ] Cash on Delivery (COD) with OTP confirmation (common in India)
- [ ] Saved payment / express checkout
- [ ] Partial COD / prepaid discount nudges

### Returns & Post-Purchase
- [ ] Self-serve return/exchange portal (build on RMA module)
- [ ] Order tracking page with live Shiprocket status timeline

### Platform
- [ ] PWA / mobile app, Performance budget & Core Web Vitals pass
- [ ] Multi-language if expanding beyond English
- [ ] Analytics: GA4 + Meta Pixel + server-side events
- [ ] Automated tests (Pest) for the custom checkout/OTP/Shiprocket flows

---

## 6. Where Things Live (for developers)

| Concern | Location |
|---------|----------|
| Custom theme CSS | `packages/Webkul/Shop/src/Resources/assets/css/urbanflaky.css` |
| SMS service | `packages/Webkul/Shop/src/Services/SmsAlertService.php` |
| OTP controllers | `.../Shop/src/Http/Controllers/Customer/OtpController.php`, `.../API/CheckoutOtpController.php` |
| Shiprocket order + webhook | `app/Listeners/CreateShiprocketOrder.php`, `app/Http/Controllers/ShiprocketWebhookController.php`, `app/Models/ShiprocketOrder.php` |
| Delivery checker | `app/Http/Controllers/DeliveryCheckController.php` |
| Search autocomplete / trending | `.../Shop/src/Http/Controllers/API/AutocompleteController.php`, `TrendingSearchController.php` |
| SMS listeners | `app/Listeners/Send*Sms.php` |
| Scheduled commands | `app/Console/Commands/SyncShiprocketAwb.php`, `ResetTrendingSearches.php` |
| Custom routes | `routes/web.php`, `packages/Webkul/Shop/src/Routes/*.php` |
| Integration config | `config/shiprocket.php`, `config/services.php` (SMSAlert templates), `config/sentry.php` |
</content>
</invoke>
