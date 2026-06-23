{{--
    ============================================================
    Urbanflaky — Exit Intent Welcome Discount Popup (Phase 1)
    ------------------------------------------------------------
    Desktop: centered modal, triggered when the cursor leaves via the
    top of the viewport (classic exit-intent).
    Mobile: bottom sheet, triggered on a back-button press (no mouse
    to read intent from, so we intercept history navigation instead —
    a dummy history entry is pushed once so the first back press is
    caught; a second press then navigates back normally).

    Gated by `App\Support\ExitIntentPopup::enabled()` so the whole
    component renders nothing — and no listeners attach — when the
    admin toggle is off. Listeners are armed only after the visitor
    has spent 10s on the site and only fire once per session; the
    "last shown" timestamp in localStorage enforces the configured
    frequency across sessions.
    ============================================================
--}}
@php
    $exitIntentPopupSettings = \App\Support\ExitIntentPopup::settings();

    $exitIntentPopup = [
        'title' => $exitIntentPopupSettings['title'],
        'description' => $exitIntentPopupSettings['description'],
        'offerText' => trans('shop::app.components.layouts.exit-intent-popup.offer', ['percent' => $exitIntentPopupSettings['discountPercentage']]),
        'couponCode' => $exitIntentPopupSettings['couponCode'],
        'ctaText' => $exitIntentPopupSettings['ctaText'],
        'desktopImage' => $exitIntentPopupSettings['desktopImage'],
        'mobileImage' => $exitIntentPopupSettings['mobileImage'],
        'frequencyDays' => $exitIntentPopupSettings['frequencyDays'],
    ];
@endphp

<v-exit-intent-popup
    popup-title="{{ $exitIntentPopup['title'] }}"
    popup-description="{{ $exitIntentPopup['description'] }}"
    offer-text="{{ $exitIntentPopup['offerText'] }}"
    coupon-code="{{ $exitIntentPopup['couponCode'] }}"
    cta-text="{{ $exitIntentPopup['ctaText'] }}"
    continue-text="{{ trans('shop::app.components.layouts.exit-intent-popup.continue-browsing') }}"
    close-label="{{ trans('shop::app.components.layouts.exit-intent-popup.close') }}"
    copied-message="{{ trans('shop::app.components.layouts.exit-intent-popup.copied') }}"
    copy-failed-template="{{ trans('shop::app.components.layouts.exit-intent-popup.copy-failed') }}"
    desktop-image="{{ $exitIntentPopup['desktopImage'] }}"
    mobile-image="{{ $exitIntentPopup['mobileImage'] }}"
    frequency-days="{{ $exitIntentPopup['frequencyDays'] }}"
