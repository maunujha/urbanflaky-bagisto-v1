{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

<div class="relative flex w-full items-center justify-between gap-6 px-10 transition-all duration-300 group-data-[scrolled=true]/header:min-h-[60px] min-h-[78px] xl:px-14 group-data-[scrolled=true]/header:py-1">

    <!-- Left: Logo + Categories -->
    <div class="flex items-center gap-x-10 max-[1180px]:gap-x-6">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

        <a
            href="{{ route('shop.home.index') }}"
            class="shrink-0 transition-all duration-300 group-data-[scrolled=true]/header:scale-90"
            aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
        >
            <img
                src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                width="131"
                height="29"
                alt="{{ config('app.name') }}"
                class="h-auto w-[120px] xl:w-[131px]"
            >
        </a>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

        <v-desktop-category>
            <div class="flex items-center gap-5">
                <span class="h-6 w-20 rounded shimmer" role="presentation"></span>
                <span class="h-6 w-20 rounded shimmer" role="presentation"></span>
                <span class="h-6 w-20 rounded shimmer" role="presentation"></span>
            </div>
        </v-desktop-category>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}
    </div>

    <!-- Right: Icon group -->
    <div class="flex items-center gap-x-1.5">

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

        <!-- Search trigger -->
        <button
            type="button"
            id="uf-search-toggle"
            aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
            aria-expanded="false"
            aria-controls="uf-search-panel"
            class="flex h-10 w-10 items-center justify-center rounded-full text-white/85 transition-all hover:bg-white/10 hover:text-uf-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-uf-accent"
        >
            <span class="icon-search text-[22px] leading-none"></span>
        </button>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

        <!-- Compare -->
        @if(core()->getConfigData('catalog.products.settings.compare_option'))
            <a
                href="{{ route('shop.compare.index') }}"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.compare')"
                class="flex h-10 w-10 items-center justify-center rounded-full text-white/85 transition-all hover:bg-white/10 hover:text-uf-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-uf-accent"
            >
                <span class="icon-compare text-[22px] leading-none" role="presentation"></span>
            </a>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

        <!-- Mini cart -->
        @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
            <div class="flex h-10 w-10 items-center justify-center rounded-full text-white/85 transition-all hover:bg-white/10 hover:text-uf-accent [&_.icon-cart]:text-[22px]">
                @include('shop::checkout.cart.mini-cart')
            </div>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

        <!-- user profile -->
        <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
            <x-slot:toggle>
                <span
                    class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-white/85 transition-all hover:bg-white/10 hover:text-uf-accent icon-users text-[22px] leading-none"
                    role="button"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                    tabindex="0"
                ></span>
            </x-slot>

                <!-- Guest Dropdown -->
                @guest('customer')
                    <x-slot:content>
                        <div class="grid gap-2.5">
                            <p class="text-xl font-poppins font-bold">
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome-guest')
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border-t border-white/10"></p>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.before') !!}

                        <div class="flex gap-4 mt-6">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="block m-0 mx-auto text-base text-center primary-button w-max rounded-2xl px-7 max-md:rounded-lg ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="block m-0 mx-auto text-base text-center border-2 secondary-button w-max rounded-2xl px-7 max-md:rounded-lg max-md:py-3 ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.sign-up')
                            </a>

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_up_button.after') !!}
                        </div>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.after') !!}
                    </x-slot>
                @endguest

                <!-- Customers Dropdown -->
                @auth('customer')
                    <x-slot:content class="!p-0">
                        <div class="grid gap-2.5 p-5 pb-0">
                            <p class="text-xl font-poppins font-bold" v-pre>
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome')’
                                {{ auth()->guard('customer')->user()->first_name }}
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border-t border-white/10"></p>

                        <div class="mt-2.5 grid gap-1 pb-2.5">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.before') !!}

                            <a
                                class="px-5 py-2 text-base cursor-pointer rounded-sm transition-colors hover:bg-white/5 hover:text-uf-accent"
                                href="{{ route('shop.customers.account.profile.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                            </a>

                            <a
                                class="px-5 py-2 text-base cursor-pointer rounded-sm transition-colors hover:bg-white/5 hover:text-uf-accent"
                                href="{{ route('shop.customers.account.orders.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.orders')
                            </a>

                            @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                <a
                                    class="px-5 py-2 text-base cursor-pointer rounded-sm transition-colors hover:bg-white/5 hover:text-uf-accent"
                                    href="{{ route('shop.customers.account.wishlist.index') }}"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.wishlist')
                                </a>
                            @endif

                            <!--Customers logout-->
                            @auth('customer')
                                <x-shop::form
                                    method="DELETE"
                                    action="{{ route('shop.customer.session.destroy') }}"
                                    id="customerLogout"
                                />

                                <a
                                    class="px-5 py-2 text-base cursor-pointer rounded-sm transition-colors hover:bg-white/5 hover:text-uf-accent"
                                    href="{{ route('shop.customer.session.destroy') }}"
                                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                                </a>
                            @endauth

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.after') !!}
                        </div>
                    </x-slot>
                @endauth
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
    </div>
