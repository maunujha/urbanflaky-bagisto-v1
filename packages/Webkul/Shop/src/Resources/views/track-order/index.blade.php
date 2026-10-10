<x-shop::layouts
    :has-feature="false"
    metaDescription="Track your Urbanflaky order in real time. Enter your order number with your email or phone, or your AWB, for live courier updates and estimated delivery."
    :canonical="url('track-order')"
>
    <x-slot:title>
        Track Your Order
    </x-slot>

    <div class="uf-track-page bg-uf-bg text-uf-text">

        {{-- ─────────────────────────  HERO  ───────────────────────── --}}
        <section class="relative overflow-hidden border-b border-uf-border">
            <div
                class="pointer-events-none absolute inset-0"
                style="background-image: radial-gradient(80% 120% at 50% -10%, rgba(199,235,49,0.12) 0%, rgba(10,10,10,0) 55%);"
            ></div>

            <div class="container relative max-md:px-5 py-14 md:py-20 text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-uf-accent/30 bg-uf-accent/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-uf-accent">
                    <span class="h-1.5 w-1.5 rounded-full bg-uf-accent motion-safe:animate-pulse"></span>
                    Live Shipment Tracking
                </span>

                <h1 class="mt-6 font-poppins text-4xl font-extrabold leading-tight md:text-6xl">
                    Track Your <span class="text-uf-accent">Order</span>
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-base text-uf-muted md:text-lg">
                    Follow your Urbanflaky order from our studio to your door. Use your order number
                    with the email or phone you ordered with, or the AWB from your shipping message.
                </p>
            </div>
        </section>

        {{-- ─────────────────────────  TRACK FORM  ───────────────────────── --}}
        <section class="container max-md:px-5 -mt-8 md:-mt-10 relative z-10">
            <div class="mx-auto max-w-3xl rounded-3xl border border-uf-border bg-uf-surface p-6 shadow-2xl shadow-black/40 md:p-8">
                {{-- Mode switch --}}
                <div class="mb-5 inline-flex rounded-2xl border border-uf-border bg-uf-surface2 p-1 text-sm font-semibold" role="tablist" aria-label="Track by">
                    <button type="button" role="tab" data-mode="order" class="uf-mode rounded-xl px-4 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-uf-accent">Order number</button>
                    <button type="button" role="tab" data-mode="awb" class="uf-mode rounded-xl px-4 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-uf-accent">Tracking number (AWB)</button>
                </div>

                <form
                    id="uf-track-form"
                    data-endpoint="{{ route('shop.track-order.track') }}"
                    data-token="{{ csrf_token() }}"
                    data-prefill-awb="{{ $prefillAwb }}"
                    data-prefill-order="{{ $prefillOrder }}"
                    class="flex flex-col gap-3"
                    novalidate
                >
                    <div id="uf-fields-order" class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="uf-track-order" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-uf-muted">Order number</label>
                            <input
                                id="uf-track-order"
                                name="order_id"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="e.g. 1042"
                                class="w-full rounded-2xl border border-uf-border bg-uf-surface2 px-4 py-4 text-uf-text placeholder:text-uf-muted/70 outline-none transition focus:border-uf-accent focus:ring-2 focus:ring-uf-accent/30"
                            />
                        </div>

                        <div>
                            <label for="uf-track-contact" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-uf-muted">Email or phone</label>
                            <input
                                id="uf-track-contact"
                                name="contact"
                                type="text"
                                autocomplete="email"
                                placeholder="Used when you ordered"
                                class="w-full rounded-2xl border border-uf-border bg-uf-surface2 px-4 py-4 text-uf-text placeholder:text-uf-muted/70 outline-none transition focus:border-uf-accent focus:ring-2 focus:ring-uf-accent/30"
                            />
                        </div>
                    </div>

                    <div id="uf-fields-awb" class="hidden">
                        <label for="uf-track-awb" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-uf-muted">AWB / tracking number</label>
                        <input
                            id="uf-track-awb"
                            name="awb"
                            type="text"
                            autocomplete="off"
                            placeholder="From your shipping email or SMS"
                            class="w-full rounded-2xl border border-uf-border bg-uf-surface2 px-4 py-4 font-mono text-uf-text placeholder:font-sans placeholder:text-uf-muted/70 outline-none transition focus:border-uf-accent focus:ring-2 focus:ring-uf-accent/30"
                        />
                    </div>

                    <button
                        type="submit"
                        id="uf-track-btn"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-uf-accent px-7 py-4 font-poppins text-sm font-bold uppercase tracking-wide text-uf-bg transition hover:bg-uf-accentHover disabled:cursor-not-allowed disabled:opacity-60 sm:self-start"
                    >
                        <span class="uf-btn-label">Track Order</span>
                        <svg class="uf-btn-spinner hidden h-5 w-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-90" d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>

                {{-- Error / empty state --}}
                <div id="uf-track-error" class="hidden mt-5 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert">
                    <span id="uf-track-error-msg"></span>
                </div>

                {{-- Results injected here --}}
                <div id="uf-track-result" class="hidden mt-6" aria-live="polite"></div>
            </div>
        </section>

        {{-- ─────────────────────────  INFO SECTIONS  ───────────────────────── --}}
        <section class="container max-md:px-5 py-16 md:py-20">
            <div class="grid gap-5 md:grid-cols-3">
                {{-- How to track --}}
                <div class="rounded-2xl border border-uf-border bg-uf-surface p-6 md:col-span-2">
                    <h2 class="font-poppins text-xl font-bold">How to Track Your Order</h2>
                    <ol class="mt-5 space-y-4">
                        @foreach ([
                            'Enter your order number and the email or phone number you ordered with. Or switch to “Tracking number” and paste your AWB.',
                            'Tap “Track Order”.',
                            'See every courier update as it happens, with your expected delivery date.',
                        ] as $i => $step)
                            <li class="flex gap-4">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-uf-accent/15 font-poppins text-sm font-bold text-uf-accent">{{ $i + 1 }}</span>
                                <span class="pt-1 text-sm text-uf-muted">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Didn't receive --}}
                <div class="rounded-2xl border border-uf-border bg-uf-surface p-6">
                    <h2 class="font-poppins text-xl font-bold">Where’s My Tracking Number?</h2>
                    <p class="mt-4 text-sm leading-relaxed text-uf-muted">
                        It’s in your shipping email as soon as a courier is booked. Until then, your
                        <span class="font-semibold text-uf-text">order number</span> shows exactly where your order is.
                    </p>
                </div>
            </div>

            {{-- Need help --}}
            <div class="mt-5 flex flex-col items-start gap-5 rounded-2xl border border-uf-accent/30 bg-uf-surface p-7 md:flex-row md:items-center md:justify-between md:p-8"
                 style="background-image: radial-gradient(120% 140% at 0% 0%, rgba(199,235,49,0.08) 0%, rgba(20,20,20,0) 50%);">
                <div class="max-w-xl">
                    <h2 class="font-poppins text-xl font-bold">Need Help?</h2>
                    <p class="mt-2 text-sm text-uf-muted">
                        Can’t track your order or have a question about your shipment? Share your order number and our
                        support team will assist you as quickly as possible.
                    </p>
                </div>
                <a href="{{ route('shop.home.contact_us') }}"
                   class="inline-flex flex-none items-center gap-2 rounded-2xl border border-uf-accent bg-transparent px-6 py-3 font-poppins text-sm font-bold uppercase tracking-wide text-uf-accent transition hover:bg-uf-accent hover:text-uf-bg">
                    Contact Support
                </a>
            </div>

            <p class="mt-10 text-center text-sm text-uf-muted">
                Thank you for shopping with <span class="font-semibold text-uf-text">Urbanflaky</span>.<br>
                <span class="text-uf-accent">Premium Streetwear. Delivered to Your Doorstep.</span>
            </p>
        </section>
    </div>

    @push('scripts')
        @verbatim
        <script>
        (function () {
            const form = document.getElementById('uf-track-form');
            if (!form) return;

            const btn       = document.getElementById('uf-track-btn');
            const label     = btn.querySelector('.uf-btn-label');
            const spinner   = btn.querySelector('.uf-btn-spinner');
            const result    = document.getElementById('uf-track-result');
            const errBox    = document.getElementById('uf-track-error');
            const errMsg    = document.getElementById('uf-track-error-msg');
            const orderBox  = document.getElementById('uf-fields-order');
            const awbBox    = document.getElementById('uf-fields-awb');
            const orderIn   = document.getElementById('uf-track-order');
            const contactIn = document.getElementById('uf-track-contact');
            const awbIn     = document.getElementById('uf-track-awb');
            const tabs      = document.querySelectorAll('.uf-mode');

            const STEPS = ['Order Confirmed', 'Shipped', 'In Transit', 'Out for Delivery', 'Delivered'];
            const ALERT = ['undelivered', 'rto_initiated', 'rto_delivered', 'canceled', 'lost'];

            let mode = 'order';

            const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => (
                { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
            ));

            const fmtDate = (raw) => {
                if (!raw) return '';
                const d = new Date(raw);
                if (isNaN(d)) return esc(raw);
                return d.toLocaleString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true,
                });
            };

            function setMode(next) {
                mode = next;
                orderBox.classList.toggle('hidden', mode !== 'order');
                awbBox.classList.toggle('hidden', mode !== 'awb');
                tabs.forEach(t => {
                    const on = t.dataset.mode === mode;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.classList.toggle('bg-uf-accent', on);
                    t.classList.toggle('text-uf-bg', on);
                    t.classList.toggle('text-uf-muted', !on);
                });
                errBox.classList.add('hidden');
            }

            tabs.forEach(t => t.addEventListener('click', () => setMode(t.dataset.mode)));

            function loading(on) {
                btn.disabled = on;
                spinner.classList.toggle('hidden', !on);
                label.textContent = on ? 'Tracking…' : 'Track Order';
            }

            function showError(msg) {
                result.classList.add('hidden');
                result.innerHTML = '';
                errMsg.textContent = msg;
                errBox.classList.remove('hidden');
            }

            function progressHtml(stage) {
                return `
                <div class="relative mt-2">
                    <div class="absolute left-0 right-0 top-4 h-0.5 bg-uf-border"></div>
                    <div class="absolute left-0 top-4 h-0.5 bg-uf-accent transition-all" style="width:${(stage / (STEPS.length - 1)) * 100}%"></div>
                    <div class="relative flex justify-between">
                        ${STEPS.map((s, i) => {
                            const done = i <= stage;
                            const isCurrent = i === stage;
                            return `
                            <div class="flex flex-1 flex-col items-center text-center">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 ${done ? 'border-uf-accent bg-uf-accent text-uf-bg' : 'border-uf-border bg-uf-surface2 text-uf-muted'} ${isCurrent ? 'ring-4 ring-uf-accent/25' : ''}">
                                    ${done ? '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>' : `<span class="text-xs font-bold">${i + 1}</span>`}
                                </div>
                                <span class="mt-2 max-w-[72px] text-[11px] font-medium leading-tight ${done ? 'text-uf-text' : 'text-uf-muted'}">${s}</span>
                            </div>`;
                        }).join('')}
                    </div>
                </div>`;
            }

            function metaRow(label, value) {
                if (!value) return '';
                return `
                <div class="rounded-xl border border-uf-border bg-uf-surface2 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-uf-muted">${esc(label)}</div>
                    <div class="mt-1 text-sm font-semibold text-uf-text break-words">${esc(value)}</div>
                </div>`;
            }

            function timelineHtml(activities) {
                if (!activities || !activities.length) return '';
                const items = activities.map((a, i) => `
                    <li class="relative pl-7">
                        <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full ${i === 0 ? 'bg-uf-accent ring-4 ring-uf-accent/20' : 'bg-uf-border'}"></span>
                        ${i < activities.length - 1 ? '<span class="absolute left-[4.5px] top-4 bottom-[-14px] w-px bg-uf-border"></span>' : ''}
                        <p class="text-sm font-semibold ${i === 0 ? 'text-uf-text' : 'text-uf-muted'}">${esc(a.activity)}</p>
                        <p class="mt-0.5 text-xs text-uf-muted">${[fmtDate(a.date), esc(a.location)].filter(Boolean).join(' · ')}</p>
                    </li>`).join('');

                return `
                <div class="mt-6">
                    <h3 class="font-poppins text-sm font-bold uppercase tracking-wide text-uf-muted">Shipment Activity</h3>
                    <ul class="mt-4 space-y-5">${items}</ul>
                </div>`;
            }

            function render(d) {
                errBox.classList.add('hidden');

                const alert = ALERT.includes(d.state);
                const badge = `
                    <span class="inline-flex items-center gap-2 rounded-full ${alert ? 'bg-white/10 text-uf-text' : 'bg-uf-accent/15 text-uf-accent'} px-4 py-1.5 text-sm font-bold">
                        <span class="h-1.5 w-1.5 rounded-full ${alert ? 'bg-uf-text' : 'bg-uf-accent'}"></span>${esc(d.current_status)}
                    </span>`;

                const delivered = d.state === 'delivered';

                result.innerHTML = `
                    <div class="rounded-2xl border border-uf-border bg-uf-surface2/60 p-5 md:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-uf-muted">${d.order_id ? `Order #${esc(d.order_id)}` : 'Tracking number'}</div>
                                ${d.awb
                                    ? `<div class="font-mono text-lg font-bold text-uf-text">AWB ${esc(d.awb)}</div>`
                                    : `<div class="text-lg font-bold text-uf-text">Placed ${esc(d.placed_on)}</div>`}
                            </div>
                            ${badge}
                        </div>

                        ${d.courier ? `<div class="mt-2 text-sm text-uf-muted">Courier: <span class="font-semibold text-uf-text">${esc(d.courier)}</span></div>` : ''}

                        ${d.note ? `<p class="mt-4 rounded-xl border border-uf-border bg-uf-surface p-3 text-sm text-uf-muted">${esc(d.note)}</p>` : ''}

                        ${d.state === 'canceled' ? '' : `<div class="mt-7">${progressHtml(d.stage)}</div>`}

                        <div class="mt-7 grid grid-cols-2 gap-3 md:grid-cols-3">
                            ${metaRow('Shipping to', d.destination)}
                            ${metaRow(delivered ? 'Delivered on' : 'Expected delivery', delivered ? d.delivered_date : d.edd)}
                            ${metaRow('Latest courier update', d.courier_status)}
                        </div>

                        ${timelineHtml(d.activities)}
                    </div>`;

                result.classList.remove('hidden');
                result.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest' });
            }

            async function submit() {
                let body;

                if (mode === 'awb') {
                    const awb = awbIn.value.trim();
                    if (!awb) { awbIn.focus(); return; }
                    body = { awb };
                } else {
                    const order_id = orderIn.value.trim().replace(/^#/, '');
                    const contact  = contactIn.value.trim();
                    if (!order_id) { orderIn.focus(); return; }
                    if (!contact) { contactIn.focus(); return; }
                    body = { order_id, contact };
                }

                loading(true);
                errBox.classList.add('hidden');

                try {
                    const res = await fetch(form.dataset.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.dataset.token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(body),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (res.status === 429) {
                        showError('Too many attempts. Please wait a minute and try again.');
                    } else if (data && data.found) {
                        render(data);
                    } else {
                        showError((data && data.message) || 'We couldn’t find that order. Please check the details and try again.');
                    }
                } catch (err) {
                    showError('Something went wrong while tracking. Please try again in a moment.');
                } finally {
                    loading(false);
                }
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submit();
            });

            /* Links from emails and the account page: ?awb=… tracks at once, ?order=… prefills. */
            if (form.dataset.prefillAwb) {
                setMode('awb');
                awbIn.value = form.dataset.prefillAwb;
                submit();
            } else {
                setMode('order');
                if (form.dataset.prefillOrder) {
                    orderIn.value = form.dataset.prefillOrder;
                    contactIn.focus();
                }
            }
        })();
        </script>
        @endverbatim
    @endpush
</x-shop::layouts>
