{!! view_render_event('bagisto.shop.categories.view.toolbar.before') !!}

<v-toolbar @filter-applied='setFilters("toolbar", $event)'></v-toolbar>

{!! view_render_event('bagisto.shop.categories.view.toolbar.after') !!}

@inject('toolbar' , 'Webkul\Product\Helpers\Toolbar')

@pushOnce('scripts')
    <script
        type="text/x-template"
        id='v-toolbar-template'
    >
        <div class="uf-cat-toolbar">
            <!-- Desktop Toolbar -->
            <div class="flex justify-between max-md:hidden">
                {!! view_render_event('bagisto.shop.categories.toolbar.filter.before') !!}

                <!-- Product Sorting Filters -->
                <x-shop::dropdown
                    class="z-[1]"
                    position="bottom-left"
                >
                    <x-slot:toggle>
                        <!-- Dropdown Toggler -->
                        <button class="hover:uf-border-accent flex w-full max-w-[220px] cursor-pointer items-center justify-between gap-4 rounded-sm border border-white/10 bg-white/[0.04] px-4 py-3 text-xs font-normal tracking-wide text-white transition max-md:w-[110px] max-md:border-0 max-md:bg-transparent">
                            @{{ sortLabel ?? "@lang('shop::app.products.sort-by.title')" }}

                            <span
                                class="icon-arrow-down text-lg text-zinc-400"
                                role="presentation"
                            ></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:menu>
                        <x-shop::dropdown.menu.item
                            v-for="(sort, key) in filters.available.sort"
                            ::class="{'bg-gray-100': sort.value == filters.applied.sort}"
                            @click="apply('sort', sort.value)"
                        >
                            @{{ sort.title }}
                        </x-shop::dropdown.menu.item>
                    </x-slot>
                </x-shop::dropdown>

                {!! view_render_event('bagisto.shop.categories.toolbar.filter.after') !!}

                {!! view_render_event('bagisto.shop.categories.toolbar.pagination.before') !!}

                <!-- Product Pagination Limit -->
                <div class="flex items-center gap-10">
                    <!-- Product Pagination Limit -->
                    <x-shop::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <!-- Dropdown Toggler -->
                            <button class="hover:uf-border-accent flex w-full cursor-pointer items-center justify-between gap-3 rounded-sm border border-white/10 bg-white/[0.04] px-4 py-3 text-xs font-normal tracking-wide text-white transition max-md:w-[80px] max-md:border-0 max-md:bg-transparent">
                                @{{ filters.applied.limit ?? "@lang('shop::app.categories.toolbar.show')" }}

                                <span
                                    class="icon-arrow-down text-lg text-zinc-400"
                                    role="presentation"
                                ></span>
                            </button>
                        </x-slot>

                        <!-- Dropdown Content -->
                        <x-slot:menu>
                            <x-shop::dropdown.menu.item
                                v-for="(limit, key) in filters.available.limit"
                                ::class="{'bg-gray-100': limit == filters.applied.limit}"
                                @click="apply('limit', limit)"
                            >
                                @{{ limit }}
                            </x-shop::dropdown.menu.item>
                        </x-slot>
                    </x-shop::dropdown>

                    <!-- Listing Mode Switcher -->
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-sm text-xl transition"
                            role="button"
                            aria-label="@lang('shop::app.categories.toolbar.list')"
                            tabindex="0"
                            :class="(filters.applied.mode === 'list') ? 'icon-listing-fill uf-text-accent uf-bg-accent-soft' : 'icon-listing text-zinc-500 hover:text-white'"
                            @click="changeMode('list')"
                        >
                        </span>

                        <span
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-sm text-xl transition"
                            role="button"
                            aria-label="@lang('shop::app.categories.toolbar.grid')"
                            tabindex="0"
                            :class="(filters.applied.mode === 'grid') ? 'icon-grid-view-fill uf-text-accent uf-bg-accent-soft' : 'icon-grid-view text-zinc-500 hover:text-white'"
                            @click="changeMode('grid')"
                        >
                        </span>
                    </div>
                </div>

                {!! view_render_event('bagisto.shop.categories.toolbar.pagination.after') !!}
            </div>

            <!-- Mobile Toolbar -->
            <div class="md:hidden">
                <ul>
                    <li
                        class="cursor-pointer px-4 py-3 text-sm text-zinc-300 transition hover:bg-white/[0.04]"
                        :class="{'uf-bg-accent-soft uf-text-accent': sort.value == filters.applied.sort}"
                        v-for="(sort, key) in filters.available.sort"
                        @click="apply('sort', sort.value)"
                    >
                        @{{ sort.title }}
                    </li>
                </ul>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-toolbar', {
            template: '#v-toolbar-template',

            data() {
                return {
                    filters: {
                        available: {
                            sort: @json($toolbar->getAvailableOrders()),

                            limit: @json($toolbar->getAvailableLimits()),

                            mode: @json($toolbar->getAvailableModes()),
                        },

                        default: {
                            sort: '{{ $toolbar->getOrder([])['value'] }}',

                            limit: '{{ $toolbar->getLimit([]) }}',

                            mode: '{{ $toolbar->getMode([]) }}',
                        },

                        applied: {
                            sort: '{{ $toolbar->getOrder($params ?? [])['value'] }}',

                            limit: '{{ $toolbar->getLimit($params ?? []) }}',

                            mode: '{{ $toolbar->getMode($params ?? []) }}',
                        }
                    }
                };
            },

            created() {
                let queryParams = new URLSearchParams(window.location.search);

                queryParams.forEach((value, filter) => {
                    if (['sort', 'limit', 'mode'].includes(filter)) {
                        this.filters.applied[filter] = value;
                    }
                });
            },

            mounted() {
                this.setFilters();
            },

            computed: {
                sortLabel() {
                    return this.filters.available.sort.find(sort => sort.value === this.filters.applied.sort).title;
                }
            },

            methods: {
                apply(type, value) {
                    this.filters.applied[type] = value;

                    this.setFilters();
                },

                changeMode(value = 'grid') {
                    this.filters.applied['mode'] = value;

                    this.setFilters();
                },

                setFilters() {
                    let filters = {};

                    for (let key in this.filters.applied) {
                        if (this.filters.applied[key] != this.filters.default[key]) {
                            filters[key] = this.filters.applied[key];
                        }
                    }

                    this.$emit('filter-applied', {
                        default: this.filters.default,
                        applied: filters,
                    });
                }
            },
        });
    </script>
@endPushOnce
