{{--
    Head tracking — Google Tag Manager + Microsoft Clarity + ecommerce data layer.

    Placed as high in <head> as possible. The data layer is initialised FIRST so
    page-level ecommerce pushes (rendered into @stack('datalayer') by individual
    views) are already queued when GTM loads and replays them. Every tag is gated
    by its config id, so emptying GTM_CONTAINER_ID / CLARITY_PROJECT_ID disables it.

    IDs come from config/services.php (committed defaults, .env-overridable).
--}}
@php
    $gtmId     = config('services.gtm.container_id');
    $clarityId = config('services.clarity.project_id');

    /* Cookie consent layer. When OFF the tags load exactly as before; when ON
       they are deferred and gated by the visitor's category choices. */
    $ccEnabled         = \App\Support\CookieConsent::enabled();
    $ccVersion         = \App\Support\CookieConsent::version();
    $ccValidityMonths  = \App\Support\CookieConsent::validityMonths();
    $ccServerConsent   = $ccEnabled
        ? \App\Support\CookieConsent::forUser(auth()->guard('customer')->user())
        : null;

    /* Coarse page_type for every page. Catalog views (product/category share one
       Bagisto route) refine this in their own @push('datalayer') — last push wins. */
    $routeName = request()->route()?->getName() ?? '';
    $pageType  = match (true) {
        $routeName === 'shop.home.index'                     => 'home',
        $routeName === 'shop.search.index'                   => 'search',
        $routeName === 'shop.checkout.cart.index'            => 'cart',
        str_contains($routeName, 'checkout.onepage.success') => 'purchase',
        str_contains($routeName, 'checkout')                 => 'checkout',
        str_contains($routeName, 'customers')                => 'account',
        $routeName === 'shop.product_or_category.index'      => 'catalog',
        default                                              => 'other',
    };
@endphp

<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        page_type: @json($pageType),
        page_location: @json(url()->current()),
    });

    @foreach (session('datalayer_events', []) as $flashedEvent)
        window.dataLayer.push(@json($flashedEvent));
    @endforeach

    /* Shared client-side data-layer helper — maps Bagisto ProductResource /
       cart objects to GA4 ecommerce items so every Vue component pushes the
       same shape. Server-rendered events build their items in PHP instead. */
    window.ufTrack = {
        currency: @json(\App\Support\DataLayer::currency()),
        brand: 'Urbanflaky',

        push: function (event, ecommerce, extra) {
            if (! window.dataLayer) return;
            var payload = Object.assign({ event: event }, extra || {});
            if (ecommerce !== undefined && ecommerce !== null) {
                window.dataLayer.push({ ecommerce: null }); // clear the previous object
                payload.ecommerce = ecommerce;
            }
            window.dataLayer.push(payload);
        },

        price: function (product) {
            var p = product && product.prices;
            var v = (p && p.final && p.final.price) || (p && p.regular && p.regular.price) || 0;
            return Math.round(parseFloat(v) * 100) / 100 || 0;
        },

        item: function (product, extra) {
            return Object.assign({
                item_id: product.sku,
                item_name: product.name,
                item_brand: this.brand,
                price: this.price(product),
                quantity: 1,
            }, extra || {});
        },

        viewItemList: function (products, listName) {
            if (! products || ! products.length) return;
            var self = this;
            var items = products.map(function (p, i) {
                return self.item(p, { index: i, item_list_name: listName });
            });
            this.push('view_item_list', { item_list_name: listName, items: items });
        },

        addToCart: function (product, qty, extra) {
            qty = qty || 1;
            var item = this.item(product, Object.assign({ quantity: qty }, extra || {}));
            this.push('add_to_cart', { currency: this.currency, value: +(item.price * qty).toFixed(2), items: [item] });
        },

        removeFromCart: function (item) {
            var qty = item.quantity || 1;
            this.push('remove_from_cart', { currency: this.currency, value: +(((item.price || 0) * qty)).toFixed(2), items: [item] });
        },

        /* Map Bagisto CartItemResource[] (cart / checkout) to GA4 items. */
        mapCartItems: function (items) {
            var self = this;
            return (items || []).map(function (it) {
                var variant = (it.options || []).map(function (o) { return o.option_label; }).filter(Boolean).join(' / ');
                return {
                    item_id: it.sku,
                    item_name: it.name,
                    item_brand: self.brand,
                    item_variant: variant || undefined,
                    price: Math.round((parseFloat(it.price) || 0) * 100) / 100,
                    quantity: it.quantity || 1,
                };
            });
        },

        /* Shared builder for begin_checkout / add_shipping_info / add_payment_info.
           `extra` is merged onto the ecommerce object (coupon, shipping_tier, payment_type). */
        checkoutStep: function (event, cart, items, extra) {
            var mapped = this.mapCartItems(items);
            var value = cart && cart.grand_total != null ? parseFloat(cart.grand_total)
                      : (cart && cart.sub_total != null ? parseFloat(cart.sub_total) : 0);
            if (! value) {
                value = mapped.reduce(function (s, i) { return s + i.price * i.quantity; }, 0);
            }
            this.push(event, Object.assign({
                currency: this.currency,
                value: Math.round(value * 100) / 100,
                items: mapped,
            }, extra || {}));
        },
    };