</div>

<!-- Slide-down Search Panel -->
<div
    id="uf-search-panel"
    class="invisible absolute left-0 right-0 top-full z-30 -translate-y-3 border-b border-white/[0.08] bg-uf-bg/95 opacity-0 shadow-[0_24px_48px_rgba(0,0,0,0.55)] backdrop-blur-2xl transition-all duration-300 ease-out data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100"
    data-open="false"
    role="region"
    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
>
    <div class="mx-auto max-w-3xl px-10 py-10 xl:px-14">
        <form
            action="{{ route('shop.search.index') }}"
            class="relative"
            role="search"
            id="desktop-search-form"
        >
            <label for="desktop-search-input" class="sr-only">
                @lang('shop::app.components.layouts.header.desktop.bottom.search')
            </label>

            <span class="icon-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-2xl text-uf-accent"></span>

            <input
                type="text"
                name="query"
                id="desktop-search-input"
                value="{{ request('query') }}"
                placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                aria-required="true"
                minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                pattern="[^\\]+"
                autocomplete="off"
                required
                class="block w-full rounded-2xl border border-white/10 bg-white/[0.04] px-14 py-4 font-poppins text-base font-medium text-white placeholder:font-normal placeholder:tracking-wide placeholder:text-white/40 transition-all focus:border-uf-accent/60 focus:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-uf-accent/30"
            >

            <button
                type="button"
                id="uf-search-close"
                aria-label="Close search"
                class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-white/60 transition-all hover:bg-white/10 hover:text-uf-accent"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <button
                type="submit"
                class="hidden"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.submit')"
            ></button>

            @if (core()->getConfigData('catalog.products.settings.image_search'))
                @include('shop::search.images.index')
            @endif
        </form>

        @if (core()->getConfigData('catalog.products.search.autocomplete') !== '0')
            <div
                id="desktop-autocomplete-dropdown"
                class="mt-5 hidden overflow-hidden rounded-2xl border border-white/10 bg-uf-surface/95 shadow-[0_20px_40px_rgba(0,0,0,0.5)] backdrop-blur"
                role="listbox"
            >
                <ul id="desktop-autocomplete-list" class="max-h-[60vh] divide-y divide-white/[0.06] overflow-auto py-1"></ul>

                <div class="border-t border-white/[0.08] px-4 py-3">
                    <a
                        id="desktop-autocomplete-viewall"
                        href="#"
                        class="block text-center text-xs font-semibold uppercase tracking-[2px] text-uf-accent hover:text-uf-accentHover"
                    >
                        @lang('shop::app.components.layouts.header.desktop.bottom.search-text') &rarr;
                    </a>
                </div>
            </div>
        @endif

        @if (core()->getConfigData('catalog.products.search.trending_searches') !== '0')
            <div
                id="desktop-trending-dropdown"
                class="mt-5 hidden overflow-hidden rounded-2xl border border-white/10 bg-uf-surface/95 shadow-[0_20px_40px_rgba(0,0,0,0.5)] backdrop-blur"
                role="listbox"
            >
                <div class="flex items-center gap-2 border-b border-white/[0.08] px-4 py-3">
                    <span class="icon-trend text-sm text-uf-accent"></span>
                    <span class="text-[11px] font-semibold uppercase tracking-[2px] text-white/60">Trending Searches</span>
                </div>
                <ul id="desktop-trending-list" class="py-1"></ul>
            </div>
        @endif
    </div>
