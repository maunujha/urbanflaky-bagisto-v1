# Urbanflaky — Data Layer Event Specification

GTM-first architecture. The site pushes a clean, GA4-shaped `window.dataLayer`;
GA4 and Meta Pixel tags fire **inside GTM** off these events. Microsoft Clarity
loads as a direct script (not via GTM).

- **dataLayer init + GTM snippet:** `components/layouts/tracking/head.blade.php` (as high in `<head>` as possible)
- **PHP item/purchase mapper:** `app/Support/DataLayer.php`
- **JS helper (client-side events):** `window.ufTrack` (defined in the tracking head partial)
- **Ecommerce convention:** every ecommerce push is preceded by `dataLayer.push({ ecommerce: null })` to clear the previous object (GA4 best practice).
- **item_id** = product **SKU** (must match the Google Merchant Center feed id). **item_brand** = `Urbanflaky`. **currency** = `INR`.
- **purchase `value`** = order grand total (what the customer paid); `tax` & `shipping` reported separately.

---

## Event catalogue

| # | Event | Fires when | Where (file) | Render |
|---|-------|-----------|--------------|--------|
| 1 | `page_view`* | every page | tracking/head.blade.php | server |
| 2 | `view_item` | PDP load | products/view.blade.php | server |
| 3 | `view_item_list` | category/search results load | categories/view.blade.php, search/index.blade.php | client (Vue) |
| 4 | `search` | search results load | search/index.blade.php | client (Vue) |
| 5 | `add_to_cart` | confirmed cart add | products/view.blade.php, components/products/card.blade.php | client (Vue) |
| 6 | `remove_from_cart` | cart line removed | checkout/cart/index.blade.php | client (Vue) |
| 7 | `begin_checkout` | checkout cart loads | checkout/onepage/index.blade.php | client (Vue) |
| 8 | `add_shipping_info` | shipping method saved | checkout/onepage/index.blade.php | client (Vue) |
| 9 | `add_payment_info` | payment method saved | checkout/onepage/index.blade.php | client (Vue) |
| 10 | `purchase` | order success page | checkout/success.blade.php | server |
| 11 | `newsletter_signup` | footer subscribe success | layouts/footer/index.blade.php | client (fetch) |
| 12 | `contact_submit` | contact form success | HomeController@sendContactUsMail → session flash | server |
| 13 | `login` | any login (password/OTP/Google) | AppServiceProvider (`customer.after.login`) | server flash |
| 14 | `sign_up` | registration | AppServiceProvider (`customer.registration.after`) | server flash |

\* `page_view` is also fired natively by the GA4 Configuration tag in GTM; the
data layer additionally carries `page_type` + `page_location` as dimensions.

---

## Payload reference

Every page first pushes a base object:
```js
{ page_type: 'home|catalog|product|category|cart|checkout|purchase|search|account|other', page_location: '<url>' }
```

### view_item
```js
dataLayer.push({ ecommerce: null });
dataLayer.push({
  event: 'view_item',
  page_type: 'product',
  ecommerce: {
    currency: 'INR',
    value: 299.5,
    items: [{ item_id: 'sku-4aa22a', item_name: 'Slim Fit Polo…', item_brand: 'Urbanflaky', item_category: 'Mens', price: 299.5, quantity: 1 }]
  }
});
```

### view_item_list
```js
{ event: 'view_item_list', ecommerce: { item_list_name: 'Mens' | 'Search Results',
  items: [{ item_id, item_name, item_brand, price, quantity, index, item_list_name }] } }
```

### search
```js
{ event: 'search', search_term: 'shirt', results_count: 24 }
```

### add_to_cart
```js
{ event: 'add_to_cart', ecommerce: { currency: 'INR', value: 599,
  items: [{ item_id, item_name, item_brand, item_category?, item_variant?, price, quantity }] } }
```
`item_variant` = selected colour/size labels joined by ` / ` when present.

### remove_from_cart
```js
{ event: 'remove_from_cart', ecommerce: { currency: 'INR', value, items: [{ item_id, item_name, item_brand, item_variant?, price, quantity }] } }
```

### begin_checkout / add_shipping_info / add_payment_info
```js
{ event: 'begin_checkout',     ecommerce: { currency, value, coupon?, items: [...] } }
{ event: 'add_shipping_info',  ecommerce: { currency, value, shipping_tier: 'Free Shipping', items: [...] } }
{ event: 'add_payment_info',   ecommerce: { currency, value, payment_type:  'Cash On Delivery', items: [...] } }
```

### purchase
```js
{ event: 'purchase', page_type: 'purchase', ecommerce: {
  transaction_id: '1000000123', order_id: 123,
  value: 1198, tax: 57, shipping: 0, currency: 'INR',
  coupon?: 'WELCOME10', discount: 100, payment_method: 'Razorpay',
  items: [{ item_id, item_name, item_brand, item_variant?, price, quantity, index }]
} }
```
Re-fires on refresh; GA4 dedupes on `transaction_id`, so revenue is never doubled.

### newsletter_signup / contact_submit / login / sign_up
```js
{ event: 'newsletter_signup', subscription_source: 'footer' }
{ event: 'contact_submit',    form_location: 'contact_page' }
{ event: 'login' }
{ event: 'sign_up' }
```

---

## Known limitations / future enhancements
- `login` / `sign_up` carry no `method` dimension yet (password vs OTP vs Google). Add per-controller flashes if you need method attribution.
- `view_item_list` re-fires on filter change and Load-More (each loaded page) — intended, GA4 allows it.
- Home-page product strips do not yet emit `view_item_list` (add if/when those become a key merchandising surface).
- `item_id` = SKU. If your Merchant Center / Meta catalog feed keys on product id instead, change `DataLayer::productItem()` and `ufTrack.item()` accordingly.
