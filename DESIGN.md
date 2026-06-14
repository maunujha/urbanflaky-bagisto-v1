---
name: Urbanflaky
description: Dark, neon-edged streetwear storefront — premium basics for ₹299-799
colors:
  uf-bg: "#0a0a0a"
  uf-surface: "#141414"
  uf-surface-2: "#1c1c1c"
  uf-border: "#262626"
  uf-muted: "#888888"
  uf-text: "#f5f5f5"
  uf-off-white: "#f0f0f0"
  uf-accent: "#c7eb31"
  uf-accent-hover: "#d4f04a"
  glow-violet: "#7c3aed"
  alert-pink: "#f85156"
  pure-black: "#000000"
  pure-white: "#ffffff"
typography:
  display:
    fontFamily: "Poppins, sans-serif"
    fontSize: "28px"
    fontWeight: 800
    lineHeight: 1
    letterSpacing: "4px"
  title:
    fontFamily: "Poppins, sans-serif"
    fontSize: "20px"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.01em"
  body:
    fontFamily: "Poppins, sans-serif"
    fontSize: "15px"
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: "0.01em"
  label:
    fontFamily: "Poppins, sans-serif"
    fontSize: "11px"
    fontWeight: 600
    lineHeight: 1
    letterSpacing: "2px"
rounded:
  none: "2px"
  sm: "4px"
  md: "6px"
  lg: "12px"
  xl: "16px"
  full: "50%"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  xl: "40px"
components:
  button-primary:
    backgroundColor: "{colors.uf-accent}"
    textColor: "{colors.uf-bg}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "13px 10px"
  button-primary-hover:
    backgroundColor: "{colors.uf-accent-hover}"
    textColor: "{colors.uf-bg}"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.pure-white}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "13px 10px"
  button-secondary-hover:
    backgroundColor: "{colors.pure-white}"
    textColor: "{colors.uf-bg}"
  chip:
    backgroundColor: "transparent"
    textColor: "{colors.uf-text}"
    rounded: "{rounded.sm}"
    height: "34px"
  chip-active:
    backgroundColor: "transparent"
    textColor: "{colors.uf-accent}"
    rounded: "{rounded.sm}"
  card-product:
    backgroundColor: "{colors.uf-bg}"
    textColor: "{colors.uf-text}"
    rounded: "{rounded.md}"
  icon-button:
    backgroundColor: "{colors.uf-surface}"
    textColor: "{colors.uf-text}"
    rounded: "{rounded.full}"
    width: "34px"
    height: "34px"
---

# Design System: Urbanflaky

## 1. Overview

**Creative North Star: "The Night Drop"**

