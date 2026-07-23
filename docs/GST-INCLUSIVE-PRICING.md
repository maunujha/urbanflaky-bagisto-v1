# GST-Inclusive Pricing

Since July 2026 every price the admin enters (price, special price, catalog/cart
rule results) is the **final selling price — GST included**. What the customer
sees on any page is exactly what Razorpay charges and what the invoice totals to.

## How it works

Bagisto 2.4 supports this natively. The switch is pure configuration
(Admin → Configure → Taxes → Calculation & Display), shipped as migration
`2026_07_23_120000_switch_tax_config_to_gst_inclusive_pricing`:

| Setting | Value |
| --- | --- |
| `sales.taxes.calculation.product_prices` | `including_tax` |
| `sales.taxes.calculation.shipping_prices` | `including_tax` |
| `sales.taxes.shopping_cart.display_*` (3 keys) | `including_tax` |
| `sales.taxes.sales.display_*` (3 keys) | `including_tax` |

The engine back-computes tax from the inclusive total (`Cart::calculateItemsTax`):

```
tax     = total_incl × rate / (100 + rate)      # ₹199 @5% → ₹9.4762
taxable = total_incl − tax                      #         → ₹189.5238
```

Item tax is rounded at 4 decimals and the grand total is rebuilt as
`taxable + tax`, so the inclusive price always survives exactly:
**₹199 in admin = ₹199 on PDP = ₹199 grand total = ₹199 at Razorpay = ₹199 on the invoice.**

The tax rate lives in Admin → Taxes (currently one rate: GST 5 %, category
"Apparel 5 %"). Nothing is hardcoded — change the rate there and the split follows.

## GST invoice presentation

`App\Support\Gst` (unchanged) splits the engine's tax into CGST + SGST
(intra-state, Rajasthan) or IGST (inter-state) — presentation only. Example for
a ₹199 product shipped inside Rajasthan with ₹50 shipping:

```
Taxable Value   189.52     ← added to the invoice PDF under inclusive display
CGST (2.5%)       4.74
SGST (2.5%)       4.74
Shipping         50.00     (no tax category on shipping)
Grand Total     249.00
```

Cart/checkout GST lines are labelled **"Includes CGST …"** so they never read
as an addition on top of the subtotal.

## Rules that compute prices

- **Special price / `to_fixed` rules** — treated as the final inclusive price; enter clean values.
- **Percentage catalog rules** — the computed price is **floored to the whole
  rupee** (`CatalogRuleProductPrice::calculate`), so 50 % off ₹399 sells at
  ₹199, never ₹199.50. Floor = always in the customer's favour.
- **Percentage coupons / cart rules** — discount is computed on the inclusive
  subtotal and **rounded up to the whole rupee** per item
  (`CartRule::process` / `processShippingDiscount`), so 10 % off ₹697 shows
  ₹70 off, not ₹69.70, and the grand total stays clean. Ceiling = customer's
  favour. Item discounts still sum to the cart discount, so invoices/refunds
  stay consistent. Fixed-amount rules (`by_fixed` / `cart_fixed`) are left
  exact — admin already enters whole values, and `cart_fixed` is already whole
  at the cart level.

## Files touched (beyond the migration)

- `packages/Webkul/CatalogRule/src/Helpers/CatalogRuleProductPrice.php` — whole-rupee floor on catalog-rule prices.
- `packages/Webkul/CartRule/src/Helpers/CartRule.php` — whole-rupee round-up on percentage cart-rule / coupon discounts.
- `packages/Webkul/Shop/src/Http/Resources/CartResource.php` — "Includes …" GST labels.
- `packages/Webkul/Shop/src/Resources/views/checkout/onepage/index.blade.php` — custom checkout SPA now renders `*_incl_tax` fields.
- `packages/Webkul/Shop/src/Resources/views/customers/account/orders/gst-breakup.blade.php` — "Includes …" prefix.
- `packages/Webkul/Shop/src/Resources/views/customers/account/orders/pdf.blade.php` — Taxable Value row on the tax invoice.
- `packages/Webkul/Core/src/Traits/PDFHandler.php` — guard for the missing `ar-php` package (any invoice PDF render used to fatal; unrelated but blocked invoicing).

## Known side effects

- **Reward coins** earn off `order->sub_total`, which is now the *pre-tax*
  value (₹189.52 for a ₹199 order) — coins earned drop ~4.8 %. Change
  `AwardCoinsOnOrder` to `sub_total_incl_tax` if earn should stay on MRP.
- Existing carts self-heal on next load (`validateCartItem` resets item prices
  from the current product price).
- Core behaviour (unchanged): GST is computed on the pre-discount total.

## Verifying after changes

Simulation scripts used for sign-off (guest cart → address → shipping → totals
→ order → invoice, intra/inter-state, coupon, configurable) live in the session
scratchpad; the invariant to re-check after touching anything price-related:

```
grand_total == sub_total_incl_tax + shipping_amount_incl_tax − discount_amount
```

and CGST + SGST (or IGST) must sum to `tax_total`.
