@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')"/>
@endPush

@push('styles')
<style>
    * { box-sizing: border-box; }
    .co-page { background:transparent; font-family:'Poppins',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; padding-bottom:48px; }

    /* ── Step nav ── */
    .co-nav { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 40px; border-bottom:1px solid rgba(255,255,255,.08); background:rgba(255,255,255,.02); }
    .co-nav-title { color:#f5f5f5; font-size:15px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; font-family:'Poppins',sans-serif; }
    .co-nav-steps { display:flex; align-items:center; gap:4px; }
    .co-step-item { display:flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; transition:background .2s; text-decoration:none; }
    .co-step-item.is-active { background:rgba(199,235,49,.10); }
    .co-step-item.is-pending{ opacity:.45; pointer-events:none; }
    .co-step-num { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
    .is-active  .co-step-num { background:#c7eb31; color:#0a0a0a; }
    .is-done    .co-step-num { background:rgba(199,235,49,.18); color:#c7eb31; }
    .is-pending .co-step-num { background:rgba(255,255,255,.08); color:rgba(255,255,255,.5); }
    .co-step-lbl { font-size:13px; font-weight:500; }
    .is-active  .co-step-lbl { color:#c7eb31; }
    .is-done    .co-step-lbl { color:#d4d4d8; }
    .is-pending .co-step-lbl { color:rgba(255,255,255,.4); }
    .co-step-sep { width:24px; height:1px; background:rgba(255,255,255,.12); }
    .co-nav-secure { display:flex; align-items:center; gap:6px; font-size:12px; color:rgba(255,255,255,.5); }

    /* ── Layout ── */
    .co-body { display:grid; grid-template-columns:1fr 360px; gap:28px; max-width:1100px; margin:0 auto; padding:32px 40px; align-items:start; }

    /* ── Cards ── */
    .co-card { background:rgba(255,255,255,.03); border-radius:16px; border:1px solid rgba(255,255,255,.08); padding:28px; margin-bottom:20px; -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); }
    .co-card-title { font-size:18px; font-weight:700; color:#f5f5f5; margin-bottom:4px; font-family:'Poppins',sans-serif; }
    .co-card-sub   { font-size:13px; color:#a1a1aa; margin-bottom:22px; }

    /* ── Fields ── */
    .co-field { margin-bottom:16px; }
    .co-label { display:block; font-size:11px; font-weight:700; color:#a1a1aa; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .co-input, .co-select { width:100%; height:44px; border:1.5px solid rgba(255,255,255,.12); border-radius:10px; padding:0 14px; font-size:14px; color:#f5f5f5; background:rgba(255,255,255,.03); outline:none; transition:border-color .15s,box-shadow .15s,background .15s; appearance:none; font-family:inherit; }
    .co-input::placeholder { color:#71717a; }
    .co-input:focus, .co-select:focus { border-color:#c7eb31; background:rgba(255,255,255,.05); box-shadow:0 0 0 3px rgba(199,235,49,.15); }
    .co-select option { background:#1c1c1c; color:#f5f5f5; }
    .co-input.is-prefilled { background:rgba(255,255,255,.02); color:#a1a1aa; border-color:rgba(255,255,255,.08); cursor:default; }
    .co-input.has-error { border-color:#ef4444; }
    .co-error-msg { font-size:12px; color:#fca5a5; margin-top:4px; }
    .co-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .co-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
    .co-phone-row { display:flex; align-items:center; gap:10px; height:44px; padding:0 14px; background:rgba(255,255,255,.03); border:1.5px solid rgba(255,255,255,.12); border-radius:10px; }
    .co-phone-badge { margin-left:auto; background:rgba(199,235,49,.12); color:#c7eb31; font-size:10px; font-weight:700; padding:2px 10px; border-radius:20px; }
    .co-cb-row { display:flex; align-items:center; gap:10px; padding:12px 14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:10px; cursor:pointer; margin-top:6px; }
    .co-cb-box { width:18px; height:18px; border:1.5px solid rgba(255,255,255,.25); border-radius:4px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .15s,border-color .15s; }
    .co-cb-box.is-checked { background:#c7eb31; border-color:#c7eb31; }
    .co-cb-text { font-size:13px; color:#d4d4d8; }

    /* ── Saved address cards ── */
    .co-addr-list { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .co-addr-card { position:relative; border:1.5px solid rgba(255,255,255,.12); border-radius:12px; padding:16px 16px 14px 44px; cursor:pointer; transition:border-color .15s,background .15s; }
    .co-addr-card.is-selected { border-color:#c7eb31; background:rgba(199,235,49,.06); }
    .co-addr-radio { position:absolute; left:15px; top:17px; width:18px; height:18px; border-radius:50%; border:2px solid rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; transition:background .15s,border-color .15s; }
    .co-addr-radio.is-on { border-color:#c7eb31; background:#c7eb31; }
    .co-addr-radio-dot { width:7px; height:7px; border-radius:50%; background:#0a0a0a; }
    .co-addr-name { font-size:14px; font-weight:600; color:#f5f5f5; margin-bottom:4px; }
    .co-addr-badge { display:inline-block; margin-left:8px; background:rgba(199,235,49,.14); color:#c7eb31; font-size:10px; font-weight:700; padding:1px 8px; border-radius:20px; vertical-align:middle; letter-spacing:.04em; }
    .co-addr-body { font-size:12.5px; color:#a1a1aa; line-height:1.6; }
    .co-addr-actions { margin-top:10px; }
    .co-addr-edit { background:none; border:none; color:#c7eb31; font-size:12px; font-weight:600; cursor:pointer; padding:0; text-decoration:underline; }
    .co-addr-add { display:flex; align-items:center; justify-content:center; gap:8px; border:1.5px dashed rgba(255,255,255,.22); border-radius:12px; padding:16px; color:#d4d4d8; font-size:13px; font-weight:600; cursor:pointer; background:transparent; transition:border-color .15s,color .15s; min-height:96px; }
    .co-addr-add:hover { border-color:#c7eb31; color:#c7eb31; }
    .co-addr-back { background:none; border:none; color:#a1a1aa; font-size:13px; cursor:pointer; padding:0; margin-bottom:14px; text-decoration:underline; }
    @media(max-width:600px) { .co-addr-list { grid-template-columns:1fr; } }

    /* ── Shipping ── */
    .co-ship-opts { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
    .co-ship-opt { display:flex; align-items:center; gap:16px; border:1.5px solid rgba(255,255,255,.10); border-radius:12px; padding:16px; cursor:pointer; transition:border-color .15s,background .15s; }
    .co-ship-opt.is-selected { border-color:#c7eb31; background:rgba(199,235,49,.06); }
    .co-radio { width:20px; height:20px; border-radius:50%; border:2px solid rgba(255,255,255,.25); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:background .15s,border-color .15s; }
    .co-radio.is-on { border-color:#c7eb31; background:#c7eb31; }
    .co-radio-dot { width:8px; height:8px; border-radius:50%; background:#0a0a0a; }
    .co-ship-info { flex:1; }
    .co-ship-name { font-size:14px; font-weight:600; color:#f5f5f5; }
    .co-ship-eta  { font-size:12px; color:#a1a1aa; margin-top:2px; }
    .co-ship-price { font-size:14px; font-weight:700; color:#f5f5f5; }
    .co-ship-price.is-free { color:#c7eb31; }

    /* ── Payment ── */
    .co-pay-opts { display:flex; flex-direction:column; gap:10px; margin-bottom:22px; }
    .co-pay-opt { display:flex; align-items:center; gap:14px; border:1.5px solid rgba(255,255,255,.10); border-radius:12px; padding:14px 16px; cursor:pointer; transition:border-color .15s,background .15s; }
    .co-pay-opt.is-selected { border-color:#c7eb31; background:rgba(199,235,49,.06); }
    .co-pay-icon { width:44px; height:28px; border-radius:6px; background:rgba(255,255,255,.06); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#d4d4d8; flex-shrink:0; }
    .co-pay-name { font-size:14px; font-weight:600; color:#f5f5f5; }
    .co-pay-desc { font-size:12px; color:#a1a1aa; margin-top:2px; }

    /* ── Coupon ── */
    .co-coupon-row { display:flex; gap:10px; }
    .co-coupon-input { flex:1; height:44px; border:1.5px solid rgba(255,255,255,.12); border-radius:10px; padding:0 14px; font-size:14px; background:rgba(255,255,255,.03); color:#f5f5f5; outline:none; transition:border-color .15s; }
    .co-coupon-input::placeholder { color:#71717a; }
    .co-coupon-input:focus { border-color:#c7eb31; }
    .co-coupon-btn { height:44px; padding:0 20px; background:rgba(255,255,255,.06); color:#f5f5f5; border:1px solid rgba(255,255,255,.12); border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:.15s; }
    .co-coupon-btn:hover { background:rgba(199,235,49,.12); border-color:#c7eb31; color:#c7eb31; }
    .co-coupon-btn:disabled { opacity:.5; cursor:default; }
    .co-coupon-applied { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; border:1px solid rgba(199,235,49,.4); background:rgba(199,235,49,.08); border-radius:10px; font-size:13px; color:#f5f5f5; }
    .co-coupon-remove { background:none; border:none; color:#fca5a5; font-size:12px; font-weight:600; cursor:pointer; padding:0; white-space:nowrap; }
    .co-coupon-remove:hover { text-decoration:underline; }

    /* ── Buttons ── */
    .co-btn-row { display:grid; grid-template-columns:auto 1fr; gap:12px; margin-top:8px; }
    .co-btn-row.first-step { grid-template-columns:1fr; }
    .co-btn-back { height:52px; padding:0 24px; background:transparent; color:#f5f5f5; border:1.5px solid rgba(255,255,255,.22); border-radius:12px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; transition:.15s; }
    .co-btn-back:hover { background:rgba(255,255,255,.05); border-color:rgba(255,255,255,.4); }
    .co-btn-next { height:52px; background:linear-gradient(180deg,#d4ef4f,#a9da1e); color:#0a0a0a; border:none; border-radius:12px; font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:filter .15s; box-shadow:0 8px 24px rgba(169,218,30,.22); }
    .co-btn-next:hover { filter:brightness(1.05); }
    .co-btn-next:disabled { opacity:.5; cursor:not-allowed; }

    /* ── Review ── */
    .co-review-addr { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.07); border-radius:12px; padding:14px; margin-bottom:16px; }
    .co-review-addr-title { font-size:11px; font-weight:700; color:#a1a1aa; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .co-review-addr-body { font-size:13px; color:#d4d4d8; line-height:1.7; }
    .co-review-item { display:flex; gap:14px; padding:14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.07); border-radius:12px; margin-bottom:14px; }
    .co-review-img { width:56px; height:56px; background:#1c1c1c; border-radius:8px; object-fit:cover; flex-shrink:0; }
    .co-review-item-name { font-size:13px; font-weight:600; color:#f5f5f5; line-height:1.4; }
    .co-review-item-meta { font-size:12px; color:#a1a1aa; margin-top:3px; }
    .co-review-item-price { font-size:14px; font-weight:700; color:#f5f5f5; }
    .co-totals-row { display:flex; justify-content:space-between; font-size:13px; color:#a1a1aa; margin-bottom:7px; }
    .co-totals-row span:last-child { color:#f5f5f5; font-weight:500; }
    .co-totals-total { display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:#f5f5f5; margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.1); }

    /* ── Sidebar ── */
    .co-summary { background:rgba(255,255,255,.03); border-radius:16px; border:1px solid rgba(255,255,255,.08); padding:22px; position:sticky; top:96px; -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); }
    .co-sum-title { font-size:14px; font-weight:700; color:#f5f5f5; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid rgba(255,255,255,.08); }
    .co-sum-item { display:flex; gap:12px; margin-bottom:16px; }
    .co-sum-img { width:54px; height:54px; border-radius:8px; object-fit:cover; background:#1c1c1c; flex-shrink:0; }
    .co-sum-item-name { font-size:13px; font-weight:600; color:#f5f5f5; line-height:1.4; }
    .co-sum-item-meta { font-size:12px; color:#a1a1aa; margin-top:2px; }
    .co-sum-item-price { font-size:13px; font-weight:700; color:#f5f5f5; margin-top:5px; }
    .co-sum-divider { border:none; border-top:1px solid rgba(255,255,255,.08); margin:14px 0; }
    .co-sum-row { display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px; }
    .co-sum-row span:first-child { color:#a1a1aa; }
    .co-sum-row span:last-child { color:#f5f5f5; }
    .co-sum-total { display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:#f5f5f5; margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.08); }
    .co-sum-secure { display:flex; align-items:center; gap:6px; margin-top:14px; padding-top:12px; border-top:1px solid rgba(255,255,255,.08); font-size:11px; color:#71717a; }

    /* ── Success ── */
    .co-success { text-align:center; padding:36px 24px; }
    .co-success-icon { width:68px; height:68px; background:#c7eb31; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; box-shadow:0 8px 28px rgba(199,235,49,.3); }
    .co-success-title { font-size:24px; font-weight:700; color:#f5f5f5; margin-bottom:8px; font-family:'Poppins',sans-serif; }
    .co-success-sub { font-size:14px; color:#a1a1aa; line-height:1.7; margin-bottom:24px; }
    .co-success-oid { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:12px; padding:16px; text-align:left; margin-bottom:20px; }
    .co-success-oid-label { font-size:11px; color:#a1a1aa; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
    .co-success-oid-val { font-size:20px; font-weight:700; color:#c7eb31; }

    /* ── Responsive ── */
    @media(max-width:768px) {
        .co-nav { padding:12px 16px; flex-wrap:wrap; gap:10px; }
        .co-nav-steps { display:none; }
        .co-nav-secure { margin-left:auto; }
        .co-body { grid-template-columns:1fr; padding:16px; gap:16px; }
        .co-right { order:-1; }
        .co-summary { position:static; top:auto; }
        .co-grid-3 { grid-template-columns:1fr 1fr; }
        .co-card { padding:18px; }
        .co-btn-row { grid-template-columns:1fr 2fr; }
    }
    @media(max-width:480px) {
        .co-grid-2, .co-grid-3 { grid-template-columns:1fr; }
        .co-btn-row { grid-template-columns:1fr; }
        .co-btn-back { display:none; }
        .co-card { padding:16px; }
        .co-card-title { font-size:16px; }
        .co-coupon-row { flex-wrap:wrap; }
        .co-coupon-btn { width:100%; }
        .co-btn-next { font-size:14px; }
    }
</style>
@endpush

<x-shop::layouts
    :has-feature="false"
>
    <x-slot:title>Checkout</x-slot>

    <v-checkout-new
        :initial-cart='@json($cart)'
        :initial-items='@json($cartItems)'
        :initial-addresses='@json($addresses)'
        :countries='@json($countries)'
        :states='@json($states)'
    ></v-checkout-new>

    @pushOnce('scripts')
    @if (core()->getConfigData('customer.captcha.credentials.status'))
        {{-- reCAPTCHA v3 (invisible, score-based) — token minted on Place Order --}}
        <script src="{{ \Webkul\Customer\Facades\Captcha::getClientEndpoint() }}?render={{ \Webkul\Customer\Facades\Captcha::getSiteKey() }}"></script>
    @endif

    <script type="text/x-template" id="v-checkout-new-template">
    <div class="co-page">

        {{-- Step nav --}}
        <nav class="co-nav">
            <span class="co-nav-title">Checkout</span>
            <div class="co-nav-steps">
                <span class="co-step-item" :class="stepCls(1)">
                    <span class="co-step-num"><span v-if="step > 1">✓</span><span v-else>1</span></span>
                    <span class="co-step-lbl">Address</span>
                </span>
                <div class="co-step-sep"></div>
                <span class="co-step-item" :class="stepCls(2)">
                    <span class="co-step-num"><span v-if="step > 2">✓</span><span v-else>2</span></span>
                    <span class="co-step-lbl">Shipping</span>
                </span>
                <div class="co-step-sep"></div>
                <span class="co-step-item" :class="stepCls(3)">
                    <span class="co-step-num"><span v-if="step > 3">✓</span><span v-else>3</span></span>
                    <span class="co-step-lbl">Payment</span>
                </span>
                <div class="co-step-sep"></div>
                <span class="co-step-item" :class="stepCls(4)">
                    <span class="co-step-num">4</span>
                    <span class="co-step-lbl">Review</span>
                </span>
            </div>
            <div class="co-nav-secure">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <rect x="2" y="6" width="10" height="7" rx="2" stroke="currentColor" stroke-width="1.2" opacity=".5"/>
                    <path d="M4 6V4a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".5"/>
                </svg>
                Secure checkout
            </div>
        </nav>

        <div class="co-body">
            <div class="co-left">

                {{-- ── Step 1: Address ── --}}
                <div v-show="step === 1">

                    {{-- Saved address selection (logged-in users with saved addresses) --}}
                    <div v-show="addressMode === 'list'">
                        <div class="co-card">
                            <div class="co-card-title">Delivery address</div>
                            <div class="co-card-sub">Choose where to deliver your order</div>

                            <div class="co-addr-list">
                                <div v-for="a in savedAddresses" :key="a.id"
                                    class="co-addr-card"
                                    :class="{'is-selected': selectedAddressId === a.id}"
                                    @click="selectSavedAddress(a)">
                                    <span class="co-addr-radio" :class="{'is-on': selectedAddressId === a.id}">
                                        <span class="co-addr-radio-dot" v-if="selectedAddressId === a.id"></span>
                                    </span>
                                    <div class="co-addr-name">
                                        @{{ a.first_name }} @{{ a.last_name }}
                                        <span class="co-addr-badge" v-if="a.default_address">Default</span>
                                    </div>
                                    <div class="co-addr-body">
                                        @{{ formatAddress(a) }}
                                        <template v-if="a.phone"><br>Phone: @{{ a.phone }}</template>
                                    </div>
                                    <div class="co-addr-actions">
                                        <button type="button" class="co-addr-edit" @click.stop="editSavedAddress(a)">Edit</button>
                                    </div>
                                </div>

                                <button type="button" class="co-addr-add" @click="showAddNewForm">
                                    <span style="font-size:18px;line-height:1;">+</span> Add a new address
                                </button>
                            </div>
                        </div>

                        <div class="co-btn-row first-step">
                            <button class="co-btn-next" @click="deliverToSelected" :disabled="!selectedAddressId || savingAddress">
                                <span v-if="savingAddress">Saving…</span>
                                <span v-else>Deliver to this address</span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 8h10M9 4l4 4-4 4" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Address form (add new · edit · guest checkout) --}}
                    <div v-show="addressMode === 'form'">
                    <button v-if="savedAddresses.length" type="button" class="co-addr-back" @click="backToList">
                        ← Back to saved addresses
                    </button>

                    <div class="co-card">
                        <div class="co-card-title">@{{ editingAddressId ? 'Edit address' : (savedAddresses.length ? 'Add a new address' : 'Delivery address') }}</div>
                        <div class="co-card-sub">Where should we deliver your order?</div>

                        {{-- Mobile number with OTP verification --}}
                        <div class="co-field">
                            <label class="co-label">Mobile number *</label>

                            @if(auth()->user()?->phone)
                                {{-- Logged-in user: verified by default, option to change --}}

                                {{-- Locked display --}}
                                <div v-if="!changingPhone" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <div class="co-phone-row" style="flex:1">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path d="M2 1.5h3l1.5 3.5-2 1.5C5.5 8.5 6.5 9.5 8.5 10.5l1.5-2 3.5 1.5v3C13.5 13.5 8 14.5 4 10.5S.5 1.5 2 1.5z" fill="#888"/>
                                        </svg>
                                        <span style="font-size:14px;color:#d4d4d8">@{{ addr.phone }}</span>
                                        <span class="co-phone-badge">Verified</span>
                                    </div>
                                    <button type="button" @click="startChangePhone"
                                        style="height:44px;padding:0 16px;background:transparent;color:#f5f5f5;border:1.5px solid rgba(255,255,255,0.25);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                                        Change
                                    </button>
                                </div>

                                {{-- OTP flow when changing number --}}
                                <div v-if="changingPhone">
                                    <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                                        <div style="flex:1">
                                            <input class="co-input"
                                                type="tel" v-model="addr.phone" maxlength="10"
                                                inputmode="numeric" placeholder="Enter new 10-digit number"
                                                @input="onPhoneChanged"
                                                :disabled="phoneVerified">
                                        </div>
                                        <button v-if="!phoneVerified && !otpSent"
                                            type="button" @click="sendOtp"
                                            :disabled="addr.phone.length !== 10 || otpSending"
                                            style="height:44px;padding:0 16px;background:#c7eb31;color:#0a0a0a;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                            :style="{opacity: (addr.phone.length !== 10 || otpSending) ? 0.5 : 1}">
                                            @{{ otpSending ? 'Sending…' : 'Send OTP' }}
                                        </button>
                                        <div v-if="phoneVerified"
                                            style="height:44px;padding:0 14px;display:flex;align-items:center;gap:6px;background:rgba(199,235,49,0.12);border:1px solid rgba(199,235,49,0.3);border-radius:10px;font-size:13px;font-weight:600;color:#c7eb31;white-space:nowrap;flex-shrink:0;">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                                <circle cx="7" cy="7" r="6" fill="#2e7d32"/>
                                                <path d="M4 7l2 2 4-4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Verified
                                        </div>
                                    </div>
                                    <div v-if="otpSent && !phoneVerified" style="margin-top:12px;">
                                        <div style="font-size:12px;color:#a1a1aa;margin-bottom:8px;">
                                            Enter the 6-digit OTP sent to @{{ addr.phone }}
                                        </div>
                                        <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                                            <input class="co-input" type="text" v-model="otpCode"
                                                maxlength="6" inputmode="numeric" placeholder="6-digit OTP"
                                                style="max-width:160px;letter-spacing:6px;font-size:18px;font-weight:600;"
                                                @keyup.enter="verifyOtp">
                                            <button type="button" @click="verifyOtp"
                                                :disabled="otpCode.length !== 6 || otpVerifying"
                                                style="height:44px;padding:0 16px;background:#c7eb31;color:#0a0a0a;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                                :style="{opacity: (otpCode.length !== 6 || otpVerifying) ? 0.5 : 1}">
                                                @{{ otpVerifying ? 'Verifying…' : 'Verify OTP' }}
                                            </button>
                                        </div>
                                        <div style="margin-top:8px;font-size:12px;color:#a1a1aa;">
                                            <span v-if="otpCooldown > 0">Resend OTP in @{{ otpCooldown }}s</span>
                                            <button v-else type="button" @click="sendOtp"
                                                style="background:none;border:none;cursor:pointer;color:#c7eb31;font-size:12px;font-weight:600;padding:0;text-decoration:underline;">
                                                Resend OTP
                                            </button>
                                        </div>
                                    </div>
                                    <div style="margin-top:10px;">
                                        <button type="button" @click="cancelChangePhone"
                                            style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:12px;text-decoration:underline;padding:0;">
                                            Cancel — keep {{ auth()->user()->phone }}
                                        </button>
                                    </div>
                                    <div class="co-error-msg" v-if="otpError" style="margin-top:6px;">@{{ otpError }}</div>
                                </div>
                            @else
                                {{-- Guest / logged-in without phone: OTP flow --}}

                                {{-- Phone input row --}}
                                <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                                    <div style="flex:1">
                                        <input class="co-input" :class="{'has-error': errors['billing.phone']}"
                                            type="tel" v-model="addr.phone" maxlength="10"
                                            inputmode="numeric" placeholder="Enter 10-digit mobile number"
                                            @input="onPhoneChanged"
                                            :disabled="phoneVerified">
                                        <div class="co-error-msg" v-if="errors['billing.phone']">@{{ errors['billing.phone'][0] }}</div>
                                    </div>

                                    {{-- Send OTP / Verified badge --}}
                                    <button v-if="!phoneVerified && !otpSent"
                                        type="button"
                                        @click="sendOtp"
                                        :disabled="addr.phone.length !== 10 || otpSending"
                                        style="height:44px;padding:0 16px;background:#c7eb31;color:#0a0a0a;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                        :style="{opacity: (addr.phone.length !== 10 || otpSending) ? 0.5 : 1}">
                                        @{{ otpSending ? 'Sending…' : 'Send OTP' }}
                                    </button>

                                    <div v-if="phoneVerified"
                                        style="height:44px;padding:0 14px;display:flex;align-items:center;gap:6px;background:rgba(199,235,49,0.12);border:1px solid rgba(199,235,49,0.3);border-radius:10px;font-size:13px;font-weight:600;color:#c7eb31;white-space:nowrap;flex-shrink:0;">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <circle cx="7" cy="7" r="6" fill="#2e7d32"/>
                                            <path d="M4 7l2 2 4-4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Verified
                                        <button type="button" @click="resetPhone"
                                            style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:11px;padding:0;margin-left:4px;">Edit</button>
                                    </div>
                                </div>

                                {{-- OTP input (shown after OTP is sent) --}}
                                <div v-if="otpSent && !phoneVerified" style="margin-top:12px;">
                                    <div style="font-size:12px;color:#a1a1aa;margin-bottom:8px;">
                                        Enter the 6-digit OTP sent to @{{ addr.phone }}
                                    </div>
                                    <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                                        <input class="co-input" type="text" v-model="otpCode"
                                            maxlength="6" inputmode="numeric" placeholder="6-digit OTP"
                                            style="max-width:160px;letter-spacing:6px;font-size:18px;font-weight:600;"
                                            @keyup.enter="verifyOtp">
                                        <button type="button" @click="verifyOtp"
                                            :disabled="otpCode.length !== 6 || otpVerifying"
                                            style="height:44px;padding:0 16px;background:#c7eb31;color:#0a0a0a;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                            :style="{opacity: (otpCode.length !== 6 || otpVerifying) ? 0.5 : 1}">
                                            @{{ otpVerifying ? 'Verifying…' : 'Verify OTP' }}
                                        </button>
                                    </div>
                                    <div style="margin-top:8px;font-size:12px;color:#a1a1aa;">
                                        <span v-if="otpCooldown > 0">Resend OTP in @{{ otpCooldown }}s</span>
                                        <button v-else type="button" @click="sendOtp"
                                            style="background:none;border:none;cursor:pointer;color:#c7eb31;font-size:12px;font-weight:600;padding:0;text-decoration:underline;">
                                            Resend OTP
                                        </button>
                                    </div>
                                </div>

                                {{-- OTP error message --}}
                                <div class="co-error-msg" v-if="otpError" style="margin-top:6px;">@{{ otpError }}</div>
                            @endif
                        </div>

                        <div class="co-grid-2">
                            <div class="co-field">
                                <label class="co-label">First name *</label>
                                <input class="co-input" :class="{'has-error': errors['billing.first_name']}"
                                    type="text" v-model="addr.first_name" placeholder="First name">
                                <div class="co-error-msg" v-if="errors['billing.first_name']">@{{ errors['billing.first_name'][0] }}</div>
                            </div>
                            <div class="co-field">
                                <label class="co-label">Last name *</label>
                                <input class="co-input" :class="{'has-error': errors['billing.last_name']}"
                                    type="text" v-model="addr.last_name" placeholder="Last name">
                                <div class="co-error-msg" v-if="errors['billing.last_name']">@{{ errors['billing.last_name'][0] }}</div>
                            </div>
                        </div>

                        <div class="co-field">
                            <label class="co-label">Email address *</label>
                            <input class="co-input" :class="{'has-error': errors['billing.email']}"
                                type="email" v-model="addr.email"
                                placeholder="you@example.com">
                            <div class="co-error-msg" v-if="errors['billing.email']">@{{ errors['billing.email'][0] }}</div>
                        </div>

                        <div class="co-field">
                            <label class="co-label">Street address *</label>
                            <input class="co-input" :class="{'has-error': errors['billing.address']}"
                                type="text" v-model="addr.address" placeholder="House no., street name, area">
                            <div class="co-error-msg" v-if="errors['billing.address']">@{{ errors['billing.address'][0] }}</div>
                        </div>

                        {{-- Pincode first → auto-fills city & state --}}
                        <div class="co-grid-3">
                            <div class="co-field">
                                <label class="co-label">Pincode *</label>
                                <input class="co-input" :class="{'has-error': errors['billing.postcode']}"
                                    type="text" v-model="addr.postcode"
                                    maxlength="6" inputmode="numeric"
                                    placeholder="6-digit pincode"
                                    @input="onPincodeInput">
                                <div class="co-error-msg" v-if="errors['billing.postcode']">@{{ errors['billing.postcode'][0] }}</div>
                                <div v-if="pincodeLoading" style="font-size:11px;color:#a1a1aa;margin-top:4px;">Looking up pincode…</div>
                                <div v-if="pincodeError" style="font-size:11px;color:#e53935;margin-top:4px;">@{{ pincodeError }}</div>
                            </div>
                            <div class="co-field">
                                <label class="co-label">City *</label>
                                <input class="co-input" :class="{'has-error': errors['billing.city']}"
                                    type="text" v-model="addr.city" placeholder="Auto-filled">
                                <div class="co-error-msg" v-if="errors['billing.city']">@{{ errors['billing.city'][0] }}</div>
                            </div>
                            <div class="co-field">
                                <label class="co-label">State *</label>
                                <select class="co-select" v-model="addr.state">
                                    <option value="">Select state</option>
                                    <option v-for="s in states" :key="s.code" :value="s.code">@{{ s.default_name }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Country: fixed to India, non-editable --}}
                        <div class="co-field" style="max-width:220px">
                            <label class="co-label">Country</label>
                            <div class="co-phone-row" style="justify-content:flex-start;gap:8px;">
                                <span>🇮🇳</span>
                                <span style="font-size:14px;color:#d4d4d8;font-weight:500;">India</span>
                                <span class="co-phone-badge">Default</span>
                            </div>
                        </div>

                        <div class="co-cb-row" @click="sameAsBilling = !sameAsBilling">
                            <div class="co-cb-box" :class="{'is-checked': sameAsBilling}">
                                <svg v-if="sameAsBilling" width="10" height="8" viewBox="0 0 10 8" fill="none">
                                    <path d="M1 4l3 3 5-6" stroke="#0a0a0a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="co-cb-text">Use same address for billing</span>
                        </div>
                    </div>

                    <div class="co-btn-row first-step">
                        <button class="co-btn-next" @click="submitAddressForm" :disabled="savingAddress">
                            <span v-if="savingAddress">Saving…</span>
                            <span v-else>@{{ submitLabel }}</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    </div>{{-- /address form --}}
                </div>

                {{-- ── Step 2: Shipping ── --}}
                <div v-show="step === 2">
                    <div class="co-card">
                        <div class="co-card-title">Shipping method</div>
                        <div class="co-card-sub">Choose your preferred delivery speed</div>

                        <div v-if="loadingShipping" style="text-align:center;padding:24px;color:#a1a1aa;font-size:14px;">
                            Loading shipping methods…
                        </div>

                        <div class="co-ship-opts" v-else>
                            <div v-for="method in shippingMethods" :key="method.method"
                                class="co-ship-opt"
                                :class="{'is-selected': selectedShipping === method.method}"
                                @click="selectedShipping = method.method">
                                <div class="co-radio" :class="{'is-on': selectedShipping === method.method}">
                                    <div class="co-radio-dot" v-if="selectedShipping === method.method"></div>
                                </div>
                                <div class="co-ship-info">
                                    <div class="co-ship-name">@{{ method.method_title }}</div>
                                    <div class="co-ship-eta">@{{ method.method_description || method.carrier_title }}</div>
                                </div>
                                <span class="co-ship-price" :class="{'is-free': method.base_price == 0}">
                                    @{{ method.base_price == 0 ? 'Free' : '₹' + method.base_price }}
                                </span>
                            </div>
                        </div>

                        <div class="co-field" style="margin-bottom:0">
                            <label class="co-label">Delivery instructions (optional)</label>
                            <input class="co-input" type="text" v-model="deliveryNote" placeholder="e.g. Leave at door, call before delivery…">
                        </div>
                    </div>

                    <div class="co-btn-row">
                        <button class="co-btn-back" @click="step = 1">← Back</button>
                        <button class="co-btn-next" @click="saveShipping" :disabled="!selectedShipping || savingShipping">
                            <span v-if="savingShipping">Saving…</span>
                            <span v-else>Continue to payment</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── Step 3: Payment ── --}}
                <div v-show="step === 3">
                    <div class="co-card">
                        <div class="co-card-title">Payment method</div>
                        <div class="co-card-sub">All transactions are 100% secure and encrypted</div>

                        <div v-if="loadingPayment" style="text-align:center;padding:24px;color:#a1a1aa;font-size:14px;">
                            Loading payment methods…
                        </div>

                        <div class="co-pay-opts" v-else>
                            <div v-for="method in paymentMethods" :key="method.method"
                                class="co-pay-opt"
                                :class="{'is-selected': selectedPayment === method.method}"
                                @click="selectedPayment = method.method">
                                <div class="co-pay-icon">@{{ method.method_title.substring(0,4).toUpperCase() }}</div>
                                <div style="flex:1">
                                    <div class="co-pay-name">@{{ method.method_title }}</div>
                                </div>
                                <div class="co-radio" :class="{'is-on': selectedPayment === method.method}">
                                    <div class="co-radio-dot" v-if="selectedPayment === method.method"></div>
                                </div>
                            </div>
                        </div>

                        <div style="border-top:.5px solid #ececec;padding-top:18px">
                            <label class="co-label" style="margin-bottom:8px">Have a coupon?</label>

                            {{-- Applied coupon state --}}
                            <div v-if="cart.coupon_code" class="co-coupon-applied">
                                <span>
                                    <strong>@{{ cart.coupon_code }}</strong> applied<template v-if="Number(cart.discount_amount) > 0"> · you save @{{ cart.formatted_discount_amount }}</template>
                                </span>
                                <button type="button" class="co-coupon-remove" @click="removeCoupon" :disabled="couponApplying">Remove</button>
                            </div>

                            {{-- Coupon entry (hidden once a coupon is applied) --}}
                            <div v-else class="co-coupon-row">
                                <input class="co-coupon-input" type="text" v-model="couponCode" placeholder="Enter coupon code" @keyup.enter="applyCoupon" :disabled="couponApplying">
                                <button class="co-coupon-btn" @click="applyCoupon" :disabled="couponApplying || !couponCode">@{{ couponApplying ? '…' : 'Apply' }}</button>
                            </div>

                            <div v-if="couponMessage" style="font-size:12px;margin-top:6px" :style="{color: couponValid ? '#c7eb31' : '#fca5a5'}">
                                @{{ couponMessage }}
                            </div>
                        </div>
                    </div>

                    <div class="co-btn-row">
                        <button class="co-btn-back" @click="step = 2">← Back</button>
                        <button class="co-btn-next" @click="savePayment" :disabled="!selectedPayment || savingPayment">
                            <span v-if="savingPayment">Saving…</span>
                            <span v-else>Review order</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── Step 4: Review ── --}}
                <div v-show="step === 4">
                    <div class="co-card">
                        <div class="co-card-title">Review your order</div>
                        <div class="co-card-sub">Please confirm everything looks correct</div>

                        <div class="co-review-addr">
                            <div class="co-review-addr-title">Delivering to</div>
                            <div class="co-review-addr-body">
                                @{{ addr.first_name }} @{{ addr.last_name }} &middot;
                                {{ auth()->user()?->phone ?? '' }}<br>
                                @{{ addr.address }}, @{{ addr.city }} @{{ addr.postcode }}, @{{ addr.state }}
                            </div>
                        </div>

                        <div class="co-review-item" v-for="item in cartItems" :key="item.id">
                            <img class="co-review-img"
                                :src="item.base_image && item.base_image.small_image_url ? item.base_image.small_image_url : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22%3E%3Crect width=%2256%22 height=%2256%22 fill=%22%23ece8e4%22/%3E%3C/svg%3E'"
                                :alt="item.name"
                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22%3E%3Crect width=%2256%22 height=%2256%22 fill=%22%23ece8e4%22/%3E%3C/svg%3E'">
                            <div style="flex:1">
                                <div class="co-review-item-name">@{{ item.name }}</div>
                                <div class="co-review-item-meta">Qty: @{{ item.quantity }}</div>
                            </div>
                            <div class="co-review-item-price">@{{ item.formatted_total || item.formatted_price }}</div>
                        </div>

                        <div class="co-totals-row"><span>Subtotal</span><span>@{{ cart.formatted_sub_total }}</span></div>
                        <div class="co-totals-row" v-if="Number(cart.discount_amount) > 0" style="color:#c7eb31">
                            <span>Discount<template v-if="cart.coupon_code"> (@{{ cart.coupon_code }})</template></span>
                            <span>− @{{ cart.formatted_discount_amount }}</span>
                        </div>
                        <div class="co-totals-row"><span>Shipping</span><span>@{{ cart.formatted_shipping_amount || 'Calculated' }}</span></div>
                        <div class="co-totals-row"><span>Tax (GST)</span><span>@{{ cart.formatted_tax_total }}</span></div>
                        <div class="co-totals-total"><span>Grand total</span><span>@{{ cart.formatted_grand_total }}</span></div>
                    </div>

                    <div class="co-btn-row">
                        <button class="co-btn-back" @click="step = 3">← Back</button>
                        <button class="co-btn-next" @click="placeOrder" :disabled="isPlacing">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="5" width="12" height="9" rx="2" stroke="#0a0a0a" stroke-width="1.3"/>
                                <path d="M5 5V4a3 3 0 016 0v1" stroke="#0a0a0a" stroke-width="1.3" stroke-linecap="round"/>
                            </svg>
                            <span v-if="!isPlacing">Place order · @{{ cart.formatted_grand_total }}</span>
                            <span v-else>Placing order…</span>
                        </button>
                    </div>
                </div>

                {{-- ── Step 5: Success ── --}}
                <div v-show="step === 5">
                    <div class="co-card">
                        <div class="co-success">
                            <div class="co-success-icon">
                                <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
                                    <path d="M6 15l7 7 11-11" stroke="#0a0a0a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="co-success-title">Order placed!</div>
                            <div class="co-success-sub">
                                Thank you for your order!<br>
                                You'll receive a tracking link once your order ships.
                            </div>
                            <div class="co-success-oid">
                                <div class="co-success-oid-label">Order ID</div>
                                <div class="co-success-oid-val">#@{{ orderId }}</div>
                            </div>
                            <a href="{{ route('shop.customers.account.orders.index') }}"
                               style="display:flex;text-decoration:none"
                               class="co-btn-next">View my orders</a>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Sidebar: Order summary ── --}}
            <div class="co-right">
                <div class="co-summary">
                    <div class="co-sum-title">Order summary</div>

                    <div v-if="!cartLoaded" style="text-align:center;padding:20px;color:#bbb;font-size:13px;">
                        Loading…
                    </div>

                    <div class="co-sum-item" v-for="item in cartItems" :key="item.id">
                        <img class="co-sum-img"
                            :src="item.base_image && item.base_image.small_image_url ? item.base_image.small_image_url : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22%3E%3Crect width=%2256%22 height=%2256%22 fill=%22%23ece8e4%22/%3E%3C/svg%3E'"
                            :alt="item.name"
                            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22%3E%3Crect width=%2256%22 height=%2256%22 fill=%22%23ece8e4%22/%3E%3C/svg%3E'">
                        <div>
                            <div class="co-sum-item-name">@{{ item.name }}</div>
                            <div class="co-sum-item-meta">Qty: @{{ item.quantity }}</div>
                            <div class="co-sum-item-price">@{{ item.formatted_total || item.formatted_price }}</div>
                        </div>
                    </div>

                    <hr class="co-sum-divider">

                    <div class="co-sum-row"><span>Subtotal</span><span>@{{ cart.formatted_sub_total }}</span></div>
                    <div class="co-sum-row" v-if="Number(cart.discount_amount) > 0" style="color:#c7eb31">
                        <span>Discount<template v-if="cart.coupon_code"> (@{{ cart.coupon_code }})</template></span>
                        <span>− @{{ cart.formatted_discount_amount }}</span>
                    </div>
                    <div class="co-sum-row"><span>Shipping</span><span>@{{ selectedShipping ? (cart.formatted_shipping_amount || '—') : '—' }}</span></div>
                    <div class="co-sum-row"><span>Tax (GST)</span><span>@{{ cart.formatted_tax_total }}</span></div>
                    <div class="co-sum-total">
                        <span>Grand total</span>
                        <span>@{{ (selectedShipping || Number(cart.discount_amount) > 0) ? cart.formatted_grand_total : cart.formatted_sub_total }}</span>
                    </div>

                    <div class="co-sum-secure">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <rect x="1" y="5" width="10" height="7" rx="1.5" stroke="#bbb" stroke-width="1"/>
                            <path d="M3.5 5V3.5a2.5 2.5 0 015 0V5" stroke="#bbb" stroke-width="1" stroke-linecap="round"/>
                        </svg>
                        Secured · 256-bit SSL
                    </div>
                </div>
            </div>
        </div>
    </div>
    </script>

    <script type="module">
    app.component('v-checkout-new', {
        template: '#v-checkout-new-template',

        props: ['initialCart', 'initialItems', 'initialAddresses', 'countries', 'states'],

        data() {
            return {
                step: 1,
                cart: this.initialCart,
                cartItems: [],
                cartLoaded: false,

                addr: {
                    first_name : '{{ auth()->user()?->first_name ?? "" }}',
                    last_name  : '{{ auth()->user()?->last_name ?? "" }}',
                    email      : '{{ auth()->user()?->email && !str_contains(auth()->user()?->email ?? "", "@noreply.") ? auth()->user()->email : "" }}',
                    phone      : '{{ auth()->user()?->phone ?? "" }}',
                    address    : '',
                    city       : '',
                    postcode   : '',
                    state      : '',
                    country    : 'IN',
                },

                errors: {},
                sameAsBilling: true,
                deliveryNote: '',
                pincodeLoading: false,
                pincodeError: '',

                /* Saved address book */
                savedAddresses: this.initialAddresses || [],
                addressMode: 'form',            // 'list' | 'form' (set in created)
                selectedAddressId: null,
                editingAddressId: null,
                isLoggedIn: {{ auth()->guard('customer')->check() ? 'true' : 'false' }},
                accountFirstName: '{{ auth()->user()?->first_name ?? "" }}',
                accountLastName: '{{ auth()->user()?->last_name ?? "" }}',
                accountEmail: '{{ auth()->user()?->email && !str_contains(auth()->user()?->email ?? "", "@noreply.") ? auth()->user()->email : "" }}',
                accountPhone: '{{ auth()->user()?->phone ?? "" }}',

                /* OTP verification state */
                changingPhone: false,
                phoneVerified: {{ auth()->user()?->phone ? 'true' : 'false' }},
                verifiedPhone: '{{ auth()->user()?->phone ?? "" }}',  // the phone number currently proven via OTP
                otpSent: false,
                otpSending: false,
                otpVerifying: false,
                otpCode: '',
                otpError: '',
                otpCooldown: 0,
                otpTimer: null,

                shippingMethods: [],
                selectedShipping: null,
                savingShipping: false,
                loadingShipping: false,

                paymentMethods: [],
                selectedPayment: null,
                savingPayment: false,
                loadingPayment: false,

                couponCode: '',
                couponMessage: '',
                couponValid: false,
                couponApplying: false,

                savingAddress: false,
                isPlacing: false,
                orderId: null,

                /* reCAPTCHA v3 site key — empty string when captcha is disabled */
                recaptchaSiteKey: '{{ core()->getConfigData("customer.captcha.credentials.status") ? \Webkul\Customer\Facades\Captcha::getSiteKey() : "" }}',
            };
        },

        computed: {
            submitLabel() {
                if (this.editingAddressId) return 'Save & deliver here';
                return this.isLoggedIn ? 'Save & deliver here' : 'Continue to shipping';
            },
        },

        created() {
            /* Logged-in users with saved addresses start on the selection list with
               their default (or first) address pre-selected. Everyone else uses the form. */
            if (this.isLoggedIn && this.savedAddresses.length) {
                this.addressMode = 'list';
                const def = this.savedAddresses.find(a => a.default_address) || this.savedAddresses[0];
                this.selectSavedAddress(def);
            } else {
                this.addressMode = 'form';
            }
        },

        mounted() {
            this.fetchCart();
        },

        methods: {
            /* ── Saved address book ── */
            stateName(code) {
                const s = (this.states || []).find(x => x.code === code);
                return s ? s.default_name : (code || '');
            },

            formatAddress(a) {
                const lines = Array.isArray(a.address) ? a.address.filter(Boolean).join(', ') : (a.address || '');
                return [lines, a.city, a.postcode, this.stateName(a.state)].filter(Boolean).join(', ');
            },

            /* Populate the editable form fields from a saved address. */
            populateAddrFrom(a) {
                this.addr.first_name = a.first_name || '';
                this.addr.last_name  = a.last_name || '';
                this.addr.email      = a.email || this.accountEmail;
                this.addr.phone      = a.phone || '';
                this.addr.address    = Array.isArray(a.address) ? a.address.filter(Boolean).join(', ') : (a.address || '');
                this.addr.city       = a.city || '';
                this.addr.postcode   = a.postcode || '';
                this.addr.state      = a.state || '';
                this.addr.country    = 'IN';
            },

            selectSavedAddress(a) {
                this.selectedAddressId = a.id;
                this.populateAddrFrom(a);
            },

            /* One-click deliver from the list. Reuses the OTP gate: if the address phone
               is already verified this checkout, save & proceed; otherwise route to the
               form to verify that number (everything else stays pre-filled). */
            deliverToSelected() {
                const a = this.savedAddresses.find(x => x.id === this.selectedAddressId);
                if (! a) return;

                this.populateAddrFrom(a);
                this.errors = {};

                if (a.phone && a.phone === this.verifiedPhone) {
                    this.phoneVerified = true;
                    this.saveAddress();
                    return;
                }

                /* Needs phone verification — edit this saved record so re-verifying updates it. */
                this.editingAddressId = a.id;
                this.phoneVerified    = false;
                this.changingPhone    = true;   // surface the OTP flow for account-phone users
                this.otpSent          = false;
                this.otpCode          = '';
                this.otpError         = 'Please verify the mobile number for this address to continue.';
                this.addressMode      = 'form';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            showAddNewForm() {
                this.editingAddressId  = null;
                this.selectedAddressId = null;
                this.errors            = {};
                this.addr = {
                    first_name : this.accountFirstName,
                    last_name  : this.accountLastName,
                    email      : this.accountEmail,
                    phone      : this.accountPhone,
                    address    : '',
                    city       : '',
                    postcode   : '',
                    state      : '',
                    country    : 'IN',
                };
                this.phoneVerified = !! this.accountPhone;
                this.verifiedPhone = this.accountPhone;
                this.changingPhone = false;
                this.otpSent       = false;
                this.otpCode       = '';
                this.otpError      = '';
                this.addressMode   = 'form';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            editSavedAddress(a) {
                this.editingAddressId  = a.id;
                this.selectedAddressId = a.id;
                this.populateAddrFrom(a);
                this.errors        = {};
                this.phoneVerified = !! (a.phone && a.phone === this.verifiedPhone);
                this.changingPhone = ! this.phoneVerified;   // show OTP flow if this phone isn't verified yet
                this.otpSent       = false;
                this.otpCode       = '';
                this.otpError      = '';
                this.addressMode   = 'form';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            backToList() {
                this.addressMode   = 'list';
                this.editingAddressId = null;
                this.changingPhone = false;
                this.errors        = {};
                this.otpError      = '';
                /* Re-sync the form with the highlighted card so a re-entry is clean. */
                const a = this.savedAddresses.find(x => x.id === this.selectedAddressId);
                if (a) {
                    this.phoneVerified = !! (a.phone && a.phone === this.verifiedPhone);
                }
            },

            /* Persist the form's address to the customer's address book (best-effort —
               the order's address is already saved to the cart by saveAddress). */
            async persistToBook() {
                const payload = {
                    first_name : this.addr.first_name,
                    last_name  : this.addr.last_name,
                    email      : this.addr.email || this.accountEmail,
                    phone      : this.addr.phone || this.accountPhone,
                    address    : [this.addr.address],
                    city       : this.addr.city,
                    postcode   : this.addr.postcode,
                    state      : this.addr.state,
                    country    : 'IN',
                };

                if (this.editingAddressId) {
                    payload.id = this.editingAddressId;
                    const res = await this.$axios.put(
                        '{{ route("shop.api.customers.account.addresses.update", ["id" => "__ID__"]) }}'.replace('__ID__', this.editingAddressId),
                        payload
                    );
                    const updated = res.data.data;
                    const i = this.savedAddresses.findIndex(x => x.id === this.editingAddressId);
                    if (i !== -1) this.savedAddresses.splice(i, 1, updated);
                    this.selectedAddressId = this.editingAddressId;
                } else {
                    payload.default_address = this.savedAddresses.length === 0 ? 1 : 0;
                    const res = await this.$axios.post(
                        '{{ route("shop.api.customers.account.addresses.store") }}',
                        payload
                    );
                    const created = res.data.data;
                    this.savedAddresses.push(created);
                    this.selectedAddressId = created.id;
                }
            },

            /* Form submit: save to cart (validates + advances), then mirror to the book. */
            async submitAddressForm() {
                if (! this.phoneVerified) {
                    this.otpError = 'Please verify your mobile number before continuing.';
                    return;
                }

                const ok = await this.saveAddress();

                if (ok && this.isLoggedIn) {
                    try {
                        await this.persistToBook();
                    } catch (e) {
                        /* best-effort: cart address is already saved, never block checkout */
                    }
                }
            },

            async fetchCart() {
                try {
                    const res = await this.$axios.get('{{ route("shop.checkout.onepage.summary") }}');
                    const data = res.data.data;
                    this.cart      = data;
                    this.cartItems = data.items || [];
                } catch {
                    this.cartItems = this.initialItems || [];
                } finally {
                    this.cartLoaded = true;
                }
            },

            stepCls(n) {
                if (this.step > n)  return 'is-done';
                if (this.step === n) return 'is-active';
                return 'is-pending';
            },

            onPincodeInput() {
                const pin = this.addr.postcode.replace(/\D/g, '').slice(0, 6);
                this.addr.postcode = pin;
                this.pincodeError  = '';
                if (pin.length !== 6) return;
                this.pincodeLoading = true;
                fetch('https://api.postalpincode.in/pincode/' + pin)
                    .then(r => r.json())
                    .then(data => {
                        if (data[0]?.Status === 'Success' && data[0].PostOffice?.length) {
                            const po = data[0].PostOffice[0];
                            this.addr.city = po.District || po.Block || '';
                            const stateName    = po.State || '';
                            const matchedState = this.states.find(s =>
                                s.default_name &&
                                s.default_name.toLowerCase() === stateName.toLowerCase()
                            );
                            if (matchedState) this.addr.state = matchedState.code;
                        } else {
                            this.pincodeError = 'Invalid pincode.';
                        }
                    })
                    .catch(() => { this.pincodeError = 'Could not verify pincode.'; })
                    .finally(() => { this.pincodeLoading = false; });
            },

            async saveAddress() {
                if (! this.phoneVerified) {
                    this.otpError = 'Please verify your mobile number before continuing.';
                    return false;
                }
                this.errors      = {};
                this.savingAddress = true;
                try {
                    const base = {
                        first_name : this.addr.first_name,
                        last_name  : this.addr.last_name,
                        email      : this.addr.email || '{{ auth()->user()?->email ?? "" }}',
                        phone      : this.addr.phone || '{{ auth()->user()?->phone ?? "" }}',
                        address    : [this.addr.address],
                        city       : this.addr.city,
                        postcode   : this.addr.postcode,
                        state      : this.addr.state,
                        country    : 'IN',
                    };
                    const payload = {
                        billing: { ...base, use_for_shipping: this.sameAsBilling ? 1 : 0 },
                    };
                    if (!this.sameAsBilling) payload.shipping = { ...base };

                    const res = await this.$axios.post(
                        '{{ route("shop.checkout.onepage.addresses.store") }}',
                        payload
                    );

                    if (res.data.redirect || res.data.data?.redirect_url) {
                        window.location.href = res.data.data?.redirect_url || res.data.data;
                        return false;
                    }

                    /* Response: { data: { redirect, data: { shippingMethods: { carrier: { rates: [] } } } } } */
                    const rates = res.data.data;
                    const shippingData = rates?.data?.shippingMethods || rates?.shippingMethods;
                    this.shippingMethods = [];
                    if (shippingData && typeof shippingData === 'object') {
                        Object.values(shippingData).forEach(group => {
                            if (group.rates) this.shippingMethods.push(...group.rates);
                            else             this.shippingMethods.push(group);
                        });
                    }
                    /* Auto-select first available shipping method */
                    if (this.shippingMethods.length > 0) {
                        this.selectedShipping = this.shippingMethods[0].method;
                    }

                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return true;
                } catch (err) {
                    console.error('Address save error:', err.response?.data);
                    if (err.response?.data?.errors) {
                        this.errors = err.response.data.errors;
                    } else if (err.response?.data?.message) {
                        this.$emitter.emit('add-flash', { type: 'error', message: err.response.data.message });
                    } else {
                        this.$emitter.emit('add-flash', { type: 'error', message: 'Could not save address. Please check all fields.' });
                    }
                    return false;
                } finally {
                    this.savingAddress = false;
                }
            },

            async saveShipping() {
                this.savingShipping = true;
                try {
                    const res = await this.$axios.post(
                        '{{ route("shop.checkout.onepage.shipping_methods.store") }}',
                        { shipping_method: this.selectedShipping }
                    );
                    /* storeShippingMethod returns response()->json({payment_methods:[...]}) */
                    const data = res.data;
                    if (Array.isArray(data)) {
                        this.paymentMethods = data;
                    } else if (data?.payment_methods && Array.isArray(data.payment_methods)) {
                        this.paymentMethods = data.payment_methods;
                    } else if (data?.data && Array.isArray(data.data)) {
                        this.paymentMethods = data.data;
                    } else {
                        this.paymentMethods = [];
                    }
                    /* Auto-select COD if available, otherwise first method */
                    const cod = this.paymentMethods.find(m => m.method === 'cashondelivery');
                    this.selectedPayment = cod ? cod.method : (this.paymentMethods[0]?.method || null);
                    await this.fetchCart();
                    this.step = 3;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch {
                    this.$emitter.emit('add-flash', { type: 'error', message: 'Failed to save shipping method.' });
                } finally {
                    this.savingShipping = false;
                }
            },

            async savePayment() {
                this.savingPayment = true;
                try {
                    await this.$axios.post(
                        '{{ route("shop.checkout.onepage.payment_methods.store") }}',
                        { payment: { method: this.selectedPayment } }
                    );
                    await this.fetchCart();
                    this.step = 4;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch {
                    this.$emitter.emit('add-flash', { type: 'error', message: 'Failed to save payment method.' });
                } finally {
                    this.savingPayment = false;
                }
            },

            async applyCoupon() {
                if (!this.couponCode || this.couponApplying) return;
                this.couponApplying = true;
                try {
                    const res = await this.$axios.post(
                        '{{ route("shop.api.checkout.cart.coupon.apply") }}',
                        { code: this.couponCode.trim() }
                    );
                    await this.fetchCart();
                    // Only treat as success if the coupon actually stuck to the cart.
                    if (this.cart.coupon_code) {
                        this.couponValid   = true;
                        this.couponMessage = res.data.message || 'Coupon applied!';
                        this.couponCode    = '';
                    } else {
                        this.couponValid   = false;
                        this.couponMessage = res.data.message || 'This coupon could not be applied to your cart.';
                    }
                } catch (err) {
                    this.couponMessage = err.response?.data?.message || 'Invalid coupon code.';
                    this.couponValid   = false;
                } finally {
                    this.couponApplying = false;
                }
            },

            async removeCoupon() {
                if (this.couponApplying) return;
                this.couponApplying = true;
                try {
                    await this.$axios.delete('{{ route("shop.api.checkout.cart.coupon.remove") }}');
                    await this.fetchCart();
                    this.couponValid   = false;
                    this.couponMessage = 'Coupon removed.';
                } catch (err) {
                    this.couponMessage = err.response?.data?.message || 'Could not remove coupon.';
                } finally {
                    this.couponApplying = false;
                }
            },

            /* ── OTP methods ── */
            startChangePhone() {
                this.changingPhone  = true;
                this.phoneVerified  = false;
                this.addr.phone     = '';
                this.otpSent        = false;
                this.otpCode        = '';
                this.otpError       = '';
                clearInterval(this.otpTimer);
                this.otpCooldown    = 0;
            },

            cancelChangePhone() {
                this.changingPhone  = false;
                this.phoneVerified  = true;
                this.addr.phone     = '{{ auth()->user()?->phone ?? "" }}';
                this.verifiedPhone  = this.accountPhone;
                this.otpSent        = false;
                this.otpCode        = '';
                this.otpError       = '';
                clearInterval(this.otpTimer);
                this.otpCooldown    = 0;
            },

            onPhoneChanged() {
                if (this.phoneVerified || this.otpSent) {
                    this.phoneVerified = false;
                    this.otpSent       = false;
                    this.otpCode       = '';
                    this.otpError      = '';
                    clearInterval(this.otpTimer);
                    this.otpCooldown   = 0;
                }
            },

            resetPhone() {
                this.phoneVerified = false;
                this.otpSent       = false;
                this.otpCode       = '';
                this.otpError      = '';
            },

            async sendOtp() {
                if (this.addr.phone.length !== 10) {
                    this.otpError = 'Please enter a valid 10-digit mobile number.';
                    return;
                }
                this.otpSending = true;
                this.otpError   = '';
                try {
                    const res = await this.$axios.post('{{ route("shop.api.checkout.otp.send") }}', {
                        phone: this.addr.phone,
                    });
                    if (res.data.verified) {
                        this.phoneVerified = true;
                        this.verifiedPhone = this.addr.phone;
                    } else {
                        this.otpSent = true;
                        this.startOtpCooldown();
                    }
                } catch (err) {
                    this.otpError = err.response?.data?.message || 'Failed to send OTP. Try again.';
                } finally {
                    this.otpSending = false;
                }
            },

            async verifyOtp() {
                if (this.otpCode.length !== 6) {
                    this.otpError = 'Please enter the 6-digit OTP.';
                    return;
                }
                this.otpVerifying = true;
                this.otpError     = '';
                try {
                    await this.$axios.post('{{ route("shop.api.checkout.otp.verify") }}', {
                        phone: this.addr.phone,
                        otp:   this.otpCode,
                    });
                    this.phoneVerified  = true;
                    this.verifiedPhone = this.addr.phone;
                    this.otpSent       = false;
                    this.changingPhone = false;
                    clearInterval(this.otpTimer);
                } catch (err) {
                    this.otpError = err.response?.data?.message || 'Invalid OTP. Please try again.';
                } finally {
                    this.otpVerifying = false;
                }
            },

            startOtpCooldown() {
                this.otpCooldown = 30;
                clearInterval(this.otpTimer);
                this.otpTimer = setInterval(() => {
                    this.otpCooldown--;
                    if (this.otpCooldown <= 0) clearInterval(this.otpTimer);
                }, 1000);
            },

            /*
             * Mint a fresh reCAPTCHA v3 token at order time (v3 tokens expire
             * after ~2 min, so we cannot reuse one from page load). Resolves to
             * '' if captcha is disabled or grecaptcha is unavailable — the server
             * fails open in that case, so the order still goes through.
             */
            async getRecaptchaToken() {
                if (! this.recaptchaSiteKey || ! window.grecaptcha) {
                    return '';
                }
                try {
                    return await new Promise((resolve) => {
                        window.grecaptcha.ready(() => {
                            window.grecaptcha
                                .execute(this.recaptchaSiteKey, { action: 'checkout' })
                                .then(resolve)
                                .catch(() => resolve(''));
                        });
                    });
                } catch {
                    return '';
                }
            },

            async placeOrder() {
                this.isPlacing = true;
                try {
                    const payload = {};
                    const token = await this.getRecaptchaToken();
                    if (token) payload.recaptcha_token = token;

                    const res = await this.$axios.post(
                        '{{ route("shop.checkout.onepage.orders.store") }}',
                        payload
                    );
                    if (res.data.data?.redirect) {
                        window.location.href = res.data.data.redirect_url;
                    } else {
                        this.orderId = res.data.data?.order?.id || res.data.data?.order_id || '—';
                        this.step    = 5;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (err) {
                    const message = err.response?.data?.message || 'Something went wrong. Please try again.';
                    this.$emitter.emit('add-flash', { type: 'error', message });
                } finally {
                    this.isPlacing = false;
                }
            },
        },
    });
    </script>
    @endPushOnce

</x-shop::layouts>
