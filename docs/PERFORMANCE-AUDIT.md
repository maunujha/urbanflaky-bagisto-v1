# Urbanflaky.in — Performance Optimization Audit & Tracker

> **Living document.** Phase 0 = baseline audit (read-only, no code changed). Later phases implement fixes one at a time. Update the **Status** column as each item is completed and verified, so at the end we can confirm every fix is done.
>
> - **Branch:** `perf/optimization` (from `dev`)
> - **Audited:** 2026-08-27 (mobile UA, live `https://urbanflaky.in`)
> - **Stack:** Bagisto 2.4 · Laravel 12 · PHP 8.3 · Vue 3 · Tailwind 3 · Vite 5 · nginx 1.24 + PHP-FPM · Redis · Meilisearch (one VPS)

## Targets

| Metric | Goal | Baseline (measured) |
|---|---|---|
| LCP | ≤ 2.5 s | not measurable locally (see gap below) |
| INP | ≤ 200 ms | not measurable locally |
| CLS | ≤ 0.10 | not measurable locally |
| **TTFB** | < 800 ms | **~200 ms home / ~180 ms catalog ✅ already met** |
| Page weight | ~< 2–3 MB | ~1.3–2.2 MB first load (assets uncompressed) |
| Requests | reduce w/o losing function | homepage: 4 CSS + 2 JS + fonts + GTM/Clarity + images |

**Measurement gap:** Playwright/Lighthouse are disabled in this project, so LCP/INP/CLS can't be measured locally. Validate field metrics **after each deploy** via PageSpeed Insights / CrUX / Search Console Core Web Vitals. Locally, verify via `curl -w` (TTFB, size, `content-encoding`) + HTTP-200 smoke + visual confirmation.

---

## Status tracker

Legend: ⬜ not started · 🟡 in progress · ✅ done & verified · ⏭️ skipped/won't do

| # | Sev | Item | Phase | Status |
|---|-----|------|-------|--------|
| 1 | 🔴 CRITICAL | Enable gzip/brotli for CSS/JS/JSON/SVG/fonts (text assets served uncompressed) | P1 | ✅ gzip live 2026-08-27 |
| 2 | 🟠 HIGH | Compress/resize oversized theme & category tile images (+ responsive `srcset`) | P2 | ✅ live 2026-08-27 (all HP images) |
| 3 | 🟠 HIGH | Reduce/split/defer render-blocking `<head>` CSS (4 stylesheets) | P4 | ✅ async CSS live 2026-08-27 |
| 4 | 🟠 HIGH | Vue mount-on-`window.load` (real issue: hero was Vue-only → late LCP) | P4 | ✅ solved via SSR hero (no mount change) |
| 5 | 🟡 MED | OPcache production tuning (validate_timestamps / max files / memory / JIT) | P1 | ✅ live 2026-08-27 |
| 6 | 🟡 MED | Trim heavy HTML (67 scripts, 52 inline SVGs → sprite; trim inline JSON) | P4 | ⬜ |
| 7 | 🟡 MED | Reduce `fetchpriority="high"` images from 5 → ~1 (LCP hero only) | P2 | ✅ live 2026-08-27 |
| 8 | 🟡 MED | Self-host Google Fonts (Poppins + DM Serif) as woff2 | P3 | ✅ live 2026-08-27 |
| 9 | ⚪ LOW | Verify spatie responsecache actually serves guests | P5 | ⬜ |
| 10 | ⚪ LOW | Static-asset TTL 30d→1y immutable (`/build/`); `logo.png`→webp/svg; fix relative img `src` | P2 | 🟡 cache-TTL ✅; logo/img-src pending |

> **Phase 1 applied 2026-08-27** (server config, live). Files version-controlled: `deploy/nginx/gzip.conf`, `deploy/php/99-urbanflaky-opcache.ini`, `deploy/nginx/urbanflaky.conf` (build block); install steps in `DEPLOYMENT.md §3b`. Measured savings below.
>
> | Asset | Before | After (gzip) |
> |---|---|---|
> | app.js | 152 KB | **46 KB** |
> | app.css | 117 KB | **20 KB** |
> | urbanflaky.css | 70 KB | **13 KB** |
> | Homepage HTML | 84 KB | **67 KB** |
>
> ~277 KB less per fresh load. Build assets now `max-age=1y, immutable`; non-hashed statics stay 30d; images correctly not gzipped. OPcache: 256 MB / 32531 files / interned 16 / revalidate_freq 60 (was 128 MB / 10000 / default) — no more eviction on 13.7k files. All smoke routes 200 (`/`, `/mens-tshirts`, `/blog`, `/track-order`, `/faqs`).
>
> **Remaining in #10 (moved to P2):** `logo.png` 28 KB PNG → webp/svg; some `<img src="storage/…">` missing leading slash.
> **Not done (needs a package install, your call):** brotli (`libnginx-mod-http-brotli`) — gzip already captures the bulk. **Optional further step:** OPcache `validate_timestamps=0` (see `deploy/php/99-urbanflaky-opcache.ini` note).

