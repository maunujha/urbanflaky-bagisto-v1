@php
    $metaTitle       = $blog->seo_title;
    $metaDescription = $blog->seo_description;
    $canonical       = $blog->url;
    $ogImage         = $blog->image_url ? url($blog->image_url) : asset('images/og-image.jpg');
    $authorName      = $blog->author ?: 'Urbanflaky';

    /* Article structured data (PHP-built to avoid @@context escaping in Blade). */
    $articleSchema = array_filter([
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => $blog->title,
        'description'      => $metaDescription,
        'image'            => [$ogImage],
        'datePublished'    => optional($blog->published_at)->toAtomString(),
        'dateModified'     => optional($blog->updated_at)->toAtomString(),
        'author'           => [
            '@type' => 'Person',
            'name'  => $authorName,
        ],
        'publisher'        => [
            '@type' => 'Organization',
            'name'  => 'Urbanflaky',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => asset('images/logo.png'),
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => $canonical,
        ],
    ]);

    /* Breadcrumb structured data. */
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
                'item'     => route('shop.blog.index'),
            ], [
                '@type'    => 'ListItem',
                'position' => 3,
                'name'     => $blog->title,
                'item'     => $canonical,
            ],
        ],
    ];
@endphp

@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    @if ($blog->meta_keywords)
        <meta name="keywords" content="{{ $blog->meta_keywords }}">
    @endif
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Open Graph (article) --}}
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    @if ($blog->published_at)
        <meta property="article:published_time" content="{{ $blog->published_at->toAtomString() }}">
    @endif
    @if ($blog->updated_at)
        <meta property="article:modified_time" content="{{ $blog->updated_at->toAtomString() }}">
    @endif
    <meta property="article:author" content="{{ $authorName }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
@endpush

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

<x-shop::layouts :has-custom-seo="true">
    <x-slot:title>
        {{ $metaTitle }}
    </x-slot>

    <article class="container max-md:px-4 mt-8 mb-16">
        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm text-uf-muted" aria-label="Breadcrumb">
            <a href="{{ route('shop.home.index') }}" class="hover:text-uf-text">@lang('blog::app.shop.breadcrumb-home')</a>
            <span class="px-1.5">/</span>
            <a href="{{ route('shop.blog.index') }}" class="hover:text-uf-text">@lang('blog::app.shop.breadcrumb-blog')</a>
            <span class="px-1.5">/</span>
            <span class="text-uf-text">{{ \Illuminate\Support\Str::limit($blog->title, 50) }}</span>
        </nav>

        <div class="mx-auto max-w-3xl">
            {{-- Header --}}
            <header class="mb-8">
                <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-wide text-uf-accent">
                    @if ($blog->published_at)
                        <time datetime="{{ $blog->published_at->toDateString() }}">
                            {{ $blog->published_at->format('d M Y') }}
                        </time>
                    @endif
                    <span class="text-uf-muted">·</span>
                    <span class="text-uf-muted normal-case tracking-normal">@lang('blog::app.shop.by-author', ['author' => $authorName])</span>
                </div>

                <h1 class="mt-3 text-4xl font-bold leading-tight text-uf-text max-md:text-3xl">
                    {{ $blog->title }}
                </h1>

                @if ($blog->short_description)
                    <p class="mt-4 text-lg text-uf-muted">
                        {{ $blog->short_description }}
                    </p>
                @endif
            </header>

            {{-- Featured image --}}
            @if ($blog->image_url)
                <figure class="mb-8 overflow-hidden rounded-2xl border border-uf-border bg-uf-surface2">
                    <img
                        src="{{ $blog->image_url }}"
                        alt="{{ $blog->title }}"
                        width="1200"
                        height="630"
                        class="h-auto w-full object-cover"
                    >
                </figure>
            @endif

            {{-- Content (RTE wrapper restores typography on the dark theme) --}}
            <div class="uf-rte text-uf-muted">
                {!! webp_picture_html($blog->content) !!}
            </div>

            {{-- Back to blog --}}
            <div class="mt-12 border-t border-uf-border pt-8">
                <a
                    href="{{ route('shop.blog.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-uf-text transition-colors hover:text-uf-accent"
                >
                    <span class="icon-arrow-left text-lg"></span>
                    @lang('blog::app.shop.back-to-blog')
                </a>
            </div>
        </div>

        {{-- Recent posts --}}
        @if ($recentBlogs->isNotEmpty())
            <section class="mx-auto mt-16 max-w-5xl border-t border-uf-border pt-12" aria-labelledby="recent-posts-heading">
                <h2 id="recent-posts-heading" class="mb-7 text-2xl font-bold text-uf-text">
                    @lang('blog::app.shop.recent-posts')
                </h2>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    @foreach ($recentBlogs as $recent)
                        <article class="group flex flex-col overflow-hidden rounded-2xl border border-uf-border bg-uf-surface transition-colors hover:border-uf-accent/50">
                            <a
                                href="{{ route('shop.blog.show', $recent->slug) }}"
                                class="block aspect-[16/10] overflow-hidden bg-uf-surface2"
                                aria-label="{{ $recent->title }}"
                            >
                                @if ($recent->image_url)
                                    <img
                                        src="{{ $recent->image_url }}"
                                        alt="{{ $recent->title }}"
                                        width="400"
                                        height="250"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    >
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-3xl font-bold text-uf-border">UF</span>
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col p-5">
                                @if ($recent->published_at)
                                    <time datetime="{{ $recent->published_at->toDateString() }}" class="text-xs font-medium uppercase tracking-wide text-uf-accent">
                                        {{ $recent->published_at->format('d M Y') }}
                                    </time>
                                @endif

                                <h3 class="mt-2 line-clamp-2 font-semibold text-uf-text transition-colors group-hover:text-uf-accent">
                                    <a href="{{ route('shop.blog.show', $recent->slug) }}">{{ $recent->title }}</a>
                                </h3>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-shop::layouts>
