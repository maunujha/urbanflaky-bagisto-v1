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
| N days after delivery | Review request | `notifications:review-requests` daily 11:00 | same |
| Bag left at checkout / payment failed | Reminder (1h, then 48h) | `notifications:abandoned-carts` hourly | same |
| No order for 90–365 days | Win-back (once per last order) | `notifications:win-back` Tuesdays 12:00 | same |

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
  off the newsletter subscription.
- **Placeholder addresses** (`{phone}@noreply.{host}`, given to phone-only
  sign-ups) are never mailed: the dispatcher skips them and a `MessageSending`
  listener cancels Bagisto's own mails to them. Bounces to them would hurt the
  sender's reputation with MailChannels.

## Admin

- **Marketing → Communications → Email Log**: every email with status
  (queued, sent, failed), a filter, today's counts, and **previews** of each
  automated email rendered with the latest real order, cart or customer.
  Previews never send.
- **Configure → Email → Automated Emails**: on/off for each automated email.

## Commands

Each takes `--dry-run`, which lists who would be emailed and sends nothing.

```bash
php artisan notifications:abandoned-carts --dry-run
php artisan notifications:review-requests --dry-run
php artisan notifications:win-back --dry-run
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