> **Phase 2 (images) — media resized on live 2026-08-27.** Theme/blog media lives outside git (`storage/app/public`), so it was optimized in place on the server with GD (no cwebp/ImageMagick on the box), backed up to `storage/app/theme-perf-backup-*`. Every homepage image now downscaled to a sane cap (heroes ≤1920, tiles ≤1200, blog ≤1600) at webp/jpg q82. Worst offenders: blog/8 1970→249 KB, blog/10 1083→84 KB, mens-cate 4024×5030/533→58 KB, mens-cate.jpg 1110→90 KB. ~4.9 MB of webp trimmed across the page (much of it lazy/carousel). Cache caveat: filenames unchanged, so returning visitors keep the old large version until the 30-day cache expires; new visitors get the small ones immediately.
>
> **Lazy-load audit (render-on-scroll):** every below-fold section already defers — carousel non-first slides (`loading=lazy`+`fetchpriority=low`), New Arrivals tiles (lazysizes `data-src`), product/category cards (`v-shimmer-image` IntersectionObserver, 200px rootMargin), feature-accordion (`loading=lazy`), looks grid + blog grid (`loading=lazy`). Only gap was the **video-banner poster** (was eager + `fetchpriority=high` + a `<head>` preload while below the fold) → fixed in `components/video-banner/index.blade.php` (poster `loading=lazy`+`decoding=async`, preload removed; `.uf-vbanner` reserves 78vh so no CLS). **This is the one Phase-2 code change — pending deploy.**
>
> **Not a bug (investigated):** product tiles showing a `<picture>` with a webp `<source>` + jpg `<img>` is the intended progressive-enhancement fallback — modern browsers fetch only the webp; the jpg never downloads. **Deferred to later:** `logo.png`→webp/svg (28 KB, minor); category-tile relative `<img src="storage/…">`/`href` (admin static content in DB, works on `/`).
>
> **Phase 2b (found via Playwright E2E on live, fixed 2026-08-27).** The browser test surfaced two more heavy homepage images (both lazy/below-fold, no LCP impact):
> - **Category carousel** used `original_image_url` (2500–3780px originals: 423/185 KB webp) at card size. The carousel deliberately uses `original` to preserve aspect (the `large`/`medium` variants are 600/300px **cover-cropped** — would change composition), so fixed by resizing the category originals in place on disk preserving aspect (media, no code): 5hD 2500×3000 423→**175 KB**, 9hNSx 3780×1890 185→**66 KB** (+jpg siblings). Guard: GD re-encode can *enlarge* already-efficient webp — two 1600px/62 KB files grew to 105 KB and were **restored from backup** (only resize genuinely oversized files).
> - **Lookbook** grid served the original **JPG** (311 KB) even though a webp sibling existed (104 KB). Fixed in code: `App\Models\LookbookItem::getImageUrlAttribute()` now prefers the same-basename `.webp` sibling. Deployed; verified live all 5 looks now load as webp.
>
> **E2E verification (Playwright, live, mobile 390×844):** #7 → only the hero carousel is preloaded/high-priority (video-poster preload gone); every below-fold section carries `loading=lazy`; render-on-scroll proven (looks-grid images `loaded:false`→`true` after scrolling); full scrolled-page image weight ≈ 2.2 MB (within target). Reusable server optimizer scripts: `scratchpad/uf-optimize*.php` (not committed — operate on live media).

## Phase 3 — fonts + CSS delivery (2026-08-27)

**#8 Self-hosted fonts ✅ live.** Dropped Google Fonts entirely (2 third-party origins + a render-blocking CSS round-trip gone). 10 woff2 in `public/fonts/` (Poppins 400/500/600/800 + DM Serif 400, latin + latin-ext), `@font-face` in `urbanflaky.css` with `unicode-range` + `font-display:swap`; layout preloads Poppins 400/600 (the above-fold weights). `public/fonts/.gitignore` whitelists `*.woff2` (dir also holds mPDF TTFs). Shop theme rebuilt + committed (`uf-deploy` root build doesn't rebuild the theme, so its `public/themes/shop/.../build` assets are version-controlled). **E2E verified:** 0 requests to googleapis/gstatic; Poppins loads from `/fonts/` (5–8 KB each) + renders; DM Serif force-load ok (200, on-demand). Commit `2bc4711`.

**Brotli — not done (infeasible here).** Stock Ubuntu nginx 1.24 has no brotli module and `libnginx-mod-http-brotli` isn't in the server's apt repos. Adding it means switching to a PPA nginx build or compiling `ngx_brotli` from source — both risky on a live box for ~15% over the gzip already active. Left off by decision.

**#3 Render-blocking CSS — initially deferred, then FIXED in P4** (see below — a real PSI on mobile proved it was the FCP bottleneck after all).

## Phase 4 — the real mobile-LCP fix (2026-08-27)

**Trigger:** a real **PageSpeed Insights mobile** run (Moto G, throttled) scored **57**: **FCP 4.0s 🔴, LCP 7.7s 🔴, Speed Index 8.9s 🔴** (TBT 170ms ✅, CLS 0.066 ✅). This contradicted an earlier *unthrottled* Playwright run (LCP 1.16s) — **lesson: never judge CWV on an unthrottled lab run; throttled/field only.** Because TBT/CLS were green, the problem was purely *time-to-paint*, not main-thread — so the originally-feared #4 "mount earlier for INP" was a red herring.

**Root cause (three compounding render-path blockers):**
1. **4 render-blocking stylesheets** blanked the screen until downloaded on slow 4G → FCP 4.0s.
2. **Hero was Vue-only.** `carousel/index.blade.php` server-rendered only a shimmer; the real hero `<img>` lived in the Vue `<script type=text/x-template>`, absent from the DOM until `app.mount()` on `window.load` — so LCP was pinned to the load event and the `<head>` hero preload was wasted → LCP 7.7s.
3. Full-screen preloader also exits on `window.load`, compounding.

