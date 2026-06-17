# Go-Live Checklist — make GA4 stream, submit sitemap, read Clarity

Website side is confirmed working on production: GTM (`GTM-TK3MV6Q3`) loads,
`gtm.js` requests fire, the dataLayer pushes all 14 events. Everything below is
account-side (GA4 / GTM / Search Console / Clarity) — nothing in the code.

---

## A. Get GA4 to stream (fixes "no streaming")

GA4 receives nothing until the GTM container is **published** with a valid
Measurement ID. Do this in order:

1. **GA4 → Admin → Data streams → Web.**
   - If there's no Web stream for `urbanflaky.in`, create one. Copy its
     **Measurement ID** — it looks like `G-XXXXXXXXXX` (NOT the `GTM-…` id).
2. **GTM → Variables → `const - GA4 Measurement ID`** (if you imported
   `gtm-container.json`) → paste that `G-XXXXXXXXXX`. Save.
   - If you did NOT import the container: open your **Google Tag / GA4
     Configuration** tag and set the Tag ID to `G-XXXXXXXXXX`, trigger = All Pages.
3. **GTM → Preview** → enter `https://urbanflaky.in` → in the Tag Assistant panel
   confirm the **GA4 Configuration (Google Tag)** tag shows **Fired** on
   "Container Loaded", and your event tags fire as you click around.
4. **GTM → Submit → Publish.** ← the step most people miss. Preview alone does
   NOT send data to live GA4 reporting; you must Publish a version.
5. **GA4 → Reports → Realtime** (or **Admin → DebugView** while GTM Preview is on)
   → you should appear within ~30 seconds.

### "Still no streaming after publishing?" — check these
- Container was **Saved** but not **Published** (most common).
- Wrong/typo Measurement ID, or you used the `GTM-…` id instead of `G-…`.
- You're looking at standard reports (24–48 h delay) instead of **Realtime /
  DebugView**.
- Your own browser blocks GA: test in **Incognito with ad-blockers off**.
- You only ran **Preview** (debug hits) — those show in DebugView but standard
  reports need a published container + non-debug traffic.

### About events in GA4
Do **not** create events manually. They populate automatically once data flows.
`purchase` is auto-created and auto-marked as a Key Event. After data arrives,
just confirm **Admin → Key events** has `purchase` (and add `generate_lead`).

---

## B. Sitemap — already live, just submit it
- URL: `https://urbanflaky.in/sitemap.xml` (dynamic, auto-updates, 40 URLs).
- **Search Console → Sitemaps → enter `sitemap.xml` → Submit.** Use the path
  only, not the full URL. Status should go to "Success" within a day.
- Nothing to create — there is intentionally no static file (the controller
  serves it fresh so new products/categories/blogs appear automatically).

---

## C. Clarity heatmaps — working, just needs data
- Recordings working = install is correct.
- Heatmaps generate **after a page accumulates enough sessions** (≈ a day / a few
  hundred views) and are **per-URL** — open Heatmaps, pick a specific page that
  has traffic. New install + low traffic = empty for ~24–48 h. No action needed.
