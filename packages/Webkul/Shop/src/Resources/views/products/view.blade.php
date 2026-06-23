@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $shippingReturnsPage = app('Webkul\CMS\Repositories\PageRepository')->findByUrlKey('shipping-returns-tab');

    $avgRatings = $reviewHelper->getAverageRating($product);

    $percentageRatings = $reviewHelper->getPercentageRating($product);

    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));

    $productBaseImage = product_image()->getProductBaseImage($product);

    $reviewCount = $reviewHelper->getTotalFeedback($product);

    /* Index-aware sellable price. Configurable parents carry no own price, so fall back to the
       minimal variant price (same logic as the Product schema), then to $product->price. This is
       what stops "Rs 0" leaking into the title / meta description for configurable products. */
    $minimalPrice = (float) $product->getTypeInstance()->getMinimalPrice();

    if ($minimalPrice <= 0) {
        $minimalPrice = (float) $product->price;
    }

    /* Price token is appended only when a positive price resolves — never advertise "Rs 0". */
    $priceLabel = $minimalPrice > 0 ? ' at Rs ' . number_format($minimalPrice, 0) : '';

    $productBaseDesc = trim($product->meta_description) != ''
        ? $product->meta_description
        : \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 80, '');

    $metaDesc = ($productBaseDesc ? $productBaseDesc . ' ' : '')
        . 'Shop ' . $product->name . $priceLabel
        . ' on Urbanflaky. Fast delivery pan India. – Gabha Enterprise';

    /* Single source of truth for the page title — reused by <title>, og:title and twitter:title
       so Facebook, Twitter/X and LinkedIn share previews all render the same heading. */
    $metaTitle = trim($product->meta_title) != ''
        ? $product->meta_title
        : $product->name . ($priceLabel !== '' ? ' — Buy Online' . $priceLabel : '') . ' | Urbanflaky';

    /* Shared og:/twitter: description. Decode any HTML entities to raw text FIRST, then let
       Blade's {{ }} escape exactly once. Previously an explicit htmlspecialchars() here plus
       Blade's own auto-escape double-encoded ampersands (& → &amp;amp;), so share previews
       showed a literal "&amp;". */
    $shareDesc = trim(html_entity_decode(strip_tags($product->description ?? ''), ENT_QUOTES));

    $productCanonical = route('shop.product_or_category.index', $product->url_key);

    /* Catalog mode: hides purchase actions storefront-wide; wishlist/compare stay on unless explicitly disabled. */
    $catalogModeEnabled = core()->getConfigData('general.catalog_mode.settings.enabled');
    $catalogModeMessage = core()->getConfigData('general.catalog_mode.settings.message');
    $catalogModeHidePrices = $catalogModeEnabled && core()->getConfigData('general.catalog_mode.settings.hide_prices');
    $catalogModeAllowWishlist = ! $catalogModeEnabled || core()->getConfigData('general.catalog_mode.settings.allow_wishlist');
    $catalogModeAllowCompare = ! $catalogModeEnabled || core()->getConfigData('general.catalog_mode.settings.allow_compare');
@endphp

<!-- SEO Meta Content — full product-specific block; flags layout to skip its generic fallback -->
@push('meta')
    <meta name="description" content="{{ $metaDesc }}">
    <meta name="keywords" content="{{ $product->meta_keywords }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $productCanonical }}">

    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $shareDesc }}">
    <meta property="og:image" content="{{ $productBaseImage['large_image_url'] ?? $productBaseImage['medium_image_url'] }}">
    <meta property="og:url" content="{{ $productCanonical }}">

    {{-- Product structured data lives in the @push('structured_data') block below.
         The core rich-snippets generator is intentionally NOT invoked here to avoid
         emitting a second, conflicting Product schema on the same page. --}}

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $shareDesc }}">
    <meta name="twitter:image:alt" content="{{ $product->name }}">
    <meta name="twitter:image" content="{{ $productBaseImage['large_image_url'] ?? $productBaseImage['medium_image_url'] }}">
@endpush

<!-- Product Structured Data (single source of truth: Webkul\Shop\Helpers\StructuredData) -->
@push('structured_data')
<script type="application/ld+json">
{!! app(\Webkul\Shop\Helpers\StructuredData::class)->getProductGraph($product) !!}
</script>
@endpush

{{-- GA4 view_item — server-rendered so price / category are exact --}}
@push('datalayer')
<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
        event: 'view_item',
        page_type: 'product',
        ecommerce: @json(\App\Support\DataLayer::viewItem($product)),
    });
</script>
@endpush

