<v-products-carousel
    src="{{ $src }}"
    title="{{ $title }}"
    navigation-link="{{ $navigationLink ?? '' }}"
>
    <x-shop::shimmer.products.carousel :navigation-link="$navigationLink ?? false" />
</v-products-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-products-carousel-template"
    >
        <section
            class="uf-prod-section"
            v-if="! isLoading && products.length"
        >
            <div class="uf-prod-container">
                <div class="uf-prod-head">
                    <h2 class="uf-prod-title" v-text="title"></h2>

                    <a
                        v-if="navigationLink"
                        :href="navigationLink"
                        class="uf-prod-viewall-inline"
                    >
                        @lang('shop::app.components.products.carousel.view-all')
                        <svg class="uf-prod-viewall-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>

                <!-- Wrapping grid — up to 4 cards per row on desktop, then wrap -->
                <div class="uf-prod-grid">
                    <x-shop::products.card v-for="product in products" />
                </div>

                <a
                    v-if="navigationLink"
                    :href="navigationLink"
                    class="uf-prod-viewall"
                    :aria-label="title"
                >
                    @lang('shop::app.components.products.carousel.view-all')
                </a>
            </div>
        </section>

        <!-- Product Card Listing -->
        <template v-if="isLoading">
            <x-shop::shimmer.products.carousel :navigation-link="$navigationLink ?? false" />
        </template>
    </script>

    <script type="module">
        app.component('v-products-carousel', {
            template: '#v-products-carousel-template',

            props: [
                'src',
                'title',
                'navigationLink',
            ],

            data() {
                return {
                    isLoading: true,
                    products: [],
                };
            },

            mounted() {
                this.getProducts();
            },

            methods: {
                getProducts() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.products = response.data.data;
                        }).catch(error => {
                            console.log(error);
                        });
                },
            },
        });
    </script>
@endPushOnce