</script>

{{-- Page-specific ecommerce events (view_item, view_item_list, …) render here,
     before the container, so GTM picks them up on its initial replay. --}}
@stack('datalayer')

@unless ($ccEnabled)
    {{-- Consent layer OFF → original unconditional loading (unchanged behavior). --}}
    @if ($gtmId)
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
        <!-- End Google Tag Manager -->
    @endif

    @if ($clarityId)
        <!-- Microsoft Clarity -->
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "{{ $clarityId }}");
        </script>
        <!-- End Microsoft Clarity -->
    @endif
@else
    {{--
        Consent layer ON. Google Consent Mode v2 defaults are set to "denied"
        BEFORE any tag loads, and GTM/Clarity are injected only once the matching
        category is granted. A returning visitor with a valid stored consent is
        re-applied synchronously here so their tags load without re-prompting.
    --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { window.dataLayer.push(arguments); }

        window.ufConsent = {
            STORAGE_KEY: 'cookie_consent',

            config: {
                version:        @json($ccVersion),
                validityMonths: {{ (int) $ccValidityMonths }},
                gtmId:          @json($gtmId),
                clarityId:      @json($clarityId),
            },

            /* Active consent applied this page load (null = undecided). */
            current: null,

            _gtmLoaded: false,
            _clarityLoaded: false,

            /* Consent Mode v2 — everything off except security until told otherwise. */
            setDefaults: function () {
                gtag('consent', 'default', {
                    ad_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied',
                    analytics_storage: 'denied',
                    functionality_storage: 'denied',
                    personalization_storage: 'denied',
                    security_storage: 'granted',
                    wait_for_update: 500,
                });
            },

            read: function () {
                try { return JSON.parse(localStorage.getItem(this.STORAGE_KEY)); }
                catch (e) { return null; }
            },

            isValid: function (c) {
                if (! c || c.version !== this.config.version || ! c.timestamp) return false;
                var age = Date.now() - new Date(c.timestamp).getTime();
                return age < this.config.validityMonths * 30 * 24 * 60 * 60 * 1000;
            },

            /* Persist + apply a fresh choice (called by the banner/modal). */
            set: function (c) {
                c.essential = true;
                c.version = this.config.version;
                c.timestamp = new Date().toISOString();
                try { localStorage.setItem(this.STORAGE_KEY, JSON.stringify(c)); } catch (e) {}
                this.apply(c);
                return c;
            },

            apply: function (c) {
                this.current = c;
                this.updateConsentMode(c);
                this.loadScripts(c);
                window.dataLayer.push({
                    event: 'cookie_consent_update',
                    cookie_consent: {
                        analytics: !! c.analytics,
                        marketing: !! c.marketing,
                        preferences: !! c.preferences,
                    },
                });
            },

            updateConsentMode: function (c) {
                gtag('consent', 'update', {
                    analytics_storage:       c.analytics ? 'granted' : 'denied',
                    ad_storage:              c.marketing ? 'granted' : 'denied',
                    ad_user_data:            c.marketing ? 'granted' : 'denied',
                    ad_personalization:      c.marketing ? 'granted' : 'denied',
                    functionality_storage:   c.preferences ? 'granted' : 'denied',
                    personalization_storage: c.preferences ? 'granted' : 'denied',
                });
            },

            /* Hard block: a tag is only injected when its category is granted. */
            loadScripts: function (c) {
                if (c.analytics || c.marketing) this.loadGTM();
                if (c.analytics) this.loadClarity();
            },

            loadGTM: function () {
                if (this._gtmLoaded || ! this.config.gtmId) return;
                this._gtmLoaded = true;
                (function (w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s),
                        dl = l != 'dataLayer' ? '&l=' + l : '';
                    j.async = true;
                    j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                    f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', this.config.gtmId);
            },

            loadClarity: function () {
                if (this._clarityLoaded || ! this.config.clarityId) return;
                this._clarityLoaded = true;
                (function (c, l, a, r, i, t, y) {
                    c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
                    t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
                    y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
                })(window, document, 'clarity', 'script', this.config.clarityId);
            },
        };

        ufConsent.setDefaults();

        /* Re-apply an existing decision: localStorage first, then the logged-in
           customer's server-stored copy (seeds a fresh device). */
        (function () {
            var local  = ufConsent.read();
            var server = @json($ccServerConsent);
            var stored = (local && ufConsent.isValid(local)) ? local
                       : (server && ufConsent.isValid(server)) ? server
                       : null;

            if (stored) {
                if (! local) {
                    try { localStorage.setItem(ufConsent.STORAGE_KEY, JSON.stringify(stored)); } catch (e) {}
                }
                ufConsent.apply(stored);
            }
        })();
    </script>
@endunless
