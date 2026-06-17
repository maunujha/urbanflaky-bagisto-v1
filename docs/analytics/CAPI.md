# Phase 6 — Meta Conversions API (CAPI) Architecture

> Documentation only — no server-side code is deployed yet. This describes how to
> add server-side Purchase (and other) events later, deduplicated against the
> browser Pixel.

## Why
iOS/ITP, ad-blockers and consent loss cause the browser Pixel to under-report by
20–40%. CAPI sends the same conversions **server-to-server**, recovering signal
and improving Meta's optimisation.

## Deduplication strategy (the key design decision)
Browser and server send the **same event** with the **same `event_id`**; Meta
keeps one. We standardise on:

| Event | `event_id` source | Already in place? |
|-------|-------------------|-------------------|
| Purchase | `order.increment_id` (= `ecommerce.transaction_id`) | ✅ pushed to data layer; Pixel tag already sets `eventID` (see GTM-SETUP §4) |
| InitiateCheckout | `cart_id` + step | data layer carries cart; add a stable id |
| Lead | session id + form | — |

Because the browser Pixel `Purchase` already sends `eventID =
{{dlv - transaction_id}}`, the server side only has to send the **same string**
as `event_id` and dedup works automatically.

## Server-side event payload (Purchase example)
Best fired from an order-placed listener (Bagisto `checkout.order.placed` /
`sales.order.save.after`) dispatched to a queue:

```
POST https://graph.facebook.com/v19.0/<PIXEL_ID>/events?access_token=<CAPI_TOKEN>
{
  "data": [{
    "event_name": "Purchase",
    "event_time": <unix>,
    "event_id": "<order.increment_id>",          // <-- dedup key, matches Pixel eventID
    "event_source_url": "<success url>",
    "action_source": "website",
    "user_data": {                                // all SHA-256 hashed
      "em": ["<sha256(lowercase email)>"],
      "ph": ["<sha256(e164 phone)>"],
      "fn": ["<sha256(first_name)>"],
      "client_ip_address": "<ip>",
      "client_user_agent": "<ua>",
      "fbp": "<_fbp cookie>",                     // pass through from browser
      "fbc": "<_fbc cookie>"
    },
    "custom_data": {
      "currency": "INR", "value": <grand_total>,
      "content_type": "product",
      "content_ids": ["<sku>", ...],
      "contents": [{ "id": "<sku>", "quantity": <q>, "item_price": <p> }],
      "num_items": <count>
    }
  }]
}
```

## Implementation plan (when approved)
1. Add `META_CAPI_TOKEN` (+ reuse Pixel ID) to `config/services.php` (env-driven).
2. Reuse `App\Support\DataLayer::purchase($order)` to build `custom_data` (the
   item shape already matches — just map `item_id`→`content_ids`).
3. Capture `_fbp` / `_fbc` cookies + IP + UA at order time (store on the order or
   pass to the job) so the queued listener can hash and forward them.
4. Queued listener on order placement → POST to the Graph API; log failures only.
5. Verify in **Meta Events Manager → your Pixel → Test Events** and confirm the
   "Deduplicated" badge on Purchase.
6. (Optional) Move to **GA4 Measurement Protocol** for server-side GA4 with the
   same pattern.

## Privacy
Hash all PII (email/phone/name) with SHA-256 before sending. Honour the
storefront consent state (`targeting_advertising` category) — skip CAPI for users
who declined ad targeting once Consent Mode is wired.
