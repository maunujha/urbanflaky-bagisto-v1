@php
    $showCompare  = (bool) core()->getConfigData('catalog.products.settings.compare_option');
    $showWishlist = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');
@endphp

<div class="mh-root lg:hidden">

    {{-- ── BAR ── --}}
    <div class="mh-bar">

        {{-- LEFT — hamburger drawer + logo --}}
        <div class="mh-left">
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.before') !!}
            <v-mobile-drawer></v-mobile-drawer>
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.before') !!}

            <a
                href="{{ route('shop.home.index') }}"
                class="mh-logo"
                aria-label="@lang('shop::app.components.layouts.header.mobile.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                >
            </a>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.after') !!}
        </div>

        {{-- RIGHT — search + cart + account --}}
        <div class="mh-right">

            {{-- Search icon --}}
            <button
                type="button"
                id="mh-search-toggle"
                class="mh-icon-btn"
                aria-label="@lang('shop::app.components.layouts.header.mobile.search')"
                aria-expanded="false"
                aria-controls="mh-search-panel"
            >
                <span class="icon-search"></span>
            </button>

            {{-- Compare (if enabled) --}}
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.before') !!}

            @if ($showCompare)
                <a
                    href="{{ route('shop.compare.index') }}"
                    class="mh-icon-btn"
                    aria-label="@lang('shop::app.components.layouts.header.mobile.compare')"
                >
                    <span class="icon-compare"></span>
                </a>
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.after') !!}

            {{-- Cart --}}
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.before') !!}

            @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                @include('shop::checkout.cart.mini-cart')
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.after') !!}

            {{-- Account --}}
            @guest('customer')
                <a
                    href="{{ route('shop.customer.session.create') }}"
                    class="mh-icon-btn"
                    aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                >
                    <span class="icon-users"></span>
                </a>
            @endguest

            @auth('customer')
                <a
                    href="{{ route('shop.customers.account.index') }}"
                    class="mh-icon-btn"
                    aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                >
                    <span class="icon-users"></span>
                </a>
            @endauth

            {{-- Reward-coins balance badge — shown only to logged-in customers with a positive balance --}}
            @auth('customer')
                @if (($coinBalance ?? 0) > 0)
                    <x-reward-coins::coin-badge :balance="$coinBalance" />
                @endif
            @endauth
        </div>
    </div>

    {{-- ── SLIDE-DOWN SEARCH PANEL ── --}}
    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.before') !!}

    <div id="mh-search-panel" class="mh-search-panel" aria-hidden="true">
        <form
            id="mobile-search-form"
            class="mh-search-form"
            action="{{ route('shop.search.index') }}"
        >
            <label for="mobile-search-input" class="sr-only">
                @lang('shop::app.components.layouts.header.mobile.search')
            </label>

            <span class="mh-search-icon icon-search" aria-hidden="true"></span>

            <div id="mobile-search-wrap" style="position: relative;">
                <input
                    type="text"
                    id="mobile-search-input"
                    class="mh-search-input"
                    name="query"
                    value="{{ request('query') }}"
                    placeholder="@lang('shop::app.components.layouts.header.mobile.search-text')"
                    autocomplete="off"
                    required
                >

                @if (core()->getConfigData('catalog.products.search.autocomplete') !== '0')
                    <div
                        id="mobile-autocomplete-dropdown"
                        class="absolute left-0 z-50 w-full rounded-lg border border-white/10 bg-uf-surface/95 shadow-[0_20px_40px_rgba(0,0,0,0.5)] backdrop-blur hidden"
                        role="listbox"
                    >
                        <ul id="mobile-autocomplete-list" class="py-1"></ul>
                        <div class="border-t border-white/10 px-4 py-2.5">
                            <a id="mobile-autocomplete-viewall" href="#" class="block text-center text-xs font-medium text-uf-accent hover:underline"></a>
                        </div>
                    </div>
                @endif

                @if (core()->getConfigData('catalog.products.search.trending_searches') !== '0')
                    <div
                        id="mobile-trending-dropdown"
                        class="absolute left-0 z-50 w-full rounded-lg border border-white/10 bg-uf-surface/95 shadow-[0_20px_40px_rgba(0,0,0,0.5)] backdrop-blur hidden"
                        role="listbox"
                    >
                        <div class="flex items-center gap-2 border-b border-white/10 px-4 py-2.5">
                            <span class="icon-trend text-uf-accent text-sm"></span>
                            <span class="text-xs font-semibold uppercase tracking-wide text-white/60">Trending Searches</span>
                        </div>
                        <ul id="mobile-trending-list" class="py-1"></ul>
                    </div>
                @endif
            </div>

            <button
                type="button"
                id="mh-search-close"
                class="mh-search-close"
                aria-label="Close search"
            >
                <span class="icon-cross-large">×</span>
            </button>

            @if (core()->getConfigData('catalog.products.settings.image_search'))
                @include('shop::search.images.index')
            @endif
        </form>
    </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.after') !!}
