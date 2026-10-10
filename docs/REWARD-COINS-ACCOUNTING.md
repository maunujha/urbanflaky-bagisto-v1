# Reward Coins: Accounting Reference

Package: `packages/Gabha/RewardCoins`. This page describes how coins are
accounted for after the Phase 1 integrity work (branch `fix/reward-coins`).
Earning and redemption policy is unchanged: 1 coin per ₹10, ₹1 per coin,
20% / ₹200 redemption caps, ₹200 minimum to redeem, 365-day expiry, 7-day
return window.

## Approved decisions

| # | Decision |
|---|---|
| 1 | Refunds never pay the coin-funded value twice. Cash refunded is net of the coin discount attributable to what is refunded; those coins are restored. |
| 2 | Shiprocket orders become eligible only on a Shiprocket "delivered" status. Shipment, dispatch or a generic `completed` never starts the clock for them. Orders without a Shiprocket record use `completed`. |
| 3 | Earned coins reverse on refunded merchandise value. Redeemed coins restore on the coin share attributed to the refunded items and shipping. Both are cumulative. |
| 4 | Restored coins keep their original expiry. If it has passed, they come back already expired. |
| 5 | Coins already spent are never recovered from future earnings. The obligation, the amount recovered and the shortfall are all recorded. |
| 6 | Windows opened before genuine delivery are reported (`reward-coins:audit-windows`) and are never changed automatically. |
| 7 | Items cancelled before invoicing (part-delivered COD orders) are treated exactly like refunded items, for both earned and redeemed coins. |
| 8 | Invoices are left as they are until the accountant confirms the GST treatment of coin discounts. |
| 9 | `backfill-lots --apply` runs on staging first, after a backup, and on production only after staging checks pass. |

## Model

| Concept | Where | Meaning |
|---|---|---|
| Lot | `coin_transactions` row of type `earned`, `refunded` or `adjusted` | A batch of credited coins. `remaining` = coins not yet spent, revoked or expired. |
| Debit | row of type `redeemed`, `revoked`, `expired`, `deducted` | Coins leaving the wallet. |
| Allocation | `coin_allocations` | Which lot(s) a debit drew on, and how much of that a restore later handed back (`restored`). `credit_transaction_id` NULL = legacy balance from before lot accounting. |
| Operation key | `coin_transactions.operation_key` (UNIQUE) | One logical operation = one key. Repeats are no-ops. |
| Refund coin share | `refunds.coins_refunded`, `refunds.base_coin_discount_amount` | Coins attributed to one credit memo. NULL `coins_refunded` = refund created before this existed. |
| Wallet | `customer_coin_wallets` | Snapshot: `balance`, `pending_balance`, `lifetime_earned`, `lifetime_redeemed`. |

**Spend order:** soonest `expires_at` first, non-expiring lots last, then oldest
first. Legacy balance is drawn after all lots. A refund claw-back drains the
order's own earned lot first.

**Single writer:** `CoinWalletService`. Each movement runs in one transaction
that first locks the customer's wallet row (`SELECT … FOR UPDATE`; the row is
created with `INSERT IGNORE` only when it does not exist yet). It then posts the
ledger row, lot updates, allocations and wallet update. All commit or all roll
back. Top-level transactions retry up to 3 times on deadlock. Different
customers never block each other.

## Operation keys

| Operation | Key |
|---|---|
| Earn on order | `earn:order:{order_id}` |
| Redeem on order | `redeem:order:{order_id}` |
| Expire a lot | `expire:lot:{lot_id}` |
| Void pending coins of an undone order | `reversal:order:{id}:void-pending` |
| Claw back to cumulative target T | `reversal:order:{id}:revoke-to:{T}` |
| Restore to cumulative target T | `reversal:order:{id}:restore-to:{T}:alloc:{allocation_id}` (or `:legacy`) |
| Admin add / deduct | `admin:{uuid}` (uuid rendered into the form) |

## Refunds (credit memos, cash and coins)

### The problem this fixes

