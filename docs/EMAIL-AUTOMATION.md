# Email automation

Package: `packages/Gabha/Notifications` (`Gabha\Notifications`). Every automated
email goes through `Services\EmailDispatcher`, which logs it, refuses a second send
for the same dedupe key, skips undeliverable addresses and queues it after the
surrounding DB transaction commits.

## What the customer receives

| Moment | Email | Trigger | Switch |
|---|---|---|---|
| Order placed (COD) | Order confirmation: "keep ₹X ready", subject "confirmed: cash on delivery". Never a payment claim or an invoice | `checkout.order.save.after` | Email → Notifications → New order |
| Order placed (prepaid, payment verified) | Order confirmation: "Payment of ₹X received via Razorpay, reference pay_…", **invoice PDF attached** (no separate invoice email) | same, sent when the request ends | same |
| Invoice raised later (COD on delivery, admin) | Invoice email | `sales.invoice.save.after` | New invoice |
| Courier label created (≥ 1 h after the order) | "Being packed" | `shiprocket.shipment.stage_changed` (AWB assigned) | Email → Automated Emails |
| Shiprocket picks up | Shipped (Bagisto) with AWB and track button | `sales.shipment.save.after` | New shipment |
| Out for delivery | Shipment update (COD amount shown) | `shiprocket.shipment.stage_changed` | Email → Automated Emails |
| Delivered | Shipment update + delivered SMS | same + `shiprocket.order.delivered` | same |
| Delivery attempt failed | Shipment update | same | same |
| Returning to origin (RTO) | Shipment update | same | same |
| 3 days after courier-confirmed delivery | Review request (link per product) | `notifications:review-requests` daily 11:00 | same |
| 7 days after delivery (optional, off by default) | 1–5 experience rating; answers in admin | `notifications:feedback-requests` daily 11:30 | same |
| Bag left at checkout / payment failed | Reminder at 1 h, then 24 h: picture, size, qty, prices, total, signed return-to-bag link | `notifications:abandoned-carts` every 15 min | same |
| Last order 45–60 days ago | Win-back (once per last order) | `notifications:win-back` daily 12:00 | same |
| Sold-out product/size restocked | Back in stock (once per request) | `notifications:back-in-stock` every 15 min | same |
| Admin campaign (new collection, promotion) | Campaign template in the store layout | Bagisto `campaign:process` daily, now safe (below) | same |

All timings above are defaults, editable under **Automated Emails → Timing**.
Nothing is sent without the event behind it: review and rating requests need a
Shiprocket delivery date, bag reminders a real email on the bag, restock emails
live stock at send time.

### Welcome and newsletter

- Bagisto's welcome and newsletter emails are unchanged, plus an optional
  **welcome offer** block (Automated Emails → Welcome & Newsletter: switch,
  coupon code, one line of text). It shows only while the coupon exists, its
  rule is active and in date, and it has uses left.
- **Double opt-in** (switch, off by default): new footer sign-ups get a
  confirmation link first; clicking it subscribes them and sends the usual
  welcome. The newest link only (the token rotates).
- **Consent ledger** (`marketing_consents`): every grant and withdrawal from
  any path (footer, account, sign-up, unsubscribe links, double opt-in, admin)
  with time, source route, IP and browser. Append-only.

### Back in stock

A "Size sold out? / Sold out" block on the product page lists only sizes that
are really sold out. It takes an email (reCAPTCHA, honeypot, rate limit) and
**sends nothing at sign-up**, so it cannot be used to mail a stranger. Requests
expire after 90 days.

### Campaigns

Bagisto's sender is replaced (container binding, core untouched). Bagisto's
version re-sent undated campaigns to everyone every day and mailed unsubscribed
guests. Now: subscribed addresses only, each campaign once per address
(birthday once a year), own daily cap `MAIL_CAMPAIGN_DAILY_CAP` (default 200)
with the rest going out over the following days, one-click unsubscribe, and
the same off-switch as the other schedules (`MAIL_AUTOMATION_SCHEDULED`).

How to run one (new collection, sale, any promotion) — all in Bagisto admin,
**Marketing → Communications**, nothing custom:

1. **Email Templates**: write the content (the store layout and unsubscribe
   footer are added around it).
