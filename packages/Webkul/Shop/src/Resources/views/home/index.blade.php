@php
    $channel = core()->getCurrentChannel();
@endphp

<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? "Shop men's polo t-shirts, slim fit tshirts and casual wear for men & women at Urbanflaky. Mid-range fashion Rs 299–799. Fast delivery pan India including Rajasthan, Jaipur and all metros. – Gabha Enterprise" }}">
    <meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? 'urbanflaky, polo tshirt online india, slim fit tshirt men, casual wear men women, buy tshirt under 500, mens fashion online, womens casual wear india, fashion jaipur rajasthan, gabha enterprise, tshirt delivery india' }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('shop.home.index') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $channel->home_seo['meta_title'] ?? "Urbanflaky — Men's Polo T-Shirts & Slim Fit Casuals Online" }}">
    <meta property="og:description" content="{{ $channel->home_seo['meta_description'] ?? "Shop polo t-shirts, slim fit casuals for men & women at Urbanflaky. Rs 299–799. Pan India delivery. – Gabha Enterprise" }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('shop.home.index') }}">
    <meta property="og:site_name" content="Urbanflaky">
    <meta property="og:locale" content="en_IN">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $channel->home_seo['meta_title'] ?? "Urbanflaky — Men's Fashion Online | Gabha Enterprise" }}">
    <meta name="twitter:description" content="Shop polo t-shirts & slim fit casuals. Rs 299–799. Pan India delivery.">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
    <meta name="twitter:site" content="@urbanflaky">
@endpush

<!-- Structured Data -->
@push('structured_data')
<script type="application/ld+json">
[
  {
    "@@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Urbanflaky",
    "alternateName": "Urbanflaky by Gabha Enterprise",
    "url": "{{ config('app.url') }}",
    "potentialAction": {
      "@type": "SearchAction",
      "target": {
        "@type": "EntryPoint",
        "urlTemplate": "{{ route('shop.search.index') }}?query={search_term_string}"
      },
      "query-input": "required name=search_term_string"
    }
  },
  {
    "@@context": "https://schema.org",
    "@type": "Organization",
    "name": "Gabha Enterprise",
    "alternateName": "Urbanflaky",
    "url": "{{ config('app.url') }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('images/og-image.png') }}"
    },
    "description": "Gabha Enterprise operates Urbanflaky — an online fashion store offering polo t-shirts, slim fit casuals and everyday wear for men and women. Mid-range fashion Rs 299–799 with pan India delivery.",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Dholpur",
      "addressRegion": "Rajasthan",
      "postalCode": "328001",
      "addressCountry": "IN"
    },
    "contactPoint": {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "availableLanguage": ["English", "Hindi"]
    },
    "sameAs": [
      "https://www.instagram.com/urbanflaky/",
      "https://www.facebook.com/urbanflaky"
    ]
  }
]
</script>
@endpush

@push('scripts')
    @if(! empty($categories))
        <script>
            localStorage.setItem('categories', JSON.stringify(@json($categories)));
        </script>
    @endif
@endpush

<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{ $channel->home_seo['meta_title'] ?? "Urbanflaky — Men's Polo T-Shirts & Slim Fit Casuals Online | Gabha Enterprise" }}
    </x-slot>

    <!-- Loop over the theme customization -->
    @foreach ($customizations as $customization)
        @php ($data = $customization->options) @endphp

        <!-- Static content -->
        @switch ($customization->type)
            @case ($customization::IMAGE_CAROUSEL)
                <!-- Image Carousel -->
                <x-shop::carousel
                    :options="$data"
                    aria-label="{{ trans('shop::app.home.index.image-carousel') }}"
                />

                @break
            @case ($customization::STATIC_CONTENT)
                <!-- push style -->
                @if (! empty($data['css']))
                    @push ('styles')
                        <style>
                            {{ $data['css'] }}
                        </style>
                    @endpush
                @endif

                <!-- render html -->
                @if (! empty($data['html']))
                    {!! $data['html'] !!}
                @endif

                @break
            @case ($customization::CATEGORY_CAROUSEL)
                <!-- Categories carousel -->
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.home.index')"
                    aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                />

                @break
            @case ($customization::PRODUCT_CAROUSEL)
                <!-- Product Carousel -->
                <x-shop::products.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.products.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                />

                @break
        @endswitch
    @endforeach
</x-shop::layouts>
