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
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isOpen && ! isMobile"
                    class="fixed inset-0 z-[1100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                    @click.self="close"
                >
                    <div
                        ref="desktopPanel"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="uf-eip-title"
                        tabindex="-1"
                        class="grid w-full max-w-[880px] overflow-hidden rounded-2xl border border-white/10 bg-uf-bg shadow-[0_24px_64px_rgba(0,0,0,0.6)] max-md:grid-cols-1"
                        :class="hasDesktopImage ? 'grid-cols-2' : 'max-w-[540px] grid-cols-1'"
                        @keydown.esc="close"
                    >
                        <!-- Image -->
                        <div
                            v-if="hasDesktopImage"
                            class="relative hidden min-h-[480px] bg-uf-surface md:block"
                        >
                            <img
                                :src="desktopImage"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>

                        <!-- Content -->
                        <div class="relative flex flex-col justify-center gap-5 p-10 max-sm:p-6">
                            <button
                                type="button"
                                class="icon-cancel absolute right-5 top-5 text-2xl text-white/60 transition-colors hover:text-uf-accent"
                                :aria-label="closeLabel"
                                @click="close"
                            ></button>

                            <p
                                id="uf-eip-title"
                                class="font-poppins text-2xl font-extrabold uppercase tracking-[2px] text-uf-text max-sm:text-xl"
                            >
                                @{{ popupTitle }}
                            </p>

                            <p class="text-sm leading-relaxed text-uf-muted">
                                @{{ popupDescription }}
                            </p>

                            <p class="text-lg font-semibold text-uf-accent">
                                @{{ offerText }}
                            </p>

                            <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-uf-accent/40 bg-white/[0.03] px-5 py-3.5">
                                <span class="font-poppins text-lg font-bold tracking-[3px] text-uf-text">@{{ couponCode }}</span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-uf-accent" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </div>

                            <button
                                type="button"
                                class="primary-button w-full py-3.5"
                                @click="claim"
                            >
                                @{{ ctaText }}
                            </button>

                            <button
                                type="button"
                                class="text-center text-sm text-uf-muted underline-offset-4 transition-colors hover:text-uf-accent hover:underline"
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
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
            >
                <div
                    v-if="isOpen && isMobile"
                    class="fixed inset-0 z-[1100] bg-black/70"
                    @click.self="close"
                ></div>
            </transition>

            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition duration-200 ease-in"
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
                    class="fixed inset-x-0 bottom-0 z-[1101] max-h-[78vh] w-full overflow-y-auto rounded-t-2xl border-t border-white/10 bg-uf-bg"
                    @keydown.esc="close"
                >
                    <button
                        type="button"
                        class="icon-cancel absolute right-4 top-4 z-10 text-2xl text-white/70 transition-colors hover:text-uf-accent"
                        :aria-label="closeLabel"
                        @click="close"
                    ></button>

                    <img
                        v-if="hasMobileImage"
                        :src="mobileImage"
                        alt=""
                        class="h-[30vh] w-full object-cover"
                        loading="lazy"
                        decoding="async"
                    >

                    <div class="flex flex-col gap-4 p-6">
                        <p
                            id="uf-eip-title-mobile"
                            class="font-poppins text-xl font-extrabold uppercase tracking-[2px] text-uf-text"
                        >
                            @{{ popupTitle }}
                        </p>

                        <p class="text-sm leading-relaxed text-uf-muted">
                            @{{ popupDescription }}
                        </p>

                        <p class="text-base font-semibold text-uf-accent">
                            @{{ offerText }}
                        </p>

                        <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-uf-accent/40 bg-white/[0.03] px-5 py-4">
                            <span class="font-poppins text-xl font-bold tracking-[3px] text-uf-text">@{{ couponCode }}</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-uf-accent" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </div>

                        <button
                            type="button"
                            class="primary-button w-full py-3.5"
                            @click="claim"
                        >
                            @{{ ctaText }}
                        </button>

                        <button
                            type="button"
                            class="text-center text-sm text-uf-muted underline-offset-4 transition-colors hover:text-uf-accent hover:underline"
                            @click="close"
                        >
                            @{{ continueText }}
                        </button>
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
