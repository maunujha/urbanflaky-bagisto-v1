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

                    <div class="flex items-center gap-4">
                        <a
                            v-if="navigationLink"
                            :href="navigationLink"
                            class="uf-prod-viewall-inline"
                        >
                            @lang('shop::app.components.products.carousel.view-all')
                            <span class="icon-arrow-right-stylish"></span>
                        </a>

                        <div class="uf-prod-controls" v-show="hasOverflow">
                            <button
                                type="button"
                                class="uf-prod-arrow"
                                :disabled="atStart"
                                aria-label="@lang('shop::components.carousel.previous')"
                                @click="swipeLeft"
                            >
                                <span class="icon-arrow-left-stylish"></span>
                            </button>
                            <button
                                type="button"
                                class="uf-prod-arrow"
                                :disabled="atEnd"
                                aria-label="@lang('shop::components.carousel.next')"
                                @click="swipeRight"
                            >
                                <span class="icon-arrow-right-stylish"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div ref="swiperContainer" class="uf-prod-strip">
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
                    hasOverflow: false,
                    atStart: true,
                    atEnd: false,
                };
            },

            mounted() {
                this.getProducts();
                window.addEventListener('resize', this.updateScrollState);
            },

            beforeUnmount() {
                window.removeEventListener('resize', this.updateScrollState);
                const container = this.$refs.swiperContainer;
                if (container) container.removeEventListener('scroll', this.updateScrollState);
            },

            methods: {
                getProducts() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.products = response.data.data;

                            this.$nextTick(() => {
                                const container = this.$refs.swiperContainer;
                                if (container) container.addEventListener('scroll', this.updateScrollState, { passive: true });
                                this.updateScrollState();
                            });
                        }).catch(error => {
                            console.log(error);
                        });
                },

                cardOffset() {
                    const container = this.$refs.swiperContainer;
                    if (! container) return 320;
                    const firstCard = container.querySelector('.uf-product-card');
                    if (! firstCard) return container.clientWidth * 0.7;
                    const style = window.getComputedStyle(container);
                    const gap = parseFloat(style.columnGap || style.gap || 20) || 20;
                    return firstCard.getBoundingClientRect().width + gap;
                },

                updateScrollState() {
                    const container = this.$refs.swiperContainer;
                    if (! container) return;
                    const maxScroll = container.scrollWidth - container.clientWidth;
                    this.hasOverflow = maxScroll > 2;
                    this.atStart = container.scrollLeft <= 2;
                    this.atEnd = container.scrollLeft >= maxScroll - 2;
                },

                swipeLeft() {
                    this.$refs.swiperContainer.scrollBy({ left: -this.cardOffset(), behavior: 'smooth' });
                },

                swipeRight() {
                    this.$refs.swiperContainer.scrollBy({ left: this.cardOffset(), behavior: 'smooth' });
                },
            },
        });
    </script>
@endPushOnce
