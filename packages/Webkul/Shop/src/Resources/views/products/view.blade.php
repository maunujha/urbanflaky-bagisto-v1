@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $avgRatings = $reviewHelper->getAverageRating($product);

    $percentageRatings = $reviewHelper->getPercentageRating($product);

    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));

    $productBaseImage = product_image()->getProductBaseImage($product);

    $reviewCount = $reviewHelper->getTotalFeedback($product);

    $productBaseDesc = trim($product->meta_description) != ''
        ? $product->meta_description
        : \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 80, '');

    $metaDesc = ($productBaseDesc ? $productBaseDesc . ' ' : '')
        . 'Shop ' . $product->name . ' at Rs ' . number_format($product->price, 0)
        . ' on Urbanflaky. Fast delivery pan India. – Gabha Enterprise';

    $productCanonical = route('shop.product_or_category.index', $product->url_key);
@endphp

<!-- SEO Meta Content — full product-specific block; flags layout to skip its generic fallback -->
@push('meta')
    <meta name="description" content="{{ $metaDesc }}">
    <meta name="keywords" content="{{ $product->meta_keywords }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $productCanonical }}">

    <meta property="og:type" content="og:product">
    <meta property="og:title" content="{{ $product->meta_title ?: $product->name }}">
    <meta property="og:description" content="{{ htmlspecialchars(trim(strip_tags($product->description ?? ''))) }}">
    <meta property="og:image" content="{{ $productBaseImage['medium_image_url'] }}">
    <meta property="og:url" content="{{ $productCanonical }}">

    @if (core()->getConfigData('catalog.rich_snippets.products.enable'))
        <script type="application/ld+json">
            {!! app('Webkul\Product\Helpers\SEO')->getProductJsonLd($product) !!}
        </script>
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $product->name }}">
    <meta name="twitter:description" content="{{ htmlspecialchars(trim(strip_tags($product->description ?? ''))) }}">
    <meta name="twitter:image:alt" content="{{ $product->name }}">
    <meta name="twitter:image" content="{{ $productBaseImage['medium_image_url'] }}">
@endpush

<!-- Product Structured Data -->
@push('structured_data')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ addslashes($product->name) }}",
  "description": "{{ addslashes(\Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 200)) }}",
  "sku": "{{ $product->sku }}",
  "image": "{{ $productBaseImage['medium_image_url'] }}",
  "url": "{{ route('shop.product_or_category.index', $product->url_key) }}",
  "brand": {
    "@type": "Brand",
    "name": "Urbanflaky"
  },
  "seller": {
    "@type": "Organization",
    "name": "Gabha Enterprise"
  },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "INR",
    "price": "{{ $product->price }}",
    "priceValidUntil": "{{ now()->addYear()->format('Y-m-d') }}",
    "availability": "{{ $product->isSaleable(1) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
    "itemCondition": "https://schema.org/NewCondition",
    "url": "{{ route('shop.product_or_category.index', $product->url_key) }}",
    "seller": {
      "@type": "Organization",
      "name": "Gabha Enterprise"
    }
  }
  @if ($avgRatings && $reviewCount > 0)
  ,"aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ number_format($avgRatings, 1) }}",
    "reviewCount": "{{ $reviewCount }}",
    "bestRating": "5",
    "worstRating": "1"
  }
  @endif
}
</script>
@endpush

