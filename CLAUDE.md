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

**Urbanflaky** — premium **dark-aesthetic fashion** brand by **Gabha Enterprise**, Dholpur, Rajasthan, India.

- Focus: oversized t-shirts, heavyweight cotton apparel, minimal monochrome streetwear for men & women · ₹299–799
- Local dev: `http://urbanflaky.test` · Admin: `/admin` · Production: `urbanflaky.in`
- Currency: INR · Timezone: Asia/Kolkata · DB: `my_bagisto_store`
- Laragon: PHP 8.3 at `C:\laragon\bin\php\php-8.3.30` · Nginx: `C:\laragon\etc\nginx\sites-enabled`

> Not a generic store. Every design, feature, and word reinforces one premium monochrome identity. Before any recommendation ask: **does this make Urbanflaky more premium, more minimal, faster, more maintainable, and stronger for long-term SEO?** If not, propose a better alternative.

---

## Brand & Voice

- **Identity**: dark aesthetic · premium minimalism · modern streetwear · monochrome · oversized silhouettes · heavyweight cotton · luxury simplicity. Feel = premium, modern, clean, confident, timeless.
- **Voice**: write like a premium fashion magazine — confident, informative, human. No clickbait, salesy lines, emoji, or AI clichés.
- **Avoid everywhere**: bright colors, gradient overload, flash-sale/marketplace look, excessive badges, cluttered layouts, cartoon illustration.

---

## Design Language

- Palette: black, white, charcoal, grey. Neon-green `uf-accent` (#c7eb31) is the **single deliberate punctuation** — CTAs / key moments only, never smeared.
- Generous whitespace · strong typography · minimal/subtle borders · soft shadows · subtle rounded corners.
- Premium, smooth motion and micro-interactions; elegant hover/focus; respect `prefers-reduced-motion`.
- High-quality imagery, modern spacious layouts. Reference lane: Represent, Fear of God Essentials, COS, Uniqlo, Aimé Leon Dore, Arket.
- **Mobile-first**: flawless and touch-friendly on mobile, one-handed, no horizontal scroll. Desktop enhances, never redefines.

---

## SEO (primary goal)

Building **topical authority in premium dark fashion**. Keyword cluster (use semantically — never stuff):
dark aesthetic fashion · dark streetwear · monochrome fashion · oversized / premium oversized / black oversized t-shirts · heavyweight cotton t-shirts · minimal streetwear · premium cotton apparel · urban / oversized fashion India.

**Always preserve**: canonical URLs · clean URL structure · **no duplicate URLs** · breadcrumb / Product / Organization / FAQ schema · sitemap integrity · robots.txt · fast pages. (StructuredData helper is the single source of JSON-LD — keep core rich-snippets OFF; pages with own meta pass `:has-custom-seo="true"`.)

**No thin pages — page requirements:**
- *Product*: storytelling description, fabric, fit guidance, size chart, care, related + recently-viewed, Product schema, descriptive ALT, internal links.
- *Category/Collection*: unique intro + SEO description, buying guide, FAQ, breadcrumbs, collection imagery, internal links.
- *Homepage*: build trust, showcase identity, feature collections, crawlable SEO content without compromising design (e.g. the brand-story accordion).
- *Blog*: topical-authority articles (dark fashion, monochrome styling, oversized guides, fabric education, capsule wardrobes, care) each linking to relevant collections/products.

---

## Engineering Principles

- **Bagisto**: extend, don't modify core — keep changes upgrade-friendly, in custom packages/themes; document major architectural changes.
- Thin controllers; logic in Services/ViewModels. Reusable Blade components. Config over hardcoded values. No duplicate logic, no dead code, minimal dependencies.
- **Performance**: lazy-load, defer JS, code-split, optimized WebP/AVIF, font optimization, lean DOM. Avoid unnecessary JS libraries.
- **Accessibility (WCAG)**: keyboard nav, ARIA labels, sufficient contrast, descriptive ALT, visible focus, semantic HTML.
- **Current priorities** (align recommendations): platform + technical SEO → premium UI refinement → performance → advanced search → product personalization (POD) → inventory/purchase mgmt → blog/content expansion → AI-search readiness → launch.

---

## Auto-Verify After Every Task

- Changed `.php` (not blade/css/js/vue) → `php -l {file}`
- Always → `php artisan optimize:clear`
- Blade changed → `php artisan view:clear` — **never `view:cache`** (Windows rename lock → 500s)
- Routes/config changed → `php artisan route:list --path=shop` / `config:show app`
- Visible change → `npm run build`; user confirms in browser (no Playwright/screenshots)
- Test file exists for touched class → `vendor/bin/pest --filter={ClassName}`
- On failure: read the exact error, fix the offending file, re-run until clean
