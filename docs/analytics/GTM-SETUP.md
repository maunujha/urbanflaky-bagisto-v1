# GTM Configuration — Urbanflaky (container `GTM-TK3MV6Q3`)

Everything below is configured **inside the GTM web UI**. The site already pushes
the data layer (see [EVENTS.md](EVENTS.md)). Fastest path: import
[`gtm-container.json`](gtm-container.json) (Admin → Import Container → *Merge* →
*Rename conflicting*), then fill in the two IDs in the **Constant** variables and
**Publish**.

> You only need to set two values: `GA4 Measurement ID` (`G-XXXXXXX`) and
> `Meta Pixel ID`. Both are GTM **Constant** variables — nothing in site code.

---

## 1. Variables

### Built-in (enable these)
`Page URL`, `Page Path`, `Page Hostname`, `Referrer`, `Event`.

### Constants
| Name | Value |
|------|-------|
| `const - GA4 Measurement ID` | `G-XXXXXXX` ← your GA4 web stream |
| `const - Meta Pixel ID` | `XXXXXXXXXXXXXXX` ← your Pixel |

### Data Layer Variables (Version 2)
| Name | Data Layer Variable Name |
|------|--------------------------|
| `dlv - ecommerce` | `ecommerce` |
| `dlv - value` | `ecommerce.value` |
| `dlv - currency` | `ecommerce.currency` |
| `dlv - items` | `ecommerce.items` |
| `dlv - transaction_id` | `ecommerce.transaction_id` |
| `dlv - tax` | `ecommerce.tax` |
| `dlv - shipping` | `ecommerce.shipping` |
| `dlv - coupon` | `ecommerce.coupon` |
| `dlv - search_term` | `search_term` |
| `dlv - results_count` | `results_count` |
| `dlv - page_type` | `page_type` |
| `dlv - subscription_source` | `subscription_source` |
| `dlv - content_ids` (Meta) | `ecommerce.items` (transformed in the tag — see §4) |

---

## 2. Triggers

One **Custom Event** trigger per data-layer event (Event name = exact match):

| Trigger name | Event name (regex off) |
|--------------|------------------------|
| `ce - view_item` | `view_item` |
| `ce - view_item_list` | `view_item_list` |
| `ce - search` | `search` |
| `ce - add_to_cart` | `add_to_cart` |
| `ce - remove_from_cart` | `remove_from_cart` |
| `ce - begin_checkout` | `begin_checkout` |
| `ce - add_shipping_info` | `add_shipping_info` |
| `ce - add_payment_info` | `add_payment_info` |
| `ce - purchase` | `purchase` |
| `ce - newsletter_signup` | `newsletter_signup` |
| `ce - contact_submit` | `contact_submit` |
| `ce - login` | `login` |
| `ce - sign_up` | `sign_up` |

Plus the built-in **Initialization - All Pages** (for Consent defaults, if used)
and **All Pages** (for the GA4 Config + Meta base tags).

---

## 3. GA4 tags

### GA4 Configuration (tag type: *Google Tag*)
- Tag ID: `{{const - GA4 Measurement ID}}`
- Trigger: **All Pages**
- Fields to set: `send_page_view` = `true` (default). Optionally map a user
  property / custom dimension `page_type` = `{{dlv - page_type}}`.

### GA4 Event tags (tag type: *GA4 Event*)
For each ecommerce event create one GA4 Event tag, **Configuration tag** = the
Google Tag above, **Event Name** = the GA4 name, and turn ON
**"Send Ecommerce data" → Data source: Data Layer**. Trigger = matching `ce - *`.

| GA4 Event Name | Trigger | Ecommerce data |
|----------------|---------|----------------|
| `view_item` | `ce - view_item` | Data Layer |
| `view_item_list` | `ce - view_item_list` | Data Layer |
| `search` | `ce - search` | — (param `search_term` = `{{dlv - search_term}}`) |
| `add_to_cart` | `ce - add_to_cart` | Data Layer |
| `remove_from_cart` | `ce - remove_from_cart` | Data Layer |
| `begin_checkout` | `ce - begin_checkout` | Data Layer |
| `add_shipping_info` | `ce - add_shipping_info` | Data Layer |
| `add_payment_info` | `ce - add_payment_info` | Data Layer |
| `purchase` | `ce - purchase` | Data Layer |
| `generate_lead` | `ce - newsletter_signup` **or** `ce - contact_submit` | param `lead_source` |
| `login` | `ce - login` | — |
| `sign_up` | `ce - sign_up` | — |

Mark `purchase` + `generate_lead` as **Key Events** (Conversions) in GA4 Admin.

---

## 4. Meta Pixel tags (via GTM)

### Base code (tag type: *Custom HTML*, trigger: All Pages)
```html
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','{{const - Meta Pixel ID}}'); fbq('track','PageView');
</script>
```
Set **Tag firing priority** high and **once per page**.

### Event tags (Custom HTML, one per event)
Map data-layer → Meta standard events. Example **AddToCart**
(trigger `ce - add_to_cart`):
```html
<script>
(function(){
  var ec = {{dlv - ecommerce}} || {};
  var ids = (ec.items||[]).map(function(i){return i.item_id;});
  fbq('track','AddToCart',{
    content_type:'product', content_ids:ids,
    contents:(ec.items||[]).map(function(i){return {id:i.item_id,quantity:i.quantity};}),
    value: ec.value, currency: ec.currency
  });
})();
</script>
```

| Meta event | Trigger | Notes |
|-----------|---------|-------|
| `PageView` | All Pages | base code |
| `ViewContent` | `ce - view_item` | content_ids, value, currency |
| `Search` | `ce - search` | `search_string` = `{{dlv - search_term}}` |
| `AddToCart` | `ce - add_to_cart` | as above |
| `InitiateCheckout` | `ce - begin_checkout` | num_items + content_ids |
| `AddPaymentInfo` | `ce - add_payment_info` | value, currency |
| `Purchase` | `ce - purchase` | **must** send value + currency + content_ids; `eventID` = `{{dlv - transaction_id}}` for future CAPI dedup |
| `Lead` | `ce - contact_submit` | — |
| `Subscribe` / `CompleteRegistration` | `ce - newsletter_signup` / `ce - sign_up` | — |

> For Purchase, set the Meta `eventID` to `{{dlv - transaction_id}}` now so
> server-side CAPI (see [CAPI.md](CAPI.md)) can deduplicate later.

---

## 5. Consent Mode v2 (recommended, not yet wired)
The storefront already has a GDPR consent UI with `measurements` &
`targeting_advertising` categories. To wire Google Consent Mode v2: add a
**Consent Initialization** template that defaults `ad_storage` /
`analytics_storage` to `denied`, then `update` to `granted` from the cookie the
consent page sets. Left as a documented next step — do **not** ship a `denied`
default without the update wiring or all tags will silently stop firing.
