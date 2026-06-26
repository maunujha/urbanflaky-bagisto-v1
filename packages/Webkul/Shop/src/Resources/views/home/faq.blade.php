{{--
    Homepage FAQ section — sits just before the footer.
    The $homeFaqs array drives BOTH the visible accordion and the FAQPage
    JSON-LD (single source). Follows the site FAQ page accordion pattern:
    Tailwind-first + small grid-rows style block + delegated vanilla JS.
    Edit copy in the array below.
--}}
@php
    $homeFaqs = [
        [
            'q' => 'What is dark aesthetic fashion?',
            'a' => 'Dark aesthetic fashion is a style built around deep blacks, charcoal and muted, monochrome tones — moody, minimal and quietly confident. At Urbanflaky it means clean silhouettes, no loud branding, and pieces designed to layer into an all-black wardrobe that works from day to night.',
        ],
        [
            'q' => 'Are Urbanflaky oversized t-shirts true to size?',
            'a' => 'Our oversized t-shirts are cut with an intentionally relaxed, drop-shoulder fit, so they sit roomier than a regular tee. For the classic boxy streetwear look, take your usual size; for a closer fit, size down. Every product page lists exact chest and length measurements in the size chart.',
        ],
        [
            'q' => 'Which cotton do you use?',
            'a' => 'We use heavyweight, high-GSM cotton chosen for structure and longevity. The dense knit feels substantial, holds its shape wash after wash, and avoids the thin, see-through feel of fast-fashion tees — giving every piece a premium drape.',
        ],
        [
            'q' => 'How should I wash oversized tees?',
            'a' => 'Machine wash cold and inside-out, with similar dark colours and a mild detergent — no bleach. Tumble dry low or line dry, and iron inside-out on medium heat. Gentle care keeps heavyweight cotton structured and the colour deep for years.',
        ],
        [
            'q' => 'Do you ship across India?',
            'a' => 'Yes — we ship pan-India, covering metros and smaller cities alike. Orders are dispatched quickly with tracking, so you can follow your parcel all the way from dispatch to delivery.',
        ],
        [
            'q' => 'What sizes are available?',
            'a' => 'Our oversized range runs from S to XXL across men’s and women’s styles. Exact garment measurements for each size are listed on every product page, so you can choose the fit that’s right for you.',
        ],
    ];
@endphp

@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => collect($homeFaqs)->map(fn ($faq) => [
        '@type'          => 'Question',
        'name'           => $faq['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $faq['a'],
        ],
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

<section aria-labelledby="home-faq-heading">
    <div class="container max-md:px-4">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)] lg:gap-16">
            {{-- Left: heading + view-all CTA --}}
            <div class="lg:sticky lg:top-24">
                <h2 id="home-faq-heading" class="font-poppins text-2xl font-extrabold leading-tight tracking-tight text-uf-text md:text-4xl" style="text-wrap: balance;">
                    Frequently asked questions
                </h2>

                <p class="mt-4 max-w-sm leading-relaxed text-uf-muted">
                    Fit, fabric, care and delivery — the essentials before you buy into the dark aesthetic.
                </p>

                <a
                    href="{{ route('shop.faqs.index') }}"
                    class="mt-7 inline-flex items-center gap-2 rounded-[2px] border border-white/25 px-6 py-3.5 font-poppins text-[11px] font-semibold uppercase tracking-[2px] text-uf-text transition-colors duration-200 hover:border-uf-accent hover:bg-uf-accent hover:text-uf-bg"
                >
                    View all FAQs
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>

            {{-- Right: accordion (independent toggles) --}}
            <div class="border-t border-uf-border">
                @foreach ($homeFaqs as $i => $faq)
                    <div class="uf-hfaq-item border-b border-uf-border">
                        <h3 class="m-0">
                            <button
                                type="button"
                                class="uf-hfaq-toggle flex w-full items-center justify-between gap-5 py-5 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-uf-accent"
                                aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                aria-controls="home-faq-panel-{{ $i }}"
                                id="home-faq-tab-{{ $i }}"
                            >
                                <span class="font-poppins text-base font-medium text-uf-text md:text-lg">{{ $faq['q'] }}</span>
                                <svg class="uf-hfaq-chevron h-5 w-5 shrink-0 text-uf-accent transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                        </h3>

                        <div
                            id="home-faq-panel-{{ $i }}"
                            class="uf-hfaq-panel {{ $i === 0 ? 'is-open' : '' }}"
                            role="region"
                            aria-labelledby="home-faq-tab-{{ $i }}"
                        >
                            <div>
                                <p class="m-0 max-w-[68ch] pb-5 text-sm leading-relaxed text-uf-muted md:text-[0.95rem]">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
    /* Smooth auto-height accordion (grid-rows technique — same as the FAQ page) */
    .uf-hfaq-panel { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s cubic-bezier(0.22, 1, 0.36, 1); }
    .uf-hfaq-panel > div { min-height: 0; overflow: hidden; }
    .uf-hfaq-panel.is-open { grid-template-rows: 1fr; }
    .uf-hfaq-toggle[aria-expanded="true"] .uf-hfaq-chevron { transform: rotate(180deg); }
    @media (prefers-reduced-motion: reduce) { .uf-hfaq-panel { transition: none; } }
</style>
@endpush

@pushOnce('scripts')
<script>
    /* Homepage FAQ accordion — independent toggles. Delegated on document so it
       survives Vue's mount/replace inside #app (same approach as the FAQ page). */
    (function () {
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('.uf-hfaq-toggle');
            if (! toggle) return;

            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            var item     = toggle.closest('.uf-hfaq-item');
            var panel    = item ? item.querySelector('.uf-hfaq-panel') : null;

            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (panel) panel.classList.toggle('is-open', ! expanded);
        });
    })();
</script>
@endPushOnce