The coin discount is applied to the whole cart, so it exists only on the order
(`orders.discount_amount`, `orders.grand_total`) and never on order items.
Bagisto builds every refund from items: `base_price × qty + item tax − item
discount`, plus shipping and adjustments. A stock refund therefore returned
the full pre-coin value. The Razorpay listener refunds `refund.base_grand_total`
at the gateway, so:

- partial refunds over-paid cash by the coin share, and the coins were also
  restored;
- a full refund asked the gateway for more than was captured, and the listener
  then **skipped the gateway refund entirely** ("already refunded; recording
  credit-memo only").

### Integration point

`Gabha\RewardCoins\Sales\CoinAwareRefundRepository` extends Bagisto's
`RefundRepository` and is bound in its place in the container. It overrides
only:

- `getOrderItemsRefundSummary()`, which drives the admin preview and the
  controller's amount validation;
- `collectTotals()`, which sets the saved refund's totals before
  `sales.refund.save.after` fires.

Each adds the refund's coin share to the refund discount. Nothing else in the
refund, inventory or payment flow changes. Orders with no redeemed coins
produce exactly the stock totals.

### Formula (`CoinRefundAllocator`)

```
R   = orders.coins_redeemed
r   = rupee_per_coin (config, ₹1)
C   = R × r                                       coin discount
P   = orders.base_grand_total + C                 pre-coin payable (items + tax − item discounts + shipping + shipping tax)
g   = refund gross before the coin share
    = Σ(base_price × qty + item tax share − item discount share) + shipping + shipping tax share
      (manual adjustment_refund / adjustment_fee are pure cash and excluded)
G   = g + Σ gross of all earlier refunds of the order

coins_cum   = min(R, round(R × min(1, G / P)))     whole coins, cumulative
coins_this  = coins_cum − Σ coins_refunded of earlier refunds
coin share  = coins_this × r
refund cash = g − coin share + adjustment_refund − adjustment_fee
```

Properties, each covered by a test:

- For every refund, cash + coin value = gross, so nothing is paid twice.
- Refunding everything returns exactly the cash paid and exactly R coins.
- Rounding happens once, on the cumulative total, so any order of partial
  refunds ends at the same totals.
- Item-level coupons are already in `g` (as item discounts); tax and shipping
  share the coin discount in proportion to value.

### Restoring redeemed coins

Items cancelled before invoicing (typically the undelivered part of a COD
order) count exactly like refunded items. Their pre-coin value X is
Σ `qty_canceled` × (unit price + unit tax − unit discount):

```
target = min(R, max(Σ refunds.coins_refunded, coins_for(G_refunds + X)))
```

Only the difference from coins already restored is posted. Refunds and
cancellations can happen in any order, and the final result is the same.

- Order undone with no refunds at all (nothing was paid back in cash): 100%.
- Refunds created before this change (`coins_refunded` NULL): the previous
  rule applies. That is ceil(R × `base_grand_total_refunded` / `base_grand_total`),
  or 100% when the order is canceled or closed.
- Shipping that is not refunded keeps its coin share. Those coins paid for
  shipping the customer still received.

### Revoking earned coins

Target = 100% when the order is canceled or closed. Otherwise:

```
ceil(earned × (base_sub_total_refunded + cancelled subtotal) / base_sub_total)
```

The cancelled subtotal is Σ `qty_canceled` × unit price. A partial
cancellation leaves the order open, so the reversal is also triggered by
Bagisto's `sales.order.cancel.after` event.

Coins are earned on the merchandise subtotal, so tax, shipping and coins are
not part of this basis. The result is cumulative, and only the difference is
posted.

- A pending lot on a reversed order is voided in full, as before.
- Confirmed coins are drawn from the order's own lot first, then from other
  lots, never below zero. The row records `meta.owed`, the recovered `amount`
  and `meta.shortfall`. The shortfall counts toward the settled total, so it
  is never taken from future earnings.

## Invariants (checked by `reward-coins:reconcile`, read-only)

- **I1:** `pending_balance` = Σ amount of pending earned lots.
- **I2:** `balance` = Σ `remaining` of confirmed lots, plus legacy balance for
  customers with pre-fix history. Customers without legacy history must match
  exactly.
- **I3:** For each tracked lot, `remaining` = amount − Σ allocations drawn from it.
- **I4:** For each keyed debit, amount = Σ its allocations.
- **I5:** For each allocation, `restored` ≤ amount.
- No balance can go negative: columns are floored at zero, and claw-back
  shortfalls are recorded rather than forced.

## Lifecycle

1. **Order placed:** a pending lot is created after commit, with
   `expires_at` = +365 days.
2. **Delivered:** `available_at` = delivery + 7 days (see Delivery).
3. **Window elapsed:** at 02:00, missing windows are reconciled, then due
   lots are confirmed. Lots on canceled or closed orders are skipped.
4. **Redeemed:** the debit draws on lots in spend order.
5. **Expired:** at 00:00, only each lot's `remaining` expires. Pending coins
   never expire. Legacy lots are not expired until backfilled. A lot that is
   overdue but not yet swept stays spendable until that night's run.

## Delivery

An order is delivered for rewards when **either**:

- it has a `shiprocket_orders` row and that row's status is exactly
  `delivered` (case-insensitive; "RTO Delivered" does not count), **or**
- it has **no** Shiprocket row and its status is `completed`;

**and** it is not canceled or closed.

| Path | Route to the coins |
|---|---|
| `POST /webhooks/tracking` (the only Shiprocket webhook; `x-api-key` header auth, query tokens rejected, IP locked out after 10 failed auths; updates `shiprocket_orders` only. The legacy `/api/webhooks/shiprocket` was retired — it sat behind CSRF and never received a call.) | Fires `shiprocket.order.delivered` on Delivered. |
| Admin / Bagisto status change to `completed` | `sales.order.update-status.after`. Counts only without a Shiprocket row. The transient `completed` set during shipment creation is ignored, because the job re-checks after commit. |
| Missed webhook, event-less save, crashed worker | The daily reconciliation uses the Shiprocket row's `updated_at` as the delivery time (or the order's, for manual orders). |