</div>

{{-- Backdrop rendered outside .mh-root so it can cover the whole page below the header --}}
<div id="mh-search-backdrop" class="mh-search-backdrop lg:hidden" aria-hidden="true"></div>

@pushOnce('scripts')
    <script>
    /* Delegated handlers — work even if Vue mounts the header later. */
    (function () {
        function $(id) { return document.getElementById(id); }

        function openSearch() {
            const panel = $('mh-search-panel');
            const backdrop = $('mh-search-backdrop');
            const toggle = $('mh-search-toggle');
            const input = $('mobile-search-input');
            if (!panel || !input) return;
            panel.classList.add('mh-open');
            backdrop?.classList.add('mh-open');
            panel.setAttribute('aria-hidden', 'false');
            toggle?.setAttribute('aria-expanded', 'true');
            document.body.classList.add('mh-search-locked');
            setTimeout(() => input.focus(), 60);
        }

        function closeSearch() {
            const panel = $('mh-search-panel');
            const backdrop = $('mh-search-backdrop');
            const toggle = $('mh-search-toggle');
            if (!panel) return;
            panel.classList.remove('mh-open');
            backdrop?.classList.remove('mh-open');
            panel.setAttribute('aria-hidden', 'true');
            toggle?.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('mh-search-locked');
            ['mobile-autocomplete-dropdown', 'mobile-trending-dropdown'].forEach(id => {
                const el = $(id);
                if (el) el.classList.add('hidden');
            });
        }

        function syncClearBtn() {
            const input = $('mobile-search-input');
            const clearBtn = $('mh-search-close');
            if (!input || !clearBtn) return;
            clearBtn.classList.toggle('mh-show', input.value.length > 0);
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('#mh-search-toggle')) {
                e.preventDefault();
                const panel = $('mh-search-panel');
                if (panel && panel.classList.contains('mh-open')) closeSearch();
                else openSearch();
                return;
            }
            if (e.target.closest('#mh-search-close')) {
                e.preventDefault();
                const input = $('mobile-search-input');
                if (input) {
                    input.value = '';
                    input.focus();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    syncClearBtn();
                }
                return;
            }
            if (e.target.closest('#mh-search-backdrop')) {
                closeSearch();
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target && e.target.id === 'mobile-search-input') syncClearBtn();
        });

        document.addEventListener('keydown', function (e) {
            const panel = $('mh-search-panel');
            if (e.key === 'Escape' && panel && panel.classList.contains('mh-open')) closeSearch();
        });
    })();
    </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-mobile-drawer-template">
        <x-shop::drawer
            position="left"
            width="100%"
            @close="onDrawerClose"
        >
            <x-slot:toggle>
                <span class="icon-hamburger" role="button" aria-label="Menu"></span>
            </x-slot>

            <x-slot:header>
                <div class="flex items-center justify-between">
                    <a href="{{ route('shop.home.index') }}">
                        <img
                            src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                            alt="{{ config('app.name') }}"
                            style="max-height: 32px; width: auto;"
                        >
                    </a>
                </div>
            </x-slot>

            <x-slot:content class="!p-0">
                <!-- Account Profile Hero Section -->
                <div class="p-4 border-b border-zinc-200">
                    <div class="grid grid-cols-[auto_1fr] items-center gap-4 rounded-xl border border-zinc-200 p-2.5">
                        <div>
                            <img
                                src="{{ auth()->user()?->image_url ?? bagisto_asset('images/user-placeholder.png') }}"
                                class="h-[60px] w-[60px] rounded-full max-md:rounded-full"
                            >
                        </div>

                        @guest('customer')
                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="flex text-base font-medium"
                            >
                                @lang('shop::app.components.layouts.header.mobile.login')
                                <i class="icon-double-arrow text-2xl ltr:ml-2.5 rtl:mr-2.5"></i>
                            </a>
                        @endguest

                        @auth('customer')
                            <div class="flex flex-col justify-between gap-2.5 max-md:gap-0" v-pre>
                                <p class="text-2xl break-all font-mediums max-md:text-xl">Hello! {{ auth()->user()?->first_name }}</p>
                                <p class="no-underline text-zinc-500 max-md:text-sm">{{ auth()->user()?->email }}</p>
                            </div>
                        @endauth
                    </div>
                </div>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.before') !!}

                <!-- Mobile category view -->
                <v-mobile-category ref="mobileCategory"></v-mobile-category>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.after') !!}
            </x-slot>

            <x-slot:footer class="!p-0">
                {{-- Guest auth CTA — pinned to the bottom of the drawer (content slot is flex-1) --}}
                @guest('customer')
                    <div class="border-t border-white/10 bg-gradient-to-t from-black/50 to-transparent px-4 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <p class="mb-3 text-center font-poppins text-[11px] font-medium tracking-[0.3px] text-white/45">
                            Sign in for faster checkout, order tracking &amp; member rewards.
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="flex items-center justify-center rounded-[2px] border border-white/25 px-4 py-3.5 font-poppins text-[11px] font-semibold uppercase tracking-[2px] text-white transition-colors duration-200 hover:border-white hover:bg-white hover:text-uf-bg"
                            >
                                @lang('shop::app.components.layouts.header.mobile.sign-in')
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="flex items-center justify-center rounded-[2px] bg-uf-accent px-4 py-3.5 font-poppins text-[11px] font-semibold uppercase tracking-[2px] text-uf-bg transition-colors duration-200 hover:bg-uf-accent-hover"
                            >
                                @lang('shop::app.components.layouts.header.mobile.sign-up')
                            </a>
                        </div>
                    </div>
                @endguest

                @if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 )
                    <div class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-white/10 bg-zinc-900 text-zinc-100 px-5 ltr:left-0 rtl:right-0">
                        <x-shop::drawer position="bottom" width="100%">
                            <x-slot:toggle>
                                <div class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base" role="button" v-pre>
                                    {{ core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode() }}
                                </div>
                            </x-slot>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">@lang('shop::app.components.layouts.header.mobile.currencies')</p>
                                </div>
                            </x-slot>
                            <x-slot:content class="!px-0">
                                <div class="overflow-auto" :style="{ height: getCurrentScreenHeight }">
                                    <v-currency-switcher></v-currency-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>

                        <span class="h-5 w-0.5 bg-white/15"></span>

                        <x-shop::drawer position="bottom" width="100%">
                            <x-slot:toggle>
                                <div class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base" role="button" v-pre>
                                    <img
                                        src="{{ ! empty(core()->getCurrentLocale()->logo_url) ? core()->getCurrentLocale()->logo_url : bagisto_asset('images/default-language.svg') }}"
                                        class="h-full"
                                        alt="Default locale"
                                        width="24"
                                        height="16"
                                    />
                                    {{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}
                                </div>
                            </x-slot>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">@lang('shop::app.components.layouts.header.mobile.locales')</p>
                                </div>
                            </x-slot>
                            <x-slot:content class="!px-0">
                                <div class="overflow-auto" :style="{ height: getCurrentScreenHeight }">
                                    <v-locale-switcher></v-locale-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>
                    </div>
                @endif
            </x-slot>
        </x-shop::drawer>
    </script>

    <script type="text/x-template" id="v-mobile-category-template">
        <div :key="resetKey" class="overflow-auto" :style="{ maxHeight: getCurrentScreenHeight }">
            <p class="px-6 pb-2 pt-5 font-poppins text-[11px] font-semibold uppercase tracking-[3px] text-white/35">
                @lang('shop::app.components.layouts.header.mobile.categories')
            </p>

            <div class="pb-8">
                <v-category-node
                    v-for="category in categories"
                    :key="category.id"
                    :category="category"
                    :level="0"
                ></v-category-node>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mobile-category', {
            template: '#v-mobile-category-template',

            data() {
                return {
                    categories: [],
                    resetKey: 0,
                }
            },

            mounted() {
                this.initCategories();
            },

            computed: {
                getCurrentScreenHeight() {
                    return window.innerHeight - (window.innerWidth < 920 ? 61 : 0) + 'px';
                },
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories');
                        if (stored) {
                            this.categories = JSON.parse(stored);
                        }
                    } catch (e) {}

                    /* Always refresh in the background. The cached copy gives an
                       instant first paint; this silent re-fetch picks up catalog
                       changes (new/renamed/reordered categories) on the next load. */
                    this.getCategories();
                },
                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            const fresh = response.data.data;
                            if (! fresh) return;

                            const serialized = JSON.stringify(fresh);
                            if (serialized !== localStorage.getItem('categories')) {
                                this.categories = fresh;
                                localStorage.setItem('categories', serialized);
                            }
                        })
                        .catch(error => { console.log(error); });
                },
                /* Collapse every expanded node by remounting the tree. */
                collapseAll() {
                    this.resetKey++;
                },
            },
        });

        app.component('v-mobile-drawer', {
            template: '#v-mobile-drawer-template',

            methods: {
                onDrawerClose() {
                    this.$refs.mobileCategory?.collapseAll();
                }
            },
        });
    </script>
@endPushOnce
