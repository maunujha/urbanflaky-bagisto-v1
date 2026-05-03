@push('meta')
    <meta name="description" content="Sign in to your account"/>
@endPush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/otp-signup.css') }}">
@endpush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>
        {{ session('show_login_otp') ? 'Verify OTP' : 'Sign In' }}
    </x-slot>

    @php $showOtp = (bool) session('show_login_otp'); @endphp

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

                {{-- ══════════════════════════════ --}}
                {{-- STEP 1 · Phone Input           --}}
                {{-- ══════════════════════════════ --}}
                <div id="step-1" style="{{ $showOtp ? 'display:none' : '' }}">

                    <div class="su-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.5 3.75 5.25v6c0 5.12 3.52 9.91 8.25 11.25 4.73-1.34 8.25-6.13 8.25-11.25v-6L12 1.5Z" clip-rule="evenodd"/>
                        </svg>
                        Welcome back
                    </div>

                    <h1 class="su-h1">Sign in to your account</h1>
                    <p class="su-sub">Enter your mobile number to receive an OTP</p>

                    @if (session('error') && ! $showOtp)
                        <div class="su-error">{{ session('error') }}</div>
                    @endif
                    @if (session('warning') && ! $showOtp)
                        <div class="su-error">{{ session('warning') }}</div>
                    @endif

                    <form id="login-otp-form" action="{{ route('shop.customer.otp.send') }}" method="POST" novalidate>
                        @csrf

                        <p class="su-label" style="margin-top:8px;">Continue with Phone + OTP</p>
                        <p class="su-hint">We'll send a 4-digit OTP to verify your number</p>

                        <div class="su-phone-wrap">
                            <div class="su-phone-cc">🇮🇳 +91</div>
                            <input
                                id="login-phone"
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
                        <p id="login-phone-error" class="su-phone-err"></p>

                        @if (core()->getConfigData('customer.captcha.credentials.status'))
                            {!! \Webkul\Customer\Facades\Captcha::render() !!}
                        @endif

                        <button id="login-send-btn" type="submit" class="su-btn">
                            <span id="login-send-spinner" class="su-spin"></span>
                            <span id="login-send-text">Send OTP</span>
                        </button>
                    </form>

                    <div class="su-divider">OR</div>

                    {{-- Google login --}}
                    @if(config('services.google.client_id'))
                    <a href="{{ route('shop.customer.google.redirect') }}" class="su-google-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg>
                        Continue with Google
                    </a>
                    @else
                    <button class="su-google-btn" disabled title="Coming soon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg>
                        Continue with Google
                    </button>
                    @endif

                    <p class="su-signin" style="margin-top:20px;">
                        New customer?
                        <a href="{{ route('shop.customers.register.index') }}">Create an account</a>
                    </p>
                </div>

                {{-- ══════════════════════════════ --}}
                {{-- STEP 2 · OTP Verification      --}}
                {{-- ══════════════════════════════ --}}
                <div id="step-2" style="{{ $showOtp ? '' : 'display:none' }}">

                    <div class="su-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.5 3.75 5.25v6c0 5.12 3.52 9.91 8.25 11.25 4.73-1.34 8.25-6.13 8.25-11.25v-6L12 1.5Z" clip-rule="evenodd"/>
                        </svg>
                        Verify Number
                    </div>

                    <h1 class="su-h1">Enter OTP</h1>

                    @php
                        $loginPhone = session('otp_phone', '');
                        $maskedLogin = $loginPhone ? '+91 ••••••' . substr($loginPhone, -4) : '';
                    @endphp

                    <p class="su-sub">Sent to <strong style="color:#111827;">{{ $maskedLogin }}</strong></p>

                    @if (session('error') && $showOtp)
                        <div class="su-error">{{ session('error') }}</div>
                    @endif
                    @if (session('success') && $showOtp)
                        <div class="su-success">{{ session('success') }}</div>
                    @endif

                    <form id="login-otp-verify-form" action="{{ route('shop.customer.otp.verify.store') }}" method="POST">
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
                        <a href="{{ route('shop.customer.session.index') }}?reset=1" class="su-change-link">← Change number</a>

                        <span id="resend-countdown" class="su-countdown">
                            Resend in <span id="countdown-val">30</span>s
                        </span>

                        <form id="resend-form" class="su-resend-form" style="display:none;" action="{{ route('shop.customer.otp.resend') }}" method="POST">
                            @csrf
                            <button type="submit" class="su-resend-btn">Resend OTP</button>
                        </form>
                    </div>
                </div>

            </div>{{-- /card --}}

            <p class="su-footer">
                @lang('shop::app.customers.login-form.footer', ['current_year' => date('Y')])
            </p>
        </div>
    </div>

    @push('scripts')
    {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
    <script>
    /* ── document-level delegation (survives Vue's app.mount) ── */

    function otpVal() {
        return Array.from(document.querySelectorAll('.otp-box'))
            .map(function(b){ return b.value || ''; }).join('');
    }

    function syncOtpBtn() {
        var val    = otpVal();
        var hidden = document.getElementById('hidden-otp');
        var btn    = document.getElementById('verify-btn');
        if (hidden) hidden.value = val;
        if (!btn) return;
        var ok = val.length === 4;
        btn.style.opacity       = ok ? '1'        : '0.4';
        btn.style.pointerEvents = ok ? 'auto'     : 'none';
        btn.style.cursor        = ok ? 'pointer'  : 'not-allowed';
    }

    document.addEventListener('input', function(e) {
        var el = e.target;

        /* Phone digit filter */
        if (el.id === 'login-phone') {
            el.value = el.value.replace(/\D/g, '').slice(0, 10);
            var err = document.getElementById('login-phone-error');
            if (err && el.value.length === 10 && /^[6-9]/.test(el.value)) err.style.display = 'none';
            return;
        }

        /* OTP box input */
        if (!el.classList.contains('otp-box')) return;
        el.value = el.value.replace(/\D/g, '').slice(-1);
        syncOtpBtn();
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        var idx   = boxes.indexOf(el);
        if (el.value && idx < boxes.length - 1) boxes[idx + 1].focus();
    });

    document.addEventListener('keydown', function(e) {
        var el = e.target;
        if (!el.classList.contains('otp-box')) return;
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        var idx   = boxes.indexOf(el);
        if (e.key === 'Backspace') {
            e.preventDefault();
            if (el.value) { el.value = ''; }
            else if (idx > 0) { boxes[idx - 1].value = ''; boxes[idx - 1].focus(); }
            syncOtpBtn(); return;
        }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); if (idx > 0) boxes[idx - 1].focus(); return; }
        if (e.key === 'ArrowRight') { e.preventDefault(); if (idx < boxes.length - 1) boxes[idx + 1].focus(); return; }
        if (e.key.length === 1 && !/\d/.test(e.key)) { e.preventDefault(); return; }
        if (/\d/.test(e.key) && el.value) el.value = '';
    });

    document.addEventListener('paste', function(e) {
        var el = e.target;
        if (!el.classList.contains('otp-box')) return;
        e.preventDefault();
        var p     = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,4);
        var boxes = Array.from(document.querySelectorAll('.otp-box'));
        p.split('').forEach(function(c, i){ if (boxes[i]) boxes[i].value = c; });
        syncOtpBtn();
        boxes[Math.min(p.length, boxes.length - 1)].focus();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('otp-box')) e.target.select();
    });

    /* Verify form submit */
    document.addEventListener('submit', function(e) {
        if (!e.target || e.target.id !== 'login-otp-verify-form') return;
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

    /* Send OTP form submit — validate phone first */
    document.addEventListener('submit', function(e) {
        if (!e.target || e.target.id !== 'login-otp-form') return;
        var phoneEl = document.getElementById('login-phone');
        var phoneErr = document.getElementById('login-phone-error');
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
        var btn = document.getElementById('login-send-btn');
        var spn = document.getElementById('login-send-spinner');
        var txt = document.getElementById('login-send-text');
        if (btn) { btn.style.opacity = '0.6'; btn.style.pointerEvents = 'none'; }
        if (spn) spn.style.display = 'inline-block';
        if (txt) txt.textContent = 'Sending…';
    });

    /* OTP step: auto-focus + countdown (runs after Vue mounts) */
    @if(session('show_login_otp'))

    /* Refresh → back to step 1 */
    if (window.performance) {
        var nav = performance.getEntriesByType('navigation');
        if (nav.length && nav[0].type === 'reload') {
            window.location.replace('{{ route("shop.customer.session.index") }}?reset=1');
        }
    }

    window.addEventListener('load', function() {
        var box0 = document.getElementById('otp-0');
        if (box0) box0.focus();

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