<!-- Page Layout -->
<x-shop::layouts :has-custom-seo="true">
    <!-- Page Title -->
    <x-slot:title>
        @if (trim($product->meta_title) != '')
            {{ $product->meta_title }}
        @else
            {{ $product->name }} — Buy Online at Rs {{ number_format($product->price, 0) }} | Urbanflaky
        @endif
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
    <div class="1180:mt-20">
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
                        <p class="text-lg text-zinc-500 max-1180:text-sm">
                            {!! $product->description !!}
                        </p>
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
                                            <p class="text-base text-black">
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
                                                <p class="text-base text-zinc-500">
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
            </x-shop::tabs>
        </div>
    </div>

    <!-- Information Section -->
    <div class="container mt-6 grid gap-3 !p-0 max-1180:px-5 1180:hidden">
        <!-- Description Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="true"
        >
            <x-slot:header class="bg-gray-100 max-md:!py-3 max-sm:!py-2">
                <p class="text-base font-medium 1180:hidden">
                    @lang('shop::app.products.view.description')
                </p>
            </x-slot>

            <x-slot:content class="max-sm:px-0">
                <div class="mb-5 text-lg text-zinc-500 max-1180:text-sm max-md:mb-1 max-md:px-4">
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
                <x-slot:header class="bg-gray-100 max-md:!py-3 max-sm:!py-2">
                    <p class="text-base font-medium 1180:hidden">
                        @lang('shop::app.products.view.additional-information')
                    </p>
                </x-slot>

                <x-slot:content class="max-sm:px-0">
                    <div class="container max-1180:px-5">
                        <div class="grid max-w-max grid-cols-[auto_1fr] gap-4 text-lg text-zinc-500 max-1180:text-sm">
                            @foreach ($customAttributeValues as $customAttributeValue)
                                @if (! empty($customAttributeValue['value']))
                                    <div class="grid">
                                        <p
                                            class="text-base text-black"
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
                                                class="text-base text-zinc-500"
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
                class="bg-gray-100 max-md:!py-3 max-sm:!py-2"
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
                <p class="truncate text-sm font-semibold text-black max-sm:text-xs" id="sticky-atc-name">{{ $product->name }}</p>
                <p class="mt-0.5 text-xs text-zinc-500 max-sm:text-[10px]" id="sticky-atc-variant"></p>
            </div>

            <!-- Price -->
            <div class="flex-shrink-0 text-right">
                <p class="text-lg font-bold text-black max-sm:text-sm" id="sticky-atc-price"></p>
            </div>

            <!-- Add to Cart Button -->
            <button
                id="sticky-atc-btn"
                type="button"
                class="flex-shrink-0 rounded-xl px-6 py-3 text-sm font-bold text-black transition-opacity hover:opacity-90 max-sm:rounded-lg max-sm:px-4 max-sm:py-2 max-sm:text-xs"
                style="background:#c7eb31;"
            >
                Add to Cart
            </button>
        </div>
    </div>

    <v-product-associations />

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

                                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                        <div
                                            class="max-sm:min-h-7 max-sm:min-w-7 flex max-h-[46px] min-h-[46px] min-w-[46px] cursor-pointer items-center justify-center rounded-full border bg-white text-2xl transition-all hover:opacity-[0.8] max-sm:max-h-7 max-sm:text-base"
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
                                {!! view_render_event('bagisto.shop.products.price.before', ['product' => $product]) !!}

                                <p class="mt-[22px] flex items-center gap-2.5 text-2xl !font-medium max-sm:mt-2 max-sm:gap-x-2.5 max-sm:gap-y-0 max-sm:text-lg">
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

                                @php
                                    $stockQty = $product->type === 'configurable'
                                        ? $product->variants->sum(fn ($v) => $v->inventories->sum('qty'))
                                        : $product->inventories->sum('qty');
                                @endphp

                                @if ($stockQty >= 1 && $stockQty <= 15)
                                    <div
                                        class="mt-3 inline-flex items-center gap-2"
                                        style="background:#FAEEDA; border-radius:20px; padding:5px 12px;"
                                    >
                                        <span style="width:8px; height:8px; border-radius:50%; background:#EF9F27; flex-shrink:0; display:inline-block;"></span>
                                        <span style="font-size:13px; font-weight:500; color:#854F0B;">Only {{ $stockQty }} left in stock</span>
                                    </div>
                                @endif

                                {!! view_render_event('bagisto.shop.products.short_description.before', ['product' => $product]) !!}

                                <p class="mt-6 text-lg text-zinc-500 max-sm:mt-1.5 max-sm:text-sm">
                                    {!! $product->short_description !!}
                                </p>

                                {!! view_render_event('bagisto.shop.products.short_description.after', ['product' => $product]) !!}

                                @include('shop::products.view.types.simple')

                                <div id="product-variant-section">
                                    @include('shop::products.view.types.configurable')
                                </div>

                                @include('shop::products.view.types.grouped')

                                @include('shop::products.view.types.bundle')

                                @include('shop::products.view.types.downloadable')

                                @include('shop::products.view.types.booking')

                                <!-- Product Actions and Quantity Box -->
                                <div id="product-atc-actions" class="mt-8 flex max-w-[470px] gap-4 max-sm:mt-4">

                                    {!! view_render_event('bagisto.shop.products.view.quantity.before', ['product' => $product]) !!}

                                    @if ($product->getTypeInstance()->showQuantityBox())
                                        <x-shop::quantity-changer
                                            name="quantity"
                                            value="1"
                                            class="gap-x-4 rounded-xl px-7 py-4 max-md:py-3 max-sm:gap-x-5 max-sm:rounded-lg max-sm:px-4 max-sm:py-1.5"
                                        />
                                    @endif

                                    {!! view_render_event('bagisto.shop.products.view.quantity.after', ['product' => $product]) !!}

                                    @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                                        <!-- Add To Cart Button -->
                                        {!! view_render_event('bagisto.shop.products.view.add_to_cart.before', ['product' => $product]) !!}

                                        <x-shop::button
                                            type="submit"
                                            class="secondary-button w-full max-w-full max-md:py-3 max-sm:rounded-lg max-sm:py-1.5"
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
                                            class="secondary-button w-full max-w-full max-md:py-3 max-sm:rounded-lg max-sm:py-1.5"
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
                                            class="primary-button mt-5 w-full max-w-[470px] max-md:py-3 max-sm:mt-3 max-sm:rounded-lg max-sm:py-1.5"
                                            button-type="primary-button"
                                            ::title="buyNowLabel"
                                            ::loading="isStoring.buyNow"
                                            ::disabled="isStoring.buyNow || ! variantSelected || ! {{ $product->isSaleable(1) ? 'true' : 'false' }}"
                                            @click="is_buy_now=1;"
                                        />
                                    @endif

                                    {!! view_render_event('bagisto.shop.products.view.buy_now.after', ['product' => $product]) !!}
                                @endif

                                {!! view_render_event('bagisto.shop.products.view.additional_actions.before', ['product' => $product]) !!}

                                <!-- Share Buttons -->
                                <div class="mt-10 flex flex-wrap items-center gap-5 max-md:mt-4 max-sm:justify-center max-sm:gap-4">
                                    {!! view_render_event('bagisto.shop.products.view.compare.before', ['product' => $product]) !!}

                                    <div
                                        class="flex cursor-pointer items-center justify-center gap-2.5 max-sm:gap-1.5 max-sm:text-base"
                                        role="button"
                                        tabindex="0"
                                        @click="is_buy_now=0; addToCompare({{ $product->id }})"
                                    >
                                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                                            <span
                                                class="icon-compare text-2xl"
                                                role="presentation"
                                            ></span>

                                            @lang('shop::app.products.view.compare')
                                        @endif
                                    </div>

                                    {!! view_render_event('bagisto.shop.products.view.compare.after', ['product' => $product]) !!}

                                    <!-- Facebook Share -->
                                    <button
                                        type="button"
                                        class="flex cursor-pointer items-center gap-2 text-sm text-zinc-500 transition-colors hover:text-blue-600"
                                        aria-label="Share on Facebook"
                                        @click="shareFacebook"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        <span>Facebook</span>
                                    </button>

                                    <!-- WhatsApp Share -->
                                    <a
                                        href="https://wa.me/?text={{ urlencode($product->name . ' | ' . route('shop.product_or_category.index', $product->url_key)) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex cursor-pointer items-center gap-2 text-sm text-zinc-500 transition-colors hover:text-green-600"
                                        aria-label="Share on WhatsApp"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                        <span>WhatsApp</span>
                                    </a>

                                    <!-- Copy Link -->
                                    <button
                                        type="button"
                                        class="flex cursor-pointer items-center gap-2 text-sm text-zinc-500 transition-colors hover:text-zinc-800"
                                        @click="copyProductLink"
                                    >
                                        <svg v-if="!copySuccess" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span>
                                            <template v-if="copySuccess">Copied!</template>
                                            <template v-else>Copy link</template>
                                        </span>
                                    </button>
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
                <h2 class="text-lg font-semibold max-md:text-base">
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
                                class="primary-button rounded-2xl px-8 py-3 max-sm:rounded-lg max-sm:px-6 max-sm:py-2"
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
                },
            });
        </script>

        <script
            type="text/x-template"
            id="v-product-associations-template"
        >
            <div ref="carouselWrapper">
                <template v-if="isVisible">
                    <!-- Featured Products -->
                    <x-shop::products.carousel
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
