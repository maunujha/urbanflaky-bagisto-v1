# Shiprocket shipping

How an order travels from checkout to the customer's door, and what to set
up so it does. Code lives in `app/Services/Shiprocket`, wired by
`App\Providers\ShiprocketServiceProvider`.

## The flow

| Step | What happens | Where |
|---|---|---|
| Product page | Pincode check: deliverable?, date, COD, free-delivery promise | `DeliveryCheckController` → `ServiceabilityService`, `DeliveryRules` |
| Checkout: address | No courier serves the pincode → no delivery option → customer is told and stays on the address step | `App\Shipping\Carriers\{Free,FlatRate}` |
| Checkout: delivery | Exactly one option: Free Shipping when it applies, otherwise Flat Rate | same |
| Checkout: payment | COD hidden above the max order value or where couriers can't collect cash | `App\Payment\CashOnDelivery` |
| Order saved | Queued push. Prepaid orders only exist after Razorpay capture, so both COD and prepaid push at this moment | `PushOrderToShiprocket` |
| Push | Create Shiprocket order (once, locked, adopts an existing one on retry), then assign the recommended courier (AWB) | `ShipmentService::push()` |
| Pickup | Booked from the admin order page, or automatically if enabled | `ShipmentService::schedulePickup()` |
| Courier scans | Webhook → status history + stage. Stage only moves forward | `ShiprocketWebhookController` → `TrackingService` |
| Picked up | Bagisto shipment created (carrier + AWB) → "order shipped" email with a tracking link, order → Shipped | `OrderFulfilmentSync` |
| Delivered | COD orders invoiced, order → Completed, `shiprocket.order.delivered` fired (RewardCoins return window opens) | same |
| Cancelled in admin | Cancelled in Shiprocket too, while the parcel is still at the warehouse | `CancelShiprocketOrder` |
| Every 30 min | Push missed orders, assign couriers that failed, poll tracking for quiet shipments | `php artisan shiprocket:sync` |

Shipment stages (`shiprocket_orders.status`): `new` → `awb_assigned` →
`pickup_scheduled` → `picked_up` → `in_transit` → `out_for_delivery` →
`delivered`, plus `undelivered` (NDR), `rto_initiated`, `rto_delivered`,
`canceled`, `lost`, `push_failed`. The courier's own wording is kept in
`current_status`; every scan is in `shiprocket_tracking_events`.

## One-time setup

### In Shiprocket
1. **API user**: Settings → API → Configure. Use a separate email from the
   main login. Credentials go in `.env` (`SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`).
2. **Courier priority**: Settings → Courier Priority → **Recommended by
   Shiprocket** (balances cost, speed and delivery performance). This is what
   automatic courier assignment follows.
3. **Couriers**: check which couriers are enabled. Delhi/Mumbai currently
   return a single courier (Blue Dart Air), which is expensive.
4. **Webhook**: Settings → API → Webhooks.
   - URL: `https://urbanflaky.in/webhooks/tracking` (Shiprocket rejects URLs
     containing "shiprocket").
   - Token (x-api-key): the value of `SHIPROCKET_WEBHOOK_TOKEN` in `.env`.
   - Use "Test" — the endpoint answers 200.

### In Bagisto admin
- **Configure → Sales → Shipping Methods**
  - Free Shipping: on. *Minimum Order Amount* blank = free on every order;
    e.g. `999` = free from ₹999.
  - Flat Rate: the fee for orders below the minimum (shown only when free
    shipping does not apply). Set the type to *Per Order* for one fee per order.
- **Configure → Sales → Payment Methods → Cash on Delivery**
  - *Maximum Order Value for COD*: `2000` (blank = no limit).
  - *Check COD Availability by Pincode*: on.
- **Configure → Sales → Shiprocket**: automation switches, pickup location
  name and pincode (must match Shiprocket), parcel size, default weight, HSN.
- **Settings → Roles**: grant *Manage Shiprocket Shipments* (under Sales →
  Orders) to staff who fulfil orders.

Settings are cached in pages: after changing shipping settings in production,
clear the response cache (`php artisan responsecache:clear`).

## Admin order page

The Shiprocket panel shows stage, courier, AWB, ETA, the last API error and
the scan history, with actions: push/retry, assign recommended courier,
choose/change courier (with live rates, before pickup), book pickup, label,
manifest, Shiprocket invoice, refresh tracking, cancel shipment.

## Customer side

- `/track-order`: order number + email or phone, or an AWB this store
  issued. Answers from our own history, refreshed from the courier at most
  every 15 minutes. `?awb=` tracks immediately; `?order=` prefills.
- Account → order: shipment card with stage, courier, AWB, ETA and a link to
  the tracking page.
- "Order shipped" email: Track your order button.

## Operations

```
php artisan shiprocket:sync --dry-run          # what the scheduler would do
php artisan shiprocket:sync --order=1042       # one order
```

Logs: `storage/logs/laravel-*.log`, search for `Shiprocket`. Failed actions
are also stored on the order (`shiprocket_orders.last_error`) and shown in the
admin panel.

Tests: `vendor/bin/pest tests/Feature/Shiprocket` (Shiprocket API is faked;
`phpunit.xml` blanks the credentials so no test can reach the live account).

## Go-live test plan

Shiprocket has no sandbox. Use real low-value orders and cancel them before
pickup.

- [ ] Webhook "Test" from Shiprocket returns 200
- [ ] COD order: appears in Shiprocket with the right COD amount and an AWB
- [ ] Prepaid order: pushed only after payment; marked Prepaid
- [ ] Pincode with no courier (e.g. 999999): checkout stops at the address step
- [ ] COD order above ₹2,000: COD not offered
- [ ] Change courier from the admin panel; download label and manifest
- [ ] Book pickup; cancel shipment before pickup
- [ ] Cancel an order in Bagisto: cancelled in Shiprocket
- [ ] After a real pickup: Bagisto shipment + shipped email; tracking page
      shows scans; delivered → order Completed (COD invoiced)

## Not built yet

NDR reattempt/address update, RTO restock and refund, returns and size
exchanges via reverse pickup, COD remittance reconciliation, bulk actions,
shipment column in the admin orders grid, stuck-order alerts.