<!-- Page Layout -->
<x-shop::layouts :has-custom-seo="true">
    <!-- Page Title -->
    <x-slot:title>
        {{ $metaTitle }}
    </x-slot>

    {!! view_render_event('bagisto.shop.products.view.before', ['product' => $product]) !!}

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        <div class="flex justify-center px-7 max-lg:hidden">
            <x-shop::breadcrumbs
                name="product"
                :entity="$product"
            />
        </div>
    @endif

    <!-- Product Information Vue Component -->
    <v-product>
        <x-shop::shimmer.products.view />
    </v-product>

    <!-- Information Section -->
    <div class="1180:mt-20 1180:pb-24">
        <div class="max-1180:hidden">
            <x-shop::tabs
                position="center"
                ref="productTabs"
            >
                <!-- Description Tab -->
                {!! view_render_event('bagisto.shop.products.view.description.before', ['product' => $product]) !!}

                <x-shop::tabs.item
                    id="descritpion-tab"
                    class="container mt-[60px] !p-0"
                    :title="trans('shop::app.products.view.description')"
                    :is-selected="true"
                >
                    <div class="container mt-[60px] max-1180:px-5">
                        <div class="uf-rte text-md text-zinc-300 max-1180:text-sm">
                            {!! $product->description !!}
                        </div>
                    </div>
                </x-shop::tabs.item>

                {!! view_render_event('bagisto.shop.products.view.description.after', ['product' => $product]) !!}

                <!-- Additional Information Tab -->
                @if(count($attributeData))
                    <x-shop::tabs.item
                        id="information-tab"
                        class="container mt-[60px] !p-0"
                        :title="trans('shop::app.products.view.additional-information')"
                        :is-selected="false"
                    >
                        <div class="container mt-[60px] max-1180:px-5">
                            <div class="mt-8 grid max-w-max grid-cols-[auto_1fr] gap-4">
                                @foreach ($customAttributeValues as $customAttributeValue)
                                    @if (! empty($customAttributeValue['value']))
                                        <div class="grid">
                                            <p class="text-base text-white">
                                                {!! $customAttributeValue['label'] !!}
                                            </p>
                                        </div>

                                        @if ($customAttributeValue['type'] == 'file')
                                            <a
                                                href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                download="{{ $customAttributeValue['label'] }}"
                                            >
                                                <span class="icon-download text-2xl"></span>
                                            </a>
                                        @elseif ($customAttributeValue['type'] == 'image')
                                            <a
                                                href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                download="{{ $customAttributeValue['label'] }}"
                                            >
                                                <img
                                                    class="min-h-5 min-w-5 h-5 w-5"
                                                    src="{{ Storage::url($customAttributeValue['value']) }}"
                                                />
                                            </a>
                                        @else
                                            <div class="grid">
                                                <p class="text-base text-zinc-400">
                                                    {!! $customAttributeValue['value'] !!}
                                                </p>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </x-shop::tabs.item>
                @endif

                <!-- Reviews Tab -->
                <x-shop::tabs.item
                    id="review-tab"
                    class="container mt-[60px] !p-0"
                    :title="trans('shop::app.products.view.review')"
                    :is-selected="false"
                >
                    @include('shop::products.view.reviews')
                </x-shop::tabs.item>

                <!-- Shipping & Returns Tab -->
                <x-shop::tabs.item
                    id="shipping-returns-tab"
                    class="container mt-[60px] !p-0"
                    title="Shipping & Returns"
                    :is-selected="false"
                >
                    <div class="container mt-[60px] max-1180:px-5">
                        <div class="uf-rte text-md text-zinc-300 max-1180:text-sm">
                            @if ($shippingReturnsPage?->html_content)
                                {!! $shippingReturnsPage->html_content !!}
                            @else
                                @include('shop::products.view.partials.shipping-returns-default')
                            @endif
                        </div>
                    </div>
                </x-shop::tabs.item>
            </x-shop::tabs>
        </div>
    </div>

    <!-- Information Section -->
    <div class="container mt-6 grid gap-3 !p-0 pb-14 max-1180:px-5 1180:hidden">
        <!-- Description Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="true"
        >
            <x-slot:header class="bg-white/5 text-white max-md:!py-3 max-sm:!py-2">
                <p class="text-base font-medium 1180:hidden">
                    @lang('shop::app.products.view.description')
                </p>
            </x-slot>

            <x-slot:content class="max-sm:px-0">
                <div class="uf-rte mb-5 text-md text-zinc-300 max-1180:text-sm max-md:mb-1 max-md:px-4">
                    {!! $product->description !!}
                </div>
            </x-slot>
        </x-shop::accordion>

        <!-- Additional Information Accordion -->
        @if (count($attributeData))
            <x-shop::accordion
                class="max-md:border-none"
                :is-active="false"
            >
                <x-slot:header class="bg-white/5 text-white max-md:!py-3 max-sm:!py-2">
                    <p class="text-base font-medium 1180:hidden">
                        @lang('shop::app.products.view.additional-information')
                    </p>
                </x-slot>

                <x-slot:content class="max-sm:px-0">
                    <div class="container max-1180:px-5">
                        <div class="grid max-w-max grid-cols-[auto_1fr] gap-4 text-md text-zinc-300 max-1180:text-sm">
                            @foreach ($customAttributeValues as $customAttributeValue)
                                @if (! empty($customAttributeValue['value']))
                                    <div class="grid">
                                        <p
                                            class="text-base text-white"
                                            v-pre
                                        >
                                            {{ $customAttributeValue['label'] }}
                                        </p>
                                    </div>

                                    @if ($customAttributeValue['type'] == 'file')
                                        <a
                                            href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                            download="{{ $customAttributeValue['label'] }}"
                                        >
                                            <span class="icon-download text-2xl"></span>
                                        </a>
                                    @elseif ($customAttributeValue['type'] == 'image')
                                        <a
                                            href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                            download="{{ $customAttributeValue['label'] }}"
                                        >
                                            <img
                                                class="min-h-5 min-w-5 h-5 w-5"
                                                src="{{ Storage::url($customAttributeValue['value']) }}"
                                                alt="Product Image"
                                            />
                                        </a>
                                    @else
                                        <div class="grid">
                                            <p
                                                class="text-base text-zinc-400"
                                                v-pre
                                            >
                                                {{ $customAttributeValue['value'] ?? '-' }}
                                            </p>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                </x-slot>
            </x-shop::accordion>
        @endif

        <!-- Reviews Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="false"
        >
            <x-slot:header
                class="bg-white/5 text-white max-md:!py-3 max-sm:!py-2"
                id="review-accordian-button"
            >
                <p class="text-base font-medium">
                    @lang('shop::app.products.view.review')
                </p>
            </x-slot>

            <x-slot:content>
                @include('shop::products.view.reviews')
            </x-slot>
        </x-shop::accordion>

        <!-- Shipping & Returns Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="false"
        >
            <x-slot:header class="bg-white/5 text-white max-md:!py-3 max-sm:!py-2">
                <p class="text-base font-medium 1180:hidden">
                    Shipping &amp; Returns
                </p>
            </x-slot>

            <x-slot:content class="max-sm:px-0">
                <div class="uf-rte mb-5 text-md text-zinc-300 max-1180:text-sm max-md:mb-1 max-md:px-4">
                    @if ($shippingReturnsPage?->html_content)
                        {!! $shippingReturnsPage->html_content !!}
                    @else
                        @include('shop::products.view.partials.shipping-returns-default')
                    @endif
                </div>
            </x-slot>
        </x-shop::accordion>
    </div>

    <!-- Sticky Add-to-Cart Bar -->
    <div
        id="sticky-atc-bar"
        class="fixed bottom-0 left-0 right-0"
        inert
    >
        <div class="mx-auto flex max-w-[1180px] items-center gap-4 px-5 py-3 max-sm:gap-2 max-sm:py-2">
            <!-- Product Thumbnail -->
            <img
                id="sticky-atc-thumb"
                src="{{ $productBaseImage['medium_image_url'] }}"
                alt="{{ $product->name }}"
                class="h-14 w-14 flex-shrink-0 rounded-lg object-cover max-sm:h-10 max-sm:w-10"
            >

            <!-- Product Info -->
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white max-sm:text-xs" id="sticky-atc-name">{{ $product->name }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 max-sm:text-[10px]" id="sticky-atc-variant"></p>
            </div>

            <!-- Price -->
            <div class="flex-shrink-0 text-right">
                <p class="text-md font-bold text-uf-accent max-sm:text-sm" id="sticky-atc-price"></p>
            </div>

            <!-- Add to Cart Button -->
            <button
                id="sticky-atc-btn"
                type="button"
                class="flex min-h-11 flex-shrink-0 items-center gap-2 rounded-md bg-gradient-to-b from-[#d4ef4f] to-[#a9da1e] px-6 py-3 text-sm font-bold text-black transition hover:brightness-105 max-sm:px-4 max-sm:py-2.5 max-sm:text-xs"
            >
                <span class="icon-cart text-lg max-sm:hidden"></span>
                Add to Cart
            </button>
        </div>
    </div>

    <v-product-associations />

    @include('shop::products.view.recently-viewed')

    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-product-template"
        >
            <x-shop::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    ref="formData"
                    @submit="handleSubmit($event, addToCart)"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="{{ $product->id }}"
                    >

                    <input
                        type="hidden"
                        name="is_buy_now"
                        v-model="is_buy_now"
                    >

                    <div class="container px-[60px] max-1180:px-0">
                        <div class="mt-12 flex gap-9 max-1180:flex-wrap max-lg:mt-0 max-sm:gap-y-4">
                            <!-- Gallery Blade Inclusion -->
                            @include('shop::products.view.gallery')

                            <!-- Details -->
                            <div class="relative max-w-[590px] max-1180:w-full max-1180:max-w-full max-1180:px-5 max-sm:px-4">
                                {!! view_render_event('bagisto.shop.products.name.before', ['product' => $product]) !!}

                                <div class="flex justify-between gap-4">
                                    <h1 class="break-words text-3xl font-medium max-sm:text-xl" v-pre>
                                        {{ $product->name }}
                                    </h1>

                                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option') && $catalogModeAllowWishlist)
                                        <div
                                            class="flex max-h-[44px] min-h-[44px] min-w-[44px] cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/5 text-xl text-white backdrop-blur-md transition-all hover:border-[#c7eb31] hover:bg-[#c7eb31] hover:text-black max-sm:text-lg"
                                            role="button"
                                            aria-label="@lang('shop::app.products.view.add-to-wishlist')"
                                            tabindex="0"
                                            :class="isWishlist ? 'icon-heart-fill text-red-600' : 'icon-heart'"
                                            @click="addToWishlist"
                                        >
                                        </div>
                                    @endif
                                </div>

                                {!! view_render_event('bagisto.shop.products.name.after', ['product' => $product]) !!}

                                <!-- Rating -->
                                {!! view_render_event('bagisto.shop.products.rating.before', ['product' => $product]) !!}

                                @if ($totalRatings = $reviewHelper->getTotalFeedback($product))
                                    <!-- Scroll To Reviews Section and Activate Reviews Tab -->
                                    <div
                                        class="mt-1 w-max cursor-pointer max-sm:mt-1.5"
                                        role="button"
                                        tabindex="0"
                                        @click="scrollToReview"
                                    >
                                        <x-shop::products.ratings
                                            class="transition-all hover:border-gray-400 max-sm:px-3 max-sm:py-1"
                                            :average="$avgRatings"
                                            :total="$totalRatings"
                                            ::rating="true"
                                        />
                                    </div>
                                @endif

                                {!! view_render_event('bagisto.shop.products.rating.after', ['product' => $product]) !!}

                                <!-- Pricing -->
                                @unless ($catalogModeHidePrices)
                                    {!! view_render_event('bagisto.shop.products.price.before', ['product' => $product]) !!}

                                    <p class="mt-[22px] flex items-center gap-2.5 text-2xl !font-medium max-sm:mt-2 max-sm:gap-x-2.5 max-sm:gap-y-0 max-sm:text-md">
                                        {!! $product->getTypeInstance()->getPriceHtml() !!}
                                    </p>

                                    @if (\Webkul\Tax\Facades\Tax::isInclusiveTaxProductPrices())
                                        <span class="text-sm font-normal text-zinc-500 max-sm:text-xs">
                                            (@lang('shop::app.products.view.tax-inclusive'))
                                        </span>
                                    @endif

                                    @if (count($product->getTypeInstance()->getCustomerGroupPricingOffers()))
                                        <div class="mt-2.5 grid gap-1.5">
                                            @foreach ($product->getTypeInstance()->getCustomerGroupPricingOffers() as $offer)
                                                <p class="text-zinc-500 [&>*]:text-black">
                                                    {!! $offer !!}
                                                </p>
                                            @endforeach
                                        </div>
                                    @endif

                                    {!! view_render_event('bagisto.shop.products.price.after', ['product' => $product]) !!}
                                @endunless

                                @php
                                    $isConfigurable = $product->type === 'configurable';

                                    // Simple products: static stock from the product's own inventory.
                                    // Configurable products: the badge is driven live by the selected
                                    // variant via JS (see configurable.blade.php), so it starts hidden
                                    // and only appears once a complete variant (e.g. size) is chosen.
                                    $stockQty = $isConfigurable ? 0 : $product->inventories->sum('qty');

                                    $showInitialBadge = ! $isConfigurable && $stockQty >= 1 && $stockQty <= 15;
                                @endphp

                                <div
                                    id="low-stock-badge"
                                    class="mt-3 inline-flex items-center gap-2"
                                    style="background:#FAEEDA; border-radius:20px; padding:5px 12px;{{ $showInitialBadge ? '' : ' display:none;' }}"
                                >
                                    <span style="width:8px; height:8px; border-radius:50%; background:#EF9F27; flex-shrink:0; display:inline-block;"></span>
                                    <span id="low-stock-text" style="font-size:13px; font-weight:500; color:#854F0B;">Only {{ $stockQty }} left in stock</span>
                                </div>

                                {!! view_render_event('bagisto.shop.products.short_description.before', ['product' => $product]) !!}

                                <div class="uf-rte mt-6 text-md text-zinc-300 max-sm:mt-4 max-sm:text-sm">
                                    {!! $product->short_description !!}
                                </div>

                                {!! view_render_event('bagisto.shop.products.short_description.after', ['product' => $product]) !!}

                                @include('shop::products.view.types.simple')

                                <div id="product-variant-section">
                                    @include('shop::products.view.types.configurable')
                                </div>

                                @include('shop::products.view.types.grouped')

                                @include('shop::products.view.types.bundle')

                                @include('shop::products.view.types.downloadable')

                                @include('shop::products.view.types.booking')

                                @if ($catalogModeEnabled)
                                    <!-- Catalog Mode: purchase actions are disabled, show message instead -->
                                    <div class="mt-8 max-w-[470px] rounded-xl border border-white/10 bg-white/[0.02] p-4 text-sm text-zinc-300">
                                        {{ $catalogModeMessage }}
                                    </div>
                                @else
                                    <!-- Product Actions and Quantity Box -->
                                    <div id="product-atc-actions" class="mt-8 flex max-w-[470px] gap-4 max-sm:mt-6">

                                        {!! view_render_event('bagisto.shop.products.view.quantity.before', ['product' => $product]) !!}

                                        @if ($product->getTypeInstance()->showQuantityBox())
                                            <x-shop::quantity-changer
                                                name="quantity"
                                                value="1"
                                                class="shrink-0 gap-x-4 rounded-xl px-5 py-2 max-md:py-1.5 max-sm:gap-x-1 max-sm:rounded-lg max-sm:px-2 max-sm:py-1"
                                            />
                                        @endif

                                        {!! view_render_event('bagisto.shop.products.view.quantity.after', ['product' => $product]) !!}

                                        @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                                            <!-- Add To Cart Button -->
                                            {!! view_render_event('bagisto.shop.products.view.add_to_cart.before', ['product' => $product]) !!}

                                            <x-shop::button
                                                type="submit"
                                                class="secondary-button w-full min-w-0 max-w-full whitespace-nowrap max-md:py-3 max-sm:rounded-lg max-sm:px-3 max-sm:py-3.5"
                                                button-type="secondary-button"
                                                :loading="false"
                                                :title="trans('shop::app.products.view.add-to-cart')"
                                                :disabled="! $product->isSaleable(1)"
                                                ::loading="isStoring.addToCart"
                                                ::disabled="isStoring.addToCart"
                                                @click="is_buy_now=0;"
                                            />

                                            {!! view_render_event('bagisto.shop.products.view.add_to_cart.after', ['product' => $product]) !!}
                                        @else
                                            <button
                                                type="button"
                                                class="secondary-button w-full min-w-0 max-w-full whitespace-nowrap max-md:py-3 max-sm:rounded-lg max-sm:px-3 max-sm:py-3.5"
                                                @click="$refs.contactUsModal.open()"
                                            >
                                                @lang('shop::app.components.layouts.footer.contact-us')
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Buy Now Button -->
                                    @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                                        {!! view_render_event('bagisto.shop.products.view.buy_now.before', ['product' => $product]) !!}

                                        @if (core()->getConfigData('catalog.products.storefront.buy_now_button_display'))
                                            <x-shop::button
                                                type="submit"
                                                class="primary-button mt-4 w-full max-w-[470px] max-md:py-3 max-sm:mt-3 max-sm:rounded-lg max-sm:py-3.5"
                                                button-type="primary-button"
                                                ::title="buyNowLabel"
                                                ::loading="isStoring.buyNow"
                                                ::disabled="isStoring.buyNow || ! variantSelected || ! {{ $product->isSaleable(1) ? 'true' : 'false' }}"
                                                @click="is_buy_now=1;"
                                            />
                                        @endif

                                        {!! view_render_event('bagisto.shop.products.view.buy_now.after', ['product' => $product]) !!}
                                    @endif
                                @endif

                                <!-- Trust Badges -->
                                <div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3 pt-2">
                                    <span class="flex items-center gap-2 text-sm font-medium text-zinc-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7ed957" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                        Free delivery
                                    </span>
                                    <span class="flex items-center gap-2 text-sm font-medium text-zinc-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7ed957" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.1"/></svg>
                                        Easy returns
                                    </span>
                                    <span class="flex items-center gap-2 text-sm font-medium text-zinc-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E8872E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Secure payment
                                    </span>
                                </div>

                                <!-- Pincode Delivery Checker -->
                                <div class="mt-6 w-full rounded-xl border border-white/10 bg-white/[0.02] p-6 max-sm:p-4">

                                    <!-- Heading -->
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-uf-accent/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c7eb31" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </span>
                                        <h3 class="font-poppins text-xl font-bold uppercase tracking-wide text-white max-sm:text-xl">Check Delivery</h3>
                                        <span class="rounded-full border border-uf-accent px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-uf-accent">New</span>
                                    </div>
                                    <p class="mt-2 text-sm text-zinc-400">Enter your pincode to check delivery availability</p>

                                    <!-- Input + button -->
                                    <div class="mt-5 flex gap-4 max-sm:flex-col">
                                        <div class="relative flex-1">
                                            <span class="pointer-events-none absolute top-1/2 -translate-y-1/2 text-zinc-500 ltr:left-4 rtl:right-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            </span>
                                            <input
                                                v-model="pincode"
                                                type="text"
                                                inputmode="numeric"
                                                maxlength="6"
                                                placeholder="Enter pincode"
                                                class="h-[60px] w-full rounded-md border border-uf-accent/60 bg-white/[0.02] text-base text-white outline-none transition placeholder:text-zinc-500 focus:border-uf-accent focus:ring-2 focus:ring-uf-accent/20 ltr:pl-12 ltr:pr-4 rtl:pr-12 rtl:pl-4"
                                                @keyup.enter="checkDelivery"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            class="flex h-[60px] flex-shrink-0 items-center justify-center gap-2.5 rounded-md bg-gradient-to-b from-[#d4ef4f] to-[#a9da1e] px-9 text-base font-bold text-black transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60 max-sm:w-full"
                                            :disabled="checkingDelivery"
                                            @click="checkDelivery"
                                        >
                                            <template v-if="!checkingDelivery">
                                                <span>Check</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                            </template>
                                            <span v-else class="opacity-60">···</span>
                                        </button>
                                    </div>

                                    <!-- Inline result -->
                                    <div v-if="deliveryResult" class="mt-3">
                                        <template v-if="deliveryResult.deliverable">
                                            <p class="flex flex-wrap items-center gap-1.5 text-sm text-uf-accent">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c7eb31" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
                                                Delivery by <strong class="ltr:ml-0.5 rtl:mr-0.5 text-white">@{{ deliveryResult.days }}</strong>
                                                <span v-if="deliveryResult.cod" class="text-xs text-zinc-400">· COD available</span>
                                            </p>
                                            <p v-if="deliveryResult.free" class="mt-1 text-xs text-zinc-400 ltr:pl-6 rtl:pr-6">Free shipping on this order</p>
                                        </template>
                                        <p v-else class="text-sm text-red-400">Delivery not available to this pincode.</p>
                                    </div>
                                    <p v-if="deliveryError" class="mt-2 text-xs text-red-400">@{{ deliveryError }}</p>

                                    <!-- Divider -->
                                    <div class="mt-5 border-t border-white/10"></div>

                                    <!-- Inline checks -->
                                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-300">
                                        <span class="flex items-center gap-2">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
                                            Free delivery
                                        </span>
                                        <span class="h-4 w-px bg-white/10"></span>
                                        <span class="flex items-center gap-2">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
                                            7-day returns
                                        </span>
                                        <span class="h-4 w-px bg-white/10"></span>
                                        <span class="flex items-center gap-2">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
                                            Easy exchange
                                        </span>
                                    </div>

                                    <!-- Trust feature card -->
                                    <div class="mt-5 grid grid-cols-1 rounded-lg border border-white/5 bg-black/30 md:grid-cols-3">
                                        <div class="flex items-center gap-3 p-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#E8872E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <div>
                                                <p class="text-base font-semibold text-white max-sm:text-sm">Secure payment</p>
                                                <p class="text-xs text-zinc-500">Safe &amp; trusted checkout</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 border-white/5 p-4 max-md:border-y md:border-x">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.1"/></svg>
                                            <div>
                                                <p class="text-base font-semibold text-white max-sm:text-sm">Easy 7-day returns</p>
                                                <p class="text-xs text-zinc-500">Hassle-free returns</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><path d="M12 2l8 3v6c0 5-3.5 8-8 11-4.5-3-8-6-8-11V5l8-3z"/><path d="M12 8.2l1.8 1.8-1.8 1.8-1.8-1.8z"/></svg>
                                            <div>
                                                <p class="text-base font-semibold text-white max-sm:text-sm">100% genuine</p>
                                                <p class="text-xs text-zinc-500">Original products only</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <!-- /Pincode Delivery Checker -->

                                {!! view_render_event('bagisto.shop.products.view.additional_actions.before', ['product' => $product]) !!}

                                <!-- Share This Product -->
                                <div class="mt-6 w-full rounded-xl border border-white/10 bg-white/[0.02] p-6 max-sm:p-4">
                                    <div class="flex items-center justify-between gap-5 max-md:flex-col max-md:items-start max-md:gap-4">
                                        <!-- Left: icon + text -->
                                        <div class="flex items-center gap-4">
                                            <span class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-purple-500/10 text-purple-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                            </span>
                                            <div>
                                                <h3 class="font-poppins text-lg font-bold uppercase tracking-wide text-white">Share this product</h3>
                                                <p class="mt-1 text-sm text-zinc-400">Know someone who&rsquo;d love this? Share it with them!</p>
                                            </div>
                                        </div>

                                        <!-- Right: share actions -->
                                        <div class="flex flex-wrap items-center gap-3 max-md:w-full max-md:justify-start">
                                            {!! view_render_event('bagisto.shop.products.view.compare.before', ['product' => $product]) !!}

                                            @if (core()->getConfigData('catalog.products.settings.compare_option') && $catalogModeAllowCompare)
                                                <button
                                                    type="button"
                                                    class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-white/5 text-zinc-300 transition-colors hover:bg-white/10 hover:text-uf-accent"
                                                    aria-label="@lang('shop::app.products.view.compare')"
                                                    @click="is_buy_now=0; addToCompare({{ $product->id }})"
                                                >
                                                    <span class="icon-compare text-xl" role="presentation"></span>
                                                </button>
                                            @endif

                                            {!! view_render_event('bagisto.shop.products.view.compare.after', ['product' => $product]) !!}

                                            <!-- Facebook Share -->
                                            <button
                                                type="button"
                                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-white/5 text-white transition-colors hover:bg-[#1877f2] hover:text-white"
                                                aria-label="Share on Facebook"
                                                @click="shareFacebook"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                            </button>

                                            <!-- WhatsApp Share -->
                                            <a
                                                href="https://wa.me/?text={{ urlencode($product->name . ' | ' . route('shop.product_or_category.index', $product->url_key)) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-white/5 text-white transition-colors hover:bg-[#25d366] hover:text-white"
                                                aria-label="Share on WhatsApp"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                            </a>

                                            <!-- Copy Link -->
                                            <button
                                                type="button"
                                                class="flex h-11 flex-shrink-0 items-center gap-2 rounded-md border border-purple-500/60 px-5 text-sm font-medium text-purple-300 transition-colors hover:bg-purple-500/10 hover:text-purple-200"
                                                @click="copyProductLink"
                                            >
                                                <svg v-if="!copySuccess" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                <span>
                                                    <template v-if="copySuccess">Copied!</template>
                                                    <template v-else>Copy link</template>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {!! view_render_event('bagisto.shop.products.view.additional_actions.after', ['product' => $product]) !!}
                            </div>
                        </div>
                    </div>
                </form>
            </x-shop::form>

            <!-- Contact Us Modal -->
            <x-shop::modal ref="contactUsModal">
                <x-slot:header>
                <h2 class="text-md font-semibold max-md:text-base">
                        @lang('shop::app.products.view.contact-us.title')
                    </h2>
                </x-slot>

                <x-slot:content>
                    <x-shop::form :action="route('shop.home.contact_us.send_mail')">
                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.name')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name')"
                                :label="trans('shop::app.products.view.contact-us.name')"
                                :placeholder="trans('shop::app.products.view.contact-us.name')"
                                :aria-label="trans('shop::app.products.view.contact-us.name')"
                                aria-required="true"
                            />

                            <x-shop::form.control-group.error control-name="name" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.email')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="email"
                                name="email"
                                rules="required|email"
                                :value="old('email')"
                                :label="trans('shop::app.products.view.contact-us.email')"
                                :placeholder="trans('shop::app.products.view.contact-us.email')"
                                :aria-label="trans('shop::app.products.view.contact-us.email')"
                                aria-required="true"
                            />

                            <x-shop::form.control-group.error control-name="email" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label>
                                @lang('shop::app.products.view.contact-us.phone-number')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="text"
                                name="contact"
                                rules="phone"
                                :value="old('contact')"
                                :label="trans('shop::app.products.view.contact-us.phone-number')"
                                :placeholder="trans('shop::app.products.view.contact-us.phone-number')"
                                :aria-label="trans('shop::app.products.view.contact-us.phone-number')"
                            />

                            <x-shop::form.control-group.error control-name="contact" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.desc')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="textarea"
                                name="message"
                                rules="required"
                                :label="trans('shop::app.products.view.contact-us.message')"
                                :placeholder="trans('shop::app.products.view.contact-us.describe-here')"
                                :aria-label="trans('shop::app.products.view.contact-us.message')"
                                aria-required="true"
                                rows="6"
                            />

                            <x-shop::form.control-group.error control-name="message" />
                        </x-shop::form.control-group>

                        @if (core()->getConfigData('customer.captcha.credentials.status'))
                            <x-shop::form.control-group class="mt-5">
                                {!! \Webkul\Customer\Facades\Captcha::render() !!}

                                <x-shop::form.control-group.error control-name="recaptcha_token" />
                            </x-shop::form.control-group>
                        @endif

                        <div class="mt-6 flex justify-end">
                            <button
                                type="submit"
                                class="primary-button rounded-2xl px-8 py-3 max-sm:rounded-lg max-sm:px-6 max-sm:py-3"
                            >
                                @lang('shop::app.products.view.contact-us.submit')
                            </button>
                        </div>
                    </x-shop::form>
                </x-slot>
            </x-shop::modal>
        </script>

        <script type="module">
            app.component('v-product', {
                template: '#v-product-template',

                data() {
                    return {
                        isWishlist: false,

                        isCustomer: '{{ auth()->guard('customer')->check() }}',

                        is_buy_now: 0,

                        // GA4 item for the add_to_cart data-layer push (price/category exact from PHP).
                        trackItem: @json(\App\Support\DataLayer::productItem($product)),
                        trackCurrency: @json(\App\Support\DataLayer::currency()),

                        isStoring: {
                            addToCart: false,

                            buyNow: false,
                        },

                        copySuccess: false,

                        variantSelected: @json($product->type !== 'configurable'),

                        buyNowLabel: @json(
                            $product->type !== 'configurable'
                                ? trans('shop::app.products.view.buy-now') . ' – ' . core()->currency($product->getTypeInstance()->getFinalPrice())
                                : trans('shop::app.products.view.buy-now')
                        ),

                        pincode: '',
                        deliveryResult: null,
                        deliveryError: '',
                        checkingDelivery: false,
                    }
                },

                mounted() {
                    this.checkWishlistStatus();

                    this.$emitter.on('configurable-variant-price-updated', ({ price, variantId }) => {
                        if (variantId && price) {
                            this.buyNowLabel    = '{{ trans('shop::app.products.view.buy-now') }} – ' + price;
                            this.variantSelected = true;
                        } else {
                            this.buyNowLabel    = '{{ trans('shop::app.products.view.buy-now') }}';
                            this.variantSelected = false;
                        }
                    });
                },

                methods: {
                    addToCart(params) {
                        const operation = this.is_buy_now ? 'buyNow' : 'addToCart';

                        this.isStoring[operation] = true;

                        let formData = new FormData(this.$refs.formData);

                        this.ensureQuantity(formData);

                        this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            })
                            .then(response => {
                                if (response.data.message) {
                                    this.$emitter.emit('update-mini-cart', response.data.data);

                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                    this.pushAddToCart(formData);

                                    if (response.data.redirect) {
                                        window.location.href= response.data.redirect;
                                    }
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }

                                this.isStoring[operation] = false;
                            })
                            .catch(error => {
                                this.isStoring[operation] = false;

                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            });
                    },

                    checkWishlistStatus() {
                        if (this.isCustomer) {
                            /**
                             * Fetches the wishlist items for the customer and checks whether the current
                             * product exists in the wishlist. If found, `isWishlist` is set to true;
                             * otherwise, it is set to false.
                             *
                             * This approach is used due to Full Page Cache (FPC) limitations. We cannot
                             * use a replacer here because `product_id` is dynamic, and the replacer
                             * cannot reliably detect it.
                             */
                            this.$axios.get('{{ route('shop.api.customers.account.wishlist.index') }}')
                                .then(response => {
                                    const wishlistItems = response.data.data || [];

                                    this.isWishlist = Boolean(wishlistItems.find(item => item.product.id == "{{ $product->id }}")?.product?.is_wishlist);
                                })
                                .catch(error => {});
                        }
                    },

                    addToWishlist() {
                        if (this.isCustomer) {
                            this.$axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', {
                                    product_id: "{{ $product->id }}"
                                })
                                .then(response => {
                                    this.isWishlist = ! this.isWishlist;

                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                    },

                    addToCompare(productId) {
                        /**
                         * This will handle for customers.
                         */
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

                        /**
                         * This will handle for guests.
                         */
                        let existingItems = this.getStorageValue(this.getCompareItemsStorageKey()) ?? [];

                        if (existingItems.length) {
                            if (! existingItems.includes(productId)) {
                                existingItems.push(productId);

                                this.setStorageValue(this.getCompareItemsStorageKey(), existingItems);

                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.products.view.add-to-compare')" });
                            } else {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('shop::app.products.view.already-in-compare')" });
                            }
                        } else {
                            this.setStorageValue(this.getCompareItemsStorageKey(), [productId]);

                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.products.view.add-to-compare')" });
                        }
                    },

                    updateQty(quantity, id) {
                        this.isLoading = true;

                        let qty = {};

                        qty[id] = quantity;

                        this.$axios.put('{{ route('shop.api.checkout.cart.update') }}', { qty })
                            .then(response => {
                                if (response.data.message) {
                                    this.cart = response.data.data;
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }

                                this.isLoading = false;
                            }).catch(error => this.isLoading = false);
                    },

                    getCompareItemsStorageKey() {
                        return 'compare_items';
                    },

                    setStorageValue(key, value) {
                        localStorage.setItem(key, JSON.stringify(value));
                    },

                    getStorageValue(key) {
                        let value = localStorage.getItem(key);

                        if (value) {
                            value = JSON.parse(value);
                        }

                        return value;
                    },

                    scrollToReview() {
                        let accordianElement = document.querySelector('#review-accordian-button');

                        if (accordianElement) {
                            accordianElement.click();

                            accordianElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }

                        let tabElement = document.querySelector('#review-tab-button');

                        if (tabElement) {
                            tabElement.click();

                            tabElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    },

                    ensureQuantity(formData) {
                        if (! formData.has('quantity')) {
                            formData.append('quantity', 1);
                        }
                    },

                    /* GA4 add_to_cart — fires on a confirmed cart store. Reuses the
                       PHP-built item so price/category are exact; quantity and the
                       selected variant (colour/size) are read from the live form. */
                    pushAddToCart(formData) {
                        if (! window.dataLayer) return;

                        const qty  = parseInt(formData.get('quantity') || 1, 10) || 1;
                        const item = Object.assign({}, this.trackItem, { quantity: qty });

                        const variantLabels = Array.from(document.querySelectorAll('[aria-selected="true"]'))
                            .map(el => (el.getAttribute('aria-label') || el.textContent || '').trim())
                            .filter(Boolean);

                        if (variantLabels.length) {
                            item.item_variant = variantLabels.join(' / ');
                        }

                        window.dataLayer.push({ ecommerce: null });
                        window.dataLayer.push({
                            event: 'add_to_cart',
                            ecommerce: {
                                currency: this.trackCurrency,
                                value: +(item.price * qty).toFixed(2),
                                items: [item],
                            },
                        });
                    },

                    shareFacebook() {
                        const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href);
                        window.open(url, 'facebook-share', 'width=580,height=480,scrollbars=yes');
                    },

                    copyProductLink() {
                        const url = window.location.href;

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(url)
                                .then(() => {
                                    this.copySuccess = true;
                                    setTimeout(() => this.copySuccess = false, 2000);
                                })
                                .catch(() => this.fallbackCopy(url));
                        } else {
                            this.fallbackCopy(url);
                        }
                    },

                    fallbackCopy(url) {
                        const el = document.createElement('textarea');
                        el.value = url;
                        el.setAttribute('readonly', '');
                        el.style.cssText = 'position:fixed;opacity:0';
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        this.copySuccess = true;
                        setTimeout(() => this.copySuccess = false, 2000);
                    },

                    checkDelivery() {
                        if (!/^\d{6}$/.test(this.pincode)) {
                            this.deliveryError  = 'Please enter a valid 6-digit pincode.';
                            this.deliveryResult = null;
                            return;
                        }

                        this.deliveryError    = '';
                        this.deliveryResult   = null;
                        this.checkingDelivery = true;

                        this.$axios.post('{{ route("check.delivery") }}', { pincode: this.pincode })
                            .then(response => {
                                this.deliveryResult = response.data;
                            })
                            .catch(() => {
                                this.deliveryError = 'Could not check delivery. Please try again.';
                            })
                            .finally(() => {
                                this.checkingDelivery = false;
                            });
                    },
                },
            });
        </script>

        <script
            type="text/x-template"
            id="v-product-associations-template"
        >
            <div ref="carouselWrapper">
                <template v-if="isVisible">
                    <!-- Related Products (responsive grid + load more) -->
                    <x-shop::products.grid
                        :title="trans('shop::app.products.view.related-product-title')"
                        :src="route('shop.api.products.related.index', ['id' => $product->id])"
                    />

                    <!-- Up-sell Products -->
                    <x-shop::products.carousel
                        :title="trans('shop::app.products.view.up-sell-title')"
                        :src="route('shop.api.products.up-sell.index', ['id' => $product->id])"
                    />
                </template>
            </div>
        </script>

        <script type="module">
            app.component('v-product-associations', {
                template: '#v-product-associations-template',

                data() {
                    return {
                        isVisible: false,
                    };
                },

                mounted() {
                    const observer = new IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    this.isVisible = true;
                                    observer.unobserve(entry.target); // Stop observing
                                }
                            });
                        },
                        { threshold: 0.1 }
                    );

                    observer.observe(this.$refs.carouselWrapper);
                }
            });
        </script>

        @if (core()->getConfigData('customer.captcha.credentials.status'))
            {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
        @endif

        {{-- Sticky Add-to-Cart Bar
             Runs after window.load (same event Vue uses to mount) so all
             Vue-rendered elements like #product-atc-actions are in the DOM. --}}
        <script>
        window.addEventListener('load', function () {
            /* Give Vue a tick to finish rendering after mount */
            setTimeout(function () {
                var bar       = document.getElementById('sticky-atc-bar');
                var stickyBtn = document.getElementById('sticky-atc-btn');
                var priceEl   = document.getElementById('sticky-atc-price');
                var varEl     = document.getElementById('sticky-atc-variant');
                var anchor    = document.getElementById('product-atc-actions');

                if (!bar || !anchor) return;

                /* Guarantee hidden on first paint */
                bar.style.transform = 'translateY(100%)';

                function showBar() {
                    bar.style.transform = 'translateY(0)';
                    bar.removeAttribute('inert');
                }

                function hideBar() {
                    bar.style.transform = 'translateY(100%)';
                    bar.setAttribute('inert', '');
                }

                /* Sync price from the page's main price paragraph */
                function syncPrice() {
                    var p = document.querySelector('p.text-2xl');
                    if (p && p.textContent.indexOf('₹') !== -1) {
                        priceEl.textContent = p.textContent.replace(/\s+/g, ' ').trim();
                    }
                }

                /* Sync selected variant labels (aria-selected="true" on active swatches) */
                function syncVariant() {
                    var labels = [];
                    document.querySelectorAll('[aria-selected="true"]').forEach(function (el) {
                        var t = (el.getAttribute('aria-label') || el.textContent).trim();
                        if (t) labels.push(t);
                    });
                    varEl.textContent = labels.join(' / ');
                }

                /* Scroll listener: show bar only when the CTA has scrolled
                   completely ABOVE the viewport (rect.bottom <= 0).
                   While the CTA is visible OR still below viewport → hide. */
                function onScroll() {
                    var rect = anchor.getBoundingClientRect();
                    if (rect.bottom <= 0) {
                        showBar();
                    } else {
                        hideBar();
                    }
                }

                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll(); /* set correct initial state */
                syncPrice();
                syncVariant();

                /* Re-sync when user picks colour / size */
                document.addEventListener('click', function () {
                    setTimeout(function () { syncPrice(); syncVariant(); }, 80);
                });

                /* Vue emits this when a configurable variant is chosen */
                document.addEventListener('configurable-variant-price-updated', function () {
                    setTimeout(function () { syncPrice(); syncVariant(); }, 50);
                });

                /* Sticky button:
                   1. Click the real Add-to-Cart button (inside v-button Vue component)
                   2. If validation fails (no variant selected), scroll to variant section */
                stickyBtn.addEventListener('click', function () {
                    /* Detect missing variant BEFORE triggering submit so we
                       don't need to race VeeValidate's async error rendering. */
                    var selectedInput  = document.getElementById('selected_configurable_option');
                    var needsVariant   = selectedInput !== null && !selectedInput.value;

                    var realBtn = anchor.querySelector('button[type="submit"]');
                    if (realBtn) realBtn.click();

                    if (needsVariant) {
                        /* Give VeeValidate one tick to paint the error messages,
                           then scroll the variant section into view. */
                        setTimeout(function () {
                            var variantSection = document.getElementById('product-variant-section');
                            if (variantSection) {
                                variantSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }, 80);
                    }
                });

            }, 300); /* 300 ms after load gives Vue time to finish rendering */
        });
        </script>

    @endPushOnce
</x-shop::layouts>
