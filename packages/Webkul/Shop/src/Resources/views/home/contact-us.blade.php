@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contact-us.css') }}">
@endpush

<x-shop::layouts :has-feature="false">
    <x-slot:title>
        @lang('shop::app.home.contact.title')
    </x-slot>

    <div class="cu-wrap">

        {{-- ── PAGE HERO ── --}}
        <div class="cu-hero">
            <div class="cu-hero-left">
                <div class="cu-crumb">
                    <a href="{{ route('shop.home.index') }}">Home</a> / <span>Contact</span>
                </div>
                <h1>Get In <em>Touch</em></h1>
            </div>
            <div class="cu-hero-right">
                <div class="cu-badge">⚡ Response Within 24 Hours</div>
                <p>Mon – Sat, 10am – 7pm IST</p>
            </div>
        </div>

        {{-- ── MAIN LAYOUT ── --}}
        <div class="cu-layout">

            {{-- ── LEFT PANEL ── --}}
            <div class="cu-left">
                <div class="cu-panel-label">Our Info</div>

                @php
                    $storeAddress = core()->getConfigData('sales.shipping.origin.address');
                    $storeCity    = core()->getConfigData('sales.shipping.origin.city');
                    $storeZip     = core()->getConfigData('sales.shipping.origin.zipcode');
                    $storeState   = core()->getConfigData('sales.shipping.origin.state');
                    $storePhone   = core()->getConfigData('sales.shipping.origin.telephone');
                    $storeEmail   = env('CONTACT_MAIL_ADDRESS', 'support@urbanflaky.in');
                @endphp

                @if ($storeAddress)
                    <div class="cu-info-block">
                        <div class="cu-ib-label">Address</div>
                        <div class="cu-ib-value">
                            {{ $storeAddress }}<br>
                            {{ $storeCity }}, {{ $storeState }}<br>
                            {{ $storeZip }}, IN
                        </div>
                    </div>
                @endif

                <div class="cu-info-block">
                    <div class="cu-ib-label">Email</div>
                    <div class="cu-ib-value"><a href="mailto:{{ $storeEmail }}">{{ $storeEmail }}</a></div>
                    <div class="cu-ib-sub">For orders, returns &amp; general queries</div>
                </div>

                @if ($storePhone)
                    <div class="cu-info-block">
                        <div class="cu-ib-label">Phone / WhatsApp</div>
                        <div class="cu-ib-value"><a href="tel:+91{{ preg_replace('/\D/', '', $storePhone) }}">{{ $storePhone }}</a></div>
                        <div class="cu-ib-sub">WhatsApp preferred for faster response</div>
                    </div>
                @endif

                <div class="cu-info-block">
                    <div class="cu-ib-label">Business Hours</div>
                    <div class="cu-hours-grid">
                        <div class="cu-hours-row"><span class="cu-day">Mon–Fri</span><span class="cu-time">10:00–19:00</span></div>
                        <div class="cu-hours-row"><span class="cu-day">Saturday</span><span class="cu-time">11:00–17:00</span></div>
                        <div class="cu-hours-row"><span class="cu-day">Sunday</span><span class="cu-time">Closed</span></div>
                    </div>
                </div>

                <div class="cu-social-strip">
                    <a href="https://instagram.com/urbanflaky" target="_blank" rel="noopener" class="cu-s-btn">Insta</a>
                    @if ($storePhone)
                        <a href="https://wa.me/91{{ preg_replace('/\D/', '', $storePhone) }}" target="_blank" rel="noopener" class="cu-s-btn">WA</a>
                    @endif
                    <a href="https://facebook.com/urbanflaky" target="_blank" rel="noopener" class="cu-s-btn">FB</a>
                </div>
            </div>

            {{-- ── RIGHT PANEL — FORM ── --}}
            <div class="cu-right">
                <div class="cu-form-header">
                    <div class="cu-fh-eyebrow">Send a Message</div>
                    <h2>We'd Love to Hear From You</h2>
                    <p>Got a question about an order, a product, or just want to say hi? Fill in the form and we'll get back to you ASAP.</p>
                </div>

                @if (session('success'))
                    <div class="cu-flash cu-flash-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="cu-flash cu-flash-error">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="cu-flash cu-flash-error">
                        @foreach ($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form id="cu-form" method="POST" action="{{ route('shop.home.contact_us.send_mail') }}" novalidate>
                    @csrf

                    {{-- Topic --}}
                    <div class="cu-field" style="margin-bottom: 24px;">
                        <label>What's this about?</label>
                        <input type="hidden" name="topic" id="cu-topic" value="{{ old('topic', 'Order Issue') }}">
                        <div class="cu-topic-tags" id="cu-topic-tags">
                            @foreach (['Order Issue','Return / Refund','Product Query','Wholesale','Other'] as $topic)
                                <button type="button" class="cu-topic-tag {{ old('topic', 'Order Issue') === $topic ? 'cu-active' : '' }}" data-topic="{{ $topic }}">{{ $topic }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Name + Email --}}
                    <div class="cu-form-row">
                        <div class="cu-field cu-half">
                            <label>Name <span class="cu-req">*</span></label>
                            <input type="text" name="name" placeholder="Your full name" value="{{ old('name') }}" required maxlength="120">
                        </div>
                        <div class="cu-field cu-half">
                            <label>Email <span class="cu-req">*</span></label>
                            <input type="email" name="email" placeholder="your@email.com" value="{{ old('email') }}" required maxlength="160">
                        </div>
                    </div>

                    {{-- Phone + OTP --}}
                    <div class="cu-field">
                        <label>Phone / WhatsApp <span class="cu-req">*</span></label>
                        <div class="cu-phone-row">
                            <div class="cu-phone-cc">+91</div>
                            <input
                                type="tel"
                                id="cu-phone"
                                name="contact"
                                placeholder="10-digit mobile number"
                                value="{{ old('contact', session('contact_otp_phone')) }}"
                                inputmode="numeric"
                                maxlength="10"
                                required
                                {{ session('contact_phone_verified') ? 'readonly' : '' }}
                            >
                            <button type="button" id="cu-send-otp" class="cu-phone-action" {{ session('contact_phone_verified') ? 'style=display:none' : '' }}>Send OTP</button>
                            <div id="cu-verified" class="cu-verified-badge {{ session('contact_phone_verified') ? 'cu-show' : '' }}">✓ Verified</div>
                        </div>
                        <div class="cu-field-err" id="cu-phone-err"></div>

                        {{-- OTP entry block --}}
                        <div class="cu-otp-block" id="cu-otp-block">
                            <div class="cu-otp-msg" id="cu-otp-msg">OTP sent to <strong></strong>. Enter the 6-digit code.</div>
                            <input type="tel" id="cu-otp-input" class="cu-otp-input" maxlength="6" inputmode="numeric" placeholder="••••••">
                            <button type="button" id="cu-verify-otp" class="cu-otp-verify" disabled>Verify</button>
                            <button type="button" id="cu-resend-otp" class="cu-resend" style="display:none;">Resend</button>
                        </div>
                    </div>

                    {{-- Message --}}
                    <div class="cu-field">
                        <label>Message <span class="cu-req">*</span></label>
                        <textarea name="message" id="cu-msg" placeholder="Tell us what's on your mind..." required maxlength="500">{{ old('message') }}</textarea>
                    </div>
                    <div class="cu-field-meta">
                        <span class="cu-char-count" id="cu-char-count">0 / 500</span>
                    </div>

                    {{-- Captcha --}}
                    @if (core()->getConfigData('customer.captcha.credentials.status'))
                        <div class="cu-field">
                            {!! \Webkul\Customer\Facades\Captcha::render() !!}
                        </div>
                    @endif

                    {{-- Submit --}}
                    <div class="cu-submit-row">
                        <div class="cu-submit-note">
                            By submitting you agree to our <a href="{{ url('page/privacy-policy') }}">Privacy Policy</a>. We never share your data with third parties.
                        </div>
                        <button type="submit" id="cu-submit" class="cu-submit-btn" {{ session('contact_phone_verified') ? '' : 'disabled' }}>
                            Send It
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
        @if (core()->getConfigData('customer.captcha.credentials.status'))
            {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
        @endif

        <script>
        /* Delegated handlers — survive Vue re-rendering the DOM after mount. */
        (function () {
            const sendUrl   = "{{ route('shop.api.contact.otp.send') }}";
            const verifyUrl = "{{ route('shop.api.contact.otp.verify') }}";

            let isVerified = {{ session('contact_phone_verified') ? 'true' : 'false' }};
            let resendTimer = null;

            function $(id) { return document.getElementById(id); }

            function showError(msg) {
                const el = $('cu-phone-err');
                if (el) el.textContent = msg || '';
            }

            function setSendBtn(text, disabled) {
                const b = $('cu-send-otp');
                if (!b) return;
                b.textContent = text;
                b.disabled = !!disabled;
            }

            // ── SEND OTP ──
            function sendOtp() {
                const phoneInput = $('cu-phone');
                const phone = phoneInput ? phoneInput.value : '';

                if (phone.length !== 10 || !/^[6-9]/.test(phone)) {
                    showError(phone.length < 10
                        ? 'Enter a 10-digit mobile number.'
                        : 'Number must start with 6, 7, 8 or 9.');
                    return;
                }

                showError('');
                setSendBtn('Sending…', true);

                window.axios.post(sendUrl, { phone: phone })
                    .then(() => {
                        const msgPhone = $('cu-otp-msg')?.querySelector('strong');
                        if (msgPhone) msgPhone.textContent = '+91 ••••••' + phone.slice(-4);
                        $('cu-otp-block')?.classList.add('cu-show');
                        $('cu-otp-input')?.focus();
                        const sendBtn = $('cu-send-otp');
                        if (sendBtn) sendBtn.style.display = 'none';
                        startResendCountdown();
                    })
                    .catch(error => {
                        showError(error.response?.data?.message || 'Failed to send OTP.');
                        setSendBtn('Send OTP', false);
                    });
            }

            // ── VERIFY OTP ──
            function verifyOtp() {
                const phoneInput = $('cu-phone');
                const otpInput = $('cu-otp-input');
                const verifyBtn = $('cu-verify-otp');
                if (!verifyBtn) return;

                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verifying…';

                window.axios.post(verifyUrl, {
                    phone: phoneInput?.value,
                    otp:   otpInput?.value,
                })
                .then(() => {
                    isVerified = true;
                    $('cu-otp-block')?.classList.remove('cu-show');
                    $('cu-verified')?.classList.add('cu-show');
                    if (phoneInput) phoneInput.setAttribute('readonly', 'readonly');
                    const submitBtn = $('cu-submit');
                    if (submitBtn) submitBtn.disabled = false;
                    if (resendTimer) clearInterval(resendTimer);
                })
                .catch(error => {
                    showError(error.response?.data?.message || 'Verification failed.');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';
                });
            }

            // ── Resend countdown ──
            function startResendCountdown() {
                let secs = 30;
                const resendBtn = $('cu-resend-otp');
                if (!resendBtn) return;
                resendBtn.style.display = 'inline-block';
                resendBtn.disabled = true;
                resendBtn.textContent = 'Resend in ' + secs + 's';

                resendTimer = setInterval(() => {
                    secs--;
                    const btn = $('cu-resend-otp');
                    if (!btn) { clearInterval(resendTimer); return; }
                    if (secs > 0) {
                        btn.textContent = 'Resend in ' + secs + 's';
                    } else {
                        clearInterval(resendTimer);
                        btn.textContent = 'Resend OTP';
                        btn.disabled = false;
                    }
                }, 1000);
            }

            // ── Char counter ──
            function updateCharCount() {
                const m = $('cu-msg');
                const c = $('cu-char-count');
                if (!m || !c) return;
                const n = m.value.length;
                c.textContent = n + ' / 500';
                c.style.color = n > 450 ? '#c7eb31' : '#444';
            }

            // ── Delegated click handler ──
            document.addEventListener('click', function (e) {
                if (e.target.closest('#cu-send-otp'))    { e.preventDefault(); sendOtp(); return; }
                if (e.target.closest('#cu-verify-otp'))  { e.preventDefault(); verifyOtp(); return; }
                if (e.target.closest('#cu-resend-otp'))  {
                    e.preventDefault();
                    const btn = $('cu-resend-otp');
                    if (btn?.disabled) return;
                    const otpInput = $('cu-otp-input');
                    if (otpInput) otpInput.value = '';
                    const verifyBtn = $('cu-verify-otp');
                    if (verifyBtn) verifyBtn.disabled = true;
                    sendOtp();
                    return;
                }

                const topicBtn = e.target.closest('.cu-topic-tag');
                if (topicBtn) {
                    e.preventDefault();
                    document.querySelectorAll('.cu-topic-tag').forEach(t => t.classList.remove('cu-active'));
                    topicBtn.classList.add('cu-active');
                    const hidden = $('cu-topic');
                    if (hidden) hidden.value = topicBtn.dataset.topic || '';
                    return;
                }
            });

            // ── Delegated input handler (phone digit filter, OTP filter, char count) ──
            document.addEventListener('input', function (e) {
                const t = e.target;
                if (!t) return;

                if (t.id === 'cu-phone') {
                    t.value = t.value.replace(/\D/g, '').slice(0, 10);
                    showError('');
                    return;
                }
                if (t.id === 'cu-otp-input') {
                    t.value = t.value.replace(/\D/g, '').slice(0, 6);
                    const verifyBtn = $('cu-verify-otp');
                    if (verifyBtn) verifyBtn.disabled = t.value.length !== 6;
                    return;
                }
                if (t.id === 'cu-msg') {
                    updateCharCount();
                    return;
                }
            });

            // ── Delegated submit handler — block when phone not verified ──
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || form.id !== 'cu-form') return;
                if (!isVerified) {
                    e.preventDefault();
                    showError('Please verify your phone number first.');
                }
            });

            // Initial char count after DOM ready (in case message has old value)
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', updateCharCount);
            } else {
                updateCharCount();
            }
        })();
        </script>
    @endpush
</x-shop::layouts>
