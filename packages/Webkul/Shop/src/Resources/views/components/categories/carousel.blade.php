<v-categories-carousel
    src="{{ $src }}"
    title="{{ $title }}"
    navigation-link="{{ $navigationLink ?? '' }}"
>
    <x-shop::shimmer.categories.carousel
        :count="8"
        :navigation-link="$navigationLink ?? false"
    />
</v-categories-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-categories-carousel-template"
    >
        <section
            class="uf-cat-section"
            v-if="! isLoading && categories?.length"
            aria-label="Shop by category"
        >
            <div class="uf-cat-container">
                <h2 class="uf-cat-title" v-if="title" v-text="title"></h2>

                <div class="uf-cat-grid" ref="swiperContainer">
                    <a
                        v-for="category in categories"
                        :key="category.id"
                        :href="category.slug"
                        :class="['uf-cat-card', category.card_background ? 'uf-bg-' + category.card_background : 'uf-bg-default']"
                        :aria-label="category.name"
                    >
                        <div class="uf-cat-card-image">
                            <picture v-if="category.logo?.original_image_url">
                                <source
                                    type="image/webp"
                                    :srcset="category.logo.original_image_url"
                                    v-if="category.logo.original_image_fallback_url"
                                >

                                <img
                                    :src="category.logo.original_image_fallback_url || category.logo.original_image_url"
                                    :alt="category.name"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </picture>
                        </div>

                        <div class="uf-cat-content">
                            <span class="uf-cat-name" v-text="category.name"></span>
                            <span class="uf-cat-cta">
                                Explore Now <span class="uf-cat-cta-arrow">›</span>
                            </span>
                        </div>
                    </a>
                </div>

                <button
                    type="button"
                    class="uf-cat-arrow uf-cat-prev"
                    v-show="hasOverflow"
                    :disabled="atStart"
                    @click="swipeLeft"
                    aria-label="@lang('shop::components.carousel.previous')"
                >
                    <span class="icon-arrow-left-stylish"></span>
                </button>

                <button
                    type="button"
                    class="uf-cat-arrow uf-cat-next"
                    v-show="hasOverflow"
                    :disabled="atEnd"
                    @click="swipeRight"
                    aria-label="@lang('shop::components.carousel.next')"
                >
                    <span class="icon-arrow-right-stylish"></span>
                </button>
            </div>
        </section>

        <!-- Category Carousel Shimmer -->
        <template v-if="isLoading">
            <x-shop::shimmer.categories.carousel
                :count="8"
                :navigation-link="$navigationLink ?? false"
            />
        </template>
    </script>

    <script type="module">
        app.component('v-categories-carousel', {
            template: '#v-categories-carousel-template',

            props: [
                'src',
                'title',
                'navigationLink',
            ],

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    hasOverflow: false,
                    atStart: true,
                    atEnd: false,
                    fallback: "{{ bagisto_asset('images/small-product-placeholder.webp') }}"
                };
            },

            mounted() {
                this.getCategories();
                window.addEventListener('resize', this.updateScrollState);
            },

            beforeUnmount() {
                window.removeEventListener('resize', this.updateScrollState);
                const container = this.$refs.swiperContainer;
                if (container) container.removeEventListener('scroll', this.updateScrollState);
            },

            methods: {
                getCategories() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;

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
                    const firstCard = container.querySelector('.uf-cat-card');
                    if (! firstCard) return container.clientWidth * 0.8;
                    const style = window.getComputedStyle(container);
                    const gap = parseFloat(style.columnGap || style.gap || 16) || 16;
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