2. **Events** (optional): a date schedules the campaign for that day; without
   an event it goes out on the next daily run.
3. **Campaigns**: subject, template, event, and the **customer group** as the
   segment (Guest = subscribers without an account; General / Wholesale = that
   group's subscribed customers). Set it active.
4. **History**: Email Log, filtered by type `campaign.{id}`, lists every
   recipient and delivery status.

Deliberately not built: a separate campaign editor, A/B tests, open/click
tracking, custom segment rules. Customer groups and Bagisto's templates cover
a store this size without extra tables or tracking pixels.

Welcome, newsletter, contact, cancel, refund, return (RMA) and GDPR emails are
Bagisto's own. Return emails (request received, every status change such as
Accepted or Declined, admin replies) go to the order's checkout email, and the
status email names the new status in its subject.

### Order, invoice and payment are separate facts

- **Order created** → order confirmation.
- **Invoice generated** → attached to the confirmation for prepaid orders, its
  own email otherwise. An invoice is never presented as a payment.
- **Payment confirmed** → only from a captured `order_transactions` row, which
  the Razorpay controller writes after verifying the signature and confirming
  the capture amount with Razorpay's API, or from a signature-verified webhook.
  A browser redirect alone never creates one. Cash on delivery and bank
  transfer (`notifications.offline_payment_methods`) never get payment wording.

## Consent and limits

- **Abandoned bag and win-back** are marketing: only for active newsletter
  subscribers, at most one per address every 48 h (`MAIL_MARKETING_GAP_HOURS`),
  at most `MAIL_MARKETING_DAILY_CAP` per day (default 100).
- **Review requests** are about the customer's own order: no opt-in needed, own
  opt-out, own daily cap (`MAIL_REVIEW_DAILY_CAP`).
- Every marketing and review email carries a signed unsubscribe link and RFC 8058
  one-click `List-Unsubscribe` headers. Unsubscribing from marketing also turns
  off the newsletter subscription and the account's newsletter toggle, and is
  recorded in the `marketing_consents` ledger.
- A **fresh newsletter opt-in** after an unsubscribe lifts the marketing
  suppression: the newest consent wins.
- **Transactional email is independent**: order, payment, shipping and account
  emails never check the newsletter or the suppression list.
- **Placeholder addresses** (`{phone}@noreply.{host}`, given to phone-only
  sign-ups) are never mailed: the dispatcher skips them and a `MessageSending`
  listener cancels Bagisto's own mails to them. Bounces to them would hurt the
  sender's reputation with MailChannels.

## Templates and design

One layout, `shop::emails.layout`: void-black canvas, charcoal panel, Poppins,
one lime CTA per email (the "Night Drop" system from DESIGN.md), tables and
inline styles only, 600px, stacking on phones, declared dark so clients do not
re-invert it. Footer: support email/phone/hours, social links
(`notifications.brand`), website and company line, and an unsubscribe line
when the email has an opt-out (`$unsubscribeUrl`).

Building blocks (`x-shop::email.*`): `heading`, `text`, `button`
(primary = lime, secondary = outline), `panel`, `items` (picture, size, qty,
price for order/invoice/shipment/refund/cart items), `totals` (GST shown as
included), `details` and `order-details` (address, payment, delivery). Every
customer template (Bagisto's and this package's) is built from them.

- **Subjects** are editable per email in **Marketing → Communications →
  Customer Emails**, with `{order_id}`, `{customer_name}`, `{status}`,
  `{product}`, `{store}`. Applied at send time to every email of that type
  (`EmailCatalog` maps each class, and each courier stage, to a key). Empty =
  built-in subject.
- **Preview**: each email rendered with the latest real record of its kind,
  HTML and plain text. No record yet = no preview (never sample data).
- **Test send**: renders the preview and sends it, marked `[Test]`, to the
  tester's address only (never to the sample's customer), 5 per admin per
  hour, logged in the Email Log.
- **Plain text**: emails without their own text template get one generated
  from the HTML at send time (links kept as "text (url)").

## Admin

- **Marketing → Communications → Customer Emails**: subject, preview and test
  send for every customer email (see above).
