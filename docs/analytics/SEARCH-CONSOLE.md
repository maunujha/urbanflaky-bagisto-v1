# Phase 8 — Google Search Console & Technical SEO

## Audit result (code already in place ✅)

| Item | Status | Evidence |
|------|--------|----------|
| `robots.txt` | ✅ | `public/robots.txt` — disallows `/admin /checkout /cart /customer /search /compare /track-order /razorpay /api`; allows `/`; declares `Sitemap: https://urbanflaky.in/sitemap.xml` |
| `sitemap.xml` | ✅ dynamic | `app/Http/Controllers/SitemapController.php` — products, categories, CMS pages, blog posts; `lastmod` + `priority`; cached 1h; served at `/sitemap.xml` |
| Canonical tags | ✅ | layout `index.blade.php` (`<link rel="canonical">`), per-page on PDP/category/CMS (`StructuredData` SEO architecture) |
| Structured data | ✅ | `Webkul\Shop\Helpers\StructuredData` (single source — Product, Category, Organization JSON-LD) |
| Meta robots | ✅ | non-indexable pages (cart, checkout, success, search) set `robots="noindex, nofollow"` |
| Open Graph / Twitter | ✅ | layout + PDP/category |

> Note: `robots.txt` + the sitemap point at the **production** domain
> `urbanflaky.in`. On the dev/staging host they resolve to `urbanflaky.test`
> via `url()`. Nothing to change for go-live.

## Search Console setup checklist (do at/after go-live)
1. Add property **`https://urbanflaky.in`** (Domain property preferred → DNS TXT
   verification; or URL-prefix → use the GTM/GA4 verification once live).
2. Verify ownership (DNS TXT is most robust; GA4/GTM verification also works since
   GTM is installed).
3. **Settings → Crawl → robots.txt** — confirm fetch succeeds.
4. **Sitemaps → Add new sitemap** → `sitemap.xml` → Submit. Confirm "Success" and
   discovered URL count matches catalog size.
5. **URL Inspection** on the homepage + one PDP + one category → "Test live URL" →
   confirm *Indexable* and rich-result eligibility (Product snippet).
6. **Page indexing** report — watch for "Discovered – not indexed" / "Crawled –
   not indexed"; expected to be clean given canonical + sitemap hygiene.
7. Link **GA4 ↔ Search Console** (GA4 Admin → Product links → Search Console) for
   organic query reporting inside GA4.
8. Bing Webmaster Tools — import from GSC (one click) for Bing/Edge coverage.

## Pre-launch verification commands (run on the live host)
```
curl -s https://urbanflaky.in/robots.txt
curl -s https://urbanflaky.in/sitemap.xml | head -40
curl -s https://urbanflaky.in/ | grep -o '<link rel="canonical"[^>]*>'
```
Then validate one PDP in Google's **Rich Results Test** for the Product schema.