</div>

@pushOnce('scripts')
    @if (core()->getConfigData('catalog.products.search.autocomplete') !== '0' || core()->getConfigData('catalog.products.search.trending_searches') !== '0')
    <script>
        (function () {
            const AUTOCOMPLETE_ENABLED = {{ core()->getConfigData('catalog.products.search.autocomplete') !== '0' ? 'true' : 'false' }};
            const TRENDING_ENABLED     = {{ core()->getConfigData('catalog.products.search.trending_searches') !== '0' ? 'true' : 'false' }};
            const AUTOCOMPLETE_URL     = '{{ route('shop.api.search.autocomplete') }}';
            const TRENDING_URL         = '{{ route('shop.api.search.trending') }}';
            const SEARCH_URL           = '{{ route('shop.search.index') }}';
            const MIN_LENGTH           = {{ max(2, (int) (core()->getConfigData('catalog.products.search.min_query_length') ?? 0)) }};
            const INPUT_IDS            = ['desktop-search-input', 'mobile-search-input'];

            let debounceTimer  = null;
            let trendingCache  = null; // null = not fetched yet

            /* ---- Element helpers ---- */
            function acEls(p) {
                return {
                    input:    document.getElementById(p + '-search-input'),
                    dropdown: document.getElementById(p + '-autocomplete-dropdown'),
                    list:     document.getElementById(p + '-autocomplete-list'),
                    viewAll:  document.getElementById(p + '-autocomplete-viewall'),
                };
            }

            function trEls(p) {
                return {
                    dropdown: document.getElementById(p + '-trending-dropdown'),
                    list:     document.getElementById(p + '-trending-list'),
                };
            }

            function prefixOf(id) { return id.replace('-search-input', ''); }

            function showEl(dropdown)  { dropdown && dropdown.classList.remove('hidden'); }
            function hideEl(dropdown)  { dropdown && dropdown.classList.add('hidden'); }

            function hideAll(p) {
                hideEl(acEls(p).dropdown);
                hideEl(trEls(p).dropdown);
            }

            /* ---- Autocomplete ---- */
            function highlight(text, query) {
                const esc = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                return text.replace(new RegExp('(' + esc + ')', 'gi'), '<mark class="bg-uf-accent/20 text-uf-accent font-semibold not-italic px-0.5 rounded-sm">$1</mark>');
            }

            function renderAutocomplete(results, query, p) {
                const e = acEls(p);
                if (! e.list) return;
                e.list.innerHTML = '';

                if (! results.length) { hideEl(e.dropdown); return; }

                results.forEach(function (product) {
                    const li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.className = 'flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-white/[0.04] transition-colors';

                    const img = product.image
                        ? '<img src="' + product.image + '" alt="" class="h-11 w-11 flex-shrink-0 rounded-lg object-cover border border-white/10">'
                        : '<div class="h-11 w-11 flex-shrink-0 rounded-lg bg-white/[0.04] flex items-center justify-center border border-white/10"><span class="icon-image text-white/40 text-lg"></span></div>';

                    const priceHtml = product.original_price
                        ? '<span class="font-bold text-uf-accent">' + product.price + '</span><span class="ml-1.5 text-xs text-white/40 line-through">' + product.original_price + '</span>'
                        : '<span class="font-bold text-uf-accent">' + product.price + '</span>';

                    li.innerHTML = img + '<div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-white">' + highlight(product.name, query) + '</p><p class="text-xs mt-1">' + priceHtml + '</p></div>';

                    li.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                        window.location.href = product.url;
                    });

                    e.list.appendChild(li);
                });

                if (e.viewAll) {
                    e.viewAll.href = SEARCH_URL + '?query=' + encodeURIComponent(query);
                    e.viewAll.textContent = 'View all results for "' + query + '"';
                }
                hideEl(trEls(p).dropdown);
                showEl(e.dropdown);
            }

            /* ---- Trending ---- */
            function renderTrending(data, p) {
                const te = trEls(p);
                if (! te.list || ! te.dropdown) return;
                if (! data.length) { hideEl(te.dropdown); return; }

                if (te.list.children.length === 0) {
                    data.forEach(function (item) {
                        const li = document.createElement('li');
                        li.className = 'flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-white/[0.04] transition-colors';
                        li.innerHTML = '<span class="icon-search text-uf-accent/70 text-xs flex-shrink-0"></span>'
                            + '<span class="text-sm text-white/85">' + item.term + '</span>'
                            + '<span class="ml-auto text-xs text-white/40">' + item.count + '</span>';

                        li.addEventListener('mousedown', function (ev) {
                            ev.preventDefault();
                            const input = document.getElementById(p + '-search-input');
                            if (input) {
                                input.value = item.term;
                                input.closest('form').submit();
                            }
                        });

                        te.list.appendChild(li);
                    });
                }

                hideEl(acEls(p).dropdown);
                showEl(te.dropdown);
            }

            function showTrending(p) {
                if (! TRENDING_ENABLED) return;

                if (trendingCache !== null) {
                    renderTrending(trendingCache, p);
                    return;
                }

                fetch(TRENDING_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        trendingCache = data;
                        renderTrending(data, p);
                    })
                    .catch(function () { trendingCache = []; });
            }

            /* ---- Event delegation ---- */

            document.addEventListener('input', function (ev) {
                if (! INPUT_IDS.includes(ev.target.id)) return;

                clearTimeout(debounceTimer);
                const p     = prefixOf(ev.target.id);
                const query = ev.target.value.trim();

                if (query.length === 0) {
                    hideEl(acEls(p).dropdown);
                    showTrending(p);
                    return;
                }

                hideEl(trEls(p).dropdown);

                if (! AUTOCOMPLETE_ENABLED || query.length < MIN_LENGTH) { hideEl(acEls(p).dropdown); return; }

                debounceTimer = setTimeout(function () {
                    const cur = document.getElementById(p + '-search-input');
                    const q   = cur ? cur.value.trim() : '';
                    if (q.length < MIN_LENGTH) return;

                    const limit = p === 'mobile' ? 4 : 8;
                    fetch(AUTOCOMPLETE_URL + '?query=' + encodeURIComponent(q) + '&limit=' + limit, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        const c = document.getElementById(p + '-search-input');
                        if (c && c.value.trim() === q) renderAutocomplete(data, q, p);
                    })
                    .catch(function () { hideEl(acEls(p).dropdown); });
                }, 300);
            });

            document.addEventListener('focusin', function (ev) {
                if (! INPUT_IDS.includes(ev.target.id)) return;
                const p     = prefixOf(ev.target.id);
                const query = ev.target.value.trim();

                if (query.length === 0) {
                    showTrending(p);
                } else if (AUTOCOMPLETE_ENABLED && query.length >= MIN_LENGTH && acEls(p).list && acEls(p).list.children.length > 0) {
                    showEl(acEls(p).dropdown);
                }
            });

            document.addEventListener('mousedown', function (ev) {
                INPUT_IDS.forEach(function (id) {
                    const p     = prefixOf(id);
                    const input = document.getElementById(id);
                    const acDrop = acEls(p).dropdown;
                    const trDrop = trEls(p).dropdown;

                    if (acDrop && ! acDrop.contains(ev.target) && ev.target !== input) hideEl(acDrop);
                    if (trDrop && ! trDrop.contains(ev.target) && ev.target !== input) hideEl(trDrop);
                });
            });

            document.addEventListener('keydown', function (ev) {
                if (! INPUT_IDS.includes(ev.target.id)) return;
                if (ev.key === 'Escape') hideAll(prefixOf(ev.target.id));
            });
        })();
    </script>
    @endif

    <!-- Sticky header: scroll-shrink + search-panel toggle (delegated, mount-safe) -->
    <script>
        (function () {
            /* --- Scroll-shrink (header element is rendered statically, exists immediately) --- */
            const header = document.getElementById('uf-header');
            if (header) {
                let ticking = false;
                const updateHeader = function () {
                    header.dataset.scrolled = (window.scrollY > 24).toString();
                    ticking = false;
                };
                window.addEventListener('scroll', function () {
                    if (! ticking) {
                        window.requestAnimationFrame(updateHeader);
                        ticking = true;
                    }
                }, { passive: true });
                updateHeader();
            }

            /* --- Search panel toggle: delegated because the toggle/panel live inside a
                   Vue x-template that only renders after the header switcher mounts. --- */
            function getPanel()  { return document.getElementById('uf-search-panel'); }
            function getToggle() { return document.getElementById('uf-search-toggle'); }
            function getInput()  { return document.getElementById('desktop-search-input'); }

            function openPanel() {
                const p = getPanel(); const t = getToggle();
                if (! p) return;
                p.dataset.open = 'true';
                if (t) t.setAttribute('aria-expanded', 'true');
                setTimeout(function () {
                    const i = getInput();
                    if (i) i.focus();
                }, 60);
            }

            function closePanel() {
                const p = getPanel(); const t = getToggle();
                if (! p) return;
                p.dataset.open = 'false';
                if (t) t.setAttribute('aria-expanded', 'false');
                ['desktop-autocomplete-dropdown', 'desktop-trending-dropdown'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.classList.add('hidden');
                });
            }

            document.addEventListener('click', function (ev) {
                if (ev.target.closest('#uf-search-toggle')) {
                    ev.preventDefault();
                    const p = getPanel();
                    if (p && p.dataset.open === 'true') closePanel();
                    else openPanel();
                    return;
                }
                if (ev.target.closest('#uf-search-close')) {
                    ev.preventDefault();
                    closePanel();
                }
            });

            document.addEventListener('keydown', function (ev) {
                if (ev.key !== 'Escape') return;
                const p = getPanel();
                if (p && p.dataset.open === 'true') closePanel();
            });

            document.addEventListener('mousedown', function (ev) {
                const p = getPanel(); const t = getToggle();
                if (! p || p.dataset.open !== 'true') return;
                if (p.contains(ev.target)) return;
                if (t && t.contains(ev.target)) return;
                closePanel();
            });
        })();
    </script>

    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <!-- Loading State -->
        <div
            class="flex items-center gap-5"
            v-if="isLoading"
        >
            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>
        </div>

        <!-- Default category layout -->
        <div
            class="flex items-center"
            v-else-if="'{{ core()->getConfigData('general.design.categories.category_view') }}' !== 'sidebar'"
        >
            <div
                class="group relative flex h-full min-h-[60px] items-center border-b-2 border-transparent transition-all duration-200 hover:border-uf-accent"
                v-for="category in categories"
            >
                <span>
                    <a
                        :href="category.url"
                        class="inline-block px-4 font-poppins text-[13px] font-semibold uppercase tracking-[2px] text-white transition-colors hover:text-uf-accent xl:px-5 xl:text-sm xl:tracking-[2.5px]"
                    >
                        @{{ category.name }}
                    </a>
                </span>

                <!-- Mega-menu dropdown with image preview -->
                <v-category-dropdown
                    :category="category"
                    v-if="category.children && category.children.length"
                ></v-category-dropdown>
            </div>
        </div>

        <!-- Sidebar category layout -->
        <div v-else>
            <!-- Categories Navigation -->
            <div class="flex items-center">
                <!-- "All" button for opening the category drawer -->
                <div
                    class="flex h-full min-h-[60px] cursor-pointer items-center border-b-2 border-transparent transition-all duration-200 hover:border-uf-accent"
                    @click="toggleCategoryDrawer"
                >
                    <span class="flex items-center gap-1.5 px-4 font-poppins text-[13px] font-semibold uppercase tracking-[2px] text-white transition-colors hover:text-uf-accent xl:px-5 xl:text-sm xl:tracking-[2.5px]">
                        <span class="text-lg icon-hamburger"></span>

                        @lang('shop::app.components.layouts.header.desktop.bottom.all')
                    </span>
                </div>

                <!-- Show only first 4 categories in main navigation -->
                <div
                    class="group relative flex h-full min-h-[60px] items-center border-b-2 border-transparent transition-all duration-200 hover:border-uf-accent"
                    v-for="category in categories.slice(0, 4)"
                >
                    <span>
                        <a
                            :href="category.url"
                            class="inline-block px-4 font-poppins text-[13px] font-semibold uppercase tracking-[2px] text-white transition-colors hover:text-uf-accent xl:px-5 xl:text-sm xl:tracking-[2.5px]"
                        >
                            @{{ category.name }}
                        </a>
                    </span>

                    <!-- Mega-menu dropdown with image preview -->
                    <v-category-dropdown
                        :category="category"
                        v-if="category.children && category.children.length"
                    ></v-category-dropdown>
                </div>
            </div>

            <!-- Bagisto Drawer Integration -->
            <x-shop::drawer
                position="left"
                width="400px"
                ::is-active="isDrawerActive"
                @toggle="onDrawerToggle"
                @close="onDrawerClose"
            >
                <x-slot:toggle></x-slot>

                <x-slot:header class="border-b border-white/10">
                    <div class="flex items-center justify-between w-full">
                        <p class="text-xl font-medium">
                            @lang('shop::app.components.layouts.header.desktop.bottom.categories')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!px-0">
                    <!-- Recursive accordion (shared with the mobile drawer) -->
                    <div
                        class="h-[calc(100vh-74px)] overflow-auto"
                        :key="resetKey"
                    >
                        <div class="pb-8">
                            <v-category-node
                                v-for="category in categories"
                                :key="category.id"
                                :category="category"
                                :level="0"
                            ></v-category-node>
                        </div>
                    </div>
                </x-slot>
            </x-shop::drawer>
        </div>
    </script>

    <script type="module">
        app.component('v-desktop-category', {
            template: '#v-desktop-category-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    isDrawerActive: false,
                    resetKey: 0,
                }
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories');

                        if (stored) {
                            this.categories = JSON.parse(stored);
                            this.isLoading = false;
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
                            this.isLoading = false;

                            const fresh = response.data.data;
                            if (! fresh) return;

                            const serialized = JSON.stringify(fresh);
                            if (serialized !== localStorage.getItem('categories')) {
                                this.categories = fresh;
                                localStorage.setItem('categories', serialized);
                            }
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                toggleCategoryDrawer() {
                    this.isDrawerActive = !this.isDrawerActive;
                },

                onDrawerToggle(event) {
                    this.isDrawerActive = event.isActive;
                },

                onDrawerClose(event) {
                    this.isDrawerActive = false;
                    this.resetKey++;   /* collapse every expanded node on close */
                },
            },
        });
    </script>

    {{-- ── Premium mega-menu dropdown (shared by both header layouts) ── --}}
    <style>
        /* Crossfade between preview images as you hover each sub-category.
           Both layers are absolutely positioned so they overlap during the swap. */
        .uf-megafade-enter-active { transition: opacity .45s ease, transform .55s ease; }
        .uf-megafade-leave-active { transition: opacity .35s ease; }
        .uf-megafade-enter-from   { opacity: 0; transform: scale(1.06); }
        .uf-megafade-leave-to     { opacity: 0; }
    </style>

    <script
        type="text/x-template"
        id="v-category-dropdown-template"
    >
        <div
            class="pointer-events-none absolute top-full z-[1] w-[660px] max-w-[94vw] translate-y-3 overflow-hidden rounded-2xl border border-white/10 bg-uf-surface/95 opacity-0 shadow-[0_32px_70px_rgba(0,0,0,0.65)] backdrop-blur-2xl transition duration-300 ease-out group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-hover:duration-200 group-hover:ease-in ltr:-left-9 rtl:-right-9"
        >
            <div class="grid grid-cols-[1fr_270px]">
                <!-- Left: sub-category list -->
                <div class="flex flex-col p-7">
                    <p class="mb-4 font-poppins text-[11px] font-semibold uppercase tracking-[3px] text-white/35">
                        @{{ category.name }}
                    </p>

                    <ul class="grid gap-0.5">
                        <li
                            v-for="child in category.children"
                            :key="child.id"
                            @mouseenter="activeChild = child"
                        >
                            <a
                                :href="child.url"
                                class="group/it flex items-center justify-between rounded-xl px-3.5 py-2.5 transition-all duration-200"
                                :class="preview.id === child.id ? 'bg-white/[0.06] text-uf-accent' : 'text-white/80 hover:text-uf-accent'"
                            >
                                <span class="font-poppins text-[15px] font-medium tracking-wide">@{{ child.name }}</span>

                                <span
                                    class="icon-arrow-right rtl:icon-arrow-left text-lg transition-all duration-200"
                                    :class="preview.id === child.id ? 'opacity-100 translate-x-0' : 'opacity-0 ltr:-translate-x-1 rtl:translate-x-1'"
                                ></span>
                            </a>

                            <!-- Optional third level -->
                            <ul
                                class="mb-1 mt-0.5 grid gap-px ltr:pl-3.5 rtl:pr-3.5"
                                v-if="child.children && child.children.length"
                            >
                                <li v-for="leaf in child.children" :key="leaf.id">
                                    <a
                                        :href="leaf.url"
                                        class="block rounded-lg px-3.5 py-1.5 text-sm text-white/55 transition-colors hover:text-white"
                                    >
                                        @{{ leaf.name }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <a
                        :href="category.url"
                        class="group/all mt-auto inline-flex items-center gap-2 pt-6 font-poppins text-[12px] font-semibold uppercase tracking-[2.5px] text-uf-accent transition-colors hover:text-uf-accentHover"
                    >
                        Shop All @{{ category.name }}
                        <span class="icon-arrow-right rtl:icon-arrow-left text-lg transition-transform duration-200 group-hover/all:translate-x-1"></span>
                    </a>
                </div>

                <!-- Right: live image preview -->
                <a
                    :href="preview.url"
                    class="relative block min-h-[330px] overflow-hidden bg-uf-bg"
                >
                    <transition name="uf-megafade">
                        <img
                            v-if="previewImage"
                            :key="preview.id"
                            :src="previewImage"
                            :alt="preview.name"
                            class="absolute inset-0 h-full w-full object-cover"
                        >

                        <div
                            v-else
                            :key="'ph-' + preview.id"
                            class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-uf-surface2 to-uf-bg"
                        >
                            <span class="font-poppins text-7xl font-bold uppercase text-white/[0.07]">@{{ preview.name.charAt(0) }}</span>
                        </div>
                    </transition>

                    <span class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/10"></span>

                    <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent p-5 pt-16">
                        <p class="font-poppins text-lg font-bold leading-tight text-white">@{{ preview.name }}</p>

                        <span class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[2.5px] text-uf-accent">
                            Shop Now
                            <span class="icon-arrow-right rtl:icon-arrow-left text-sm"></span>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-category-dropdown', {
            template: '#v-category-dropdown-template',

            props: {
                category: {
                    type: Object,
                    required: true,
                },
            },

            data() {
                return {
                    activeChild: null,
                };
            },

            computed: {
                /* Currently previewed child — defaults to the first one until hovered. */
                preview() {
                    return this.activeChild
                        || (this.category.children && this.category.children[0])
                        || this.category;
                },

                /* Banner is preferred (wide); fall back to logo, then to a placeholder. */
                previewImage() {
                    const c = this.preview;

                    return (c && (c.banner_url || c.logo_url)) || '';
                },
            },
        });
    </script>
@endPushOnce
{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}
