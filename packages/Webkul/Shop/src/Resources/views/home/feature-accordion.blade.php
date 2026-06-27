{{--
    Homepage "Brand Story" feature accordion (SEO content section).
    Left: synced accordion · Right: image stage that swaps per item.

    ── How to edit ──────────────────────────────────────────────
    • Copy / headings / SEO body: edit the $ufFeatureItems array below.
    • Right-side image: drop files in  public/images/feature-accordion/
      then set each item's 'image' to  asset('images/feature-accordion/<file>')
      (or a full URL). Leave 'image' empty to show the placeholder panel.
      Recommended: portrait ~1000x1250 (4:5), optimised JPG/WebP.
    • Static styling: Tailwind utilities inline (uf-* tokens).
    • Animation / active-state hooks: assets/css/feature-accordion.css
    ─────────────────────────────────────────────────────────────
--}}
@php
    $ufFeatureItems = [
        [
            'title' => "Urbanflaky's brand story",
            'image' => asset('images/feature-accordion/brand-story.webp'),
            'alt'   => 'The Urbanflaky streetwear brand story',
            'body'  => "Urbanflaky is a homegrown streetwear label by Gabha Enterprise, crafted in Dholpur, Rajasthan for a new generation of Indian dressers. We started with one belief — premium, dark-aesthetic fashion shouldn't cost a fortune. Every drop pairs bold streetwear attitude with everyday wearability, delivered pan-India.",
        ],
        [
            'title' => 'Dark aesthetic fashion',
            'image' => asset('images/feature-accordion/dark-aesthetic.webp'),
            'alt'   => 'Dark aesthetic fashion by Urbanflaky',
            'body'  => "Our collections live in deep blacks, charcoal greys and muted tones — a dark aesthetic built for people who like their wardrobe moody and minimal. These versatile, low-key pieces layer effortlessly and stay in rotation from day to night, season after season.",
        ],
        [
            'title' => 'Premium oversized t-shirts',
            'image' => asset('images/feature-accordion/oversized.webp'),
            'alt'   => 'Premium oversized t-shirt by Urbanflaky',
            'body'  => "Urbanflaky oversized t-shirts feature a relaxed drop-shoulder cut and a boxy, street-ready silhouette. Designed to fall just right on both men and women, our premium oversized tees give you that effortless, broad-shouldered streetwear look — roomy without ever feeling shapeless.",
        ],
        [
            'title' => 'Heavyweight cotton',
            'image' => asset('images/feature-accordion/heavyweight-cotton.webp'),
            'alt'   => 'Heavyweight cotton fabric, close up',
            'body'  => "We use heavyweight, high-GSM cotton that feels substantial the moment you pull it on. The dense knit holds its shape wash after wash, resists thin see-through fabric and gives every garment a structured, premium drape that lightweight fast-fashion tees simply can't match.",
        ],
        [
            'title' => 'Monochrome streetwear',
            'image' => '',
            'alt'   => 'Monochrome streetwear outfit by Urbanflaky',
            'body'  => "Monochrome is our signature. A tight black, white and grey palette makes mixing and matching effortless and keeps every fit looking intentional. Urbanflaky monochrome streetwear is the easiest way to build a cohesive, head-turning wardrobe from just a handful of pieces.",
        ],
        [
            'title' => 'Minimal fashion',
            'image' => '',
            'alt'   => 'Minimal fashion essentials by Urbanflaky',
            'body'  => "Clean lines, no loud logos, no clutter. Our minimal fashion philosophy strips each piece back to fit, fabric and form — essentials engineered to be worn on repeat and styled a hundred different ways. Less noise, more wear.",
        ],
        [
            'title' => 'Why choose Urbanflaky',
            'image' => asset('images/feature-accordion/why-urbanflaky.webp'),
            'alt'   => 'Why choose Urbanflaky streetwear',
            'body'  => "With prices from just ₹299–799, fast pan-India delivery, secure checkout and easy returns, Urbanflaky makes premium streetwear genuinely accessible. Thousands of shoppers choose us for honest pricing, dependable quality and a dark, distinctive aesthetic you won't find on generic marketplaces.",
        ],
    ];
@endphp

@push('styles')
    @bagistoVite(['src/Resources/assets/css/feature-accordion.css'])
@endpush

