{{--
    Urbanflaky — GDPR Cookie Consent (premium, script-gating, DB-backed).

    Rendered as a sibling before #app (Vue never manages it). All tracking is
    gated by window.ufConsent (defined in tracking/head.blade.php): GA4/GTM and
    Clarity only load once the matching category is granted. Plain vanilla JS —
    no Vue, no build step — so the bar can appear the instant the DOM is ready.
--}}
@php
    $privacyUrl  = url('privacy-policy');
    $isLoggedIn  = auth()->guard('customer')->check();

    $categories = [
        'analytics' => [
            'title' => 'Analytics Cookies',
            'desc'  => 'Help us understand how visitors interact with our website.',
        ],
        'marketing' => [
            'title' => 'Marketing Cookies',
            'desc'  => 'Used to personalize advertising and measure campaign performance.',
        ],
        'preferences' => [
            'title' => 'Preference Cookies',
            'desc'  => 'Remember your settings and improve your experience.',
        ],
    ];
@endphp

<!-- Consent Bar -->
<section
    id="uf-cc-banner"
    class="uf-cc-banner uf-cc-hidden"
    role="dialog"
    aria-live="polite"
    aria-label="Cookie consent"
    aria-describedby="uf-cc-banner-desc"
>
    <div class="uf-cc-banner-inner">
        <div class="uf-cc-banner-copy">
            <h2 class="uf-cc-banner-title">We value your privacy</h2>
            <p id="uf-cc-banner-desc" class="uf-cc-banner-text">
                We use cookies to keep the store running, analyse traffic and personalise content.
                Choose “Accept All” to enable everything, or manage your choices.
                Read our <a href="{{ $privacyUrl }}" class="uf-cc-link">Privacy Policy</a>.
            </p>
        </div>

        <div class="uf-cc-banner-actions">
            <button type="button" class="uf-cc-btn uf-cc-btn-ghost" data-cc-action="customize">
                Customize Preferences
            </button>
            <button type="button" class="uf-cc-btn uf-cc-btn-outline" data-cc-action="reject">
                Reject Non-Essential
            </button>
            <button type="button" class="uf-cc-btn uf-cc-btn-solid" data-cc-action="accept">
                Accept All
            </button>
        </div>
    </div>
</section>

