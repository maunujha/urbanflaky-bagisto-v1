@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')"/>
@endPush

@push('styles')
<style>
    * { box-sizing: border-box; }
    .co-page { background:#f4f4f4; min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }

    /* ── Nav ── */
    .co-nav { background:#1a1a1a; height:56px; display:flex; align-items:center; justify-content:space-between; padding:0 40px; position:sticky; top:0; z-index:100; }
    .co-nav-logo { color:#fff; font-size:18px; font-weight:700; letter-spacing:.5px; text-decoration:none; }
    .co-nav-steps { display:flex; align-items:center; gap:4px; }
    .co-step-item { display:flex; align-items:center; gap:8px; padding:0 14px; height:56px; border-bottom:2px solid transparent; transition:border-color .2s; text-decoration:none; }
    .co-step-item.is-active { border-bottom-color:#fff; }
    .co-step-item.is-done   { border-bottom-color:rgba(255,255,255,.35); }
    .co-step-item.is-pending{ opacity:.4; pointer-events:none; }
    .co-step-num { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; }
    .is-active  .co-step-num { background:#fff; color:#1a1a1a; }
    .is-done    .co-step-num { background:rgba(255,255,255,.2); color:#fff; }
    .is-pending .co-step-num { background:rgba(255,255,255,.1); color:rgba(255,255,255,.5); }
    .co-step-lbl { font-size:13px; font-weight:500; }
    .is-active  .co-step-lbl { color:#fff; }
    .is-done    .co-step-lbl { color:rgba(255,255,255,.6); }
    .is-pending .co-step-lbl { color:rgba(255,255,255,.3); }
    .co-step-sep { width:24px; height:1px; background:rgba(255,255,255,.15); }
    .co-nav-secure { display:flex; align-items:center; gap:6px; font-size:12px; color:rgba(255,255,255,.45); }

    /* ── Layout ── */
    .co-body { display:grid; grid-template-columns:1fr 360px; gap:28px; max-width:1100px; margin:0 auto; padding:32px 40px; align-items:start; }

    /* ── Cards ── */
    .co-card { background:#fff; border-radius:16px; border:.5px solid #e0e0e0; padding:28px; margin-bottom:20px; }
    .co-card-title { font-size:17px; font-weight:700; color:#1a1a1a; margin-bottom:4px; }
    .co-card-sub   { font-size:13px; color:#999; margin-bottom:22px; }

    /* ── Fields ── */
    .co-field { margin-bottom:16px; }
    .co-label { display:block; font-size:11px; font-weight:700; color:#666; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .co-input, .co-select { width:100%; height:44px; border:1.5px solid #e8e8e8; border-radius:10px; padding:0 14px; font-size:14px; color:#1a1a1a; background:#fafafa; outline:none; transition:border-color .15s,background .15s; appearance:none; }
    .co-input:focus, .co-select:focus { border-color:#1a1a1a; background:#fff; }
    .co-input.is-prefilled { background:#f2f2f2; color:#666; border-color:#e5e5e5; cursor:default; }
    .co-input.has-error { border-color:#e53935; }
    .co-error-msg { font-size:12px; color:#e53935; margin-top:4px; }
    .co-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .co-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
    .co-phone-row { display:flex; align-items:center; gap:10px; height:44px; padding:0 14px; background:#f2f2f2; border:1.5px solid #e5e5e5; border-radius:10px; }
    .co-phone-badge { margin-left:auto; background:#e8f5e9; color:#2e7d32; font-size:10px; font-weight:700; padding:2px 10px; border-radius:20px; }
    .co-cb-row { display:flex; align-items:center; gap:10px; padding:12px 14px; background:#f7f7f7; border-radius:10px; cursor:pointer; margin-top:6px; }
    .co-cb-box { width:18px; height:18px; border:1.5px solid #ccc; border-radius:4px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .15s,border-color .15s; }
    .co-cb-box.is-checked { background:#1a1a1a; border-color:#1a1a1a; }
    .co-cb-text { font-size:13px; color:#444; }

    /* ── Shipping ── */
    .co-ship-opts { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
    .co-ship-opt { display:flex; align-items:center; gap:16px; border:1.5px solid #e8e8e8; border-radius:12px; padding:16px; cursor:pointer; transition:border-color .15s,background .15s; }
    .co-ship-opt.is-selected { border-color:#1a1a1a; background:#fafafa; }
    .co-radio { width:20px; height:20px; border-radius:50%; border:2px solid #ccc; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:background .15s,border-color .15s; }
    .co-radio.is-on { border-color:#1a1a1a; background:#1a1a1a; }
    .co-radio-dot { width:8px; height:8px; border-radius:50%; background:#fff; }
    .co-ship-info { flex:1; }
    .co-ship-name { font-size:14px; font-weight:600; color:#1a1a1a; }
    .co-ship-eta  { font-size:12px; color:#999; margin-top:2px; }
    .co-ship-price { font-size:14px; font-weight:700; color:#1a1a1a; }
    .co-ship-price.is-free { color:#2e7d32; }

    /* ── Payment ── */
    .co-pay-opts { display:flex; flex-direction:column; gap:10px; margin-bottom:22px; }
    .co-pay-opt { display:flex; align-items:center; gap:14px; border:1.5px solid #e8e8e8; border-radius:12px; padding:14px 16px; cursor:pointer; transition:border-color .15s; }
    .co-pay-opt.is-selected { border-color:#1a1a1a; }
    .co-pay-icon { width:44px; height:28px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#555; flex-shrink:0; }
    .co-pay-name { font-size:14px; font-weight:600; color:#1a1a1a; }
    .co-pay-desc { font-size:12px; color:#aaa; margin-top:2px; }

    /* ── Coupon ── */
    .co-coupon-row { display:flex; gap:10px; }
    .co-coupon-input { flex:1; height:44px; border:1.5px solid #e8e8e8; border-radius:10px; padding:0 14px; font-size:14px; background:#fafafa; outline:none; transition:border-color .15s; }
    .co-coupon-input:focus { border-color:#1a1a1a; }
    .co-coupon-btn { height:44px; padding:0 20px; background:#1a1a1a; color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; }
    .co-coupon-btn:hover { opacity:.85; }

    /* ── Buttons ── */
    .co-btn-row { display:grid; grid-template-columns:auto 1fr; gap:12px; margin-top:8px; }
    .co-btn-row.first-step { grid-template-columns:1fr; }
    .co-btn-back { height:52px; padding:0 24px; background:#fff; color:#1a1a1a; border:1.5px solid #1a1a1a; border-radius:12px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .co-btn-back:hover { background:#f5f5f5; }
    .co-btn-next { height:52px; background:#1a1a1a; color:#fff; border:none; border-radius:12px; font-size:15px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
    .co-btn-next:hover { opacity:.88; }
    .co-btn-next:disabled { opacity:.5; cursor:not-allowed; }

    /* ── Review ── */
    .co-review-addr { background:#f7f7f7; border-radius:12px; padding:14px; margin-bottom:16px; }
    .co-review-addr-title { font-size:11px; font-weight:700; color:#aaa; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .co-review-addr-body { font-size:13px; color:#444; line-height:1.7; }
    .co-review-item { display:flex; gap:14px; padding:14px; background:#f7f7f7; border-radius:12px; margin-bottom:14px; }
    .co-review-img { width:56px; height:56px; background:#ece8e4; border-radius:8px; object-fit:cover; flex-shrink:0; }
    .co-review-item-name { font-size:13px; font-weight:600; color:#1a1a1a; line-height:1.4; }
    .co-review-item-meta { font-size:12px; color:#999; margin-top:3px; }
    .co-review-item-price { font-size:14px; font-weight:700; color:#1a1a1a; }
    .co-totals-row { display:flex; justify-content:space-between; font-size:13px; color:#888; margin-bottom:7px; }
    .co-totals-row span:last-child { color:#1a1a1a; font-weight:500; }
    .co-totals-total { display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:#1a1a1a; margin-top:12px; padding-top:12px; border-top:.5px solid #e8e8e8; }

    /* ── Sidebar ── */
    .co-summary { background:#fff; border-radius:16px; border:.5px solid #e0e0e0; padding:22px; position:sticky; top:76px; }
    .co-sum-title { font-size:14px; font-weight:700; color:#1a1a1a; margin-bottom:16px; padding-bottom:14px; border-bottom:.5px solid #ececec; }
    .co-sum-item { display:flex; gap:12px; margin-bottom:16px; }
    .co-sum-img { width:54px; height:54px; border-radius:8px; object-fit:cover; background:#ece8e4; flex-shrink:0; }
    .co-sum-item-name { font-size:13px; font-weight:600; color:#1a1a1a; line-height:1.4; }
    .co-sum-item-meta { font-size:12px; color:#aaa; margin-top:2px; }
    .co-sum-item-price { font-size:13px; font-weight:700; color:#1a1a1a; margin-top:5px; }
    .co-sum-divider { border:none; border-top:.5px solid #ececec; margin:14px 0; }
    .co-sum-row { display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px; }
    .co-sum-row span:first-child { color:#999; }
    .co-sum-total { display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:#1a1a1a; margin-top:12px; padding-top:12px; border-top:.5px solid #ececec; }
    .co-sum-secure { display:flex; align-items:center; gap:6px; margin-top:14px; padding-top:12px; border-top:.5px solid #ececec; font-size:11px; color:#bbb; }

    /* ── Success ── */
    .co-success { text-align:center; padding:36px 24px; }
    .co-success-icon { width:68px; height:68px; background:#1a1a1a; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; }
    .co-success-title { font-size:24px; font-weight:700; color:#1a1a1a; margin-bottom:8px; }
    .co-success-sub { font-size:14px; color:#888; line-height:1.7; margin-bottom:24px; }
    .co-success-oid { background:#f5f5f5; border-radius:12px; padding:16px; text-align:left; margin-bottom:20px; }
    .co-success-oid-label { font-size:11px; color:#aaa; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
    .co-success-oid-val { font-size:20px; font-weight:700; color:#1a1a1a; }

    /* ── Responsive ── */
    @media(max-width:768px) {
        .co-nav { padding:0 16px; }
        .co-nav-steps { display:none; }
        .co-body { grid-template-columns:1fr; padding:16px; gap:16px; }
        .co-right { order:-1; }
        .co-summary { position:static; }
        .co-grid-3 { grid-template-columns:1fr 1fr; }
        .co-card { padding:18px; }
        .co-btn-row { grid-template-columns:1fr 2fr; }
    }
    @media(max-width:480px) {
        .co-grid-2, .co-grid-3 { grid-template-columns:1fr; }
        .co-btn-row { grid-template-columns:1fr; }
        .co-btn-back { display:none; }
    }
</style>
@endpush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>Checkout</x-slot>

    <v-checkout-new
        :initial-cart='@json($cart)'
        :initial-items='@json($cartItems)'
        :countries='@json($countries)'
        :states='@json($states)'
    ></v-checkout-new>

    @pushOnce('scripts')
    <script type="text/x-template" id="v-checkout-new-template">
    <div class="co-page">

        {{-- Nav --}}
        <nav class="co-nav">
            <a href="{{ route('shop.home.index') }}" class="co-nav-logo">
                <img src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                     alt="{{ config('app.name') }}" style="height:28px;width:auto;filter:brightness(0) invert(1);">
            </a>
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
                    <div class="co-card">
                        <div class="co-card-title">Delivery address</div>
                        <div class="co-card-sub">Where should we deliver your order?</div>

                        {{-- Mobile: editable if no phone on profile, locked if exists --}}
                        <div class="co-field">
                            <label class="co-label">Mobile number *</label>
                            @if(auth()->user()?->phone)
                                <div class="co-phone-row">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M2 1.5h3l1.5 3.5-2 1.5C5.5 8.5 6.5 9.5 8.5 10.5l1.5-2 3.5 1.5v3C13.5 13.5 8 14.5 4 10.5S.5 1.5 2 1.5z" fill="#888"/>
                                    </svg>
                                    <span style="font-size:14px;color:#555">{{ auth()->user()->phone }}</span>
                                    <span class="co-phone-badge">Auto-filled</span>
                                </div>
                            @else
                                <input class="co-input" :class="{'has-error': errors['billing.phone']}"
                                    type="tel" v-model="addr.phone" maxlength="10"
                                    inputmode="numeric" placeholder="Enter 10-digit mobile number">
                                <div class="co-error-msg" v-if="errors['billing.phone']">@{{ errors['billing.phone'][0] }}</div>
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
                                <div v-if="pincodeLoading" style="font-size:11px;color:#999;margin-top:4px;">Looking up pincode…</div>
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
                                <span style="font-size:14px;color:#555;font-weight:500;">India</span>
                                <span class="co-phone-badge">Default</span>
                            </div>
                        </div>

                        <div class="co-cb-row" @click="sameAsBilling = !sameAsBilling">
                            <div class="co-cb-box" :class="{'is-checked': sameAsBilling}">
                                <svg v-if="sameAsBilling" width="10" height="8" viewBox="0 0 10 8" fill="none">
                                    <path d="M1 4l3 3 5-6" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="co-cb-text">Use same address for billing</span>
                        </div>
                    </div>

                    <div class="co-btn-row first-step">
                        <button class="co-btn-next" @click="saveAddress" :disabled="savingAddress">
                            <span v-if="savingAddress">Saving…</span>
                            <span v-else>Continue to shipping</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── Step 2: Shipping ── --}}
                <div v-show="step === 2">
                    <div class="co-card">
                        <div class="co-card-title">Shipping method</div>
                        <div class="co-card-sub">Choose your preferred delivery speed</div>

                        <div v-if="loadingShipping" style="text-align:center;padding:24px;color:#999;font-size:14px;">
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
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── Step 3: Payment ── --}}
                <div v-show="step === 3">
                    <div class="co-card">
                        <div class="co-card-title">Payment method</div>
                        <div class="co-card-sub">All transactions are 100% secure and encrypted</div>

                        <div v-if="loadingPayment" style="text-align:center;padding:24px;color:#999;font-size:14px;">
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
                            <div class="co-coupon-row">
                                <input class="co-coupon-input" type="text" v-model="couponCode" placeholder="Enter coupon code" @keyup.enter="applyCoupon">
                                <button class="co-coupon-btn" @click="applyCoupon">Apply</button>
                            </div>
                            <div v-if="couponMessage" style="font-size:12px;margin-top:6px" :style="{color: couponValid ? '#2e7d32' : '#e53935'}">
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
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
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
                        <div class="co-totals-row"><span>Shipping</span><span>@{{ cart.formatted_shipping_amount || 'Calculated' }}</span></div>
                        <div class="co-totals-row"><span>Tax (GST)</span><span>@{{ cart.formatted_tax_total }}</span></div>
                        <div class="co-totals-total"><span>Grand total</span><span>@{{ cart.formatted_grand_total }}</span></div>
                    </div>

                    <div class="co-btn-row">
                        <button class="co-btn-back" @click="step = 3">← Back</button>
                        <button class="co-btn-next" @click="placeOrder" :disabled="isPlacing">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="5" width="12" height="9" rx="2" stroke="#fff" stroke-width="1.3"/>
                                <path d="M5 5V4a3 3 0 016 0v1" stroke="#fff" stroke-width="1.3" stroke-linecap="round"/>
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
                                    <path d="M6 15l7 7 11-11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
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
                    <div class="co-sum-row"><span>Shipping</span><span>@{{ selectedShipping ? (cart.formatted_shipping_amount || '—') : '—' }}</span></div>
                    <div class="co-sum-row"><span>Tax (GST)</span><span>@{{ cart.formatted_tax_total }}</span></div>
                    <div class="co-sum-total">
                        <span>Grand total</span>
                        <span>@{{ selectedShipping ? cart.formatted_grand_total : cart.formatted_sub_total }}</span>
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

        props: ['initialCart', 'initialItems', 'countries', 'states'],

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

                savingAddress: false,
                isPlacing: false,
                orderId: null,
            };
        },

        mounted() {
            this.fetchCart();
        },

        methods: {
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
                        return;
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
                } catch (err) {
                    console.error('Address save error:', err.response?.data);
                    if (err.response?.data?.errors) {
                        this.errors = err.response.data.errors;
                    } else if (err.response?.data?.message) {
                        this.$emitter.emit('add-flash', { type: 'error', message: err.response.data.message });
                    } else {
                        this.$emitter.emit('add-flash', { type: 'error', message: 'Could not save address. Please check all fields.' });
                    }
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
                if (!this.couponCode) return;
                try {
                    const res = await this.$axios.post(
                        '{{ route("shop.api.checkout.cart.coupon.apply") }}',
                        { code: this.couponCode }
                    );
                    this.couponValid   = true;
                    this.couponMessage = res.data.message || 'Coupon applied!';
                    await this.fetchCart();
                } catch (err) {
                    this.couponMessage = err.response?.data?.message || 'Invalid coupon code.';
                    this.couponValid   = false;
                }
            },

            async placeOrder() {
                this.isPlacing = true;
                try {
                    const res = await this.$axios.post(
                        '{{ route("shop.checkout.onepage.orders.store") }}'
                    );
                    if (res.data.data?.redirect) {
                        window.location.href = res.data.data.redirect_url;
                    } else {
                        this.orderId = res.data.data?.order?.id || res.data.data?.order_id || '—';
                        this.step    = 5;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch {
                    this.$emitter.emit('add-flash', { type: 'error', message: 'Something went wrong. Please try again.' });
                } finally {
                    this.isPlacing = false;
                }
            },
        },
    });
    </script>
    @endPushOnce

</x-shop::layouts>