<section class="uf-fa bg-uf-bg py-12 md:py-20 lg:py-24" aria-labelledby="uf-fa-heading">
    <div class="container max-md:px-4">
        <header class="mb-8 max-w-2xl md:mb-12">
            <h2 id="uf-fa-heading" class="uf-fa-title font-poppins text-2xl font-extrabold leading-[1.05] tracking-tight text-uf-text md:text-4xl">
                Heavyweight, <span class="text-uf-accent">dark-aesthetic</span> streetwear
            </h2>
            <p class="uf-fa-intro mt-4 max-w-[60ch] leading-relaxed text-uf-muted">
                Get to know Urbanflaky — the premium oversized t-shirts, heavyweight cotton and
                monochrome streetwear that define the label. Tap through to see what makes every
                piece worth wearing on repeat.
            </p>
        </header>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-14">
            {{-- Left: accordion --}}
            <div class="border-t border-uf-border" role="list">
                @foreach ($ufFeatureItems as $i => $item)
                    <div
                        class="uf-fa-item relative border-b border-uf-border {{ $i === 0 ? 'is-active' : '' }}"
                        data-fa-item="{{ $i }}"
                        role="listitem"
                    >
                        <h3 class="m-0">
                            <button
                                type="button"
                                class="uf-fa-toggle grid w-full grid-cols-[auto_1fr_auto] items-center gap-4 py-4 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-uf-accent md:py-5"
                                aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                aria-controls="uf-fa-panel-{{ $i }}"
                                id="uf-fa-tab-{{ $i }}"
                            >
                                <span class="uf-fa-index font-poppins text-xs font-semibold tabular-nums tracking-wider text-uf-muted">{{ sprintf('%02d', $i + 1) }}</span>
                                <span class="uf-fa-q font-poppins text-base font-semibold tracking-tight text-uf-muted md:text-xl">{{ $item['title'] }}</span>
                                <svg class="uf-fa-chevron h-5 w-5 shrink-0 text-uf-muted transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </h3>

                        <div
                            id="uf-fa-panel-{{ $i }}"
                            class="uf-fa-panel"
                            role="region"
                            aria-labelledby="uf-fa-tab-{{ $i }}"
                        >
                            <div>
                                {{-- Mobile: the image lives inside the open panel, next to its content --}}
                                <div class="mb-4 mt-1 aspect-[4/3] w-full overflow-hidden rounded-xl border border-uf-border bg-uf-surface lg:hidden">
                                    @include('shop::home.feature-accordion-media', ['item' => $item, 'i' => $i])
                                </div>

                                <p class="uf-fa-body m-0 max-w-[56ch] pb-5 text-sm leading-relaxed text-uf-muted">{{ $item['body'] }}</p>
                            </div>
                        </div>

                        <span class="pointer-events-none absolute -bottom-px left-0 h-0.5 w-full overflow-hidden" aria-hidden="true">
                            <span class="uf-fa-bar block h-full w-full bg-uf-accent shadow-[0_0_10px_rgba(199,235,49,0.55)]"></span>
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Right: sticky image stage — desktop only (crossfades to the active item).
                 On mobile the image lives inside each open panel instead. --}}
            <div class="relative hidden aspect-[4/5] w-full overflow-hidden rounded-2xl border border-uf-border bg-uf-surface lg:sticky lg:top-24 lg:block">
                @foreach ($ufFeatureItems as $i => $item)
                    <figure
                        class="uf-fa-media absolute inset-0 m-0 {{ $i === 0 ? 'is-active' : '' }}"
                        data-fa-media="{{ $i }}"
                        aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
                    >
                        @include('shop::home.feature-accordion-media', ['item' => $item, 'i' => $i])
                    </figure>
                @endforeach
            </div>
        </div>
    </div>
</section>

@pushOnce('scripts')
    <script>
        /*
         | Feature accordion: click to open + auto-advance with a neon progress
         | bar. The section is server-rendered inside #app (the Vue root), which
         | re-renders/replaces these nodes when Vue mounts — so, exactly like the
         | FAQ page, everything is delegated on `document` and re-queried at
         | call-time. Auto-advance is driven purely by the CSS progress
         | animation: when the active item's bar finishes, we advance. Under
         | prefers-reduced-motion the bar is disabled, so it stays manual.
         */
        (function () {
            function activate(root, index) {
                var items  = root.querySelectorAll('[data-fa-item]');
                var medias = root.querySelectorAll('[data-fa-media]');

                items.forEach(function (it) {
                    var on  = +it.getAttribute('data-fa-item') === index;
                    var btn = it.querySelector('.uf-fa-toggle');
                    it.classList.toggle('is-active', on);
                    if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
                });

                medias.forEach(function (m) {
                    var on = +m.getAttribute('data-fa-media') === index;
                    m.classList.toggle('is-active', on);
                    m.setAttribute('aria-hidden', on ? 'false' : 'true');
                });

                root.setAttribute('data-fa-active', index);
            }

            function advance(root) {
                var count = root.querySelectorAll('[data-fa-item]').length;
                if (! count) return;
                var current = parseInt(root.getAttribute('data-fa-active') || '0', 10);
                activate(root, (current + 1) % count);
            }

            /* Open the clicked item (single-always-open; clicking active is a no-op). */
            document.addEventListener('click', function (e) {
                var toggle = e.target.closest('.uf-fa-toggle');
                if (! toggle) return;

                var root = toggle.closest('.uf-fa');
                var item = toggle.closest('[data-fa-item]');
                if (! root || ! item) return;

                activate(root, parseInt(item.getAttribute('data-fa-item'), 10));
            });

            /* Up/Down arrows move between items when focus is on a toggle. */
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
                var toggle = e.target.closest('.uf-fa-toggle');
                if (! toggle) return;

                var root  = toggle.closest('.uf-fa');
                var items = root ? root.querySelectorAll('[data-fa-item]') : [];
                if (! items.length) return;

                e.preventDefault();
                var current = parseInt(root.getAttribute('data-fa-active') || '0', 10);
                var count   = items.length;
                var next    = e.key === 'ArrowDown'
                    ? (current + 1) % count
                    : (current - 1 + count) % count;

                activate(root, next);
                var nextBtn = items[next].querySelector('.uf-fa-toggle');
                if (nextBtn) nextBtn.focus();
            });

            /* Auto-advance: the active item's progress bar finishing = move on. */
            document.addEventListener('animationend', function (e) {
                if (! e.target.classList || ! e.target.classList.contains('uf-fa-bar')) return;
                var root = e.target.closest('.uf-fa');
                if (root) advance(root);
            });
        })();
    </script>
@endPushOnce