**Fix (contained, no checkout/mount-timing surgery) — commit `d460ce7`:**
- **SSR the first hero slide** (`carousel/index.blade.php`): the LCP `<img>` (with `fetchpriority=high`) is now in the initial HTML; Vue replaces it on mount (same URL → cached, no reflow). #4 solved *without* touching the global Vue mount (avoids the checkout/variant risk entirely).
- **Non-render-blocking CSS** (`layouts/index.blade.php`): capture `@bagistoVite(...)->toHtml()` and rewrite the stylesheet `<link>`s to `media="print" onload="this.media='all'"` + `<noscript>` fallback + a load-time safety flip. **The inline-styled full-screen preloader masks the brief unstyled window → zero FOUC.** Regex no-match falls back to render-blocking (safe).

**Verified on live (Playwright):** SSR hero present in raw HTML; 3 stylesheets async then flip to `media=all` (CSS fully applied, hero has its CSS height → no FOUC); unthrottled FCP 560→**352ms**, LCP 1160→**352ms**; screenshot renders correctly (dark theme, hero, fonts). **Awaiting a fresh PSI mobile run for the throttled score.** If Speed Index still lags, next lever is the **preloader exit timing** (reveal when CSS+hero ready instead of full `window.load`) — deferred until measured. Minor: `feature-accordion.css` (1.3 KB, below-fold) left render-blocking.


## Phase 7 — storefront templates (2026-08-28)

Scope limited by request to changes that **cannot move the current layout**. The
PDP tabs/accordion duplication (the single biggest template win) is deliberately
left for a later phase — see "Deferred" below.

**Measured baseline (local, `curl`, before/after each page):**

| Page | raw | gzip | tags | inline x-templates | module JS |
|---|---|---|---|---|---|
| blog listing | 240.6 KB | 44.5 KB | 837 | 71.6 KB | 33.2 KB |
| blog article | 228.8 KB | 45.2 KB | 1073 | 71.6 KB | 33.2 KB |
| category | 354.4 KB | 60.1 KB | 1679 | 165.9 KB | 59.4 KB |
| homepage | 408.4 KB | 70.6 KB | 1730 | 132.0 KB | 66.2 KB |
| PDP | 481.0 KB | 80.5 KB | 2461 | 236.7 KB | 95.7 KB |

Inline Vue templates + module JS are **44–69% of every document**. That is the
structural headline for any future phase.

### What changed

**Product card** (`components/products/card.blade.php`) — used by homepage
carousels, the category grid and PDP related products. The card shipped a
desktop half *and* a mobile half on every breakpoint, with CSS hiding one:

- `.uf-hover-panel` (22 static elements + a `v-for` over every super-attribute
  option, rendered twice inside it) is `display:none` below 1180px.
- `.uf-mob-price` / `.uf-mob-cta` / `.uf-mob-swatches` (19 elements) are
  `display:none` above 767px.
- `.uf-delivery-strip`, `.uf-card-price-row`, `.uf-card-subtitle` and the
  `.uf-card-rating-badge` are `display:none` below 768px.
- `.uf-card-brand` ("Urbanflaky" eyebrow) is `display:none` at **every**
  breakpoint — the mobile media query restyles it but never reveals it.

Each half is now mounted only for the breakpoint that paints it, driven by two
shared `matchMedia` queries that mirror the CSS breakpoints 1:1 and stay
reactive on resize (one listener pair for the whole page, not one per card).
The permanently-hidden brand node was deleted. **~44 fewer elements per card on
mobile, ~19 on desktop**, before counting the per-option swatch nodes.

**Below-fold section fetches** (`components/products/carousel`,
`components/products/grid`, `components/categories/carousel`) fired their XHR on
`mounted()`. They now start when the shimmer placeholder comes within 600px of
the viewport, matching the pattern `v-product-associations` and the reviews
component already use. The homepage has **5 product carousels + 1 category
carousel** — six requests moved off the critical path. Falls back to an
immediate fetch when `IntersectionObserver` is unavailable. The two shimmer
roots gained `{{ $attributes }}` so they can carry the `ref`.

**Category listing** (`categories/view.blade.php`) — the desktop toolbar was
only CSS-hidden on mobile, so phones built a **second `v-toolbar` instance**
behind the one in the filter drawer. Now gated with `v-if="! isMobile"`, the
same way `shop::categories.filters` already gates itself.

**PDP** (`products/view.blade.php`, `products/view/gallery/mobile.blade.php`):

- Removed `$reviewCount = $reviewHelper->getTotalFeedback($product)` — assigned
  and never read (the rating block recomputes it into `$totalRatings`). One
  fewer query per product view. *`$percentageRatings` looks equally unused but
  is NOT — `shop::products.view.reviews` reads it through the inherited include
  scope. It stays, with a comment saying so.*
- Mobile gallery slides 2..n are `loading="lazy"` + `fetchpriority="low"`; only
  slide 1 is in the viewport, the rest are translated off-screen. The fixed
  `aspect-square` box means a late slide cannot shift layout.
- The sticky add-to-cart thumbnail got `width`/`height` + `fetchpriority="low"`
  + `decoding="async"`. It must stay eager (a lazy image inside a transformed
  `fixed` bar may never trigger) but it should not compete with the gallery LCP.

**Blog** (`Shop/BlogController`, `HomeBlogComposer`, both shop views):

