@props([
    'src'       => '',
    'title'     => '',
    'subtitle'  => '',
    'instagram' => 'https://www.instagram.com/urbanflaky/',
])

<v-lookbook-grid
    src="{{ $src }}"
    title="{{ $title }}"
    subtitle="{{ $subtitle }}"
    instagram="{{ $instagram }}"
>
</v-lookbook-grid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-lookbook-grid-template"
    >
        <section class="uf-looks-section" v-if="! isLoading && looks.length">
            <div class="uf-looks-container">
                <!-- Header -->
                <div class="uf-looks-head">
                    <div class="uf-looks-head-text">
                        <span class="uf-looks-handle">@lang('lookbook::app.shop.handle')</span>

                        <h2 class="uf-looks-title">
                            <span v-text="title"></span>
                            <svg class="uf-looks-ig-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="2" y="2" width="20" height="20" rx="5.5"/>
                                <circle cx="12" cy="12" r="4.2"/>
                                <circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"/>
                            </svg>
                        </h2>

                        <p class="uf-looks-subtitle" v-text="subtitle"></p>

                        <a :href="instagram" target="_blank" rel="noopener" class="uf-looks-follow">
                            @lang('lookbook::app.shop.follow-instagram')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                        </a>
                    </div>

                    <a :href="instagram" target="_blank" rel="noopener" class="uf-looks-all">
                        @lang('lookbook::app.shop.view-all-reels')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                    </a>
                </div>

                <!-- Hero row: featured (large) + up to 4 first-row cards -->
                <div class="uf-looks-hero">
                    <article
                        v-if="layout.featured"
                        class="uf-look-card uf-look-card--featured"
                        @click="openModal(layout.featured)"
                    >
                        <div class="uf-look-media">
                            <img :src="layout.featured.image_url" :alt="cardAlt(layout.featured)" class="uf-look-img" loading="lazy" />

                            <span class="uf-look-badge">@lang('lookbook::app.shop.featured')</span>

                            <span class="uf-look-play" v-if="layout.featured.video_url" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>

                            <div class="uf-look-caption uf-look-caption--lg">
                                <span class="uf-look-eyebrow2" v-if="layout.featured.collection_name" v-text="layout.featured.collection_name"></span>
                                <h3 class="uf-look-name" v-if="layout.featured.title" v-text="layout.featured.title"></h3>
                                <p class="uf-look-sub" v-if="layout.featured.caption" v-text="layout.featured.caption"></p>
                            </div>

                            <div class="uf-look-overlay">
                                <span class="uf-look-cta">
                                    @lang('lookbook::app.shop.view-look')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </div>
                        </div>
                    </article>

                    <article
                        v-for="look in layout.heroCards"
                        :key="look.id"
                        class="uf-look-card"
                        @click="openModal(look)"
                    >
                        <div class="uf-look-media">
                            <img :src="look.image_url" :alt="cardAlt(look)" class="uf-look-img" loading="lazy" />

                            <span class="uf-look-play" v-if="look.video_url" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>

                            <div class="uf-look-caption">
                                <span class="uf-look-eyebrow2" v-if="look.collection_name" v-text="look.collection_name"></span>
                                <h3 class="uf-look-name" v-if="look.title" v-text="look.title"></h3>
                                <p class="uf-look-sub" v-if="look.caption" v-text="look.caption"></p>
                            </div>

                            <div class="uf-look-overlay">
                                <span class="uf-look-cta">
                                    @lang('lookbook::app.shop.view-look')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Second row: remaining cards, smaller -->
                <div class="uf-looks-rest" v-if="layout.restCards.length">
                    <article
                        v-for="look in layout.restCards"
                        :key="look.id"
                        class="uf-look-card uf-look-card--sm"
                        @click="openModal(look)"
                    >
                        <div class="uf-look-media">
                            <img :src="look.image_url" :alt="cardAlt(look)" class="uf-look-img" loading="lazy" />

                            <span class="uf-look-play" v-if="look.video_url" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>

                            <div class="uf-look-caption">
                                <span class="uf-look-eyebrow2" v-if="look.collection_name" v-text="look.collection_name"></span>
                                <h3 class="uf-look-name" v-if="look.title" v-text="look.title"></h3>
                                <p class="uf-look-sub" v-if="look.caption" v-text="look.caption"></p>
                            </div>

                            <div class="uf-look-overlay">
                                <span class="uf-look-cta uf-look-cta--sm">
                                    @lang('lookbook::app.shop.view-look')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Footer CTA bar -->
                <div class="uf-looks-footer">
                    <div class="uf-looks-footer-intro">
                        <span class="uf-looks-footer-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"/>
                            </svg>
                        </span>
                        <p class="uf-looks-footer-text">@lang('lookbook::app.shop.stats-footer')</p>
                    </div>

                    <a :href="instagram" target="_blank" rel="noopener" class="uf-looks-footer-btn">
                        @lang('lookbook::app.shop.follow-instagram')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                    </a>
                </div>
            </div>

            <!-- Fullscreen modal -->
            <teleport to="body">
                <div
                    v-if="activeLook"
                    class="uf-look-modal-backdrop"
                    @click="closeModal"
                >
                    <div class="uf-look-modal" @click.stop>
                        <button type="button" class="uf-look-modal-close" @click="closeModal" aria-label="@lang('lookbook::app.shop.close')">&times;</button>

                        <!-- Media side -->
                        <div class="uf-look-modal-media">
                            <video
                                v-if="activeLook.is_reel && isPlayableVideo(activeLook.video_url)"
                                :src="activeLook.video_url"
                                :poster="activeLook.image_url"
                                class="uf-look-modal-video"
                                controls
                                autoplay
                                loop
                                playsinline
                            ></video>

                            <iframe
                                v-else-if="activeLook.is_reel && activeLook.video_url"
                                :src="activeLook.video_url"
                                class="uf-look-modal-video"
                                frameborder="0"
                                allow="autoplay; encrypted-media"
                                allowfullscreen
                            ></iframe>

                            <img
                                v-else
                                :src="activeLook.image_url"
                                :alt="activeLook.title"
                                class="uf-look-modal-img"
                            />
                        </div>

                        <!-- Info side -->
                        <div class="uf-look-modal-info">
                            <div class="uf-look-modal-scroll">
                                <span class="uf-look-modal-collection" v-if="activeLook.collection_name" v-text="activeLook.collection_name"></span>

                                <h3 class="uf-look-modal-title" v-if="activeLook.title" v-text="activeLook.title"></h3>

                                <p class="uf-look-modal-caption" v-if="activeLook.caption" v-text="activeLook.caption"></p>

                                <!-- Instagram CTA -->
                                <a
                                    v-if="activeLook.permalink"
                                    :href="activeLook.permalink"
                                    target="_blank"
                                    rel="noopener"
                                    class="uf-look-ig-cta"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"/>
                                    </svg>
                                    @lang('lookbook::app.shop.watch-on-instagram')
                                </a>

                                <!-- Tagged products -->
                                <template v-if="activeLook.products && activeLook.products.length">
                                    <div class="uf-look-modal-products-head">
                                        <p class="uf-look-modal-label">@lang('lookbook::app.shop.shop-the-look')</p>

                                        <button type="button" class="uf-look-shop-all" :disabled="isAddingLook" @click="shopLook(activeLook)">
                                            <span v-if="isAddingLook">···</span>
                                            <span v-else>@lang('lookbook::app.shop.shop-look')</span>
                                        </button>
                                    </div>

                                    <div class="uf-look-products">
                                        <div class="uf-look-product" v-for="product in activeLook.products" :key="product.id">
                                            <a :href="product.url" class="uf-look-product-img">
                                                <img :src="product.image" :alt="product.name" loading="lazy" />
                                            </a>

                                            <div class="uf-look-product-info">
                                                <a :href="product.url" class="uf-look-product-name" v-text="product.name"></a>
                                                <div class="uf-look-product-price" v-html="product.price_html"></div>

                                                <a :href="product.url" class="uf-look-product-link">
                                                    @lang('lookbook::app.shop.view-product')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </teleport>
        </section>
    </script>

    <script type="module">
        app.component('v-lookbook-grid', {
            template: '#v-lookbook-grid-template',

            props: ['src', 'title', 'subtitle', 'instagram'],

            data() {
                return {
                    isLoading: true,
                    looks: [],
                    activeLook: null,
                    isAddingLook: false,
                };
            },

            computed: {
                /**
                 * Split looks into the large featured card, the first-row cards
                 * (max 4) and the remaining smaller cards shown in the row below.
                 */
                layout() {
                    const looks = [...this.looks];

                    if (! looks.length) {
                        return { featured: null, heroCards: [], restCards: [] };
                    }

                    let fi = looks.findIndex(look => !! look.is_featured);
                    if (fi < 0) fi = 0;

                    const featured = looks.splice(fi, 1)[0];

                    return {
                        featured,
                        heroCards: looks.slice(0, 4),
                        restCards: looks.slice(4),
                    };
                },
            },

            mounted() {
                this.fetchLooks();

                this._escHandler = (e) => {
                    if (e.key === 'Escape' && this.activeLook) this.closeModal();
                };
                document.addEventListener('keydown', this._escHandler);
            },

            beforeUnmount() {
                document.removeEventListener('keydown', this._escHandler);
                document.body.style.overflow = '';
            },

            methods: {
                fetchLooks() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.looks = response.data.data;
                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },

                cardAlt(look) {
                    return look.title || look.collection_name || 'Urbanflaky look';
                },

                openModal(look) {
                    this.activeLook = look;
                    document.body.style.overflow = 'hidden';
                },

                closeModal() {
                    this.activeLook = null;
                    document.body.style.overflow = '';
                },

                isPlayableVideo(url) {
                    return !! url && /\.(mp4|webm|mov)(\?.*)?$/i.test(url);
                },

                shopLook(look) {
                    if (! look.products || ! look.products.length) return;

                    this.isAddingLook = true;

                    const requests = look.products.map(product =>
                        this.$axios.post('{{ route('shop.api.checkout.cart.store') }}', {
                            quantity: 1,
                            product_id: product.id,
                        }).then(() => true).catch(() => false)
                    );

                    Promise.allSettled(requests).then(results => {
                        const added = results.filter(r => r.status === 'fulfilled' && r.value).length;

                        this.isAddingLook = false;

                        if (added > 0) {
                            this.$emitter.emit('update-mini-cart');
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: added + ' item(s) added to your bag.',
                            });
                        } else {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: 'Please choose options on the product page to add these items.',
                            });
                        }
                    });
                },
            },
        });
    </script>
@endPushOnce