Duplicate and delayed webhooks never move a window that is already open. A
stale "In Transit" arriving after "Delivered" leaves the window in place.

**Historical windows:** `reward-coins:audit-windows` lists lots whose
`available_at` has no delivery basis, or that opened before the recorded
delivery, with a proposed correction. It changes nothing.

## Legacy data

- Migration `000006` fills `remaining` only for still-pending earned rows.
  This is provable: pending coins were never spendable.
- Migration `000007` adds the refund coin-share columns and leaves existing
  refunds NULL.
- `reward-coins:backfill-lots` is a dry run unless `--apply` is given. It
  writes only when its replay lands exactly on the wallet balance, never
  changes a balance, and skips ambiguous histories (legacy `adjusted` rows,
  lots already expired by the old full-amount job).

## Known limitations

- **Invoices still show the pre-coin total.** Razorpay creates them on
  capture, and Bagisto's invoice totals ignore the cart-level coin discount.
  The order and refunds are correct, but the invoice PDF and
  `orders.base_grand_total_invoiced` overstate what was paid by the coin
  value. Changing GST invoice documents is a separate tax decision.
- The coin share uses the current `rupee_per_coin`. It has always been ₹1; if
  it ever changes, store the rate on the order first.
- Earned-coin reversal is proportional to the original earned amount. Per-item
  earning is not stored, so category multipliers are not attributed to
  individual items.

## Operations

```
php artisan reward-coins:reconcile                # read-only audit
php artisan reward-coins:audit-windows            # read-only window report
php artisan reward-coins:backfill-lots            # dry run
php artisan reward-coins:backfill-lots --apply    # write accepted plans only (needs approval)
php artisan reward-coins:confirm-available        # also runs daily at 02:00
php artisan reward-coins:expire                   # also runs daily at 00:00
```

Concurrency is verified by `ConcurrentRedemptionTest`: real parallel PHP
processes race redemptions on committed data. It fails (140 coins spent from
100) when the wallet lock is removed.
