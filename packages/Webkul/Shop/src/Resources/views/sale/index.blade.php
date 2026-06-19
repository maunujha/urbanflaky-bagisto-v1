{{--
    Sale collection page (/sale).
    Hero + SEO + JSON-LD render server-side; the product grid hydrates via the
    `v-sale` component off the shop.api.sale.products endpoint, reusing the same
    Vue product card, grid, shimmer and Load-More the rest of the storefront uses.
--}}
<x-shop::layouts :has-custom-seo="true">
    <x-slot:title>
        Urbanflaky Sale | Dark Streetwear, Oversized T-Shirts & Monochrome Fashion Deals
    </x-slot>

    {{-- Page-specific SEO (layout suppresses its defaults via :has-custom-seo) --}}
    @push('meta')
        <meta name="description" content="Shop the Urbanflaky Sale Collection featuring premium oversized t-shirts, dark streetwear, monochrome essentials, and minimalist fashion at discounted prices. Discover exclusive deals on black oversized tees and urban aesthetic apparel.">
        <meta name="keywords" content="urbanflaky sale, dark streetwear sale, oversized t shirt sale, black oversized t shirts, monochrome fashion sale, dark aesthetic clothing, streetwear deals india, urban fashion sale, minimalist streetwear, premium oversized tees, men oversized t shirt sale, women oversized t shirt sale, black streetwear india, dark fashion brand, urbanflaky discounts">
        <meta name="robots" content="index, follow">

        <link rel="canonical" href="{{ route('shop.sale.index') }}">

        <meta property="og:title" content="Urbanflaky Sale | Premium Dark Streetwear Deals">
        <meta property="og:description" content="Explore discounted oversized tees, monochrome essentials, and dark aesthetic fashion from Urbanflaky. Limited-time offers on premium streetwear collections.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('shop.sale.index') }}">
        <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Urbanflaky Sale | Premium Dark Streetwear Deals">
        <meta name="twitter:description" content="Explore discounted oversized tees, monochrome essentials, and dark aesthetic fashion from Urbanflaky. Limited-time offers on premium streetwear collections.">
        <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">
    @endpush

    @push('structured_data')
        <script type="application/ld+json">
            @json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        </script>
    @endpush

    {{-- ── Hero (server-rendered for SEO) ── --}}
    <section class="container px-[60px] max-lg:px-8 max-sm:px-4 pt-[clamp(2.5rem,6vw,5rem)] pb-[clamp(1.25rem,3vw,2.25rem)]">
        <div class="max-w-[820px]">
            <h1
                class="font-poppins font-extrabold uppercase leading-[1.04] tracking-[0.015em] text-uf-text text-[clamp(2.25rem,6vw,4.5rem)]"
                style="text-wrap: balance;"
            >
                Urbanflaky <span class="text-uf-accent">Sale</span> Collection
            </h1>

            <h2
                class="mt-4 max-w-[58ch] font-poppins font-medium leading-snug text-uf-muted text-[clamp(1rem,2.2vw,1.375rem)]"
                style="text-wrap: balance;"
            >
                Exclusive Deals on Dark Streetwear &amp; Oversized T-Shirts
            </h2>

            @if ($count > 0)
                <p class="mt-7 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs font-semibold uppercase tracking-[2px] text-uf-muted max-sm:text-[11px]">
                    <span class="text-uf-accent">Up to {{ $maxDiscount }}% off</span>
                    <span class="text-uf-border" aria-hidden="true">/</span>
                    <span>{{ $count }} {{ $count === 1 ? 'style' : 'styles' }} live</span>
                    <span class="text-uf-border" aria-hidden="true">/</span>
                    <span>while stock lasts</span>
                </p>
            @endif
        </div>
    </section>

    {{-- ── Product grid (client-hydrated) ── --}}
    <section class="container px-[60px] max-lg:px-8 max-sm:px-4 pb-[clamp(3rem,7vw,6rem)]">
        <v-sale>
            {{-- Pre-mount placeholder: shimmer in the same grid shape --}}
            <div class="uf-related-grid">
                <x-shop::shimmer.products.cards.grid count="8" />
            </div>
        </v-sale>
    </section>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-sale-template"
        >
            <div>
                {{-- Loading --}}
                <div class="uf-related-grid" v-if="isLoading">
                    <x-shop::shimmer.products.cards.grid count="8" />
                </div>

                <template v-else>
                    {{-- Results --}}
                    <template v-if="products.length">
                        <div class="uf-related-grid">
                            <x-shop::products.card
                                class="uf-related-grid-item"
                                ::mode="'grid'"
                                v-for="product in products"
                                ::key="product.id"
                            />
                        </div>

                        <div class="uf-loadmore-wrap" v-if="links.next">
                            <button
                                type="button"
                                class="uf-loadmore-btn"
                                :disabled="isLoadingMore"
                                @click="loadMore"
                            >
                                <span v-if="isLoadingMore">Loading…</span>
                                <span v-else>Load More</span>
                            </button>
                        </div>
                    </template>

                    {{-- Empty (no live markdowns) --}}
                    <div
                        class="flex flex-col items-center justify-center py-24 text-center max-sm:py-16"
                        v-else
                    >
                        <p class="font-poppins text-2xl font-extrabold uppercase tracking-[0.03em] text-uf-text max-sm:text-xl">
                            No live drops right now
                        </p>
                        <p class="mt-3 max-w-md text-uf-muted">
                            Our markdowns sell out fast. Fresh styles land every week — check back soon or explore the latest arrivals.
                        </p>
                        <a
                            href="{{ route('shop.home.index') }}"
                            class="uf-loadmore-btn mt-8"
                        >
                            Shop New Arrivals
                        </a>
                    </div>
                </template>
            </div>
        </script>

        <script type="module">
            app.component('v-sale', {
                template: '#v-sale-template',

                data() {
                    return {
                        isLoading: true,
                        isLoadingMore: false,
                        products: [],
                        links: {},
                    }
                },

                mounted() {
                    this.getProducts();
                },

                methods: {
                    getProducts() {
                        this.$axios.get("{{ route('shop.api.sale.products') }}")
                            .then(response => {
                                this.isLoading = false;
                                this.products = response.data.data;
                                this.links = response.data.links;

                                if (window.ufTrack && this.products.length) {
                                    window.ufTrack.viewItemList(this.products, 'Sale');
                                }
                            })
                            .catch(() => {
                                this.isLoading = false;
                            });
                    },

                    loadMore() {
                        if (! this.links.next || this.isLoadingMore) {
                            return;
                        }

                        this.isLoadingMore = true;

                        this.$axios.get(this.links.next)
                            .then(response => {
                                this.products = [...this.products, ...response.data.data];
                                this.links = response.data.links;
                                this.isLoadingMore = false;
                            })
                            .catch(() => {
                                this.isLoadingMore = false;
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>
