@php
    $metaTitle       = trans('blog::app.shop.meta-title');
    $metaDescription = trans('blog::app.shop.meta-description');
    $listUrl         = route('shop.blog.index');
    $canonical       = $blogs->currentPage() > 1 ? $blogs->url($blogs->currentPage()) : $listUrl;

    /* Breadcrumb structured data (PHP-built to avoid @@context escaping in Blade). */
    $breadcrumbSchema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => trans('blog::app.shop.breadcrumb-home'),
                'item'     => route('shop.home.index'),
            ], [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => trans('blog::app.shop.breadcrumb-blog'),
                'item'     => $listUrl,
            ],
        ],
    ];

    /* Blog / ItemList structured data for the posts on this page. */
    $blogSchema = [
        '@context'       => 'https://schema.org',
        '@type'          => 'Blog',
        'name'           => $metaTitle,
        'description'    => $metaDescription,
        'url'            => $listUrl,
        'blogPost'       => $blogs->map(fn ($blog) => [
            '@type'         => 'BlogPosting',
            'headline'      => $blog->title,
            'url'           => $blog->url,
            'datePublished' => optional($blog->published_at)->toAtomString(),
            'author'        => ['@type' => 'Person', 'name' => $blog->author ?: 'Urbanflaky'],
        ])->all(),
    ];
@endphp

@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonical }}">

    @if ($blogs->onFirstPage() === false)
        <link rel="prev" href="{{ $blogs->previousPageUrl() }}">
    @endif
    @if ($blogs->hasMorePages())
        <link rel="next" href="{{ $blogs->nextPageUrl() }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">
@endpush

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @if ($blogs->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode($blogSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

<x-shop::layouts :has-custom-seo="true">
    <x-slot:title>
        {{ $metaTitle }}
    </x-slot>

    <div class="container max-md:px-4 mt-8 mb-16">
        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm text-uf-muted" aria-label="Breadcrumb">
            <a href="{{ route('shop.home.index') }}" class="hover:text-uf-text">@lang('blog::app.shop.breadcrumb-home')</a>
            <span class="px-1.5">/</span>
            <span class="text-uf-text">@lang('blog::app.shop.breadcrumb-blog')</span>
        </nav>

        {{-- Heading --}}
        <header class="mb-10">
            <h1 class="text-4xl font-bold text-uf-text max-md:text-3xl">
                @lang('blog::app.shop.heading')
            </h1>

            <p class="mt-3 max-w-2xl text-uf-muted">
                @lang('blog::app.shop.subheading')
            </p>
        </header>

        @if ($blogs->isEmpty())
            <p class="rounded-2xl border border-uf-border bg-uf-surface px-6 py-16 text-center text-uf-muted">
                @lang('blog::app.shop.no-posts')
            </p>
        @else
            <div class="grid grid-cols-1 gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($blogs as $blog)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border border-uf-border bg-uf-surface transition-colors hover:border-uf-accent/50">
                        <a
                            href="{{ route('shop.blog.show', $blog->slug) }}"
                            class="block aspect-[16/10] overflow-hidden bg-uf-surface2"
                            aria-label="{{ $blog->title }}"
                        >
                            @if ($blog->image_url)
                                <img
                                    src="{{ $blog->image_url }}"
                                    alt="{{ $blog->title }}"
                                    width="600"
                                    height="375"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                >
                            @else
                                <span class="flex h-full w-full items-center justify-center text-4xl font-bold text-uf-border">UF</span>
                            @endif
                        </a>

                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-uf-accent">
                                @if ($blog->published_at)
                                    <time datetime="{{ $blog->published_at->toDateString() }}">
                                        {{ $blog->published_at->format('d M Y') }}
                                    </time>
                                @endif
                                @if ($blog->author)
                                    <span class="text-uf-muted">·</span>
                                    <span class="text-uf-muted normal-case tracking-normal">{{ $blog->author }}</span>
                                @endif
                            </div>

                            <h2 class="mt-2 line-clamp-2 text-lg font-semibold text-uf-text transition-colors group-hover:text-uf-accent">
                                <a href="{{ route('shop.blog.show', $blog->slug) }}">{{ $blog->title }}</a>
                            </h2>

                            @if ($blog->short_description)
                                <p class="mt-2 line-clamp-3 text-sm text-uf-muted">
                                    {{ $blog->short_description }}
                                </p>
                            @endif

                            <a
                                href="{{ route('shop.blog.show', $blog->slug) }}"
                                class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-uf-text transition-colors group-hover:text-uf-accent"
                            >
                                @lang('blog::app.shop.read-more')
                                <span class="icon-arrow-right text-base transition-transform group-hover:translate-x-0.5"></span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination (Prev / Next) --}}
            @if ($blogs->hasPages())
                <nav class="mt-12 flex items-center justify-center gap-3" aria-label="Pagination">
                    @if ($blogs->onFirstPage())
                        <span class="cursor-not-allowed rounded-full border border-uf-border px-5 py-2.5 text-sm text-uf-muted/50">
                            ← @lang('blog::app.shop.newer')
                        </span>
                    @else
                        <a
                            href="{{ $blogs->previousPageUrl() }}"
                            rel="prev"
                            class="rounded-full border border-uf-border px-5 py-2.5 text-sm font-semibold text-uf-text transition-colors hover:border-uf-accent hover:text-uf-accent"
                        >
                            ← @lang('blog::app.shop.newer')
                        </a>
                    @endif

                    <span class="text-sm text-uf-muted">
                        {{ $blogs->currentPage() }} / {{ $blogs->lastPage() }}
                    </span>

                    @if ($blogs->hasMorePages())
                        <a
                            href="{{ $blogs->nextPageUrl() }}"
                            rel="next"
                            class="rounded-full border border-uf-border px-5 py-2.5 text-sm font-semibold text-uf-text transition-colors hover:border-uf-accent hover:text-uf-accent"
                        >
                            @lang('blog::app.shop.older') →
                        </a>
                    @else
                        <span class="cursor-not-allowed rounded-full border border-uf-border px-5 py-2.5 text-sm text-uf-muted/50">
                            @lang('blog::app.shop.older') →
                        </span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</x-shop::layouts>
