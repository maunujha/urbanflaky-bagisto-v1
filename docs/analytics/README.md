# Urbanflaky Analytics & Tracking — Implementation Report

**Environment:** `dev` branch · **Status:** NOT deployed to production (awaiting approval)
**Architecture:** GTM-first — site pushes a clean data layer; GA4 + Meta Pixel fire inside GTM; Clarity loads directly.
**IDs:** GTM `GTM-TK3MV6Q3` · Clarity `x80ym5sh5u` (committed config defaults, `.env`-overridable, render-gated).

| Doc | Contents |
|-----|----------|
| [EVENTS.md](EVENTS.md) | Full data-layer event spec + payloads (Deliverables #2, #4, #5) |
| [GTM-SETUP.md](GTM-SETUP.md) | GTM variables, triggers, GA4 tags, Meta Pixel tags (Deliverables #3, #4, #5) |
| [gtm-container.json](gtm-container.json) | Importable GTM container — 20 tags / 13 triggers / 7 vars |
| [CAPI.md](CAPI.md) | Meta Conversions API architecture + dedup (Deliverable: Phase 6) |
| [SEARCH-CONSOLE.md](SEARCH-CONSOLE.md) | GSC + technical SEO checklist (Phase 8) |
| README.md (this) | Audit, event list, Clarity report, testing, issues, readiness (#1, #6, #10, #11, #12) |

---

## 1. Audit summary (Phase 1)
The site had **zero** prior tracking — no GA, GTM, Meta Pixel, Clarity, or data
layer. No duplicates, no conflicts. Strong existing foundation: clean `<head>`
injection points, server-rendered `$order` on the success page, dynamic sitemap,
correct `robots.txt`, canonical tags, and a GDPR consent UI whose categories map
to Consent Mode v2. We built clean and GTM-first.

## 2. Events implemented (Phase 2) — 14
`page_view`, `view_item`, `view_item_list`, `search`, `add_to_cart`,
`remove_from_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`,
`purchase`, `newsletter_signup`, `contact_submit`, `login`, `sign_up`.
All render-verified over HTTP on dev (valid GA4 JSON). Full spec → [EVENTS.md](EVENTS.md).

## 3. GTM (Phase 3) / GA4 (Phase 4) / Meta Pixel (Phase 5)
- Single GTM container, head + `<body>` noscript, no duplicate scripts.
- GA4 + Meta tags fire from GTM off the data layer → no second hardcoded snippets, zero duplication risk.
- Importable container provided. GA4 maps every ecommerce event incl. revenue; Meta maps PageView/ViewContent/Search/AddToCart/InitiateCheckout/Purchase/Lead with `eventID` set for future CAPI dedup.

## 4. Microsoft Clarity (Phase 7) — verification report
Installed as a direct script in `<head>` (your chosen method), id `x80ym5sh5u`,
config-gated. **Verified:** snippet renders on every storefront page (HTTP-checked
`clarity.ms/tag/` present). **You must confirm in the Clarity dashboard** (I can't
access your account / run a browser): Recordings populate, Heatmaps (click +
scroll) build after traffic, and the auto-captured signals — **rage clicks, dead
clicks, excessive scrolling, quick-backs** — appear under Dashboard → smart events.
First data appears within minutes of real sessions.

## 5. Issues found & fixed
1. **No tracking at all** → full GTM-first stack implemented.
2. **`CartItemResource` lacked `sku` / `product_id`** → added (additive) so
   `remove_from_cart` and the checkout funnel carry correct `item_id`.
3. **Redirect-based actions (contact/login/sign_up) can't be caught client-side**
   → added a server-side `DataLayer::flash()` mechanism flushed in the tracking head.
4. **Multiple login entry points** (password/OTP/Google) → covered centrally via
   `customer.after.login` + dedupe against `sign_up` (no per-controller drift).
5. **Ecommerce object bleed between events** → every push clears with `ecommerce: null` first.

## 6. Testing plan (Phase 9)
> Browser/account-driven verification is yours to run (browser automation is
> disabled here and I can't sign into your GTM/GA4/Meta). Use **GTM Preview** +
> **GA4 DebugView** + **Meta Pixel Helper**. Expected events per journey:

| Scenario | Expected data-layer events (in order) |
|----------|----------------------------------------|
| Home → PDP → ATC → Checkout → Purchase | `page_view` → `view_item` → `add_to_cart` → `begin_checkout` → `add_shipping_info` → `add_payment_info` → `purchase` |
| Google login → PDP → ATC → Purchase | `login` → `view_item` → `add_to_cart` → … → `purchase` |
| OTP login → PDP → ATC → Purchase | `login` → `view_item` → `add_to_cart` → … → `purchase` |
| Newsletter signup | `newsletter_signup` |
| Contact form submit | `contact_submit` (on the page you land on after submit) |
| Category browse + search | `view_item_list`, `search` (+`results_count`) |
| Remove a cart line | `remove_from_cart` |

For each: confirm the event name, `ecommerce.value`, `currency: INR`,
`items[].item_id` (= SKU), and (Purchase) `transaction_id`. In Pixel Helper
confirm the mapped Meta event + value/currency/content_ids.

## 7. Recommendations before production launch
1. **Import & publish** `gtm-container.json`; fill the `GA4 Measurement ID` + `Meta Pixel ID` constants; mark `purchase` & `generate_lead` as GA4 Key Events.
2. **Run the 7 test journeys** above in GTM Preview + GA4 DebugView + Pixel Helper; capture the screenshots (Deliverables #7–#9 — only you can, with live accounts).
3. **Wire Consent Mode v2** to the existing GDPR consent categories before EU traffic (GTM-SETUP §5) — ship the `update` wiring, never a bare `denied` default.
4. **Plan Meta CAPI** for Purchase to recover ~20–40% lost signal (CAPI.md) — `eventID` dedup is already prepared.
5. **Set `item_id` policy** — confirm SKU matches your Merchant Center / Meta catalog feed id; adjust the mapper if it keys on product id.
6. **Filter internal traffic** in GA4 (your office/dev IPs) so pre-launch testing doesn't pollute production reports — or use a separate GA4 dev stream.
7. **Submit `sitemap.xml`** in Search Console and link GA4 ↔ GSC (SEARCH-CONSOLE.md).
8. (Optional) Add `view_item_list` to homepage product strips and a `method` dimension to `login`/`sign_up`.

## 8. Production launch readiness score

### 7 / 10

**What's done (code):** complete, render-verified data layer for all 14 events;
single clean GTM + Clarity install; importable GA4 + Meta container; SEO/sitemap
foundation passing; CAPI + Consent Mode documented; nothing hardcoded that blocks
go-live. Code is production-ready and self-activates when deployed.

**What's holding it back from 10 (all require your accounts / a live browser, not code):**
- GTM container not yet imported/published; GA4 + Pixel IDs not yet entered (−1)
- Live event verification (DebugView / Pixel Helper) + screenshots not yet run (−1)
- Consent Mode v2 not wired and Meta CAPI not built (both documented, not shipped) (−1)

Complete recommendations #1–#3 and this moves to a confident **9/10**; CAPI (#4)
takes it to 10.

---

**Deliverables #7, #8, #9 (screenshots from GTM Preview / GA4 DebugView / Meta
Pixel Helper) are not included** — they require operating your GTM/GA4/Meta
accounts in a live browser, which this environment cannot do and I will not
fabricate. The test plan in §6 makes capturing them a quick click-through once
the container is published.

**Nothing here is deployed to production. Awaiting your approval to go live.**
