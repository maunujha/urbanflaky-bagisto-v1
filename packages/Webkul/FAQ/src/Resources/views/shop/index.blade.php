@php
    $faqSchema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [],
    ];

    foreach ($categories as $category) {
        foreach ($category->activeFaqs as $faq) {
            $faqSchema['mainEntity'][] = [
                '@type'          => 'Question',
                'name'           => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => trim(preg_replace('/\s+/', ' ', strip_tags($faq->answer))),
                ],
            ];
        }
    }
@endphp

@push('meta')
    <link rel="canonical" href="{{ url('faqs') }}" />

    @if (! empty($faqSchema['mainEntity']))
        <script type="application/ld+json">
            {!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

<x-shop::layouts
    :has-feature="false"
    :metaDescription="trans('faq::app.shop.meta-description')"
    :canonical="url('faqs')"
>
    <x-slot:title>
        @lang('faq::app.shop.title')
    </x-slot>

    <div class="container max-md:px-4 mt-6 mb-16">
        {{-- Heading --}}
        <h1 class="text-3xl font-bold text-uf-text max-md:text-2xl">
            @lang('faq::app.shop.heading')
        </h1>

        {{-- ───────────── Order Tracking Quick Access ───────────── --}}
        <div
            class="mt-6 flex flex-col gap-5 rounded-2xl border border-uf-accent/40 bg-uf-surface p-6 md:flex-row md:items-center md:justify-between md:p-8"
            style="background-image: radial-gradient(120% 120% at 0% 0%, rgba(199,235,49,0.08) 0%, rgba(20,20,20,0) 45%);"
        >
            <div class="max-w-xl">
                <span class="inline-block rounded-full bg-uf-accent/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-uf-accent">
                    @lang('faq::app.shop.top-queries')
                </span>

                <p class="mt-3 text-lg font-medium text-uf-text">
                    @lang('faq::app.shop.track-text')
                </p>
            </div>

            <a
                href="{{ route('shop.customers.account.orders.index') }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-uf-accent px-7 py-3 font-semibold text-black transition-colors hover:bg-uf-accentHover"
            >
                @lang('faq::app.shop.track-btn')
            </a>
        </div>

        {{-- ───────────── FAQ Search ───────────── --}}
        <div class="relative z-20 mt-8 lg:mx-auto lg:max-w-3xl" id="faq-search-wrap">
            <div class="flex items-center gap-3 rounded-full border border-uf-border bg-uf-surface2 px-5 py-3 transition-colors focus-within:border-uf-accent">
                <span class="icon-search shrink-0 text-2xl text-uf-muted"></span>

                <input
                    type="text"
                    id="faq-search-input"
                    autocomplete="off"
                    class="w-full border-0 bg-transparent text-uf-text placeholder:text-uf-muted focus:outline-none focus:ring-0"
                    placeholder="{{ trans('faq::app.shop.search-placeholder') }}"
                    aria-label="{{ trans('faq::app.shop.search-placeholder') }}"
                    aria-expanded="false"
                    aria-controls="faq-search-results"
                    role="combobox"
                />

                <span id="faq-search-spinner" class="hidden shrink-0">
                    <span class="block h-4 w-4 animate-spin rounded-full border-2 border-uf-muted border-t-uf-accent"></span>
                </span>
            </div>

            {{-- Results dropdown --}}
            <div
                id="faq-search-results"
                role="listbox"
                class="absolute left-0 right-0 top-full z-30 mt-2 hidden max-h-96 overflow-auto rounded-2xl border border-uf-border bg-uf-surface shadow-2xl"
            ></div>
        </div>

        {{-- ───────────── Categorized Accordion ───────────── --}}
        @if ($categories->isEmpty())
            <p class="mt-10 text-center text-uf-muted">
                @lang('faq::app.shop.no-faqs')
            </p>
        @else
            @php $globalIndex = 0; @endphp

            <div class="mt-12 lg:grid lg:grid-cols-[250px_minmax(0,1fr)] lg:gap-12">
                {{-- Left: category rail (horizontal scroll on mobile, sticky list on desktop) --}}
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <p class="mb-3 hidden text-xs font-semibold uppercase tracking-wider text-uf-muted lg:block">
                        @lang('faq::app.shop.categories')
                    </p>

                    <nav
                        class="uf-cat-rail -mx-4 flex gap-2 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:gap-1.5 lg:overflow-visible lg:px-0 lg:pb-0"
                        aria-label="@lang('faq::app.shop.categories')"
                    >
                        @foreach ($categories as $category)
                            <a
                                href="#cat-{{ $category->id }}"
                                data-cat-link="{{ $category->id }}"
                                class="uf-cat-link {{ $loop->first ? 'uf-cat-active' : '' }} flex shrink-0 items-center justify-between gap-3 whitespace-nowrap rounded-full border border-uf-border bg-uf-surface px-4 py-2 text-sm font-medium text-uf-muted transition-colors hover:border-uf-accent/50 hover:text-uf-text lg:rounded-lg lg:py-2.5"
                            >
                                <span>{{ $category->name }}</span>

                                <span class="uf-cat-count rounded-full bg-uf-surface2 px-2 py-0.5 text-xs font-semibold text-uf-muted">
                                    {{ $category->activeFaqs->count() }}
                                </span>
                            </a>
                        @endforeach
                    </nav>
                </aside>

                {{-- Right: FAQ sections --}}
                <div class="mt-8 flex flex-col gap-12 lg:mt-0">
                    @foreach ($categories as $category)
                        <section
                            id="cat-{{ $category->id }}"
                            data-cat-section="{{ $category->id }}"
                            class="scroll-mt-28"
                            aria-labelledby="faq-cat-{{ $category->id }}"
                        >
                            {{-- Premium section header --}}
                            <div class="mb-5 flex items-center gap-3 border-b border-uf-border pb-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-uf-accent/15 text-sm font-bold text-uf-accent">
                                    {{ sprintf('%02d', $loop->iteration) }}
                                </span>

                                <h2 id="faq-cat-{{ $category->id }}" class="text-xl font-bold text-uf-text max-md:text-lg">
                                    {{ $category->name }}
                                </h2>

                                <span class="ml-auto rounded-full bg-uf-surface2 px-2.5 py-1 text-xs font-medium text-uf-muted">
                                    {{ trans_choice('faq::app.shop.question-count', $category->activeFaqs->count(), ['count' => $category->activeFaqs->count()]) }}
                                </span>
                            </div>

                            <div class="flex flex-col gap-3">
                                @foreach ($category->activeFaqs as $faq)
                                    @php $isFirst = $globalIndex === 0; $globalIndex++; @endphp

                                    <div
                                        id="faq-{{ $faq->id }}"
                                        class="uf-faq-item scroll-mt-28 rounded-xl border border-uf-border bg-uf-surface transition-all hover:border-uf-accent/40"
                                    >
                                        <h3 class="m-0">
                                            <button
                                                type="button"
                                                class="uf-faq-toggle flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                                                aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                                aria-controls="faq-panel-{{ $faq->id }}"
                                            >
                                                <span class="font-medium text-uf-text">{{ $faq->question }}</span>

                                                <span class="uf-faq-chevron icon-arrow-down shrink-0 text-2xl text-uf-accent transition-transform"></span>
                                            </button>
                                        </h3>

                                        <div
                                            id="faq-panel-{{ $faq->id }}"
                                            role="region"
                                            aria-labelledby="faq-{{ $faq->id }}"
                                            class="uf-faq-panel {{ $isFirst ? 'is-open' : '' }}"
                                        >
                                            <div class="min-h-0 overflow-hidden">
                                                <div class="uf-rte border-t border-uf-border/60 px-5 py-4 text-uf-muted">
                                                    {!! $faq->answer !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            /* Smooth auto-height accordion via grid-rows (no JS height measuring) */
            .uf-faq-panel {
                display: grid;
                grid-template-rows: 0fr;
                transition: grid-template-rows .28s ease;
            }
            .uf-faq-panel > * {
                min-height: 0;
                overflow: hidden;
            }
            .uf-faq-panel.is-open {
                grid-template-rows: 1fr;
            }
            .uf-faq-toggle[aria-expanded="true"] .uf-faq-chevron {
                transform: rotate(180deg);
            }
            .uf-faq-item.uf-faq-highlight {
                box-shadow: 0 0 0 2px #c7eb31;
                border-color: #c7eb31;
            }

            /* Active category in the rail */
            .uf-cat-link.uf-cat-active {
                background-color: rgba(199, 235, 49, 0.12);
                border-color: rgba(199, 235, 49, 0.5);
                color: #f5f5f5;
            }
            .uf-cat-link.uf-cat-active .uf-cat-count {
                background-color: #c7eb31;
                color: #0a0a0a;
            }

            /* Thin scrollbar for the mobile category rail */
            .uf-cat-rail::-webkit-scrollbar { height: 0; }
            .uf-cat-rail { scrollbar-width: none; }
        </style>
    @endpush

    @pushOnce('scripts')
        <script>
            /*
             | The FAQ content lives inside #app (the Vue root). When Vue mounts it
             | re-renders that subtree and REPLACES the DOM nodes, so any element
             | reference captured here at parse-time becomes detached. Everything
             | below therefore uses event delegation on `document` and re-queries
             | elements by id at call-time so it keeps working after Vue mounts.
             */
            (function () {
                var MIN_LENGTH  = 3;
                var DEBOUNCE_MS = 300;
                var SEARCH_URL  = "{{ route('shop.api.faqs.search') }}";
                var NO_RESULTS  = @json(trans('faq::app.shop.no-results'));

                var timer       = null;
                var activeXHR   = null;
                var highlightTO = null;
                var spyTimer    = null;

                function el(id) {
                    return document.getElementById(id);
                }

                function escapeHtml(str) {
                    return (str || '').replace(/[&<>"']/g, function (c) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                    });
                }

                function hideResults() {
                    var results = el('faq-search-results');
                    var input   = el('faq-search-input');
                    if (results) {
                        results.classList.add('hidden');
                        results.innerHTML = '';
                    }
                    if (input) {
                        input.setAttribute('aria-expanded', 'false');
                    }
                }

                function showSpinner(show) {
                    var spinner = el('faq-search-spinner');
                    if (spinner) {
                        spinner.classList.toggle('hidden', ! show);
                    }
                }

                function openResults(html) {
                    var results = el('faq-search-results');
                    var input   = el('faq-search-input');
                    if (! results) {
                        return;
                    }
                    results.innerHTML = html;
                    results.classList.remove('hidden');
                    if (input) {
                        input.setAttribute('aria-expanded', 'true');
                    }
                }

                function renderResults(items) {
                    if (! items.length) {
                        openResults('<div class="px-5 py-4 text-sm text-uf-muted">' + escapeHtml(NO_RESULTS) + '</div>');
                        return;
                    }

                    var html = items.map(function (item) {
                        return ''
                            + '<button type="button" role="option" data-faq-id="' + item.id + '" '
                            + 'class="block w-full border-b border-uf-border px-5 py-3 text-left last:border-0 hover:bg-uf-surface2">'
                            + '<div class="text-xs font-semibold uppercase tracking-wide text-uf-accent">' + escapeHtml(item.category) + '</div>'
                            + '<div class="mt-0.5 font-medium text-uf-text">' + escapeHtml(item.question) + '</div>'
                            + (item.snippet ? '<div class="mt-0.5 line-clamp-1 text-sm text-uf-muted">' + escapeHtml(item.snippet) + '</div>' : '')
                            + '</button>';
                    }).join('');

                    openResults(html);
                }

                function runSearch(query) {
                    showSpinner(true);

                    if (activeXHR) {
                        activeXHR.abort();
                    }
                    activeXHR = new AbortController();

                    fetch(SEARCH_URL + '?query=' + encodeURIComponent(query), {
                        headers: { 'Accept': 'application/json' },
                        signal: activeXHR.signal,
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            showSpinner(false);
                            renderResults(data.results || []);
                        })
                        .catch(function (err) {
                            if (err.name === 'AbortError') {
                                return;
                            }
                            showSpinner(false);
                            hideResults();
                        });
                }

                function openFaq(id) {
                    var item = el('faq-' + id);
                    if (! item) {
                        return;
                    }

                    var panel  = item.querySelector('.uf-faq-panel');
                    var toggle = item.querySelector('.uf-faq-toggle');
                    if (panel && toggle) {
                        panel.classList.add('is-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    }

                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    item.classList.add('uf-faq-highlight');
                    if (highlightTO) {
                        clearTimeout(highlightTO);
                    }
                    highlightTO = setTimeout(function () {
                        item.classList.remove('uf-faq-highlight');
                    }, 2200);
                }

                function setActiveCategory(id) {
                    var links = document.querySelectorAll('[data-cat-link]');
                    for (var i = 0; i < links.length; i++) {
                        links[i].classList.toggle('uf-cat-active', links[i].getAttribute('data-cat-link') === String(id));
                    }
                }

                function updateActiveFromScroll() {
                    var sections = document.querySelectorAll('[data-cat-section]');
                    var current  = null;
                    for (var i = 0; i < sections.length; i++) {
                        if (sections[i].getBoundingClientRect().top <= 160) {
                            current = sections[i].getAttribute('data-cat-section');
                        }
                    }
                    if (current !== null) {
                        setActiveCategory(current);
                    }
                }

                /* ---- Accordion toggle (delegated) ---- */
                document.addEventListener('click', function (e) {
                    var toggle = e.target.closest('.uf-faq-toggle');
                    if (! toggle) {
                        return;
                    }
                    var expanded = toggle.getAttribute('aria-expanded') === 'true';
                    var item     = toggle.closest('.uf-faq-item');
                    var panel    = item ? item.querySelector('.uf-faq-panel') : null;

                    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    if (panel) {
                        panel.classList.toggle('is-open', ! expanded);
                    }
                });

                /* ---- Search typing (delegated, debounced) ---- */
                document.addEventListener('input', function (e) {
                    if (! e.target || e.target.id !== 'faq-search-input') {
                        return;
                    }
                    var query = e.target.value.trim();

                    if (timer) {
                        clearTimeout(timer);
                    }

                    if (query.length < MIN_LENGTH) {
                        showSpinner(false);
                        hideResults();
                        return;
                    }

                    timer = setTimeout(function () {
                        runSearch(query);
                    }, DEBOUNCE_MS);
                });

                /* ---- Clicks: result selection + category nav (delegated) ---- */
                document.addEventListener('click', function (e) {
                    var option = e.target.closest('[data-faq-id]');
                    if (option && option.closest('#faq-search-results')) {
                        hideResults();
                        var input = el('faq-search-input');
                        if (input) {
                            input.blur();
                        }
                        openFaq(option.getAttribute('data-faq-id'));
                        return;
                    }

                    var catLink = e.target.closest('[data-cat-link]');
                    if (catLink) {
                        e.preventDefault();
                        var id  = catLink.getAttribute('data-cat-link');
                        var sec = el('cat-' + id);
                        if (sec) {
                            sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                        setActiveCategory(id);
                        return;
                    }

                    /* Close dropdown when clicking outside the search box */
                    var wrap = el('faq-search-wrap');
                    if (wrap && ! wrap.contains(e.target)) {
                        hideResults();
                    }
                });

                /* ---- Escape closes the dropdown ---- */
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && e.target && e.target.id === 'faq-search-input') {
                        hideResults();
                    }
                });

                /* ---- Scroll-spy: highlight the category currently in view ---- */
                window.addEventListener('scroll', function () {
                    if (spyTimer) {
                        return;
                    }
                    spyTimer = setTimeout(function () {
                        spyTimer = null;
                        updateActiveFromScroll();
                    }, 120);
                }, { passive: true });
            })();
        </script>
    @endPushOnce
</x-shop::layouts>
