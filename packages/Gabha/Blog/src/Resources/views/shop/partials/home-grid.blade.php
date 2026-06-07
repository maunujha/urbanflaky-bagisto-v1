@php
    /** @var \Illuminate\Support\Collection $blogs */
    $blogs = $blogs ?? collect();
@endphp

@if ($blogs->isNotEmpty())
    <section class="container max-md:px-4 my-14" aria-labelledby="home-blog-heading">
        <div class="mb-7 flex items-end justify-between gap-4">
            <div>
                <h2 id="home-blog-heading" class="text-3xl font-bold text-uf-text max-md:text-2xl">
                    @lang('blog::app.shop.home-section-title')
                </h2>

                <p class="mt-1.5 text-uf-muted max-md:text-sm">
                    @lang('blog::app.shop.home-section-subtitle')
                </p>
            </div>

            <a
                href="{{ route('shop.blog.index') }}"
                class="hidden shrink-0 items-center gap-1.5 rounded-full border border-uf-border px-5 py-2.5 text-sm font-semibold text-uf-text transition-colors hover:border-uf-accent hover:text-uf-accent sm:inline-flex"
            >
                @lang('blog::app.shop.view-all')
                <span class="icon-arrow-right text-lg"></span>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
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

                    <div class="flex flex-1 flex-col p-5">
                        @if ($blog->published_at)
                            <time datetime="{{ $blog->published_at->toDateString() }}" class="text-xs font-medium uppercase tracking-wide text-uf-accent">
                                {{ $blog->published_at->format('d M Y') }}
                            </time>
                        @endif

                        <h3 class="mt-2 line-clamp-2 font-semibold text-uf-text transition-colors group-hover:text-uf-accent">
                            <a href="{{ route('shop.blog.show', $blog->slug) }}">{{ $blog->title }}</a>
                        </h3>

                        @if ($blog->short_description)
                            <p class="mt-2 line-clamp-2 text-sm text-uf-muted">
                                {{ $blog->short_description }}
                            </p>
                        @endif

                        <a
                            href="{{ route('shop.blog.show', $blog->slug) }}"
                            class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-uf-text transition-colors group-hover:text-uf-accent"
                        >
                            @lang('blog::app.shop.read-more')
                            <span class="icon-arrow-right text-base transition-transform group-hover:translate-x-0.5"></span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8 text-center sm:hidden">
            <a
                href="{{ route('shop.blog.index') }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-uf-border px-6 py-3 text-sm font-semibold text-uf-text transition-colors hover:border-uf-accent hover:text-uf-accent"
            >
                @lang('blog::app.shop.view-all')
                <span class="icon-arrow-right text-lg"></span>
            </a>
        </div>
    </section>
@endif