></v-exit-intent-popup>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-exit-intent-popup-template"
    >
        <teleport to="body">
            <!-- Desktop: centered modal -->
            <transition
                enter-active-class="transition duration-300 ease-out motion-reduce:transition-none"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in motion-reduce:transition-none"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isOpen && ! isMobile"
                    class="fixed inset-0 z-[9990] flex items-center justify-center bg-black/80 p-4 backdrop-blur-[3px]"
                    @click.self="close"
                >
                    <div
                        ref="desktopPanel"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="uf-eip-title"
                        tabindex="-1"
                        class="relative w-full overflow-hidden rounded-xl border border-white/10 bg-uf-bg shadow-[0_40px_90px_-30px_rgba(0,0,0,0.85)]"
                        :class="hasDesktopImage ? 'grid max-w-[680px] md:grid-cols-[270px_minmax(0,1fr)]' : 'max-w-[420px]'"
                        @keydown.esc="close"
                    >
                        <!-- Image -->
                        <div
                            v-if="hasDesktopImage"
                            class="relative hidden bg-uf-surface md:block"
                        >
                            <img
                                :src="desktopImage"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover grayscale-[15%]"
                                loading="lazy"
                                decoding="async"
                            >
                            <div class="absolute inset-0 bg-gradient-to-r from-black/20 to-uf-bg/70"></div>
                        </div>

                        <!-- Close -->
                        <button
                            type="button"
                            class="absolute right-3.5 top-3.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full text-white/55 transition-colors hover:bg-white/10 hover:text-white"
                            :aria-label="closeLabel"
                            @click="close"
                        >
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>

                        <!-- Content -->
                        <div class="flex flex-col gap-4 px-8 py-9 max-sm:px-6 max-sm:py-7">
                            <h2
                                id="uf-eip-title"
                                class="font-poppins text-[22px] font-bold uppercase leading-[1.1] tracking-[0.14em] text-uf-text"
                                style="text-wrap: balance;"
                            >
                                @{{ popupTitle }}
                            </h2>

                            <p class="max-w-[44ch] text-[13px] leading-relaxed text-uf-muted">
                                @{{ popupDescription }}
                            </p>

                            <p class="text-[15px] font-medium text-uf-text">
                                @{{ offerText }}
                            </p>

                            <button
                                type="button"
                                class="group flex items-center justify-between gap-3 rounded-md border border-dashed border-white/25 bg-white/[0.02] px-4 py-3 text-left transition-colors hover:border-white/45"
                                @click="claim"
                            >
                                <span class="font-poppins text-base font-bold tracking-[0.28em] text-uf-text">@{{ couponCode }}</span>
                                <span class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/45 transition-colors group-hover:text-white/70">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </span>
                            </button>

                            <button
                                type="button"
                                class="mt-1 w-full rounded-md bg-white px-6 py-3 font-poppins text-[13px] font-semibold uppercase tracking-[0.16em] text-uf-bg transition hover:bg-white/90 active:scale-[0.99]"
                                @click="claim"
                            >
                                @{{ ctaText }}
                            </button>

                            <button
                                type="button"
                                class="-mb-1 text-center text-[12px] tracking-wide text-white/45 underline-offset-4 transition-colors hover:text-white/80 hover:underline"
                                @click="close"
                            >
                                @{{ continueText }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Mobile: bottom sheet -->
            <transition
                enter-active-class="transition duration-300 ease-out motion-reduce:transition-none"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in motion-reduce:transition-none"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isOpen && isMobile"
                    class="fixed inset-0 z-[9990] bg-black/80"
                    @click.self="close"
                ></div>
            </transition>

            <transition
                enter-active-class="transition-transform duration-300 ease-out motion-reduce:transition-none"
                enter-from-class="translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition-transform duration-200 ease-in motion-reduce:transition-none"
                leave-from-class="translate-y-0"
                leave-to-class="translate-y-full"
            >
                <div
                    v-if="isOpen && isMobile"
                    ref="mobilePanel"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="uf-eip-title-mobile"
                    tabindex="-1"
                    class="fixed inset-x-0 bottom-0 z-[9991] flex max-h-[82vh] w-full flex-col overflow-hidden rounded-t-2xl border-t border-white/10 bg-uf-bg"
                    @keydown.esc="close"
                >
                    <!-- Close -->
                    <button
                        type="button"
                        class="absolute right-3.5 top-3.5 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-black/45 text-white/80 backdrop-blur-sm transition-colors hover:bg-black/65 hover:text-white"
                        :aria-label="closeLabel"
                        @click="close"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>

                    <div class="overflow-y-auto">
                        <img
                            v-if="hasMobileImage"
                            :src="mobileImage"
                            alt=""
                            class="h-48 w-full object-cover grayscale-[15%]"
                            loading="lazy"
                            decoding="async"
                        >

                        <!-- Grabber (shown when there's no image to anchor the sheet) -->
                        <div v-else class="flex justify-center pt-3">
                            <span class="h-1 w-10 rounded-full bg-white/20"></span>
                        </div>

                        <div class="flex flex-col gap-3.5 px-6 pb-7 pt-6">
                            <h2
                                id="uf-eip-title-mobile"
                                class="font-poppins text-[19px] font-bold uppercase leading-[1.1] tracking-[0.14em] text-uf-text"
                                style="text-wrap: balance;"
                            >
                                @{{ popupTitle }}
                            </h2>

                            <p class="text-[13px] leading-relaxed text-uf-muted">
                                @{{ popupDescription }}
                            </p>

                            <p class="text-[15px] font-medium text-uf-text">
                                @{{ offerText }}
                            </p>

                            <button
                                type="button"
                                class="group flex items-center justify-between gap-3 rounded-md border border-dashed border-white/25 bg-white/[0.02] px-4 py-3.5 text-left transition-colors hover:border-white/45"
                                @click="claim"
                            >
                                <span class="font-poppins text-lg font-bold tracking-[0.28em] text-uf-text">@{{ couponCode }}</span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white/45" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>

                            <button
                                type="button"
                                class="mt-1 w-full rounded-md bg-white px-6 py-3.5 font-poppins text-[13px] font-semibold uppercase tracking-[0.16em] text-uf-bg transition hover:bg-white/90 active:scale-[0.99]"
                                @click="claim"
                            >
                                @{{ ctaText }}
                            </button>

                            <button
                                type="button"
                                class="text-center text-[12px] tracking-wide text-white/45 underline-offset-4 transition-colors hover:text-white/80 hover:underline"
                                @click="close"
                            >
                                @{{ continueText }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </script>

    <script type="module">
        app.component('v-exit-intent-popup', {
            template: '#v-exit-intent-popup-template',

            props: [
                'popupTitle',
                'popupDescription',
                'offerText',
                'couponCode',
                'ctaText',
                'continueText',
                'closeLabel',
                'copiedMessage',
                'copyFailedTemplate',
                'desktopImage',
                'mobileImage',
                'frequencyDays',
            ],

            data() {
                return {
                    isOpen: false,
                    isMobile: false,
                };
            },

            computed: {
                hasDesktopImage() {
                    return !! this.desktopImage;
                },

                hasMobileImage() {
                    return !! this.mobileImage;
                },
            },

            mounted() {
                this.isMobile = window.matchMedia('(max-width: 767px)').matches;

                /* Already shown within the configured frequency window, or already
                   shown this session — skip arming any listeners entirely. */
                if (this.hasRecentlyShown() || this.shownThisSession()) {
                    return;
                }

                this.engageTimer = setTimeout(() => this.arm(), 10000);
            },

            beforeUnmount() {
                clearTimeout(this.engageTimer);
                this.disarm();
            },

            methods: {
                hasRecentlyShown() {
                    try {
                        const last = parseInt(localStorage.getItem('uf_exit_intent_last_shown') || '0', 10);
                        const days = parseInt(this.frequencyDays, 10) || 7;

                        return (Date.now() - last) < (days * 24 * 60 * 60 * 1000);
                    } catch (e) {
                        return false;
                    }
                },

                shownThisSession() {
                    try {
                        return !! sessionStorage.getItem('uf_exit_intent_shown_session');
                    } catch (e) {
                        return false;
                    }
                },

                arm() {
                    if (this.shownThisSession()) {
                        return;
                    }

                    if (this.isMobile) {
                        this.popstateHandler = () => this.trigger();

                        /* Push a dummy entry so the next back press fires `popstate`
                           here instead of leaving the page immediately. */
                        history.pushState({ ufExitIntent: true }, '', location.href);

                        window.addEventListener('popstate', this.popstateHandler);
                    } else {
                        this.mouseLeaveHandler = (e) => {
                            if (e.clientY <= 0) {
                                this.trigger();
                            }
                        };

                        document.documentElement.addEventListener('mouseleave', this.mouseLeaveHandler);
                    }
                },

                disarm() {
                    if (this.mouseLeaveHandler) {
                        document.documentElement.removeEventListener('mouseleave', this.mouseLeaveHandler);
                    }

                    if (this.popstateHandler) {
                        window.removeEventListener('popstate', this.popstateHandler);
                    }
                },

                trigger() {
                    if (this.isOpen || this.shownThisSession()) {
                        return;
                    }

                    this.isOpen = true;

                    try {
                        localStorage.setItem('uf_exit_intent_last_shown', String(Date.now()));
                        sessionStorage.setItem('uf_exit_intent_shown_session', '1');
                    } catch (e) {}

                    /* One trigger per session — tear down whichever listener fired. */
                    this.disarm();

                    this.$nextTick(() => {
                        const panel = this.isMobile ? this.$refs.mobilePanel : this.$refs.desktopPanel;

                        panel && panel.focus();
                    });
                },

                close() {
                    this.isOpen = false;
                },

                claim() {
                    const code = this.couponCode;

                    const flash = (type, message) => {
                        const emitter = window.app && window.app.config.globalProperties.$emitter;

                        if (emitter) {
                            emitter.emit('add-flash', { type, message });
                        }
                    };

                    const onSuccess = () => {
                        flash('success', this.copiedMessage);
                        this.close();
                    };

                    const onFailure = () => {
                        flash('warning', this.copyFailedTemplate.replace(':code', code));
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(code).then(onSuccess).catch(onFailure);

                        return;
                    }

                    /* Fallback for browsers without the Clipboard API / non-secure context. */
                    try {
                        const textarea = document.createElement('textarea');

                        textarea.value = code;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';

                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);

                        onSuccess();
                    } catch (e) {
                        onFailure();
                    }
                },
            },
        });
    </script>
@endPushOnce
