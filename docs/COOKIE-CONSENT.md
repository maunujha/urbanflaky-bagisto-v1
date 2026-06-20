# Cookie Consent Management (GDPR)

A premium, script-gating, DB-backed cookie consent layer for Urbanflaky. It sits
in front of the existing **GTM-first** analytics stack (GTM → GA4 + Meta Pixel;
Clarity direct) and blocks every non-essential tag until the visitor opts in.

---

## 1. How it works

- **Off by default.** Nothing changes until an admin switches it on. When OFF the
  banner is not rendered and GTM / GA4 / Clarity / Meta load unconditionally
  exactly as before (no guard).
- **When ON:**
  - Google **Consent Mode v2** defaults are set to `denied` before any tag loads.
  - **GTM** is injected only after the visitor grants *analytics* or *marketing*.
  - **Clarity** is injected only after *analytics* is granted.
  - Consent Mode `update` signals are pushed per category, so inside GTM the GA4
    tag honours `analytics_storage` and the Meta tag honours `ad_storage`.
  - The no-JS GTM `<noscript>` iframe is withheld (no-JS users can't consent).
- Choices are stored in **localStorage** (always) and the **`cookie_consents`
  table** (logged-in customers, for cross-device sync).
- Consent is valid for **6 months**, then the banner returns. Bumping the policy
  **version** also forces everyone to re-consent.

### Categories → Consent Mode mapping

| Category    | Consent Mode keys                                            | Loads            |
|-------------|-------------------------------------------------------------|------------------|
| Essential   | `security_storage` (always granted)                         | —                |
| Analytics   | `analytics_storage`                                         | GTM (GA4), Clarity |
| Marketing   | `ad_storage`, `ad_user_data`, `ad_personalization`          | GTM (Meta Pixel) |
| Preferences | `functionality_storage`, `personalization_storage`          | —                |

---

## 2. Enable it (admin)

1. **Admin → Configuration → General → GDPR → Cookie Consent Manager**
2. Turn **Enable Cookie Consent Manager** ON → Save.
3. `php artisan optimize:clear` (config cache).

The footer gains a **“Cookie Preferences”** link; the bottom banner appears for
visitors without a valid stored choice.

> Reuse note: this is a dedicated toggle (`general.gdpr.consent_manager.enable`)
> registered by `App\Providers\CookieConsentServiceProvider`. It is independent
> of Bagisto's other GDPR features.

---

## 3. Configuration

`config/cookie-consent.php` (env-overridable):

| Key                | Env                              | Default |
|--------------------|----------------------------------|---------|
| `version`          | `COOKIE_CONSENT_VERSION`         | `1.0`   |
| `validity_months`  | `COOKIE_CONSENT_VALIDITY_MONTHS` | `6`     |

**Force re-consent after a policy change:** bump the version, e.g. in `.env`:

```
COOKIE_CONSENT_VERSION=1.1
```

Then `php artisan optimize:clear`. Every stored consent with a different version
is treated as invalid and the banner reappears.

---

## 4. Database

Migration: `database/migrations/2026_06_20_120000_create_cookie_consents_table.php`

```
cookie_consents
  id, user_id (→ customers.id, nullable, cascade),
  analytics, marketing, preferences (bool),
  consent_version, created_at, updated_at
```

Run: `php artisan migrate`

---

## 5. GTM container (one-time, recommended)

The code emits Consent Mode v2 signals and a `cookie_consent_update` dataLayer
event. For the strictest per-category gating *inside* GTM:

1. GTM → **Admin → Container Settings → Enable consent overview**.
2. **GA4 tag** → Consent Settings → require `analytics_storage` (GA4 honours this
   natively via Consent Mode — usually nothing to do).
3. **Meta Pixel tag** → add a trigger exception or "Additional consent" requiring
   `ad_storage` **granted**, or fire it on the custom event `cookie_consent_update`
   where `cookie_consent.marketing` is `true`.

Even without this, the hard block still holds: GTM itself is not loaded unless
analytics or marketing is granted.

---

## 6. Files

**Created**
- `config/cookie-consent.php`, `config/cookie-consent-system.php`
- `app/Providers/CookieConsentServiceProvider.php`
- `app/Support/CookieConsent.php`
- `app/Models/CookieConsent.php`
- `app/Http/Controllers/Shop/CookieConsentController.php`
- `database/migrations/2026_06_20_120000_create_cookie_consents_table.php`

**Changed**
- `bootstrap/providers.php` — register provider
- `routes/web.php` — `POST /cookie-consent` (`cookie.consent.store`)
- `packages/Webkul/Shop/.../layouts/tracking/head.blade.php` — Consent Mode v2 +
  deferred GTM/Clarity loaders (`window.ufConsent`)
- `packages/Webkul/Shop/.../layouts/tracking/noscript.blade.php` — gate iframe
- `packages/Webkul/Shop/.../layouts/cookie/index.blade.php` — premium banner + modal
- `packages/Webkul/Shop/.../layouts/index.blade.php` — render banner as #app sibling
- `packages/Webkul/Shop/.../layouts/footer/index.blade.php` — “Cookie Preferences” link
- `packages/Webkul/Shop/src/Resources/assets/css/urbanflaky.css` — `uf-cc-*` styles

---

## 7. Testing checklist

- [ ] Enable the toggle; reload a fresh browser (clear `localStorage`) → banner shows.
- [ ] DevTools → Network: **no** `gtm.js` / `clarity.ms` request before choosing.
- [ ] **Accept All** → `gtm.js` + `clarity.ms` load; `dataLayer` has
      `cookie_consent_update {analytics,marketing,preferences:true}`; banner hides.
- [ ] **Reject Non-Essential** → no tracking requests; banner hides.
- [ ] **Customize → analytics only → Save** → `gtm.js` loads, GA4 collects,
      Clarity loads, Meta stays blocked (`ad_storage=denied`).
- [ ] Footer **Cookie Preferences** reopens the modal with current toggles.
- [ ] Keyboard: Tab is trapped in the modal, **Esc** closes, focus returns to trigger.
- [ ] Logged-in customer: row written to `cookie_consents`; log in on another
      browser → choice restored without re-prompting.
- [ ] After 6 months (or bump `COOKIE_CONSENT_VERSION`) → banner reappears.
- [ ] Toggle OFF → banner gone, `gtm.js` loads unconditionally (legacy behavior).
- [ ] Mobile: full-width bottom sheet, tap targets, modal scrolls.
