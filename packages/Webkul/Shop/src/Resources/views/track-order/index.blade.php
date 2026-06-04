<x-shop::layouts
    :has-feature="false"
    metaDescription="Track your Urbanflaky order in real time — enter your AWB or Order ID for live courier updates, shipping status and estimated delivery."
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
                    <span class="h-1.5 w-1.5 rounded-full bg-uf-accent animate-pulse"></span>
                    Live Shipment Tracking
                </span>

                <h1 class="mt-6 font-poppins text-4xl font-extrabold leading-tight md:text-6xl">
                    Track Your <span class="text-uf-accent">Order</span>
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-base text-uf-muted md:text-lg">
                    Stay updated on your Urbanflaky order every step of the way. Enter your AWB
                    (tracking) number or Order ID below to see exactly where your fit is.
                </p>
            </div>
        </section>

        {{-- ─────────────────────────  TRACK FORM  ───────────────────────── --}}
        <section class="container max-md:px-5 -mt-8 md:-mt-10 relative z-10">
            <div class="mx-auto max-w-3xl rounded-3xl border border-uf-border bg-uf-surface p-6 shadow-2xl shadow-black/40 md:p-8">
                <form
                    id="uf-track-form"
                    data-endpoint="{{ route('shop.track-order.track') }}"
                    data-token="{{ csrf_token() }}"
                    class="flex flex-col gap-3 sm:flex-row"
                >
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-uf-muted">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 7h13v10H3z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 10h3l2 3v4h-5z" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>
                            </svg>
                        </span>
                        <input
                            id="uf-track-input"
                            name="awb"
                            type="text"
                            autocomplete="off"
                            placeholder="Enter AWB / Tracking number or Order ID"
                            class="w-full rounded-2xl border border-uf-border bg-uf-surface2 py-4 pl-12 pr-4 text-uf-text placeholder:text-uf-muted/70 outline-none transition focus:border-uf-accent focus:ring-2 focus:ring-uf-accent/30"
                        />
                    </div>

                    <button
                        type="submit"
                        id="uf-track-btn"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-uf-accent px-7 py-4 font-poppins text-sm font-bold uppercase tracking-wide text-uf-bg transition hover:bg-uf-accentHover disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span class="uf-btn-label">Track Order</span>
                        <svg class="uf-btn-spinner hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-90" d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-3 text-center text-xs text-uf-muted sm:text-left">
                    Tip: your AWB number is in your shipping confirmation email &amp; SMS. Allow 24–48 hours after dispatch for tracking to appear.
                </p>

                {{-- Error / empty state --}}
                <div id="uf-track-error" class="hidden mt-5 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300">
                    <span id="uf-track-error-msg"></span>
                </div>

                {{-- Results injected here --}}
                <div id="uf-track-result" class="hidden mt-6"></div>
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
                            'Enter your AWB / Tracking number (or Order ID) in the form above.',
                            'Tap “Track Order”.',
                            'View real-time updates on shipping progress and estimated delivery.',
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
                    <h2 class="font-poppins text-xl font-bold">Didn’t Get Tracking Details?</h2>
                    <p class="mt-4 text-sm leading-relaxed text-uf-muted">
                        Tracking is shared via email and SMS once your order ships. Please allow up to
                        <span class="font-semibold text-uf-text">24–48 hours</span> after dispatch for updates to appear.
                    </p>
                </div>
            </div>

            {{-- Need help --}}
            <div class="mt-5 flex flex-col items-start gap-5 rounded-2xl border border-uf-accent/30 bg-uf-surface p-7 md:flex-row md:items-center md:justify-between md:p-8"
                 style="background-image: radial-gradient(120% 140% at 0% 0%, rgba(199,235,49,0.08) 0%, rgba(20,20,20,0) 50%);">
                <div class="max-w-xl">
                    <h2 class="font-poppins text-xl font-bold">Need Help?</h2>
                    <p class="mt-2 text-sm text-uf-muted">
                        Can’t track your order or have a question about your shipment? Share your Order ID and our
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
            const form    = document.getElementById('uf-track-form');
            if (!form) return;

            const input   = document.getElementById('uf-track-input');
            const btn     = document.getElementById('uf-track-btn');
            const label   = btn.querySelector('.uf-btn-label');
            const spinner = btn.querySelector('.uf-btn-spinner');
            const result  = document.getElementById('uf-track-result');
            const errBox  = document.getElementById('uf-track-error');
            const errMsg  = document.getElementById('uf-track-error-msg');

            const STEPS = ['Order Confirmed', 'Picked Up', 'In Transit', 'Out for Delivery', 'Delivered'];

            const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => (
                { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
            ));

            const fmtDate = (raw) => {
                if (!raw) return '';
                const d = new Date(raw.replace(' ', 'T'));
                if (isNaN(d)) return esc(raw);
                return d.toLocaleString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true,
                });
            };

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
                                    ${done ? '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>' : `<span class="text-xs font-bold">${i + 1}</span>`}
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

                const badge = `
                    <span class="inline-flex items-center gap-2 rounded-full bg-uf-accent/15 px-4 py-1.5 text-sm font-bold text-uf-accent">
                        <span class="h-1.5 w-1.5 rounded-full bg-uf-accent"></span>${esc(d.current_status)}
                    </span>`;

                result.innerHTML = `
                    <div class="rounded-2xl border border-uf-border bg-uf-surface2/60 p-5 md:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-uf-muted">AWB / Tracking No.</div>
                                <div class="font-mono text-lg font-bold text-uf-text">${esc(d.awb)}</div>
                            </div>
                            ${badge}
                        </div>

                        ${d.courier ? `<div class="mt-2 text-sm text-uf-muted">Courier: <span class="font-semibold text-uf-text">${esc(d.courier)}</span></div>` : ''}

                        <div class="mt-7">${progressHtml(d.stage)}</div>

                        <div class="mt-7 grid grid-cols-2 gap-3 md:grid-cols-4">
                            ${metaRow('From', d.origin)}
                            ${metaRow('To', d.destination)}
                            ${metaRow(d.stage >= 4 ? 'Delivered On' : 'Est. Delivery', d.stage >= 4 ? (d.delivered_date || d.edd) : d.edd)}
                            ${metaRow('Recipient', d.consignee)}
                        </div>

                        ${timelineHtml(d.activities)}
                    </div>`;

                result.classList.remove('hidden');
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const awb = input.value.trim();
                if (!awb) { input.focus(); return; }

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
                        body: JSON.stringify({ awb }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (data && data.found) {
                        render(data);
                    } else {
                        showError((data && data.message) || 'We couldn’t find that shipment. Please check your AWB / Order ID and try again.');
                    }
                } catch (err) {
                    showError('Something went wrong while tracking. Please try again in a moment.');
                } finally {
                    loading(false);
                }
            });
        })();
        </script>
        @endverbatim
    @endpush
</x-shop::layouts>