Urbanflaky is the limited-drop on a dark street corner: a near-black canvas (#0a0a0a) lit by one signal — the neon yellow-green accent (#c7eb31) — and a slow-shifting gradient of midnight violet and ember undertones drifting behind everything, like sodium lights bleeding into night sky. The brand sells affordable polo shirts and slim-fit casuals at ₹299-799, but the interface never reads as a budget marketplace; it reads as a drop you'd queue for. Every surface is dark by default. The accent is rare and load-bearing — it marks the thing you're meant to act on (Add to Cart, an active filter, a sale badge, a focused input) and nothing else.

This system explicitly rejects the cream/sand "AI default" aesthetic, dense Myntra/Ajio-style marketplace clutter, gradient-text sale badges, and sterile corporate minimalism. It also rejects loud chaos — the boldness comes from contrast and restraint (one accent, near-black everywhere else), not from piling on color or effects.

**Key Characteristics:**
- Near-black base (#0a0a0a / #141414 / #1c1c1c) with a single neon accent (#c7eb31) used sparingly and deliberately
- Poppins carries the entire type system — weight and uppercase tracking build hierarchy, not font-switching
- Sharp, confident geometry: 2px CTAs, 4-6px cards/inputs, full circles for icon buttons — no in-between radii
- An animated, slow-drifting dark gradient + ambient violet/lime glow behind the whole page, signalling "always alive" without distracting
- Hover states glow (accent-tinted shadows, lift) rather than just darken — interaction feels like switching something on

## 2. Colors

The palette is night-and-signal: everything is a shade of near-black except one neon accent that does all the talking.

### Primary
- **Signal Lime** (#c7eb31): the single accent. Used on primary CTAs (Add to Cart, View All), active states (selected size/color, focused inputs, active tab), sale badges, price emphasis, list-marker bullets in rich text, and the ambient background glow. On hover/press it shifts to **Signal Lime Hover** (#d4f04a).

### Secondary (optional)
- **Glow Violet** (rgba(124, 58, 237, ...)): appears only in the ambient background glow-drift, mixed with the lime glow via `mix-blend-mode: screen`. Never used on UI elements directly — it's atmosphere, not a UI color.
- **Alert Pink** (#f85156): reserved for inline variant/validation errors (e.g. "select a size" warning). The only non-lime "you must act" signal.

### Neutral
- **Void Black** (#0a0a0a — `--uf-bg`): page background, product card shells, badge text on lime.
- **Charcoal Surface** (#141414 — `--uf-surface`): one level up from void — section backgrounds, tab bars.
- **Graphite Surface** (#1c1c1c — `--uf-surface-2`): inputs, hover fills, recently-viewed card media placeholders.
- **Hairline Border** (#262626 — `--uf-border`): dividers, scrollbar track edges — barely visible, structural only.
- **Smoke Text** (#888888 — `--uf-muted`): secondary text, strike-through prices, subtitles.
- **Paper Text** (#f5f5f5 — `--uf-text`): primary text on dark — headings, product names, body copy.
- **Off-White** (#f0f0f0 — `--uf-off-white`): outline-button borders/text (View All, Load More) — a softer white than pure #fff for secondary CTAs.

### Named Rules
**The One Signal Rule.** Signal Lime (#c7eb31) is the only accent color in the UI. If something needs to stand out — a CTA, an active filter, a price, a focus ring — it gets lime or it doesn't stand out. Never introduce a second "brand color" for variety; variety comes from typography scale and spacing, not a second hue.

**The Atmosphere-Isn't-UI Rule.** The violet glow and gradient drift live only in `body::after` and background gradients. They never appear as a button, badge, or text color — atmosphere stays behind the content, never on it.

## 3. Typography

**Display Font:** Poppins (with system sans-serif fallback)
**Body Font:** Poppins (with system sans-serif fallback)
**Label/Mono Font:** Poppins (uppercase, wide letter-spacing — the same family, a different mode)

**Character:** One geometric sans carrying the whole system. Hierarchy comes from weight (400 → 800) and letter-spacing (normal → 6px tracked-out uppercase for headings/labels), not from mixing typefaces. The effect is consistent and confident — every screen feels like the same voice at a different volume.

### Hierarchy
- **Display** (800, 28-32px desktop / 17px mobile, line-height 1, letter-spacing 4-6px, uppercase): category names, product-strip section titles. The widest tracking in the system — this is the "shout" register, used once per section.
- **Title** (600-700, 18-20px, line-height 1.2): product names on hover, drawer headings, modal titles.
- **Body** (400, 14-15px, line-height 1.45-1.7): product card names, RTE/CMS copy (`.uf-rte`), descriptions. Cap prose at 65-75ch where it runs long-form (blog, CMS pages).
- **Label** (500-700, 10-12px, letter-spacing 1.5-3px, uppercase): button text, badges, size/variant pills, "Explore Now" CTAs, delivery-strip items. The workhorse for all interactive micro-copy.

### Named Rules
**The Tracking-Carries-Weight Rule.** Letter-spacing scales with importance: body text is near-normal, labels get 1.5-3px, and display headings get 4-6px. A heading that isn't tracked out reads as a mistake, not as restraint.

## 4. Elevation

Urbanflaky is a hybrid: flat dark surfaces at rest, with depth conveyed through two mechanisms — soft ambient shadows for static layering, and **accent-glow shadows** as the signature hover response. There is no light-source-based shadow system; shadows are either neutral (separation) or lime-tinted (interaction feedback).

### Shadow Vocabulary
- **Card Rest** (`box-shadow: 0 8px 22px rgba(0,0,0,0.45), 0 1px 2px rgba(0,0,0,0.4), inset 0 0 0 1px rgba(255,255,255,0.04)`): default product card separation from the animated background — a faint inner hairline plus a soft drop shadow.
- **Card Hover Glow** (`box-shadow: 0 18px 40px rgba(0,0,0,0.65), 0 2px 6px rgba(0,0,0,0.4), inset 0 0 0 1px rgba(199,235,49,0.25)`): on hover, the card lifts (`translateY(-4px)`) and its inner hairline turns lime — the card feels "switched on".
- **CTA Glow** (`box-shadow: 0 8px 24px rgba(199,235,49,0.25)`): outline buttons (View All, Load More) on hover, paired with a fill-to-lime background change and a 2px lift.
- **Glass Overlay** (`backdrop-filter: blur(8-24px)`): header, modals, bottom sheets, search panels — translucent dark surfaces over the animated background.

### Named Rules
**The Glow-On-Touch Rule.** Hover/focus elevation always recruits Signal Lime — either as an inset ring, an outer glow, or a fill change. Neutral shadows only ever deepen (more black, more blur); they never get bigger as a hover effect. If a hover state needs to communicate "interactive", reach for lime glow before reaching for a bigger neutral shadow.

## 5. Components

Sharp and confident: hard 2px edges on primary CTAs, small 4-6px radii on cards/inputs, and true circles for every icon button. Nothing in between — the radius scale is deliberately bimodal (near-flat or full-round), which keeps the UI feeling tight and fast rather than soft.

### Buttons
- **Shape:** Primary/secondary CTAs use a near-flat 2px radius (`--rounded-none` in the token scale). Icon buttons are full circles (50%). Outline CTAs (View All, Load More) use 6px.
- **Primary** (`.uf-btn-atc`): background Signal Lime, text Void Black, Poppins 600 12px uppercase, letter-spacing 0.08em, padding `13px 10px`, 1px lime border.
- **Hover/Focus:** background shifts to Signal Lime Hover (#d4f04a), lifts `translateY(-1px)`. Disabled drops to 0.4 opacity.
- **Secondary** (`.uf-btn-buy`): transparent background, 1px white/50% border, white text. Hover inverts to solid white background with Void Black text.
- **Outline CTA** (View All / Load More): transparent, 1.5px off-white (#f0f0f0) border, 6px radius, uppercase 12px/3px tracking. Hover fills Signal Lime with the CTA Glow shadow and lifts 2px.

### Chips / Pills
- **Style** (`.uf-size-pill`): transparent background, 1px rgba(255,255,255,0.2) border, 4px radius, 42px min-width × 34px height (52×44 inside mobile bottom sheets for thumb targets).
- **State:** unselected = white text/border; hover and active both switch border + text to Signal Lime (active additionally forces transparent background via `!important` to prevent fill).
- **Color swatches** (`.uf-color-dot`): 22px circles, 1.5px white/55% border; active state scales 1.15x and gains a lime outer ring (`box-shadow: 0 0 0 2px rgba(199,235,49,0.35)`).

### Cards / Containers
- **Corner Style:** 6px radius on product cards; 12px on category cards, recently-viewed cards, and the tab bar; 16px (top corners only) on the mobile bottom sheet.
- **Background:** Void Black (#0a0a0a) for product card shell and content area; the 3:4 image wrap uses a light #efefef background to match product photography.
- **Shadow Strategy:** Card Rest at idle, Card Hover Glow on hover (desktop ≥1180px only) — see Elevation.
- **Border:** none on product cards (shadow does the separation); 1px rgba(255,255,255,0.08) hairlines on recently-viewed cards and the tab bar.
- **Internal Padding:** 16px (12px on mobile) for card content; 16px for hover-drawer bodies.

### Inputs / Fields
- **Style:** transparent or rgba(255,255,255,0.05) background, 1px rgba(255,255,255,0.12) border, 6px radius, Poppins 14px.
- **Focus:** border shifts to Signal Lime with a soft lime focus ring (`box-shadow: 0 0 0 3px rgba(199,235,49,0.15)`) — same glow-on-touch language as buttons and cards.
- **Placeholder:** muted gray (#71717a) — verify this meets 4.5:1 against the input background before reuse on light surfaces.

### Navigation
- Sticky header (`#uf-header`) is a translucent dark glass bar (`rgba(21,21,28,0.6)` → `0.85` on scroll, 24px backdrop blur), auto-hiding on scroll-down and revealing on scroll-up. Mobile header (`.mh-root`, ≤1023px) is a 56px bar with circular 40px icon buttons that glow lime on hover/active.
- Mega-menus and search panels are near-opaque dark glass (`rgba(18,18,24,0.95-0.98)`, 14px blur) sliding in with cubic-bezier easing.
- Active/selected nav and tab states get the lime underline-glow treatment (`inset 0 -24px 28px -24px rgba(199,235,49,0.35)` for the active tab).

### Hero Carousel & Category Cards (signature components)
- Hero slides sit on a light (#f5f5f5) background to match photography, with white circular arrow buttons (desktop hover only) and a segmented progress-bar indicator at the bottom (white fill, lime-free — the hero is the one place lime steps back to let imagery lead).
- Category cards use a dark gradient fallback (`linear-gradient(135deg, #1c1c1c, #0a0a0a)`), bottom-aligned overlay text with heavy letter-spacing, and a lime underline that expands on hover under the "Explore Now" label.

## 6. Do's and Don'ts

### Do:
- **Do** keep Signal Lime (#c7eb31) to CTAs, active/selected states, prices, and focus rings — its rarity is what makes it read as a signal.
- **Do** use Poppins weight + uppercase letter-spacing (1.5-6px depending on hierarchy level) to build typographic hierarchy instead of introducing a second typeface.
- **Do** pair every hover/focus state with a lime-tinted glow or ring (shadow, border, or fill) — this is the system's "alive" signal.
- **Do** keep radii bimodal: near-flat (2px) for primary CTAs and outline buttons at 6px, full circles for icon buttons, 4-6px for cards/inputs/pills. Avoid in-between radii like 8-10px.
- **Do** respect `prefers-reduced-motion`: the background gradient drift, glow drift, and card-reveal animations all have `animation: none` fallbacks — every new animated component needs the same.
- **Do** verify body text and placeholders hit ≥4.5:1 against #0a0a0a/#141414/#1c1c1c — `--uf-muted` (#888) passes on the darkest surfaces but check on lighter ones (#1c1c1c).

### Don't:
- **Don't** introduce a second accent color "for variety" — this is the cream/sand AI-default failure mode in reverse; one signal color is the brand.
- **Don't** use gradient text, side-stripe borders as decorative accents (the lime left-border on `.uf-prod-title` is a deliberate exception — a structural marker, not decoration, and it's the only one), or glassmorphism outside the established header/modal/sheet surfaces.
- **Don't** add tiny uppercase tracked "eyebrows" above sections or numbered (01/02/03) section markers — neither pattern exists in this system and both read as generic AI scaffolding.
- **Don't** make the violet ambient glow (rgba(124,58,237,...)) appear on any interactive element — it's background atmosphere only, never a UI color.
- **Don't** default to cream/sand/warm-neutral surfaces anywhere, including modals or empty states — everything stays in the near-black `--uf-bg`/`--uf-surface`/`--uf-surface-2` family.
- **Don't** use bounce/elastic easing — all transitions use `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out-quint-like) or simple `ease`/`ease-in-out`.