- The listing, the home "From the Journal" grid and the article's recent-posts
  rail all hydrated the full `content` column (the entire article HTML) for
  rows that only render a card. All three now select card columns only —
  `image` stays selected because the appended `image_url` accessor reads it.
  Measured on the 9-row listing: **104,421 → 3,912 bytes hydrated (96% less)**.
- The listing's first card image was `loading="lazy"` — it is the LCP candidate.
  Row 1 (3 cards desktop / 1 mobile) is now eager, card 1 carries
  `fetchpriority="high"`, the rest stay lazy.
- The article's featured image (always the LCP) gained `fetchpriority="high"`.

### Cost, stated honestly

Served HTML grew **+0.1 to +1.1 KB gzipped per page** — the observer methods and
the `v-if` attributes live inside the inline x-templates. The wins are in
runtime DOM, deferred requests and DB hydration, not in document bytes.

### Verification

- All five routes 200; `/blog?page=2` and `/mens-tshirts?sort=price-asc&limit=24` 200.
- **Every JSON-LD block byte-identical** before/after on all five pages.
- SEO-visible surface diffed before/after: links, images, headings and visible
  text **all identical** (0 lost) on all five pages.
- `HomePageTest` + `StructuredDataTest`: 26 passed, 2 failed — the 2 failures
  (newsletter `shop.subscription.store` redirect) reproduce identically on a
  stashed clean tree, so they pre-date this phase.
- `API/CategoryProductTest`: 5 passed, 2 failed — also identical on the clean tree.
- No CSS/JS source touched and no new Tailwind class introduced, so no theme
  rebuild is required.

### Still to verify in a browser (cannot be checked locally)

1. Product cards at ≥1180px, 768–1179px and ≤767px look identical to before,
   and **resizing across 767px / 1180px** swaps the halves correctly.
2. Swiping the mobile PDP gallery — slide 2 should paint within the 700ms
   transition; if a blank frame is visible on slow connections, warm the
   next slide on `handleDragEnd` instead of relying on `loading="lazy"`.
3. Category page on a phone: filter drawer, sort drawer, load-more all intact.
4. Homepage: scroll from top — every carousel fills before it reaches the
   viewport (600px rootMargin).

### Deferred (needs layout work — not in this pass)

- **PDP renders its long-form content twice.** `products/view.blade.php` emits
  the description, the attribute table, `<v-product-reviews>` and the
  shipping-returns CMS HTML once for the ≥1180px tab strip and again for the
  `1180:hidden` accordion. `v-tabs-template` alone is 20.5 KB. Deduping means
  making one DOM serve both presentations — a real change to the tabs/accordion
  UX, so it was left out.
- **Lookbook grid** still fetches on mount: its template renders nothing while
  loading, so there is no element to observe. Needs a placeholder first.
- **Inline x-template weight** (the 44–69% figure above) — blog pages ship
  105 KB of Vue templates (mini-cart 15.6 KB, exit-intent 11 KB, headers,
  category tree) they never use. Gating those per route is the largest
  remaining template-level win.


## Phase 8 — final validation (2026-08-28)

**Two facts frame everything below.**

1. **Phase 7 is not deployed.** It is uncommitted in the working tree. Production
   (`urbanflaky.in`) currently runs **P1–P4**. Any PSI run today validates P1–P4,
   not the template work.
2. **LCP / INP / CLS were not measured, and are not estimated here.** They need a
   real throttled browser or field data. Phase 4 already recorded why a local lab
   number is worse than none: an unthrottled Playwright run reported LCP 1.16s on
   the same page a throttled PSI mobile run scored **LCP 7.7s**. The authority
   stays PSI / CrUX / Search Console on production.

**Local TTFB is not the site's TTFB.** Measured 0.85–1.9s here (Laragon/Windows,
FPC off, predis, untuned OPcache). Production is documented at ~200ms home /
~180ms catalog — already inside target. Do not read the local column as a
regression.

### Resource graph — local, transfer bytes on the wire (gzip applied)

| page | device | doc | css | js | font | img eager | img lazy | critical path | total | reqs |
|---|---|---|---|---|---|---|---|---|---|---|
| homepage | mobile | 71.5 KB | 36.1 | 45.6 | 15.6 | 258.0 | 4,175.7 | **426.7 KB** | 4.6 MB | 25 |
| homepage | desktop | 71.5 KB | 36.1 | 45.6 | 15.6 | 258.0 | 4,175.7 | **426.7 KB** | 4.6 MB | 26 |
| category | mobile | 60.6 KB | 35.6 | 45.6 | 15.6 | 0 | 0 | **157.4 KB** | 157.4 KB | 8 |
| category | desktop | 60.6 KB | 35.6 | 45.6 | 15.6 | 0 | 0 | **157.4 KB** | 157.4 KB | 8 |
| product | mobile | 81.5 KB | 35.6 | 45.6 | 15.6 | 3.3 | 0 | **181.6 KB** | 181.6 KB | 9 |
| product | desktop | 81.5 KB | 35.6 | 45.6 | 15.6 | 3.3 | 0 | **181.6 KB** | 181.6 KB | 9 |
| blog | mobile | 44.5 KB | 35.6 | 45.6 | 15.6 | 1,937.5 | 9,515.0 | **2,078.8 KB** | **11.6 MB** | 16 |
| blog | desktop | 44.5 KB | 35.6 | 45.6 | 15.6 | 1,937.5 | 9,515.0 | **2,078.8 KB** | **11.6 MB** | 16 |