- **Marketing → Communications → Email Log**: every email with status
  (Queued, Sent, Failed, Suppressed), filters and today's counts.
  - **Retry** (own ACL permission) on a failed email re-queues the exact
    failed job, once. Refused when the email has gone out another way since,
    or its job is no longer in `failed_jobs`.
  - **Search a full email address** to see that person's marketing consent:
    newsletter status, opt-outs and the consent history.
- **Marketing → Communications → Customer Feedback**: ratings and comments.
- **Marketing → Communications → Newsletter Subscriptions / Campaigns /
  Events / Email Templates**: Bagisto's own (see Campaigns).
- **Configure → Email → Automated Emails**: per-email switches, timings,
  welcome offer, double opt-in.
- **Configure → Email → Email Settings**: sender name and address (Bagisto's).
- Bagisto's own emails (cancel, refund, registration…) keep their switches
  under **Configure → Email → Notifications**.

## Reliability

- **Queue**: `QUEUE_CONNECTION=redis` in production, Supervisor worker
  (DEPLOYMENT.md). Mail waits for the database commit, so a worker never sees
  an order that rolled back.
- **Retries**: every automation email and the order confirmation try 4 times,
  waiting 1, 5, then 15 minutes (`TracksDelivery`), overriding the worker's
  flat back-off. After the last try the job lands in `failed_jobs` and the log
  row turns Failed with the error and the job's id.
- **No duplicates**: one log row per dedupe key (unique index); a send whose
  row is already Sent is cancelled at the mailer, so a retried or replayed job
  never emails twice. A failed row parks its key as `{key}#failed{id}` so a
  later run can send, and admin retry knows whether one did.
- **Deleted records**: a job whose customer, order or product was deleted is
  marked Failed ("no longer exists") and not offered for retry. The failure is
  read from the job payload without restoring its models.
- **Never blocks an order**: dispatch failures are caught and logged; checkout
  carries on.
- **Scheduling**: every automation command runs `withoutOverlapping()`.
- **Links**: unsubscribe, bag recovery, feedback and subscription confirmation
  are signed URLs (bag and feedback expire). GET never changes anything.
- **Privacy**: application logs carry the email type and error only, never
  addresses or content. `email_logs` keep recipients for 180 days, then prune.

## Commands

Each takes `--dry-run`, which lists who would be emailed and sends nothing.

```bash
php artisan notifications:abandoned-carts --dry-run
php artisan notifications:review-requests --dry-run
php artisan notifications:feedback-requests --dry-run
php artisan notifications:win-back --dry-run
php artisan notifications:back-in-stock --dry-run
```

The scheduler runs them only when `MAIL_AUTOMATION_SCHEDULED=true` (default: on
in production, off elsewhere, so a dev machine with real SMTP credentials never
mails customers by itself). `email_logs` rows older than 180 days are pruned weekly.

## Testing without sending

The test suite fakes the mailer or uses the in-memory `array` transport, mocks
SMS, and `phpunit.xml` blanks `SMSALERT_APIKEY` (the SMS service makes no HTTP
call without a key). Run: `vendor/bin/pest packages/Gabha/Notifications`.

To check an email by eye, use the admin previews instead of sending one.

## Fixed with this module

- Phone sign-ups were queued two welcome emails (controller + listener).
- Invoice, shipment and refund listeners set `email_sent = 1` on **every** row of
  their table instead of the one emailed.
- Order and admin emails were queued inside the checkout transaction, so a worker
  could run before the order existed. Now they wait for the commit.
- The "inventory source" copy of every shipment email to the store's own mailbox
  is switched off (one-time migration; the admin switch still works).
- Return (RMA) emails went to the account email: the `@noreply` placeholder for
  phone-only customers, and nothing at all for guests (the error was swallowed).
- On the `sync` queue, Bagisto's mail listeners render inside their own
  try/catch again, so a broken template can never fail an invoice or checkout.
- The admin test-send mailable redeclared Laravel's typed `$html` property,
  a fatal error whenever the class loaded.
- Bag-recovery and feedback links used `{cart}` / `{order}` route parameters,
  which Bagisto binds to models globally: every click was a 500. They are now
  `{cartId}` / `{orderId}`.
- The unsubscribe link withdrew consent with a bulk update, so the consent
  ledger never saw it.
- A failed job whose models were deleted left its log row at Queued forever
  (the failure listener tried to unserialize those same models).
