{{--
    "Email me when it's back" on the product page (Gabha\Notifications).
    Rendered inside the product form's Vue template, so: no <form> (it would
    nest), no inline script; the delegated script below drives it.
--}}
@php
    $choices = \Gabha\Notifications\Support\StockStatus::soldOutChoices($product);
    $customerEmail = auth()->guard('customer')->user()?->email;
    $customerEmail = \Gabha\Notifications\Services\EmailDispatcher::deliverable($customerEmail) ? $customerEmail : '';
@endphp

@if ($choices && app(\Gabha\Notifications\Services\AutomationSettings::class)->enabled('lifecycle.back_in_stock'))
    <div
        class="uf-stock-alert mt-6 w-full rounded-xl border border-white/10 bg-white/[0.02] p-6 max-sm:p-4"
        data-endpoint="{{ route('shop.notifications.stock-alerts.store') }}"
        data-recaptcha-key="{{ \Webkul\Customer\Facades\Captcha::isActive() ? \Webkul\Customer\Facades\Captcha::getSiteKey() : '' }}"
    >
        <p class="text-base font-medium text-white">
            {{ count($choices) === 1 && reset($choices) === '' ? trans('notifications::app.stock.title-product') : trans('notifications::app.stock.title-size') }}
        </p>

        <p class="mt-1 text-sm text-zinc-400">
            @lang('notifications::app.stock.info')
        </p>

        <div class="mt-4 flex flex-col gap-3">
            @if (count($choices) === 1 && reset($choices) === '')
                <input type="hidden" data-stock-field="product_id" value="{{ array_key_first($choices) }}">
            @else
                <label class="sr-only" for="uf-stock-size-{{ $product->id }}">@lang('notifications::app.stock.size')</label>

                <select
                    id="uf-stock-size-{{ $product->id }}"
                    data-stock-field="product_id"
                    class="w-full rounded-lg border border-white/15 bg-transparent px-4 py-3 text-sm text-white focus:border-white focus:outline-none"
                >
                    @foreach ($choices as $id => $label)
                        <option value="{{ $id }}" class="bg-zinc-900">{{ $label }}</option>
                    @endforeach
                </select>
            @endif

            <label class="sr-only" for="uf-stock-email-{{ $product->id }}">@lang('notifications::app.stock.email')</label>

            <div class="flex gap-2 max-sm:flex-col">
                <input
                    id="uf-stock-email-{{ $product->id }}"
                    type="email"
                    data-stock-field="email"
                    value="{{ $customerEmail }}"
                    autocomplete="email"
                    placeholder="@lang('notifications::app.stock.email')"
                    class="min-w-0 flex-1 rounded-lg border border-white/15 bg-transparent px-4 py-3 text-sm text-white placeholder:text-zinc-500 focus:border-white focus:outline-none"
                >

                {{-- Honeypot: hidden from people and assistive tech. --}}
                <input type="text" data-stock-field="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden">

                <button
                    type="button"
                    data-stock-submit
                    class="whitespace-nowrap rounded-lg border border-white px-6 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-white hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black disabled:opacity-50"
                >
                    @lang('notifications::app.stock.cta')
                </button>
            </div>

            <p data-stock-message class="text-sm text-zinc-300" role="status" aria-live="polite"></p>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            (function () {
                let recaptchaLoad = null;

                function loadRecaptcha(key) {
                    if (! key) return Promise.resolve(null);
                    if (window.grecaptcha && window.grecaptcha.execute) return Promise.resolve(window.grecaptcha);
                    if (recaptchaLoad) return recaptchaLoad;

                    recaptchaLoad = new Promise(function (resolve) {
                        const s = document.createElement('script');
                        s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(key);
                        s.async = true;
                        s.onload = function () { resolve(window.grecaptcha || null); };
                        s.onerror = function () { recaptchaLoad = null; resolve(null); };
                        document.head.appendChild(s);
                    });

                    return recaptchaLoad;
                }

                function recaptchaToken(key) {
                    return loadRecaptcha(key).then(function (g) {
                        if (! g) return '';

                        return new Promise(function (resolve) {
                            g.ready(function () {
                                g.execute(key, { action: 'stock_alert' }).then(resolve, function () { resolve(''); });
                            });
                        });
                    });
                }

                function xsrfToken() {
                    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

                    return match ? decodeURIComponent(match[1]) : '';
                }

                function field(box, name) {
                    const el = box.querySelector('[data-stock-field="' + name + '"]');

                    return el ? el.value.trim() : '';
                }

                function submit(box) {
                    const button = box.querySelector('[data-stock-submit]');
                    const message = box.querySelector('[data-stock-message]');
                    const email = field(box, 'email');

                    if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        message.textContent = @json(trans('notifications::app.stock.invalid-email'));
                        return;
                    }

                    button.disabled = true;
                    message.textContent = '';

                    recaptchaToken(box.dataset.recaptchaKey)
                        .then(function (token) {
                            return fetch(box.dataset.endpoint, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-XSRF-TOKEN': xsrfToken(),
                                },
                                body: JSON.stringify({
                                    product_id: field(box, 'product_id'),
                                    email: email,
                                    website: field(box, 'website'),
                                    recaptcha_token: token,
                                }),
                            });
                        })
                        .then(function (r) { return r.json().catch(function () { return {}; }); })
                        .then(function (body) {
                            message.textContent = body.message || @json(trans('notifications::app.stock.error'));
                        })
                        .catch(function () {
                            message.textContent = @json(trans('notifications::app.stock.error'));
                        })
                        .finally(function () { button.disabled = false; });
                }

                ['focusin', 'pointerdown'].forEach(function (type) {
                    document.addEventListener(type, function (e) {
                        const box = e.target.closest && e.target.closest('.uf-stock-alert');
                        if (box) loadRecaptcha(box.dataset.recaptchaKey);
                    }, { passive: true });
                });

                document.addEventListener('click', function (e) {
                    const button = e.target.closest && e.target.closest('[data-stock-submit]');
                    if (! button) return;

                    e.preventDefault();
                    submit(button.closest('.uf-stock-alert'));
                });

                /* Enter in the email field must not submit the surrounding add-to-cart form. */
                document.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' || ! e.target.closest || ! e.target.closest('.uf-stock-alert')) return;

                    e.preventDefault();
                    submit(e.target.closest('.uf-stock-alert'));
                });
            })();
        </script>
    @endPushOnce
@endif