Mobile and desktop are identical because the server renders one HTML for both;
the split happens client-side. Category and product show ~0 server-rendered
images because their grids/galleries are Vue-rendered — their images arrive
after the API call and are measured separately (below).

### The headline finding: blog images

`/blog` is **11.6 MB**, ~70× the category page. Eight images, **1,432 KB average**:

| file | size |
|---|---|
| blog/xNLa9EBz… | **3,796.9 KB** |
| blog/q2HOIYsS… | **3,201.1 KB** |
| blog/ayp0PN53… | 1,969.5 KB |
| blog/ljqL18uX… | 1,082.9 KB |
| blog/uMSU1NaP… | 644.6 KB |
| blog/dQ9xmVsJ… | 317.3 KB |
| blog/5dXEQmVW… | 230.2 KB |
| blog/EaK8kCAG… | 210.0 KB |

Root cause: `Blog::getImageUrlAttribute()` returns `Storage::url($this->image)` —
the raw upload. No resizer, no `<picture>`, no WebP-sibling preference (the fix
`LookbookItem` got in P2b). A 3.8 MB file is painted into a 600×375 card.

For contrast, **product** card images go through the Bagisto resizer
(`/cache/medium/…`) and measure **5 KB each** (13 unique = 63 KB total). The
pipeline works; blog simply bypasses it.

P2 resized blog/8 (1970→249 KB) and blog/10 (1083→84 KB) **on the live server**
because they appear on the homepage. Local still holds the originals, and the
listing shows 8 images — so **the multi-MB files on `/blog` were probably never
touched on production either.** Verify there before anything else.

### Runtime API payloads (gzipped)

| endpoint | size |
|---|---|
| category products (12/page) | 2.8 KB |
| related products (PDP) | 1.3 KB |
| header category tree | 0.5 KB |
| filter attributes / max price | 0.1 KB each |

Negligible. The API layer is not a bottleneck.

### Verification — what passed

- **SEO** across homepage / category / product / blog / article: exactly one
  `<title>`, one canonical (absolute, self-referencing), meta description,
  `robots: index, follow`, og:title + og:image — all five pages.
- **robots.txt**: disallows `/admin /checkout /cart /customer /api /search
  /compare`, points at the production sitemap.
- **sitemap.xml**: 200, valid `urlset`, 46 `<loc>` entries.
- **noindex** correctly set on cart, login and register.
- **Structured data**: every JSON-LD block parses. homepage `WebSite`,
  `Organization`, `FAQPage` · category `CollectionPage`, `BreadcrumbList` ·
  product `Product`, `BreadcrumbList` · blog `Blog`, `BreadcrumbList` · article
  `BlogPosting`, `BreadcrumbList`.
- **Add to cart** (simple): 200, cart populated, **Rs 249.00 = Rs 237.14 +
  Rs 11.86 tax** — GST-inclusive pricing back-computing correctly.
- **Variants**: configurable + valid `super_attribute` → 200, item in cart.
  Configurable **without** options → 400 "Options are missing for this product."
  An invalid colour/size combination is also correctly rejected.
- **Checkout**: `/checkout/onepage` 200 with a populated cart, no redirect;
  `v-checkout-new` SPA, OTP gate, reCAPTCHA and address selection all present.
- **Login**: OTP architecture intact (send / verify / resend forms), Google OAuth
  entry present, CSRF wired, guests 302 away from `/customer/account/profile`.
  *The OTP send endpoint was deliberately not fired — it dispatches a real SMS.*
- **Search**: `/search` 200 for plain and natural-language queries; API returns
  12 results.
- **Filters / sorting / pagination**: `sort`, `limit`, `mode`, price range and
  `/blog?page=2` all 200; products API honours sort+limit; 3 filterable
  attributes returned.
- **Analytics**: GTM `GTM-TK3MV6Q3` async, dataLayer initialised, Clarity present,
  Consent Mode v2 default + cookie layer, PDP `view_item` push with ecommerce
  payload, `ufTrack` helper present.
- **Meta Pixel**: no direct `fbq(` — **by design**, it fires through the GTM
  container. Presence cannot be confirmed without publishing/inspecting GTM.

### CORRECTION (2026-08-28, from the pre-deploy production run)

**The soft-404 finding below is a LOCAL-ONLY artifact — it does not reproduce on
production.** Live, `/images/<missing>.png` correctly returns **404**. Laragon has
no static-asset regex so everything falls through to `index.php`, whereas the
production vhost has `location ~* \.(css|js|jpg|webp|...)$ { try_files $uri =404; }`
which 404s properly. `deploy/nginx/urbanflaky.conf` even documents this difference.
The "return 404 for unknown asset paths" recommendation is therefore **withdrawn** —
there is nothing to fix.

**The relative-`src` finding IS real and confirmed live:** the homepage serves
`src="storage/theme/1/..."` and two more without a leading slash. They resolve at
`/` and are the single FAIL in the pre-deploy acceptance run. Still worth fixing as
fragility, but with production 404ing correctly they degrade to a broken image
rather than a 421 KB HTML download.

### Verification — issues found (none caused by P7)

- **Soft-404s.** Unknown paths that look like files return **HTTP 200 with a
  421 KB HTML page** instead of 404: `/images/nope.png`,
  `/mens-tshirts/storage/…`, `/blog/storage/…`. Single-segment unknown slugs
  correctly 404. Wastes crawl budget and serves duplicate content on garbage URLs.
