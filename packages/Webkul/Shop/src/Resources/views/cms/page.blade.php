<!-- SEO Meta Content -->
@push('meta')
    <meta name="title" content="{{ $page->meta_title }}" />

    <meta name="description" content="{{ $page->meta_description }}" />

    <meta name="keywords" content="{{ $page->meta_keywords }}" />

    <link rel="canonical" href="{{ url($page->url_key) }}" />
@endPush

<!-- Page Layout -->
<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{ $page->meta_title }}
    </x-slot>

    <!-- Page Content -->
    <div class="uf-rte container mt-8 mb-16 px-[60px] text-zinc-300 max-lg:px-8">
        {!! $page->html_content !!}
    </div>
</x-shop::layouts>