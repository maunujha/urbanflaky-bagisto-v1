<v-categories-carousel
    src="{{ $src }}"
    title="{{ $title ?: 'Shop by Category' }}"
    navigation-link="{{ $navigationLink ?? '' }}"
>
    <x-shop::shimmer.categories.carousel
        :count="4"
        :navigation-link="$navigationLink ?? false"
    />
</v-categories-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-categories-carousel-template"
    >
        <section
            class="uf-cat-section w-full px-3 py-5 md:px-10 md:py-10 xl:py-14"
            v-if="! isLoading && categories?.length"
            aria-label="Shop by category"
        >
            <h2 class="sr-only" v-text="title"></h2>

            <div class="relative mx-auto max-w-[1120px]">
                <div
                    class="scrollbar-hide -mx-3 -my-2 snap-x snap-mandatory scroll-pl-3 overflow-x-auto px-3 py-2 [container-type:inline-size] md:-mx-10 md:scroll-pl-10 md:px-10 lg:mx-0 lg:snap-none lg:scroll-pl-0 lg:px-0"
                    ref="swiperContainer"
                >
                    <div
                        :class="['flex w-max min-w-full gap-2 lg:gap-4', { 'justify-center': categories.length <= 4 }]"
                        ref="track"
                    >
                        <a
                            v-for="(category, index) in categories"
                            :key="category.id"
                            :href="category.slug"
                            :class="[
                                'group block shrink-0 snap-start rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-uf-accent lg:w-[calc((100cqw-48px)/4)]',
                                categories.length <= 3 ? 'w-[calc((100cqw-16px)/3)]' : 'w-[calc((100cqw-24px)/3.25)]',
                                { 'lg:sticky lg:left-[calc(var(--uf-cat-i)*14px)]': isStacked },
                            ]"
                            :style="{ '--uf-cat-i': Math.min(index, 3) }"
                        >
                            {{-- No aria-label: the visible name is the accessible name
                                 (an aria-label that differs from it fails WCAG 2.5.3). --}}
                            <span
                                :class="[
                                    'relative block h-[150px] origin-left overflow-hidden rounded-md transition-[translate] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-safe:animate-[uf-cat-in_0.7s_cubic-bezier(0.22,1,0.36,1)_backwards] sm:h-[200px] md:h-[260px] lg:aspect-[4/5] lg:h-auto lg:group-hover:[translate:0_-6px]',
                                    category.card_background ? 'uf-bg-' + category.card_background : 'uf-bg-default',
                                ]"
                                :style="{ animationDelay: Math.min(index, 5) * 70 + 'ms' }"
                            >
                                <picture v-if="category.logo?.card_image_url">
                                    <source
                                        type="image/webp"
                                        :srcset="category.logo.card_image_url"
                                        v-if="category.logo.card_image_fallback_url"
                                    >

                                    <img
                                        class="absolute inset-0 h-full w-full select-none object-cover object-top transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none lg:group-hover:scale-[1.07]"
                                        :src="category.logo.card_image_fallback_url || category.logo.card_image_url"
                                        :alt="category.name"
                                        loading="lazy"
                                        decoding="async"
                                        draggable="false"
                                    />
                                </picture>

                                {{-- Keeps white text legible on any photo, including white backdrops. --}}
                                <span
                                    class="pointer-events-none absolute inset-x-0 bottom-0 h-3/4 bg-[linear-gradient(to_top,rgba(0,0,0,0.92)_0%,rgba(0,0,0,0.72)_35%,rgba(0,0,0,0)_100%)]"
                                    aria-hidden="true"
                                ></span>

                                <span
                                    class="pointer-events-none absolute inset-0 bg-black opacity-[var(--uf-cat-dim,0)]"
                                    aria-hidden="true"
                                ></span>

                                <span
                                    class="pointer-events-none absolute inset-0 rounded-md ring-1 ring-inset ring-white/10 transition-shadow duration-500 lg:group-hover:ring-uf-accent/50"
                                    aria-hidden="true"
                                ></span>

                                <span class="absolute inset-x-2 bottom-2 md:inset-x-3 md:bottom-3 lg:inset-x-4 lg:bottom-4">
                                    <span class="relative block w-fit max-w-full pb-1 font-poppins text-xs font-bold uppercase leading-4 tracking-[0.08em] text-white md:text-[15px] md:leading-5 lg:text-lg lg:leading-6">
                                        <span v-text="category.name"></span>

                                        <span
                                            class="absolute bottom-0 left-0 h-0.5 w-full origin-left scale-x-0 bg-uf-accent transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-x-100 group-focus-visible:scale-x-100 motion-reduce:transition-none"
                                            aria-hidden="true"
                                        ></span>
                                    </span>

                                    <span
                                        class="mt-0.5 line-clamp-2 block font-poppins text-[10px] leading-[14px] text-white/85 md:text-xs md:leading-[18px] lg:text-[13px]"
                                        v-text="category.card_subtitle || 'Explore the collection'"
                                    ></span>
                                </span>
                            </span>
                        </a>
                    </div>
                </div>

                <button
                    type="button"
                    class="absolute -left-5 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-uf-bg text-lg text-uf-text shadow-[0_6px_16px_rgba(0,0,0,0.45)] transition-colors duration-300 hover:border-uf-accent hover:text-uf-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-uf-accent disabled:pointer-events-none disabled:opacity-40 lg:flex"
                    v-show="hasOverflow"
                    :disabled="atStart"
                    @click="swipeLeft"
                    aria-label="@lang('shop::components.carousel.previous')"
                >
                    <span class="icon-arrow-left-stylish"></span>
                </button>

                <button
                    type="button"
                    class="absolute -right-5 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-uf-bg text-lg text-uf-text shadow-[0_6px_16px_rgba(0,0,0,0.45)] transition-colors duration-300 hover:border-uf-accent hover:text-uf-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-uf-accent disabled:pointer-events-none disabled:opacity-40 lg:flex"
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
                ref="shimmer"
                :count="4"
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
                    fallback: "{{ bagisto_asset('images/small-product-placeholder.webp') }}",
                    observer: null,
                    scrollFrame: null,
                };
            },

            computed: {
                /* The stacked deck only applies once there are more cards than the four visible. */
                isStacked() {
                    return this.categories.length > 4;
                },
            },

            mounted() {
                this.observeAndFetch();
                window.addEventListener('resize', this.updateScrollState);
            },

            beforeUnmount() {
                if (this.observer) {
                    this.observer.disconnect();
                }

                if (this.scrollFrame) {
                    cancelAnimationFrame(this.scrollFrame);
                }

                window.removeEventListener('resize', this.updateScrollState);
                const container = this.$refs.swiperContainer;
                if (container) container.removeEventListener('scroll', this.onScroll);
            },

            methods: {
                /* Deferred until the placeholder approaches the viewport so the
                   category tiles do not compete with the hero on first paint.
                   Browsers without IntersectionObserver fetch immediately. */
                observeAndFetch() {
                    const target = this.$refs.shimmer;

                    if (! target || typeof IntersectionObserver === 'undefined') {
                        this.getCategories();

                        return;
                    }

                    this.observer = new IntersectionObserver((entries) => {
                        if (! entries.some(entry => entry.isIntersecting)) {
                            return;
                        }

                        this.observer.disconnect();
                        this.observer = null;

                        this.getCategories();
                    }, { rootMargin: '600px 0px' });

                    this.observer.observe(target);
                },

                getCategories() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;

                            this.$nextTick(() => {
                                const container = this.$refs.swiperContainer;
                                if (container) container.addEventListener('scroll', this.onScroll, { passive: true });
                                this.updateScrollState();
                            });
                        }).catch(error => {
                            console.log(error);
                        });
                },

                cardOffset() {
                    const track = this.$refs.track;
                    if (! track?.firstElementChild) return 320;
                    const gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
                    return track.firstElementChild.offsetWidth + gap;
                },

                reducedMotion() {
                    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                },

                onScroll() {
                    if (this.scrollFrame) return;

                    this.scrollFrame = requestAnimationFrame(() => {
                        this.scrollFrame = null;
                        this.updateScrollState();
                    });
                },

                updateScrollState() {
                    const container = this.$refs.swiperContainer;
                    if (! container) return;
                    const maxScroll = container.scrollWidth - container.clientWidth;
                    this.hasOverflow = maxScroll > 2;
                    this.atStart = container.scrollLeft <= 2;
                    this.atEnd = container.scrollLeft >= maxScroll - 2;
                    this.updateStackDepth(container);
                },

                /* Cards shrink and dim as the next one slides over them (desktop deck only). */
                updateStackDepth(container) {
                    if (! this.isStacked) return;

                    const animate = window.matchMedia('(min-width: 1024px)').matches && ! this.reducedMotion();
                    const position = container.scrollLeft / this.cardOffset();

                    Array.from(this.$refs.track.children).forEach((card, index) => {
                        const covered = animate ? Math.min(Math.max(position - index, 0), 1) : 0;
                        const face = card.firstElementChild;

                        face.style.transform = covered ? `scale(${1 - 0.08 * covered})` : '';
                        face.style.setProperty('--uf-cat-dim', covered * 0.55);
                    });
                },

                swipe(direction) {
                    this.$refs.swiperContainer.scrollBy({
                        left: direction * this.cardOffset(),
                        behavior: this.reducedMotion() ? 'auto' : 'smooth',
                    });
                },

                swipeLeft() {
                    this.swipe(-1);
                },

                swipeRight() {
                    this.swipe(1);
                },
            },
        });
    </script>
@endPushOnce
