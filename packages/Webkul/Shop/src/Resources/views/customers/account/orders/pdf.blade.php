<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{{ app()->getLocale() }}" dir="{{ core()->getCurrentLocale()->direction }}">
<head>
    <meta http-equiv="Cache-control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    @php
        $fontPath = [];
        $fontFamily = ['regular' => 'DejaVu Sans', 'bold' => 'DejaVu Sans'];

        $gstin        = core()->getConfigData('sales.shipping.origin.vat_id') ?? '';
        $storePhone   = core()->getConfigData('sales.shipping.origin.telephone') ?? '';
        $storeName    = core()->getConfigData('sales.shipping.origin.store_name') ?? 'Urbanflaky';
    @endphp

    <style>
        * { margin: 0; padding: 0; }

        body {
            font-family: {{ $fontFamily['regular'] }};
            font-size: 11px;
            color: #333333;
            background: #ffffff;
        }

        table { border-collapse: collapse; }

        /* ── HEADER ── */
        .hdr { background: #000000; width: 100%; }
        .hdr td { padding: 26px 36px; vertical-align: middle; }

        .brand-name {
            font-size: 26px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 4px;
            text-transform: uppercase;
            line-height: 1;
            font-family: {{ $fontFamily['bold'] }};
        }

        .brand-tagline {
            font-size: 9px;
            color: #c7eb31;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .inv-label {
            font-size: 36px;
            font-weight: bold;
            color: #c7eb31;
            letter-spacing: 5px;
            text-transform: uppercase;
            line-height: 1;
            font-family: {{ $fontFamily['bold'] }};
        }

        .inv-id {
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            color: #888888;
            margin-top: 5px;
        }

        /* ── YELLOW STRIPE ── */
        .stripe { background: #c7eb31; height: 5px; font-size: 1px; line-height: 5px; }

        /* ── META BAR ── */
        .meta { background: #f5f5f5; width: 100%; border-bottom: 1px solid #e0e0e0; }
        .meta td { padding: 14px 28px; vertical-align: top; }

        .ml { font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: #888888; font-weight: bold; }
        .mv { font-family: "Courier New", Courier, monospace; font-size: 12px; color: #000000; font-weight: bold; margin-top: 3px; }

        /* ── ADDRESS SECTION ── */
        .addr-tbl { width: 100%; border-bottom: 1px solid #e0e0e0; }
        .addr-tbl td { padding: 20px 28px; vertical-align: top; }
        .addr-tbl .br { border-right: 1px solid #e0e0e0; }

        .blk-lbl {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #888888;
            font-weight: bold;
            margin-bottom: 8px;
            border-left: 3px solid #c7eb31;
            padding-left: 6px;
        }

        .a-name { font-size: 13px; font-weight: bold; color: #000000; margin-bottom: 4px; font-family: {{ $fontFamily['bold'] }}; }
        .a-txt  { font-size: 11px; color: #555555; line-height: 1.8; }
        .a-ph   { font-family: "Courier New", Courier, monospace; font-size: 11px; color: #000000; margin-top: 6px; }

        /* ── METHOD ROW ── */
        .mth { width: 100%; border-bottom: 1px solid #e0e0e0; }
        .mth td { padding: 12px 28px; }

        .pill { background: #f5f5f5; border: 1px solid #e0e0e0; padding: 8px 14px; }
        .pill-dot { width: 8px; height: 8px; background: #c7eb31; border: 2px solid #000000; display: inline-block; margin-right: 6px; vertical-align: middle; }
        .pill-lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #888888; font-weight: bold; }
        .pill-val { font-size: 12px; font-weight: bold; color: #000000; margin-top: 2px; font-family: {{ $fontFamily['bold'] }}; }

        /* ── ITEMS ── */
        .sec-wrap { padding: 22px 36px; }

        .sec-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #000000;
            text-transform: uppercase;
            border-bottom: 3px solid #c7eb31;
            padding-bottom: 6px;
            margin-bottom: 14px;
            font-family: {{ $fontFamily['bold'] }};
        }

        .itbl { width: 100%; }
        .itbl thead tr { background: #000000; }
        .itbl thead th {
            padding: 10px 12px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: bold;
            text-align: left;
            color: #ffffff;
        }
        .itbl thead th.r { text-align: right; }

        .itbl tbody tr { border-bottom: 1px solid #e0e0e0; }
        .itbl tbody tr.last { border-bottom: 2px solid #000000; }
        .itbl tbody tr.even { background: #f5f5f5; }

        .itbl tbody td { padding: 12px 12px; vertical-align: top; font-size: 12px; color: #333333; }
        .itbl tbody td.sku  { font-family: "Courier New", Courier, monospace; font-size: 10px; color: #888888; }
        .itbl tbody td.prd  { font-weight: bold; color: #000000; font-family: {{ $fontFamily['bold'] }}; }
        .itbl tbody td.r    { text-align: right; font-family: "Courier New", Courier, monospace; }
        .itbl tbody td.tot  { text-align: right; font-family: "Courier New", Courier, monospace; font-weight: bold; color: #000000; }

        /* ── TOTALS ── */
        .tot-wrap { padding: 0 36px 28px; }
        .tot-tbl  { width: 280px; float: right; }

        .tot-tbl tbody td { padding: 8px 4px; border-bottom: 1px solid #e0e0e0; font-size: 12px; }
        .tot-tbl tbody td.tl { color: #666666; }
        .tot-tbl tbody td.tv { font-family: "Courier New", Courier, monospace; color: #333333; text-align: right; }

        .tot-grand { background: #000000; }
        .tot-grand td { padding: 13px 14px; }
        .gl { font-size: 15px; font-weight: bold; letter-spacing: 2px; color: #c7eb31; text-transform: uppercase; font-family: {{ $fontFamily['bold'] }}; }
        .gv { font-family: "Courier New", Courier, monospace; font-size: 15px; font-weight: bold; color: #ffffff; text-align: right; }

        /* ── FOOTER ── */
        .ftr { background: #000000; width: 100%; margin-top: 10px; }
        .ftr td { padding: 16px 36px; vertical-align: middle; }

        .ftr-brand { font-size: 18px; font-weight: bold; letter-spacing: 3px; color: #c7eb31; text-transform: uppercase; font-family: {{ $fontFamily['bold'] }}; }
        .ftr-web   { font-size: 10px; color: #c7eb31; letter-spacing: 1px; margin-top: 3px; }
        .ftr-note  { font-size: 10px; color: #666666; text-align: center; line-height: 1.7; }
        .ftr-thanks{ font-size: 11px; font-weight: bold; letter-spacing: 2px; color: #c7eb31; text-transform: uppercase; text-align: center; margin-top: 5px; font-family: {{ $fontFamily['bold'] }}; }
        .ftr-ct    { font-size: 10px; color: #888888; text-align: right; line-height: 1.9; }
        .ftr-ct span { color: #c7eb31; }
    </style>
</head>

<body dir="{{ core()->getCurrentLocale()->direction }}">

    {{-- ── HEADER ── --}}
    <table class="hdr" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width: 54px; vertical-align: middle;">
                            @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.logo'))
                                <img
                                    src="data:image/png;base64,{{ base64_encode(file_get_contents(Storage::url(core()->getConfigData('sales.invoice_settings.pdf_print_outs.logo')))) }}"
                                    style="max-width: 54px; max-height: 54px;"
                                />
                            @else
                                <div style="width:52px;height:52px;background:#c7eb31;text-align:center;line-height:52px;font-size:22px;font-weight:bold;color:#000000;">UF</div>
                            @endif
                        </td>
                        <td style="padding-left: 14px; vertical-align: middle;">
                            <div class="brand-name">Urbanflaky</div>
                            <div class="brand-tagline">Premium Streetwear</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <div class="inv-label">Invoice</div>
                <div class="inv-id">
                    @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.invoice_id'))
                        #{{ $invoice->increment_id ?? $invoice->id }}
                    @endif
                    &nbsp;&middot;&nbsp; Order #{{ $invoice->order->increment_id }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ── YELLOW STRIPE ── --}}
    <div class="stripe">&nbsp;</div>

    {{-- ── META BAR ── --}}
    <table class="meta" cellpadding="0" cellspacing="0">
        <tr>
            @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.invoice_id'))
                <td>
                    <div class="ml">Invoice Date</div>
                    <div class="mv">{{ core()->formatDate($invoice->created_at, 'd-m-Y') }}</div>
                </td>
            @endif
            <td>
                <div class="ml">Order Date</div>
                <div class="mv">{{ core()->formatDate($invoice->order->created_at, 'd-m-Y') }}</div>
            </td>
            <td>
                <div class="ml">Payment</div>
                <div class="mv">{{ core()->getConfigData('sales.payment_methods.' . $invoice->order->payment->method . '.title') }}</div>
            </td>
            <td>
                <div class="ml">Status</div>
                <div class="mv" style="color:#c7eb31; background:#000; padding:2px 8px; display:inline-block;">Paid</div>
            </td>
        </tr>
    </table>

    {{-- ── ADDRESSES ── --}}
    <table class="addr-tbl" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Seller --}}
            @if (! empty(core()->getConfigData('sales.shipping.origin.country')))
                <td width="33%" class="br">
                    <div class="blk-lbl">Seller</div>
                    <div class="a-name">{{ $storeName }}</div>
                    <div class="a-txt">
                        {{ core()->getConfigData('sales.shipping.origin.address') }}<br>
                        {{ core()->getConfigData('sales.shipping.origin.zipcode') }} {{ core()->getConfigData('sales.shipping.origin.city') }}<br>
                        {{ core()->getConfigData('sales.shipping.origin.state') }}, {{ core()->getConfigData('sales.shipping.origin.country') }}
                    </div>
                    @if ($gstin)
                        <div class="a-ph" style="margin-top:8px;">
                            <span style="color:#888;">GSTIN:</span> {{ $gstin }}
                        </div>
                    @endif
                    @if ($storePhone)
                        <div class="a-ph"><span style="color:#888;">Ph:</span> {{ $storePhone }}</div>
                    @endif
                </td>
            @endif

            {{-- Bill To --}}
            @if ($invoice->order->billing_address)
                <td width="33%" class="br">
                    <div class="blk-lbl">Bill To</div>
                    @if ($invoice->order->billing_address->company_name)
                        <div class="a-name">{{ $invoice->order->billing_address->company_name }}</div>
                    @endif
                    <div class="a-name">{{ $invoice->order->billing_address->name }}</div>
                    <div class="a-txt">
                        {{ $invoice->order->billing_address->address }}<br>
                        {{ $invoice->order->billing_address->postcode }} {{ $invoice->order->billing_address->city }}<br>
                        {{ $invoice->order->billing_address->state }}, {{ core()->country_name($invoice->order->billing_address->country) }}
                    </div>
                    <div class="a-ph">{{ $invoice->order->billing_address->phone }}</div>
                </td>
            @endif

            {{-- Ship To --}}
            @if ($invoice->order->shipping_address)
                <td width="34%">
                    <div class="blk-lbl">Ship To</div>
                    @if ($invoice->order->shipping_address->company_name)
                        <div class="a-name">{{ $invoice->order->shipping_address->company_name }}</div>
                    @endif
                    <div class="a-name">{{ $invoice->order->shipping_address->name }}</div>
                    <div class="a-txt">
                        {{ $invoice->order->shipping_address->address }}<br>
                        {{ $invoice->order->shipping_address->postcode }} {{ $invoice->order->shipping_address->city }}<br>
                        {{ $invoice->order->shipping_address->state }}, {{ core()->country_name($invoice->order->shipping_address->country) }}
                    </div>
                    <div class="a-ph">{{ $invoice->order->shipping_address->phone }}</div>
                </td>
            @endif
        </tr>
    </table>

    {{-- ── PAYMENT & SHIPPING METHODS ── --}}
    <table class="mth" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div class="pill">
                                <span class="pill-dot"></span>
                                <span class="pill-lbl">Payment &nbsp;</span>
                                <span class="pill-val">
                                    {{ core()->getConfigData('sales.payment_methods.' . $invoice->order->payment->method . '.title') }}
                                    @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($invoice->order->payment->method); @endphp
                                    @if (! empty($additionalDetails))
                                        &nbsp;<span style="font-size:10px;color:#888;">· {{ $additionalDetails['title'] }}: {{ $additionalDetails['value'] }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        @if ($invoice->order->shipping_address)
                            <td style="padding-left: 10px;">
                                <div class="pill">
                                    <span class="pill-dot"></span>
                                    <span class="pill-lbl">Shipping &nbsp;</span>
                                    <span class="pill-val">{{ $invoice->order->shipping_title }}</span>
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── ORDER ITEMS ── --}}
    <div class="sec-wrap">
        <div class="sec-title">Order Items</div>

        <table class="itbl" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:13%;">SKU</th>
                    <th style="width:40%;">Product</th>
                    <th class="r" style="width:15%;">Price</th>
                    <th class="r" style="width:9%;">Qty</th>
                    <th class="r" style="width:23%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $i => $item)
                    @php $isLast = $loop->last; @endphp
                    <tr class="{{ $i % 2 === 1 ? 'even' : '' }}{{ $isLast ? ' last' : '' }}">
                        <td class="sku">{{ $item->getTypeInstance()->getOrderedItem($item)->sku }}</td>

                        <td class="prd">
                            {{ $item->name }}
                            @if (isset($item->additional['attributes']))
                                <div style="font-size:10px;color:#666;margin-top:4px;font-weight:normal;">
                                    @foreach ($item->additional['attributes'] as $attribute)
                                        @if (! isset($attribute['attribute_type']) || $attribute['attribute_type'] !== 'file')
                                            <span style="color:#999;">{{ $attribute['attribute_name'] }}:</span>
                                            {{ $attribute['option_label'] }}<br>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <td class="r">
                            @if (core()->getConfigData('sales.taxes.sales.display_prices') == 'including_tax')
                                {!! core()->formatPrice($item->price_incl_tax, $orderCurrencyCode) !!}
                            @elseif (core()->getConfigData('sales.taxes.sales.display_prices') == 'both')
                                {!! core()->formatPrice($item->price_incl_tax, $orderCurrencyCode) !!}
                                <div style="font-size:9px;color:#999;">excl. {!! core()->formatPrice($item->price, $orderCurrencyCode) !!}</div>
                            @else
                                {!! core()->formatPrice($item->price, $orderCurrencyCode) !!}
                            @endif
                        </td>

                        <td class="r">{{ $item->qty }}</td>

                        <td class="tot">
                            @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                {!! core()->formatPrice($item->total_incl_tax, $orderCurrencyCode) !!}
                            @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                {!! core()->formatPrice($item->total_incl_tax, $orderCurrencyCode) !!}
                                <div style="font-size:9px;color:#999;">excl. {!! core()->formatPrice($item->total, $orderCurrencyCode) !!}</div>
                            @else
                                {!! core()->formatPrice($item->total, $orderCurrencyCode) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── TOTALS ── --}}
    <div class="tot-wrap">
        <table class="tot-tbl" cellpadding="0" cellspacing="0" align="right">
            <tbody>
                @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.subtotal')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->sub_total_incl_tax, $orderCurrencyCode) !!}</td>
                    </tr>
                @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.subtotal-incl-tax')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->sub_total_incl_tax, $orderCurrencyCode) !!}</td>
                    </tr>
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.subtotal-excl-tax')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->sub_total, $orderCurrencyCode) !!}</td>
                    </tr>
                @else
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.subtotal')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->sub_total, $orderCurrencyCode) !!}</td>
                    </tr>
                @endif

                @if (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'including_tax')
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->shipping_amount_incl_tax, $orderCurrencyCode) !!}</td>
                    </tr>
                @elseif (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'both')
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling-incl-tax')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->shipping_amount_incl_tax, $orderCurrencyCode) !!}</td>
                    </tr>
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling-excl-tax')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->shipping_amount, $orderCurrencyCode) !!}</td>
                    </tr>
                @else
                    <tr>
                        <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling')</td>
                        <td class="tv">{!! core()->formatPrice($invoice->shipping_amount, $orderCurrencyCode) !!}</td>
                    </tr>
                @endif

                <tr>
                    <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.tax')</td>
                    <td class="tv">{!! core()->formatPrice($invoice->tax_amount, $orderCurrencyCode) !!}</td>
                </tr>

                <tr>
                    <td class="tl">@lang('shop::app.customers.account.orders.invoice-pdf.discount')</td>
                    <td class="tv">{!! core()->formatPrice($invoice->discount_amount, $orderCurrencyCode) !!}</td>
                </tr>
            </tbody>

            <tfoot>
                <tr class="tot-grand">
                    <td class="gl">@lang('shop::app.customers.account.orders.invoice-pdf.grand-total')</td>
                    <td class="gv">{!! core()->formatPrice($invoice->grand_total, $orderCurrencyCode) !!}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── FOOTER ── --}}
    <table class="ftr" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%">
                <div class="ftr-brand">Urbanflaky&#8482;</div>
                <div class="ftr-web">www.urbanflaky.in</div>
            </td>
            <td width="40%" style="text-align:center;">
                <div class="ftr-note">This is a computer-generated invoice. No signature required.</div>
                <div class="ftr-thanks">&#9829; Thank You for Shopping with Us!</div>
            </td>
            <td width="30%">
                <div class="ftr-ct">
                    <span>Support:</span> support@urbanflaky.in<br>
                    @if ($storePhone)
                        <span>WhatsApp:</span> {{ $storePhone }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
