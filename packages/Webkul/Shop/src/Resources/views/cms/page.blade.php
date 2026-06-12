@php
    /* Single source of truth for the page title — reused by <title>, og:title and twitter:title.
       Falls back to the page heading so an empty admin meta-title never yields a blank <title>. */
    $pageTitle = trim((string) $page->meta_title) !== '' ? $page->meta_title : $page->page_title;
@endphp

<!-- SEO Meta Content — full page-specific block; flags layout to skip its generic fallback -->
@push('meta')
    <meta name="description" content="{{ $page->meta_description }}" />
    <meta name="keywords" content="{{ $page->meta_keywords }}" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="{{ url($page->url_key) }}" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $pageTitle }}" />
    <meta property="og:description" content="{{ $page->meta_description }}" />
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}" />
    <meta property="og:url" content="{{ url($page->url_key) }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $pageTitle }}" />
    <meta name="twitter:description" content="{{ $page->meta_description }}" />
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}" />
@endPush

<!-- Page Layout -->
<x-shop::layouts :has-custom-seo="true">
    <!-- Page Title -->
    <x-slot:title>
        {{ $pageTitle }}
    </x-slot>

    <!-- Page Content -->
    <div class="uf-rte container mt-8 mb-16 px-[60px] text-zinc-300 max-lg:px-8">
        {!! $page->html_content !!}
    </div>
</x-shop::layouts>