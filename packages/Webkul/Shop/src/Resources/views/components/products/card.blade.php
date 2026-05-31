<v-product-card
    {{ $attributes }}
    :product="product"
>
</v-product-card>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-card-template"
    >
        <!-- Grid Card -->
        <div class="uf-product-card" v-if="mode != 'list'">

            <!-- ── IMAGE CONTAINER (3:4 portrait) ── -->
            <div class="uf-img-wrap">

                {!! view_render_event('bagisto.shop.components.products.card.image.before') !!}

                <a
                    class="uf-img-link"
                    :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)"
                    :aria-label="product.name"
                >
                    <x-shop::media.images.lazy
                        ::src="currentImage.medium_image_url"
                        ::srcset="`
                            ${currentImage.small_image_url} 150w,
                            ${currentImage.medium_image_url} 300w,
                        `"
                        sizes="(max-width: 768px) 150px, (max-width: 1200px) 300px, 600px"
                        ::key="`${product.id}-${currentImage.medium_image_url}`"
                        ::index="product.id"
                        width="291"
                        height="388"
                        ::alt="product.name"
                    />
                </a>

                {!! view_render_event('bagisto.shop.components.products.card.image.after') !!}

                <!-- Dark overlay on hover (desktop) -->
                <div class="uf-overlay"></div>

                <!-- Badge top-left -->
                <p class="uf-badge uf-badge-sale" v-if="product.on_sale">
                    @lang('shop::app.components.products.card.sale')
                </p>
                <p class="uf-badge uf-badge-new" v-else-if="product.is_new">
                    @lang('shop::app.components.products.card.new')
                </p>

                <!-- Top-right: Wishlist + Quick View stacked -->
                <div class="uf-card-icons">

                    {!! view_render_event('bagisto.shop.components.products.card.wishlist_option.before') !!}

                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                        <span
                            class="uf-icon-btn"
                            role="button"
                            tabindex="0"
                            aria-label="@lang('shop::app.components.products.card.add-to-wishlist')"
                            :class="product.is_wishlist ? 'icon-heart-fill uf-icon-active' : 'icon-heart'"
                            @click.prevent="addToWishlist()"
                        ></span>
                    @endif

                    {!! view_render_event('bagisto.shop.components.products.card.wishlist_option.after') !!}

                    <!-- Quick View (below wishlist, desktop hover only) -->
                    <button
                        type="button"
                        class="uf-icon-btn uf-quick-view"
                        aria-label="Quick view"
                        title="Quick view"
                        @click.stop.prevent="openQuickView()"
                    >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <div class="card-rating">
                <!-- Ratings badge (bottom-left) -->
                {!! view_render_event('bagisto.shop.components.products.card.average_ratings.before') !!}
                </div>

                @if (core()->getConfigData('catalog.products.review.summary') == 'star_counts')
                    <x-shop::products.ratings
                        class="absolute bottom-2 items-center !border-white bg-white/80 !px-2 !py-1 text-xs ltr:left-2 rtl:right-2"
                        ::average="product.ratings.average"
                        ::total="product.ratings.total"
                        ::rating="false"
                        v-if="product.ratings.total"
                    />
                @else
                    <x-shop::products.ratings
                        class="absolute bottom-2 items-center !border-white bg-white/80 !px-2 !py-1 text-xs ltr:left-2 rtl:right-2"
                        ::average="product.ratings.average"
                        ::total="product.reviews.total"
                        ::rating="false"
                        v-if="product.reviews.total"
                    />
                @endif

                {!! view_render_event('bagisto.shop.components.products.card.average_ratings.after') !!}
            </div>
            <!-- /uf-img-wrap -->

            <!-- ── CONTENT (static, zero layout shift) ── -->
            <div class="uf-card-content">

                {!! view_render_event('bagisto.shop.components.products.card.name.before') !!}

                <p class="uf-card-name">@{{ product.name }}</p>

                <p class="uf-card-subtitle" v-if="product.short_description">@{{ product.short_description }}</p>

                {!! view_render_event('bagisto.shop.components.products.card.name.after') !!}

                <div class="uf-card-price-row">

                    {!! view_render_event('bagisto.shop.components.products.card.price.before') !!}

                    <div class="uf-card-price" v-html="product.price_html"></div>

                    {!! view_render_event('bagisto.shop.components.products.card.price.after') !!}

                    @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                        <button
                            type="button"
                            class="uf-mobile-cart"
                            :disabled="! product.is_saleable || isAddingToCart"
                            aria-label="@lang('shop::app.components.products.card.add-to-cart')"
                            @click.prevent="mobileCartClick()"
                        ><span v-if="isAddingToCart">···</span><span v-else>+</span></button>
                    @endif
                </div>

                <!-- Subtle trust strip (single line, minimal) -->
                <div class="uf-delivery-strip">
                    <span class="uf-ds-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="1" y="6" width="13" height="11" rx="1"/>
                            <path d="M14 9h4l3 3v5h-7"/>
                            <circle cx="6" cy="19" r="2"/>
                            <circle cx="17" cy="19" r="2"/>
                        </svg>
                        Free Delivery
                    </span>
                    <span class="uf-ds-sep">|</span>
                    <span class="uf-ds-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 .49-3.86"/>
                        </svg>
                        7-Day Returns
                    </span>
                </div>
            </div>
            <!-- /uf-card-content -->

            <!-- Desktop Hover Drawer (variants + CTAs) — sits over the card on hover -->
            <div class="uf-hover-panel">
                <div class="uf-drawer-body">
                    <template v-if="isConfigurable && product.super_attributes && product.super_attributes.length">
                        <template v-for="attribute in product.super_attributes" :key="attribute.id">
                            <div class="uf-variant-section">
                                <p class="uf-variant-label" v-text="attribute.label"></p>
                                <div class="uf-swatch-row" v-if="attribute.swatch_type === 'color'">
                                    <span
                                        v-for="opt in attribute.options"
                                        :key="opt.id"
                                        class="uf-color-dot"
                                        :style="{ background: opt.swatch_value || '#ccc' }"
                                        :class="{ 'uf-color-dot-active': selectedAttributes[attribute.id] == opt.id }"
                                        :title="opt.label"
                                        @click.stop="selectAttribute(attribute.id, opt.id)"
                                    ></span>
                                </div>
                                <div class="uf-size-row" v-else>
                                    <span
                                        v-for="opt in attribute.options"
                                        :key="opt.id"
                                        class="uf-size-pill"
                                        :class="{ 'uf-size-active': selectedAttributes[attribute.id] == opt.id }"
                                        @click.stop="selectAttribute(attribute.id, opt.id)"
                                    >@{{ opt.label }}</span>
                                </div>
                            </div>
                        </template>
                    </template>

                    <p class="uf-variant-error" v-if="variantError">@{{ variantError }}</p>

                    <div class="uf-drawer-divider"></div>

                    <div class="uf-drawer-price" v-html="product.price_html"></div>

                    @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                        {!! view_render_event('bagisto.shop.components.products.card.add_to_cart.before') !!}

                        <div class="uf-cta-row">
                            <button
                                type="button"
                                class="uf-btn-atc"
                                :disabled="! product.is_saleable || isAddingToCart"
                                @click.stop="addToCart()"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                    <path d="M16 10a4 4 0 0 1-8 0"/>
                                </svg>
                                <span v-if="isAddingToCart">···</span>
                                <span v-else>@lang('shop::app.components.products.card.add-to-cart')</span>
                            </button>
                            <button
                                type="button"
                                class="uf-btn-buy"
                                :disabled="! product.is_saleable || isAddingToCart"
                                @click.stop="buyNow()"
                            >Buy Now</button>
                        </div>

                        {!! view_render_event('bagisto.shop.components.products.card.add_to_cart.after') !!}
                    @endif
                </div>
            </div>

            <!-- Mobile Variant Bottom Sheet -->
            <teleport to="body" v-if="variantSheetOpen">
                <div class="uf-sheet-backdrop fixed inset-0" @click="closeVariantSheet()"></div>
                <div class="uf-sheet fixed inset-x-0 bottom-0 bg-zinc-900" @click.stop>
                    <div class="uf-sheet-handle"></div>

                    <button
                        type="button"
                        class="uf-sheet-close"
                        aria-label="Close"
                        @click="closeVariantSheet()"
                    >×</button>

                    <div class="uf-sheet-scroll px-5">
                        <p class="mb-1 text-sm text-white">@{{ product.name }}</p>
                        <div class="uf-card-price mb-4" v-html="product.price_html"></div>

                        <template v-if="isConfigurable && product.super_attributes && product.super_attributes.length">
                            <div
                                class="mb-4"
                                v-for="attribute in product.super_attributes"
                                :key="attribute.id"
                            >
                                <p class="mb-2 text-xs uppercase tracking-wider text-zinc-500">@{{ attribute.label }}</p>
                                <div class="flex flex-wrap gap-2" v-if="attribute.swatch_type === 'color'">
                                    <span
                                        v-for="opt in attribute.options"
                                        :key="opt.id"
                                        class="uf-color-dot"
                                        :style="{ background: opt.swatch_value || '#ccc' }"
                                        :class="{ 'uf-color-dot-active': selectedAttributes[attribute.id] == opt.id }"
                                        :title="opt.label"
                                        @click="selectAttribute(attribute.id, opt.id)"
                                    ></span>
                                </div>
                                <div class="flex flex-wrap gap-2" v-else>
                                    <span
                                        v-for="opt in attribute.options"
                                        :key="opt.id"
                                        class="uf-size-pill"
                                        :class="{ 'uf-size-active': selectedAttributes[attribute.id] == opt.id }"
                                        @click="selectAttribute(attribute.id, opt.id)"
                                    >@{{ opt.label }}</span>
                                </div>
                            </div>
                        </template>

                        <p class="mb-3 text-xs text-red-400" v-if="variantError">@{{ variantError }}</p>

                        <div class="uf-cta-row">
                            <button
                                type="button"
                                class="uf-btn-atc"
                                :disabled="! product.is_saleable || isAddingToCart"
                                @click="addToCart()"
                            >
                                <span v-if="isAddingToCart">···</span>
                                <span v-else>@lang('shop::app.components.products.card.add-to-cart')</span>
                            </button>
                            <button
                                type="button"
                                class="uf-btn-buy"
                                :disabled="! product.is_saleable || isAddingToCart"
                                @click="buyNow()"
                            >Buy Now</button>
                        </div>
                    </div>
                </div>
            </teleport>

            <!-- Quick View Modal -->
            <teleport to="body" v-if="quickViewOpen">
                <div
                    class="uf-qv-backdrop fixed inset-0 flex items-center justify-center p-4"
                    @click="closeQuickView()"
                    @keydown.esc="closeQuickView()"
                >
                    <div
                        class="uf-qv-modal relative w-full overflow-hidden rounded-lg border border-white/10 bg-zinc-900"
                        @click.stop
                    >
                        <button
                            type="button"
                            class="uf-qv-close absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full border border-white/10 text-xl text-white"
                            aria-label="Close"
                            @click="closeQuickView()"
                        >×</button>

                        <div class="uf-qv-grid">
                            <!-- Image -->
                            <div class="uf-qv-image flex items-center justify-center overflow-hidden bg-black">
                                <img
                                    :src="currentImage.large_image_url || currentImage.medium_image_url"
                                    :alt="product.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>

                            <!-- Info -->
                            <div class="uf-qv-info overflow-y-auto p-8 max-md:p-5">
                                <h3 class="mb-3 text-xl font-medium text-white">@{{ product.name }}</h3>

                                <div class="uf-text-accent mb-5 text-xl" v-html="product.price_html"></div>

                                <!-- Variants -->
                                <template v-if="isConfigurable && product.super_attributes && product.super_attributes.length">
                                    <div
                                        class="mb-5"
                                        v-for="attribute in product.super_attributes"
                                        :key="attribute.id"
                                    >
                                        <p class="mb-2 text-xs uppercase tracking-wider text-zinc-500">
                                            @{{ attribute.label }}
                                        </p>
                                        <div class="flex flex-wrap gap-2" v-if="attribute.swatch_type === 'color'">
                                            <span
                                                v-for="opt in attribute.options"
                                                :key="opt.id"
                                                class="uf-color-dot"
                                                :style="{ background: opt.swatch_value || '#ccc' }"
                                                :class="{ 'uf-color-dot-active': selectedAttributes[attribute.id] == opt.id }"
                                                :title="opt.label"
                                                @click="selectAttribute(attribute.id, opt.id)"
                                            ></span>
                                        </div>
                                        <div class="flex flex-wrap gap-2" v-else>
                                            <span
                                                v-for="opt in attribute.options"
                                                :key="opt.id"
                                                class="uf-size-pill"
                                                :class="{ 'uf-size-active': selectedAttributes[attribute.id] == opt.id }"
                                                @click="selectAttribute(attribute.id, opt.id)"
                                            >@{{ opt.label }}</span>
                                        </div>
                                    </div>
                                </template>

                                <p
                                    class="mb-3 rounded-sm bg-red-500 px-3 py-2 text-xs text-white"
                                    v-if="variantError"
                                >@{{ variantError }}</p>

                                <!-- CTAs -->
                                <div class="uf-cta-row mt-2 flex gap-2">
                                    <button
                                        type="button"
                                        class="uf-btn-atc"
                                        :disabled="! product.is_saleable || isAddingToCart"
                                        @click="addToCart()"
                                    >
                                        <span v-if="isAddingToCart">···</span>
                                        <span v-else>@lang('shop::app.components.products.card.add-to-cart')</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="uf-btn-buy"
                                        :disabled="! product.is_saleable || isAddingToCart"
                                        @click="buyNow()"
                                    >Buy Now</button>
                                </div>

                                <a
                                    :href="productUrl"
                                    class="mt-4 inline-block text-xs text-zinc-400 underline transition hover:text-white"
                                >
                                    View Full Details →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </teleport>

        </div>

        <!-- List Card -->
        <div
            class="relative flex max-w-max grid-cols-2 gap-4 overflow-hidden rounded max-sm:flex-wrap"
            v-else
        >
            <div class="group relative max-h-[258px] max-w-[250px] overflow-hidden">

                {!! view_render_event('bagisto.shop.components.products.card.image.before') !!}

                <a :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)">
                    <x-shop::media.images.lazy
                        class="after:content-[' '] relative min-w-[250px] bg-zinc-100 transition-all duration-300 after:block after:pb-[calc(100%+9px)] group-hover:scale-105"
                        ::src="product.base_image.medium_image_url"
                        ::key="product.id"
                        ::index="product.id"
                        width="291"
                        height="300"
                        ::alt="product.name"
                    />
                </a>

                {!! view_render_event('bagisto.shop.components.products.card.image.after') !!}

                <div class="action-items bg-black">
                    <p
                        class="absolute top-5 inline-block rounded-[44px] bg-red-500 px-2.5 text-sm text-white ltr:left-5 max-sm:ltr:left-2 rtl:right-5"
                        v-if="product.on_sale"
                    >
                        @lang('shop::app.components.products.card.sale')
                    </p>

                    <p
                        class="absolute top-5 inline-block rounded-[44px] bg-uf-accent px-2.5 text-sm font-semibold text-black ltr:left-5 max-sm:ltr:left-2 rtl:right-5"
                        v-else-if="product.is_new"
                    >
                        @lang('shop::app.components.products.card.new')
                    </p>

                    <div class="opacity-0 transition-all duration-300 group-hover:bottom-0 group-hover:opacity-100 max-sm:opacity-100">

                        {!! view_render_event('bagisto.shop.components.products.card.wishlist_option.before') !!}

                        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                            <span
                                class="absolute top-5 flex h-[30px] w-[30px] cursor-pointer items-center justify-center rounded-md bg-white text-2xl ltr:right-5 rtl:left-5"
                                role="button"
                                aria-label="@lang('shop::app.components.products.card.add-to-wishlist')"
                                tabindex="0"
                                :class="product.is_wishlist ? 'icon-heart-fill text-red-600' : 'icon-heart'"
                                @click="addToWishlist()"
                            >
                            </span>
                        @endif

                        {!! view_render_event('bagisto.shop.components.products.card.wishlist_option.after') !!}

                        {!! view_render_event('bagisto.shop.components.products.card.compare_option.before') !!}

                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                            <span
                                class="icon-compare absolute top-16 flex h-[30px] w-[30px] cursor-pointer items-center justify-center rounded-md bg-white text-2xl ltr:right-5 rtl:left-5"
                                role="button"
                                aria-label="@lang('shop::app.components.products.card.add-to-compare')"
                                tabindex="0"
                                @click="addToCompare(product.id)"
                            >
                            </span>
                        @endif

                        {!! view_render_event('bagisto.shop.components.products.card.compare_option.after') !!}
                    </div>
                </div>
            </div>

            <div class="grid content-start gap-4">

                {!! view_render_event('bagisto.shop.components.products.card.name.before') !!}

                <p class="text-base">
                    @{{ product.name }}
                </p>

                {!! view_render_event('bagisto.shop.components.products.card.name.after') !!}

                {!! view_render_event('bagisto.shop.components.products.card.price.before') !!}

                <div
                    class="flex gap-2.5 text-lg font-semibold"
                    v-html="product.price_html"
                >
                </div>

                {!! view_render_event('bagisto.shop.components.products.card.price.after') !!}

                <!-- Needs to implement that in future -->
                <div class="flex hidden gap-4">
                    <span class="block h-[30px] w-[30px] rounded-full bg-[#B5DCB4]">
                    </span>

                    <span class="block h-[30px] w-[30px] rounded-full bg-zinc-500">
                    </span>
                </div>

                {!! view_render_event('bagisto.shop.components.products.card.average_ratings.before') !!}

                <p class="text-sm text-zinc-500">
                    <template  v-if="! product.ratings.total">
                        <p class="text-sm text-zinc-500">
                            @lang('shop::app.components.products.card.review-description')
                        </p>
                    </template>

                    <template v-else>
                        @if (core()->getConfigData('catalog.products.review.summary') == 'star_counts')
                            <x-shop::products.ratings
                                ::average="product.ratings.average"
                                ::total="product.ratings.total"
                                ::rating="false"
                            />
                        @else
                            <x-shop::products.ratings
                                ::average="product.ratings.average"
                                ::total="product.reviews.total"
                                ::rating="false"
                            />
                        @endif
                    </template>
                </p>

                {!! view_render_event('bagisto.shop.components.products.card.average_ratings.after') !!}

                @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))

                    {!! view_render_event('bagisto.shop.components.products.card.add_to_cart.before') !!}

                    <x-shop::button
                        class="primary-button whitespace-nowrap px-8 py-2.5"
                        :title="trans('shop::app.components.products.card.add-to-cart')"
                        ::loading="isAddingToCart"
                        ::disabled="! product.is_saleable || isAddingToCart"
                        @click="addToCart()"
                    />

                    {!! view_render_event('bagisto.shop.components.products.card.add_to_cart.after') !!}

                @endif
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-card', {
            template: '#v-product-card-template',

            props: ['mode', 'product'],

            data() {
                return {
                    isCustomer: '{{ auth()->guard('customer')->check() }}',
                    isAddingToCart: false,
                    selectedAttributes: {},
                    variantError: null,
                    currentImage: this.product.base_image,
                    quickViewOpen: false,
                    variantSheetOpen: false,
                }
            },

            computed: {
                isConfigurable() {
                    return this.product.type === 'configurable';
                },

                colorAttribute() {
                    if (!this.product.super_attributes) return null;
                    return this.product.super_attributes.find(a => a.swatch_type === 'color') || null;
                },

                allSelected() {
                    if (!this.isConfigurable || !this.product.super_attributes) return true;
                    return this.product.super_attributes.length === Object.keys(this.selectedAttributes).length;
                },

                productUrl() {
                    return '{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', this.product.url_key);
                },
            },

            mounted() {
                this._escHandler = (e) => {
                    if (e.key !== 'Escape') return;
                    if (this.quickViewOpen)    this.closeQuickView();
                    if (this.variantSheetOpen) this.closeVariantSheet();
                };
                document.addEventListener('keydown', this._escHandler);
            },

            beforeUnmount() {
                document.removeEventListener('keydown', this._escHandler);
                if (this.quickViewOpen || this.variantSheetOpen) document.body.style.overflow = '';
            },

            methods: {
                openQuickView() {
                    this.quickViewOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                closeQuickView() {
                    this.quickViewOpen = false;
                    document.body.style.overflow = '';
                },

                mobileCartClick() {
                    if (this.isConfigurable) {
                        this.openVariantSheet();
                    } else {
                        this.addToCart();
                    }
                },

                openVariantSheet() {
                    this.variantSheetOpen = true;
                    this.variantError = null;
                    document.body.style.overflow = 'hidden';
                },

                closeVariantSheet() {
                    this.variantSheetOpen = false;
                    document.body.style.overflow = '';
                },

                selectAttribute(attrId, optionId) {
                    const updated = { ...this.selectedAttributes };
                    if (updated[attrId] == optionId) {
                        delete updated[attrId];
                    } else {
                        updated[attrId] = optionId;
                    }
                    this.selectedAttributes = updated;
                    this.variantError = null;

                    // Swap card image when the selected attribute is the color swatch
                    if (this.colorAttribute && attrId == this.colorAttribute.id) {
                        const selectedColorId = updated[attrId];
                        if (selectedColorId && this.product.variant_images && this.product.variant_images[selectedColorId]) {
                            this.currentImage = this.product.variant_images[selectedColorId];
                        } else {
                            this.currentImage = this.product.base_image;
                        }
                    }
                },

                addToWishlist() {
                    if (this.isCustomer) {
                        this.$axios.post(`{{ route('shop.api.customers.account.wishlist.store') }}`, {
                                product_id: this.product.id
                            })
                            .then(response => {
                                this.product.is_wishlist = ! this.product.is_wishlist;

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                            })
                            .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                },

                addToCompare(productId) {
                    if (this.isCustomer) {
                        this.$axios.post('{{ route("shop.api.compare.store") }}', {
                                'product_id': productId
                            })
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                            })
                            .catch(error => {
                                if ([400, 422].includes(error.response.status)) {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.data.message });

                                    return;
                                }

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message});
                            });

                        return;
                    }

                    let items = this.getStorageValue() ?? [];

                    if (items.length) {
                        if (! items.includes(productId)) {
                            items.push(productId);
                            localStorage.setItem('compare_items', JSON.stringify(items));
                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.components.products.card.add-to-compare-success')" });
                        } else {
                            this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('shop::app.components.products.card.already-in-compare')" });
                        }
                    } else {
                        localStorage.setItem('compare_items', JSON.stringify([productId]));
                        this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.components.products.card.add-to-compare-success')" });
                    }
                },

                getStorageValue(key) {
                    let value = localStorage.getItem('compare_items');
                    if (! value) return [];
                    return JSON.parse(value);
                },

                buildCartPayload(isBuyNow = false) {
                    const payload = {
                        quantity: 1,
                        product_id: this.product.id,
                    };

                    if (this.isConfigurable && Object.keys(this.selectedAttributes).length) {
                        payload.super_attribute = this.selectedAttributes;
                    }

                    if (isBuyNow) {
                        payload.is_buy_now = 1;
                    }

                    return payload;
                },

                addToCart() {
                    if (this.isConfigurable && !this.allSelected) {
                        this.variantError = 'Please select variant to add to cart';
                        return;
                    }

                    this.variantError = null;
                    this.isAddingToCart = true;

                    this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', this.buildCartPayload())
                        .then(response => {
                            this.$emitter.emit('update-mini-cart', response.data.data);
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                            this.isAddingToCart = false;
                            if (this.variantSheetOpen) this.closeVariantSheet();
                            if (this.quickViewOpen)    this.closeQuickView();
                        })
                        .catch(error => {
                            this.variantError = error.response?.data?.message || 'Could not add to cart';
                            this.isAddingToCart = false;
                        });
                },

                buyNow() {
                    if (this.isConfigurable && !this.allSelected) {
                        this.variantError = 'Please select variant to proceed';
                        return;
                    }

                    this.variantError = null;
                    this.isAddingToCart = true;

                    this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', this.buildCartPayload(true))
                        .then(response => {
                            window.location.href = response.data.redirect || '{{ route("shop.checkout.onepage.index") }}';
                        })
                        .catch(error => {
                            this.variantError = error.response?.data?.message || 'Could not proceed';
                            this.isAddingToCart = false;
                        });
                },
            },
        });
    </script>
@endpushOnce
