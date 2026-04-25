@push('meta')
    <meta name="description" content="Create your account"/>
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>
        {{ session('show_otp_modal') ? 'Verify OTP' : 'Create Account' }}
    </x-slot>

    @php $showOtp = (bool) session('show_otp_modal'); @endphp

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/otp-signup.css') }}">
    @endpush

    <div class="su-wrap">
        <div class="su-outer">

            {{-- Logo --}}
            <div class="su-logo-wrap">
                <a href="{{ route('shop.home.index') }}">
                    <img
                        src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    >
                </a>
            </div>

            <div class="su-card">

                {{-- ══════════════════════════ --}}
                {{-- STEP 1 · Phone + Name     --}}
                {{-- ══════════════════════════ --}}
                <div id="step-1" style="{{ $showOtp ? 'display:none' : '' }}">

                    <div class="su-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.5 3.75 5.25v6c0 5.12 3.52 9.91 8.25 11.25 4.73-1.34 8.25-6.13 8.25-11.25v-6L12 1.5Z" clip-rule="evenodd"/>
                        </svg>
                        Join {{ config('app.name') }}
                    </div>

                    <h1 class="su-h1">Create your account</h1>
                    <p class="su-sub">Fast, secure &amp; hassle-free shopping</p>

                    @if ($errors->any())
                        <div class="su-error">{{ $errors->first() }}</div>
                    @endif
                    @if (session('error') && ! $showOtp)
                        <div class="su-error">{{ session('error') }}</div>
                    @endif

                    <form id="step1-form" action="{{ route('shop.customers.register.store') }}" method="POST" novalidate>
                        @csrf

                        {{-- Name --}}
                        <p class="su-label">1. Your Details</p>
                        <div class="su-name-row">
                            <input
                                id="first_name"
                                type="text"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                placeholder="First name *"
                                class="su-input"
                                required
                            />
                            <input
                                id="last_name"
                                type="text"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                placeholder="Last name *"
                                class="su-input"
                                required
                            />
                        </div>

                        {{-- Phone --}}
                        <p class="su-label">2. Continue with Phone + OTP</p>
                        <p class="su-hint">We'll send a 4-digit OTP to verify your number</p>

                        <div class="su-phone-wrap">
                            <div class="su-phone-cc">🇮🇳 +91</div>
                            <input
                                id="phone"
                                type="tel"
                                name="phone"
                                value="{{ old('phone') }}"
                                maxlength="10"
                                inputmode="numeric"
                                placeholder="Enter your mobile number"
                                autocomplete="tel"
                                class="su-phone-input"
                            />
                        </div>
                        <p id="phone-error" class="su-phone-err"></p>

                        <button id="send-otp-btn" type="submit" class="su-btn">
                            <span id="send-spinner" class="su-spin"></span>
                            <span id="send-otp-text">Send OTP</span>
                        </button>
                    </form>

                    <div class="su-divider">OR</div>

                    <p class="su-signin">
                        Already have an account?
                        <a href="{{ route('shop.customer.session.index') }}">Sign in</a>
                    </p>
                </div>

                {{-- ══════════════════════════ --}}
                {{-- STEP 2 · OTP Verify       --}}
                {{-- ══════════════════════════ --}}
                <div id="step-2" style="{{ $showOtp ? '' : 'display:none' }}">

                    <div class="su-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M1.5 8.25a.75.75 0 0 1 .75-.75h19.5a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1-.75-.75Z"/>
                        </svg>
                        Verify Number
                    </div>

                    <h1 class="su-h1">Enter OTP</h1>

                    @php
                        $rawPhone = session('signup_pending.phone', '');
                        $masked   = $rawPhone ? '+91 ••••••' . substr($rawPhone, -4) : '';
                    @endphp

                    <p class="su-sub">Sent to <strong style="color:#111827;">{{ $masked }}</strong></p>

                    @if (session('error') && $showOtp)
                        <div class="su-error">{{ session('error') }}</div>
                    @endif
                    @if (session('success') && $showOtp)
                        <div class="su-success">{{ session('success') }}</div>
                    @endif

                    <form id="step2-form" action="{{ route('shop.customers.register.verify.phone.store') }}" method="POST">
                        @csrf

                        <div class="otp-boxes">
                            <input id="otp-0" type="tel" maxlength="1" inputmode="numeric" placeholder="0" class="otp-box" autocomplete="one-time-code"/>
                            <input id="otp-1" type="tel" maxlength="1" inputmode="numeric" placeholder="0" class="otp-box" autocomplete="off"/>
                            <input id="otp-2" type="tel" maxlength="1" inputmode="numeric" placeholder="0" class="otp-box" autocomplete="off"/>
                            <input id="otp-3" type="tel" maxlength="1" inputmode="numeric" placeholder="0" class="otp-box" autocomplete="off"/>
                        </div>
                        <input type="hidden" name="otp" id="hidden-otp"/>

                        <button id="verify-btn" type="submit" class="su-btn" style="opacity:.4;pointer-events:none;cursor:not-allowed;">
                            <span id="verify-spinner" class="su-spin"></span>
                            <span id="verify-text">Verify OTP</span>
                        </button>
                    </form>

                    <div class="su-resend-row">
                        <a href="{{ route('shop.customers.register.index') }}?reset=1" class="su-change-link">← Change number</a>

                        <span id="resend-countdown" class="su-countdown">
                            Resend in <span id="countdown-val">30</span>s
                        </span>

                        <form id="resend-form" class="su-resend-form" style="display:none;" action="{{ route('shop.customers.register.resend.phone.otp') }}" method="POST">
                            @csrf
                            <button type="submit" class="su-resend-btn">Resend OTP</button>
                        </form>
                    </div>
                </div>

            </div>{{-- /card --}}

            <p class="su-footer">
                @lang('shop::app.customers.signup-form.footer', ['current_year' => date('Y')])
            </p>
        </div>
    </div>

    @push('scripts')
    <script>
    /* ─────────────────────────────────────────────────────────
       All OTP box events use document-level delegation so they
       survive Vue's DOM replacement (app.mount runs AFTER our
       scripts in Bagisto's layout).
    ───────────────────────────────────────────────────────── */

    /* Helper: read all .otp-box values as a string */
    function otpVal() {
        return Array.from(document.querySelectorAll('.otp-box'))
            .map(function(b){ return b.value || ''; }).join('');
    }

    /* Helper: update verify button + hidden field */
    function syncOtpBtn() {
        var val    = otpVal();
        var hidden = document.getElementById('hidden-otp');
        var btn    = document.getElementById('verify-btn');
        if (hidden) hidden.value = val;
        if (!btn) return;
        var ok = val.length === 4;
        btn.style.opacity       = ok ? '1'           : '0.4';
        btn.style.pointerEvents = ok ? 'auto'        : 'none';
        btn.style.cursor        = ok ? 'pointer'     : 'not-allowed';
    }

    /* OTP: input (digit entry + auto-advance) */
    document.addEventListener('input', function(e) {
        var el = e.target;
        if (!el.classList.contains('otp-box')) return;
        el.value = el.value.replace(/\D/g, '').slice(-1);
        syncOtpBtn();
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        var idx   = boxes.indexOf(el);
        if (el.value && idx < boxes.length - 1) boxes[idx + 1].focus();
    });

    /* OTP: keydown (backspace, arrows, digit-replace, block non-digits) */
    document.addEventListener('keydown', function(e) {
        var el = e.target;
        if (!el.classList.contains('otp-box')) return;
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        var idx   = boxes.indexOf(el);

        if (e.key === 'Backspace') {
            e.preventDefault();
            if (el.value) { el.value = ''; }
            else if (idx > 0) { boxes[idx - 1].value = ''; boxes[idx - 1].focus(); }
            syncOtpBtn();
            return;
        }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); if (idx > 0) boxes[idx - 1].focus(); return; }
        if (e.key === 'ArrowRight') { e.preventDefault(); if (idx < boxes.length - 1) boxes[idx + 1].focus(); return; }
        if (e.key.length === 1 && !/\d/.test(e.key)) { e.preventDefault(); return; }
        if (/\d/.test(e.key) && el.value) el.value = ''; /* clear before new digit */
    });

    /* OTP: paste */
    document.addEventListener('paste', function(e) {
        var el = e.target;
        if (!el.classList.contains('otp-box')) return;
        e.preventDefault();
        var p     = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        p.split('').forEach(function(c, i){ if (boxes[i]) boxes[i].value = c; });
        syncOtpBtn();
        boxes[Math.min(p.length, boxes.length - 1)].focus();
    });

    /* OTP: click → select so user can retype */
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('otp-box')) e.target.select();
    });

    /* Step-2 form submit */
    document.addEventListener('submit', function(e) {
        if (!e.target || e.target.id !== 'step2-form') return;
        var val    = otpVal();
        var hidden = document.getElementById('hidden-otp');
        if (hidden) hidden.value = val;
        if (val.length < 4) { e.preventDefault(); return; }
        var btn = document.getElementById('verify-btn');
        var spn = document.getElementById('verify-spinner');
        var txt = document.getElementById('verify-text');
        if (btn) { btn.style.opacity = '0.6'; btn.style.pointerEvents = 'none'; }
        if (spn) spn.style.display = 'inline-block';
        if (txt) txt.textContent = 'Verifying…';
    });

    /* Step-1 phone validation + submit */
    document.addEventListener('submit', function(e) {
        if (!e.target || e.target.id !== 'step1-form') return;
        var phoneEl  = document.getElementById('phone');
        var phoneErr = document.getElementById('phone-error');
        var v = phoneEl ? phoneEl.value.replace(/\D/g, '') : '';
        if (v.length !== 10 || !/^[6-9]/.test(v)) {
            e.preventDefault();
            if (phoneErr) {
                phoneErr.textContent = v.length < 10
                    ? 'Enter a valid 10-digit mobile number.'
                    : 'Number must start with 6, 7, 8 or 9.';
                phoneErr.style.display = 'block';
            }
            return;
        }
        var btn = document.getElementById('send-otp-btn');
        var spn = document.getElementById('send-spinner');
        var txt = document.getElementById('send-otp-text');
        if (btn) { btn.style.opacity = '0.6'; btn.style.pointerEvents = 'none'; }
        if (spn) spn.style.display = 'inline-block';
        if (txt) txt.textContent = 'Sending…';
    });

    /* Phone: live digit filter */
    document.addEventListener('input', function(e) {
        var el = e.target;
        if (el.id !== 'phone') return;
        el.value = el.value.replace(/\D/g, '').slice(0, 10);
        var err = document.getElementById('phone-error');
        if (err && el.value.length === 10 && /^[6-9]/.test(el.value)) err.style.display = 'none';
    });

    /* localStorage: save name fields */
    document.addEventListener('input', function(e) {
        var el = e.target;
        if (el.id === 'first_name') localStorage.setItem('uf_first_name', el.value);
        if (el.id === 'last_name')  localStorage.setItem('uf_last_name',  el.value);
    });

    /* localStorage: restore name fields as soon as DOM is ready */
    (function restoreNames() {
        var fn = localStorage.getItem('uf_first_name');
        var ln = localStorage.getItem('uf_last_name');
        var fnEl = document.getElementById('first_name');
        var lnEl = document.getElementById('last_name');
        if (fn && fnEl && !fnEl.value) fnEl.value = fn;
        if (ln && lnEl && !lnEl.value) lnEl.value = ln;
    })();

    /* ── OTP step only: auto-focus + countdown + refresh redirect ── */
    @if(session('show_otp_modal'))

    /* Refresh → back to step 1 */
    if (window.performance) {
        var nav = performance.getEntriesByType('navigation');
        if (nav.length && nav[0].type === 'reload') {
            window.location.replace('{{ route("shop.customers.register.index") }}?reset=1');
        }
    }

    /* Auto-focus: run after Vue mounts (Vue mounts on window.load, so we wait for it) */
    window.addEventListener('load', function() {
        var box0 = document.getElementById('otp-0');
        if (box0) box0.focus();

        /* 30s countdown */
        var secs   = 30;
        var cdVal  = document.getElementById('countdown-val');
        var cdWrap = document.getElementById('resend-countdown');
        var rfForm = document.getElementById('resend-form');

        var timer = setInterval(function() {
            secs = secs - 1;
            if (cdVal) cdVal.textContent = secs;
            if (secs <= 0) {
                clearInterval(timer);
                if (cdWrap) cdWrap.style.display = 'none';
                if (rfForm) rfForm.style.display  = 'block';
            }
        }, 1000);
    });

    @endif
    </script>
    @endpush

</x-shop::layouts>
