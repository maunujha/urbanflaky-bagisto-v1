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
| 3 | 🟠 HIGH | Reduce/split/defer render-blocking `<head>` CSS (4 stylesheets) | P3 | ⏭️ deferred (gzip covered bulk) |
| 4 | 🟠 HIGH | Vue whole-page app mounts on `window.load` → mount earlier (INP/TBT) | P4 | ⬜ |
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

**#3 Render-blocking CSS — ⏭️ deferred (recommendation).** After P1 gzip the 4 `<head>` stylesheets total ≈ 37 KB gzipped (`app` 20 + `urbanflaky` 13.6 + `app` chunk 2.9 + `feature-accordion` 0.5), fetched in parallel over HTTP/2. The remaining levers — critical-CSS inlining or aggressive Tailwind purge of Bagisto's `app.css` — are high FOUC/regression risk for a premium UI and low reward now that transfer is small. Recommend measuring with PSI/CrUX first and only doing surgical CSS work if it's a proven bottleneck.

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