<!-- Preferences Modal -->
<div id="uf-cc-modal" class="uf-cc-modal-overlay uf-cc-hidden" data-cc-action="backdrop">
    <div
        class="uf-cc-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="uf-cc-modal-title"
    >
        <div class="uf-cc-modal-head">
            <h2 id="uf-cc-modal-title" class="uf-cc-modal-title">Cookie Preferences</h2>
            <button
                type="button"
                class="uf-cc-close"
                data-cc-action="close"
                aria-label="Close cookie preferences"
            >&times;</button>
        </div>

        <div class="uf-cc-modal-body">
            <!-- Essential (locked) -->
            <div class="uf-cc-cat">
                <div class="uf-cc-cat-head">
                    <span class="uf-cc-cat-title" id="uf-cc-essential-label">Essential Cookies</span>
                    <span class="uf-cc-locked">Always On</span>
                    <label class="uf-cc-toggle">
                        <input
                            type="checkbox"
                            class="uf-cc-switch"
                            checked
                            disabled
                            aria-labelledby="uf-cc-essential-label"
                        />
                        <span class="uf-cc-slider" aria-hidden="true"></span>
                    </label>
                </div>
                <p class="uf-cc-cat-desc">Required for the website to function properly.</p>
            </div>

            @foreach ($categories as $key => $cat)
                <div class="uf-cc-cat">
                    <div class="uf-cc-cat-head">
                        <span class="uf-cc-cat-title" id="uf-cc-{{ $key }}-label">{{ $cat['title'] }}</span>
                        <label class="uf-cc-toggle">
                            <input
                                type="checkbox"
                                class="uf-cc-switch"
                                data-cc-toggle="{{ $key }}"
                                aria-labelledby="uf-cc-{{ $key }}-label"
                            />
                            <span class="uf-cc-slider" aria-hidden="true"></span>
                        </label>
                    </div>
                    <p class="uf-cc-cat-desc">{{ $cat['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="uf-cc-modal-foot">
            <button type="button" class="uf-cc-btn uf-cc-btn-outline" data-cc-action="reject">
                Reject Non-Essential
            </button>
            <button type="button" class="uf-cc-btn uf-cc-btn-ghost" data-cc-action="save">
                Save Preferences
            </button>
            <button type="button" class="uf-cc-btn uf-cc-btn-solid" data-cc-action="accept">
                Accept All
            </button>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
    (function () {
        var STORE_URL   = @json(route('cookie.consent.store'));
        var CSRF        = @json(csrf_token());
        var IS_LOGGED   = @json($isLoggedIn);
        var CATEGORIES  = ['analytics', 'marketing', 'preferences'];

        /* Prefer the per-request XSRF-TOKEN cookie so a full-page-cached HTML
           response (stale inline token) never causes a 419. */
        function csrfToken() {
            var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            return m ? decodeURIComponent(m[1]) : CSRF;
        }

        var banner = document.getElementById('uf-cc-banner');
        var modal  = document.getElementById('uf-cc-modal');
        if (! banner || ! modal || ! window.ufConsent) return;

        var lastFocused = null;

        function show(el)  { el.classList.remove('uf-cc-hidden'); }
        function hide(el)  { el.classList.add('uf-cc-hidden'); }

        /* ----- Persist a decision everywhere (consent mode, scripts, storage, DB) ----- */
        function persist(consent) {
            window.ufConsent.set(consent); // localStorage + Consent Mode + lazy-load tags

            if (IS_LOGGED) {
                fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': csrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(consent),
                    keepalive: true,
                }).catch(function () {});
            }

            hide(banner);
            closeModal();
        }

        function acceptAll() {
            persist({ analytics: true, marketing: true, preferences: true });
        }

        function rejectAll() {
            persist({ analytics: false, marketing: false, preferences: false });
        }

        function saveFromToggles() {
            var consent = {};
            CATEGORIES.forEach(function (key) {
                var input = modal.querySelector('[data-cc-toggle="' + key + '"]');
                consent[key] = !! (input && input.checked);
            });
            persist(consent);
        }

        /* ----- Modal open/close with focus management ----- */
        function syncToggles() {
            var current = window.ufConsent.current || {};
            CATEGORIES.forEach(function (key) {
                var input = modal.querySelector('[data-cc-toggle="' + key + '"]');
                if (input) input.checked = !! current[key];
            });
        }

        function openModal() {
            lastFocused = document.activeElement;
            syncToggles();
            show(modal);
            document.addEventListener('keydown', onKeydown);
            var focusable = getFocusable();
            if (focusable.length) focusable[0].focus();
        }

        function closeModal() {
            if (modal.classList.contains('uf-cc-hidden')) return;
            hide(modal);
            document.removeEventListener('keydown', onKeydown);
            if (lastFocused && lastFocused.focus) lastFocused.focus();
        }

        function getFocusable() {
            return Array.prototype.slice.call(modal.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), a[href]'
            )).filter(function (el) { return el.offsetParent !== null; });
        }

        function onKeydown(e) {
            if (e.key === 'Escape') { closeModal(); return; }
            if (e.key !== 'Tab') return;

            var f = getFocusable();
            if (! f.length) return;
            var first = f[0], last = f[f.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault(); last.focus();
            } else if (! e.shiftKey && document.activeElement === last) {
                e.preventDefault(); first.focus();
            }
        }

        /* ----- Wire up actions (event delegation) ----- */
        function handleAction(e) {
            var trigger = e.target.closest('[data-cc-action]');
            if (! trigger) return;
            var action = trigger.getAttribute('data-cc-action');

            if (action === 'backdrop' && e.target !== modal) return; // ignore inner clicks

            switch (action) {
                case 'accept':    acceptAll(); break;
                case 'reject':    rejectAll(); break;
                case 'save':      saveFromToggles(); break;
                case 'customize': openModal(); break;
                case 'close':
                case 'backdrop':  closeModal(); break;
            }
        }

        banner.addEventListener('click', handleAction);
        modal.addEventListener('click', handleAction);

        /* Footer link / any element opting in via the public hook. */
        window.ufOpenCookiePreferences = function () {
            hide(banner);
            openModal();
        };
        document.addEventListener('click', function (e) {
            if (e.target.closest('.js-cookie-preferences')) {
                e.preventDefault();
                window.ufOpenCookiePreferences();
            }
        });

        /* ----- First run: show the bar only when there's no valid decision ----- */
        if (! window.ufConsent.current) {
            show(banner);
        }
    })();
    </script>
@endPushOnce
