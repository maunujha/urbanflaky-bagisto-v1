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

                <!-- Two independent rows — each scrolls on its own with its own arrows -->
                <div
                    v-for="idx in [0, 1]"
                    :key="idx"
                    class="uf-prod-rowwrap"
                    v-show="rows[idx].length"
                >
                    <button
                        type="button"
                        class="uf-prod-edge uf-prod-edge--prev"
                        v-show="rowState[idx].hasOverflow && ! rowState[idx].atStart"
                        aria-label="@lang('shop::components.carousel.previous')"
                        @click="swipe(idx, -1)"
                    >
                        <svg class="uf-prod-edge-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <div :ref="'strip' + idx" class="uf-prod-strip">
                        <x-shop::products.card v-for="product in rows[idx]" />
                    </div>

                    <button
                        type="button"
                        class="uf-prod-edge uf-prod-edge--next"
                        v-show="rowState[idx].hasOverflow && ! rowState[idx].atEnd"
                        aria-label="@lang('shop::components.carousel.next')"
                        @click="swipe(idx, 1)"
                    >
                        <svg class="uf-prod-edge-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
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
                    rows: [[], []],
                    rowState: [
                        { hasOverflow: false, atStart: true, atEnd: false },
                        { hasOverflow: false, atStart: true, atEnd: false },
                    ],
                };
            },

            mounted() {
                this.getProducts();
                window.addEventListener('resize', this.updateAllScrollStates);
            },

            beforeUnmount() {
                window.removeEventListener('resize', this.updateAllScrollStates);
                [0, 1].forEach(idx => {
                    const strip = this.stripEl(idx);
                    if (strip) strip.removeEventListener('scroll', this.scrollHandlers[idx]);
                });
            },

            created() {
                // Per-row scroll handlers, bound once so they can be removed on unmount.
                this.scrollHandlers = [
                    () => this.updateScrollState(0),
                    () => this.updateScrollState(1),
                ];
            },

            methods: {
                getProducts() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.products = response.data.data;

                            // Interleave so the strongest products lead BOTH rows
                            // (even indices → row 0, odd indices → row 1).
                            const top = [], bottom = [];
                            this.products.forEach((product, i) => {
                                (i % 2 === 0 ? top : bottom).push(product);
                            });
                            this.rows = [top, bottom];

                            this.$nextTick(() => {
                                [0, 1].forEach(idx => {
                                    const strip = this.stripEl(idx);
                                    if (strip) strip.addEventListener('scroll', this.scrollHandlers[idx], { passive: true });
                                });
                                this.updateAllScrollStates();
                            });
                        }).catch(error => {
                            console.log(error);
                        });
                },

                stripEl(idx) {
                    const ref = this.$refs['strip' + idx];
                    // v-for refs resolve to an array in Vue 3.
                    return Array.isArray(ref) ? ref[0] : ref;
                },

                cardOffset(strip) {
                    if (! strip) return 320;
                    const firstCard = strip.querySelector('.uf-product-card');
                    if (! firstCard) return strip.clientWidth * 0.7;
                    const style = window.getComputedStyle(strip);
                    const gap = parseFloat(style.columnGap || style.gap || 20) || 20;
                    return firstCard.getBoundingClientRect().width + gap;
                },

                updateScrollState(idx) {
                    const strip = this.stripEl(idx);
                    if (! strip) return;
                    const maxScroll = strip.scrollWidth - strip.clientWidth;
                    this.rowState[idx].hasOverflow = maxScroll > 2;
                    this.rowState[idx].atStart = strip.scrollLeft <= 2;
                    this.rowState[idx].atEnd = strip.scrollLeft >= maxScroll - 2;
                },

                updateAllScrollStates() {
                    this.updateScrollState(0);
                    this.updateScrollState(1);
                },

                swipe(idx, direction) {
                    const strip = this.stripEl(idx);
                    if (! strip) return;
                    strip.scrollBy({ left: direction * this.cardOffset(strip), behavior: 'smooth' });
                },
            },
        });
    </script>
@endPushOnce
