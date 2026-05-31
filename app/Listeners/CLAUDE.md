# Listeners

- Pattern: inject service via constructor, `handle()` returns void
- SMS listeners use `SmsAlertService` from `packages/Webkul/Shop/src/Services/`
- Register ALL listeners in: `packages/Webkul/Shop/src/Providers/EventServiceProvider.php`
- Each SMS listener needs matching `SMSALERT_TEMPLATE_*` key in `.env`
- Copy structure from: `SendOrderSms.php`
- No Playwright needed — backend only