- **4 relative image `src`** remain on the homepage
  (`src="storage/theme/5/mens-cate-01.webp"`, no leading slash — audit item #10).
  Harmless at `/`, but on any nested path they now resolve to a **421 KB HTML
  soft-404** instead of an image. The two bugs compound.
- **Missing assets under `/storage/` return 403**, not 404.

### Regressions

**None found.** JSON-LD is byte-identical pre/post-P7 on all five pages; links,
images, headings and visible text all identical. The two `HomePageTest` and two
`API/CategoryProductTest` failures reproduce on a stashed clean tree and pre-date
this work.

One honest cost and one caveat:

- P7 added **+0.1 to +1.1 KB gzipped per page** (observer methods and `v-if`
  attributes live inside the inline x-templates).
- P7 made the blog listing's first row eager so the LCP image is not lazy. That
  is correct in principle, but with 1.4 MB images it now gives a multi-MB file
  high priority. **Ship the blog-image fix with it, or before it.**

### Recommended next steps, in order

1. **Fix blog images.** Resize + WebP on upload, serve through the resizer or a
   `<picture>`, backfill existing posts. ~11 MB → well under 1 MB on `/blog`.
   Every other item on this list is a rounding error next to it.
2. **Deploy P7 and run PSI mobile** on all four page types — those are the real
   "after" CWV numbers, and the only ones worth recording.
3. **Return 404 for unknown asset paths** in nginx (`try_files $uri =404` for
   file-extension locations) and fix the 4 relative `storage/` srcs.
4. **Verify FPC actually serves guests** (audit item #9, still open).
5. Only then the template-level work: per-route gating of inline Vue templates
   (blog pages ship 105 KB they never use) and the PDP tabs/accordion dedup.

### Not verifiable without a browser

LCP · INP · CLS · product-card rendering at each breakpoint and across resize ·
mobile PDP gallery swipe · GTM/Meta tags actually firing · cookie-consent gating
behaviour · checkout end-to-end through OTP.


## Phase 9 — blog images (2026-08-28)

Phase 8's #1 recommendation, implemented. **`/blog` went from 11.6 MB to 310 KB.**

### The problem

`Blog::getImageUrlAttribute()` returned `Storage::url($this->image)` — the raw
upload — into a 600×375 card. Eight images averaging 1,432 KB, worst 3.8 MB.
The Bagisto resizer was right there (product cards come out at 5 KB); blog just
never used it.

The obvious fix — point at the existing `small`/`medium`/`large` templates —
doesn't work: **all three `cover()` to a square**, which would re-crop editorial
photography. Same trap the category carousel hit in P2b.

### The fix

Two scale-only templates, registered from the app so the Webkul package config
stays untouched and a cached config still picks them up:

| template | width | used by |
|---|---|---|
| `blog_card` | 800px | listing, home grid, recent-posts rail |
| `blog_wide` | 1600px | article featured image + og:image |

Both use `scaleDown()`, which preserves aspect ratio **and never enlarges** — a
small upload passes through untouched instead of being upscaled into a bigger
file. Verified on the worst offender:

| | dimensions | size |
|---|---|---|
| original | 5184×3456 | 1,969.5 KB |
| `blog_card` | 800×533 | 46.5 KB |
| `blog_wide` | 1600×1067 | 136.1 KB |

3:2 in, 3:2 out. The cards already crop in CSS via `object-cover`, so the
rendered result is unchanged.

### Results

| page | before | after | change |
|---|---|---|---|
| `/blog` total | 11,593.8 KB | **309.9 KB** | −97.3% |
| `/blog` critical path | 2,078.8 KB | **176.6 KB** | −91.5% |
| `/blog` card images | 11,452.5 KB | **168.6 KB** | −98.5% |
| homepage total | 4,602.4 KB | **777.2 KB** | −83.1% |
| homepage lazy images | 4,175.7 KB | **350.5 KB** | −91.6% |

The homepage improved because the "From the Journal" grid uses the same cards.

This also retires the Phase 7 watch item: the listing's first row is eager with
`fetchpriority="high"`, which was a liability at 1.4 MB and is now correct at
~40 KB.

### A real finding underneath: ImageCache caches nothing

Despite the name, `Webkul\ImageCache` has **no server-side cache**. The
controller reads the source, applies the filter and re-encodes on *every*
request; the `lifetime` config only feeds HTTP cache headers. `public/cache/`
did not exist on disk at all.

Measured locally (every `/cache/` hit boots Laravel):

| | median TTFB |
|---|---|
| static file via nginx | **15 ms** |
| `/cache/medium/product/…` (existing) | 592 ms |
| `/cache/blog_card/…` (18 MP source) | 1,955 ms |

So the byte win came with a CPU cost on cold requests. The production nginx
block is already `location ^~ /cache/ { try_files $uri /index.php...; }` — the
`try_files $uri` half means **a file that exists on disk is served statically
and never reaches PHP.**

`php artisan blog:warm-images` writes exactly those files
(`public/cache/<template>/<path>`), turning a ~0.5 s PHP render into a ~15 ms
static hit. It is purely an optimisation — delete `public/cache/` and the
resizer still answers. Run it after a deploy, or after publishing a post with a
new image. Local run: **20 derivatives, 12.7 MB of sources → 882.7 KB.**

`/public/cache/` is gitignored — rebuildable output, never source.

> **Worth noting for later:** this applies to *product* images too. Every
> product image on the site is re-encoded per cold request today. Extending the
> warm-up to products is likely a meaningful CPU saving on the VPS, but it was
> left out of this phase as out of scope.

### Verification

- All five routes 200. Link, heading and visible-text counts identical to the
  pre-Phase-7 baseline; image *counts* unchanged (only the URLs changed).
- JSON-LD identical on four of five pages. The single diff is intentional and an
  improvement: `BlogPosting.image` now points at the 1600px derivative instead
  of the 5184px original.
- `HomePageTest` + `StructuredDataTest`: 26 passed, same 2 pre-existing failures
  that reproduce on a clean tree.
- Aspect ratios verified numerically (above) rather than by eye.

### Files

`app/Support/ImageTemplates/BlogCard.php`, `BlogWide.php` (new) ·
`app/Console/Commands/WarmBlogImages.php` (new) ·
`app/Providers/AppServiceProvider.php` (template registration) ·
`packages/Gabha/Blog/src/Models/Blog.php` (`card_image_url`, `hero_image_url`) ·
blog `index`, `show`, `partials/home-grid` views · `.gitignore`.

`image_url` is deliberately left returning the original — nothing on the
storefront uses it now, but it stays available and unsurprising.

### Still to confirm in a browser

Blog cards and the article hero render identically to before. The maths says
they must (same aspect, same CSS crop), but it has not been looked at.

---

## Baseline: what's already good (do NOT touch)

- **Backend is fast.** Live `.env`: Redis for `CACHE_STORE` + `SESSION_DRIVER` + `QUEUE_CONNECTION` (phpredis); `RESPONSE_CACHE_ENABLED=true` (spatie, redis); config/routes/events cached; OPcache on. TTFB ~200 ms — **do not "optimize" TTFB.**
- Homepage HTML render is query-light (theme customizations + category tree); product/category carousels load via AJAX to `shop.api.*`.
- WebP pipeline working (48 webp vs 5 jpg / 6 png references on homepage).
- Hero LCP image preloaded correctly (`<link rel=preload as=image fetchpriority=high>`, mobile + desktop split) — 71 KB, good.
- GTM + Clarity load async and are consent-gateable; fonts are non-blocking (preload + onload swap, `display=swap`).
- TensorFlow/jsdelivr is **on-demand** (visual image-search only, not homepage). Instagram/Facebook on homepage are schema `sameAs` + footer `href` links — **no embed scripts**. Looks grid uses an internal API.
- Theme images cached `30d, public, immutable`.

## Measured payload (live, mobile UA)

| Asset | Served size | Compressed on live? |
|---|---|---|
| Homepage HTML | 398 KB raw → **84 KB gz** | ✅ (text/html only) |
| `app.js` | **152 KB** | ❌ none |
| `app.css` | **117 KB** | ❌ none |
| `urbanflaky.css` | **70 KB** | ❌ none |
| `app-*.css` chunk + `feature-accordion.css` | ~18 KB | ❌ none |
| Hero webp (LCP) | 71 KB | n/a |
| `mens-cate-01.webp` (category tile) | **545 KB** | n/a — too big |
| `logo.png` | 28 KB | n/a |

Homepage also: **67 `<script>` tags** (mostly inline Vue templates), 52 inline SVGs, ~1,680 DOM tags, entire page is one Vue app mounted on `window.load`.

---

## Findings (detail)

### 🔴 #1 — Text assets served uncompressed  · Phase 1  · Status: ✅ done (gzip, 2026-08-27)
nginx has `gzip on` but `gzip_types` / `gzip_comp_level` / `gzip_vary` are **commented out** in `/etc/nginx/nginx.conf` → default gzip compresses only `text/html`. Measured: `app.js` 152 KB, `app.css` 117 KB, `urbanflaky.css` 70 KB all returned `content-encoding: NONE`. ~357 KB of CSS+JS shipped raw; gzip/brotli → ~80 KB (**~280 KB saved**). Biggest single win, zero functional risk.
- **Fix:** enable `gzip_types` (css, js, json, svg, xml, fonts), `gzip_comp_level 5–6`, `gzip_vary on`; add brotli if the module is available.
- **Files:** `/etc/nginx/nginx.conf` (or `urbanflaky.conf`) — **server-side, outside git**. Document in `DEPLOYMENT.md`; re-apply on rebuild.
- **Verify:** `curl -sI --compressed <asset>` shows `content-encoding: gzip|br`.

### 🟠 #2 — Oversized theme/category tile images  · Phase 2  · Status: ⬜
`mens-cate-01.webp` = **545 KB** (webp but served full-resolution, bypasses the resizer); several such tiles ≈ 1.5–2 MB across the category row. Hurts page weight + mobile LCP.
- **Fix:** re-export/compress tiles to rendered dimensions; serve responsive `srcset`/`<picture>`; keep width/height to avoid CLS.
- **Files:** theme image assets + category-carousel component + `home/index.blade.php`.

### 🟠 #3 — Render-blocking `<head>` CSS (4 stylesheets)  · Phase 3  · Status: ⬜
`app-*.css` ×2 + `urbanflaky.css` + `feature-accordion.css` all render-blocking (~205 KB raw). Even after gzip, still 4 blocking requests + parse.
- **Fix:** tighten Tailwind purge; split critical vs below-fold (e.g. defer feature-accordion); consider inlining critical CSS.
- **Files:** `components/layouts/index.blade.php` (`@bagistoVite`), `Shop/vite.config.js`, `Shop/tailwind.config.js`, `assets/css/urbanflaky.css`.

### 🟠 #4 — Whole-page Vue app mounts on `window.load`  · Phase 4  · Status: ⬜
`app.mount('#app')` fires on `window.load`, which waits for **all** images before the page is interactive → delayed INP/TBT. `#app` wraps header + main + footer.
- **Fix:** mount on `DOMContentLoaded`/idle instead of `load`; consider trimming globally-registered components.
- **Files:** `components/layouts/index.blade.php` (mount script), `assets/js/app.js`.
- **⚠ Highest regression risk** — affects checkout, PDP variants, carousels, search, add-to-cart. Do last, test thoroughly.

### 🟡 #5 — OPcache production tuning  · Phase 1  · Status: ✅ done (2026-08-27)
Live: `opcache.validate_timestamps=On` (stat every file per request), `max_accelerated_files=10000` (Bagisto exceeds 10k PHP files → churn), JIT off, memory 128 MB.
- **Fix:** `max_accelerated_files≈20000`, `memory_consumption=256`, `validate_timestamps=0` (deploy MUST opcache-reset / fpm-reload — verify `uf-deploy` does this).
- **Files:** `/etc/php/8.3/fpm/php.ini` — server-side, outside git. Document in `DEPLOYMENT.md`.

### 🟡 #6 — Heavy HTML document  · Phase 4  · Status: ⬜
398 KB raw / 84 KB gz, 67 `<script>` tags (inline Vue templates), 52 inline SVGs, inline `localStorage` categories dump.
- **Fix:** SVG sprite for repeated icons; trim inline JSON/localStorage payloads.
- **Files:** header/footer SVGs, `home/index.blade.php`, various component blades.

### 🟡 #7 — Too many high-priority images  · Phase 2  · Status: ⬜
5 `fetchpriority="high"` images on the homepage; should be ~1 (the LCP hero). Extra hints starve the real LCP image on mobile.
- **Files:** carousel + category-tile components emitting `fetchpriority`.

### 🟡 #8 — Google Fonts external  · Phase 3  · Status: ⬜
Poppins (4 weights) + DM Serif via preload+swap from `fonts.googleapis.com` / `fonts.gstatic.com`.
- **Fix:** self-host woff2 (subset, `font-display:swap`, `size-adjust`/fallback metrics) → removes 2 third-party origins + cuts font-swap CLS.
- **Files:** layout head, `@font-face` in `urbanflaky.css`, self-hosted font files.

### ⚪ #9 — responsecache effectiveness unverified  · Phase 5  · Status: ⬜
Homepage sends a fresh session cookie + `cache-control: no-cache, private`, no cache-hit header — confirm guests are actually served from spatie responsecache (TTFB is fine regardless).
- **Files:** responsecache config/middleware, session-cookie-on-GET behavior.

### ⚪ #10 — Misc small wins  · Phase 2  · Status: 🟡 cache-TTL ✅ (2026-08-27); logo/img-src pending
Static assets `expires 30d` (hashed build files could be 1y `immutable`); `logo.png` 28 KB PNG → webp/svg; some `<img src="storage/…">` lack a leading slash (fragile on non-root paths).
- **Files:** nginx `expires` rule; logo asset; category-carousel blade.

---

## Phase plan

| Phase | Scope | Risk |
|---|---|---|
| **P1** | Server config: #1 gzip/brotli, #5 OPcache, #10 cache-TTL | Low (no code, reversible) |
| **P2** | Images: #2 resize/srcset, #7 fetchpriority, logo | Low–Med |
| **P3** | CSS & fonts: #3 render-blocking CSS, #8 self-host fonts | Med (FOUC risk) |
| **P4** | JS/hydration: #4 mount timing, #6 HTML/DOM trim | **High** (checkout/variants) |
| **P5** | Verify: #9 responsecache | Low |

> ~60–70% of achievable improvement is **P1 alone** (compression + OPcache) — pure server config.

## Global risks & rules
- **Do-not-break:** SEO/JSON-LD (StructuredData single source), custom checkout (OTP + reCAPTCHA), configurable variants, Meilisearch + NL search, GTM/GA4/Clarity/Meta + cookie consent, premium monochrome design.
- **Server changes are live-only** (nginx/php.ini outside git) — persist in `DEPLOYMENT.md`, re-apply on any rebuild.
- `validate_timestamps=0` requires opcache-reset on deploy or code changes won't take effect.

## Verification checklist (run after each phase)
- [ ] `php artisan optimize:clear`; `view:clear` after blade edits (never `view:cache`)
- [ ] Rebuild theme: `cd packages/Webkul/Shop && npm run build`
- [ ] HTTP-200 smoke (curl): `/`, `/mens-tshirts`, a PDP, `/blog`, cart, checkout, search
- [ ] Re-measure `curl -w`: TTFB, size, `content-encoding` on CSS/JS (should be gzip/br), image sizes
- [ ] Functional: search NL feedback key present, add-to-cart datalayer events fire, checkout OTP/reCAPTCHA intact
- [ ] Pest filters for any touched class with tests
- [ ] Field CWV after deploy: PageSpeed Insights / CrUX / Search Console — user confirms visually

## Final sign-off (fill at the end)
- [ ] All CRITICAL/HIGH items ✅
- [ ] LCP ≤ 2.5 s (field) · INP ≤ 200 ms · CLS ≤ 0.10 confirmed on PSI/CrUX
- [ ] No regression in checkout, variants, search, analytics, SEO, design
- [ ] `DEPLOYMENT.md` updated with all server-side changes
