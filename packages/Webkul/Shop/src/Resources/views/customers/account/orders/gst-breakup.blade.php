{{--
    GST tax breakup line(s) for order/invoice totals.

    Props:
      $taxAmount    float  total GST amount
      $taxableValue float  taxable value (sub total excl. tax) — used to derive the %
      $state        string place-of-supply state code (shipping state)
      $country      string place-of-supply country code (default IN)
      $currency     string currency code for formatting (optional)

    Shows CGST + SGST for intra-state supply, a single IGST line for inter-state,
    and falls back to a plain "Tax" line when the breakup is disabled or unknown.
--}}
@php
    $gstLines = \App\Support\Gst::breakup(
        (float) ($taxAmount ?? 0),
        (float) ($taxableValue ?? 0),
        $state ?? null,
        $country ?? 'IN'
    );

    $gstCurrency = $currency ?? core()->getCurrentCurrencyCode();
@endphp

@if (\App\Support\Gst::showBreakup() && count($gstLines))
    @foreach ($gstLines as $line)
        <div class="flex w-full justify-between gap-x-5">
            <p class="{{ $labelClass ?? '' }}">{{ \App\Support\Gst::label($line) }}</p>

            <p>
                {{ core()->formatPrice($line['amount'], $gstCurrency) }}
            </p>
        </div>
    @endforeach
@else
    <div class="flex w-full justify-between gap-x-5">
        <p class="{{ $labelClass ?? '' }}">@lang('shop::app.customers.account.orders.view.information.tax')</p>

        <p>
            {{ core()->formatPrice($taxAmount ?? 0, $gstCurrency) }}
        </p>
    </div>
@endif
