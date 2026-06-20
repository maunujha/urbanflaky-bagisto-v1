@props(['options' => []])

@php
    /*
     | Resolve the SEO-friendly destination (and optional price) server-side so the
     | banner ships a real <a href> — good for crawlers and keyboard users alike.
     */
    $vbLink  = null;
    $vbPrice = null;

    $linkType = $options['link_type'] ?? null;
    $linkId   = $options['link_id'] ?? null;

    if ($linkType === 'product' && $linkId) {
        $vbProduct = app(\Webkul\Product\Repositories\ProductRepository::class)->find($linkId);

        if ($vbProduct) {
            $vbLink = route('shop.product_or_category.index', $vbProduct->url_key);

            if (! empty($options['show_price'])) {
                $vbPrice = core()->formatPrice($vbProduct->getTypeInstance()->getMinimalPrice());
            }
        }
    } elseif ($linkType === 'category' && $linkId) {
        $vbCategory = app(\Webkul\Category\Repositories\CategoryRepository::class)->find($linkId);

        $vbLink = $vbCategory?->url;
    }

    $vbConfig = [
        'desktop' => ! empty($options['video']) ? asset($options['video']) : '',
        'mobile'  => ! empty($options['mobile_video']) ? asset($options['mobile_video']) : '',
        'poster'  => ! empty($options['poster']) ? asset($options['poster']) : '',
        'logo'    => ! empty($options['logo']) ? asset($options['logo']) : '',
        'title'   => $options['title'] ?? '',
        'desc'    => $options['description'] ?? '',
        'price'   => $vbPrice,
        'link'    => $vbLink,
    ];
@endphp

{{-- Preload poster so it paints instantly and reserves space (no layout shift). --}}
@if (! empty($vbConfig['poster']))
    @push('meta')
        <link rel="preload" as="image" href="{{ $vbConfig['poster'] }}" fetchpriority="high">
    @endpush
@endif

{{-- Fallback reserves the banner height (and paints the poster) before Vue
     mounts on window.load, so there is no layout shift. --}}
<v-video-banner :config="{{ json_encode($vbConfig) }}">
    <section class="uf-vbanner">
        @if (! empty($vbConfig['poster']))
            <img class="uf-vbanner-poster" src="{{ $vbConfig['poster'] }}" alt="" aria-hidden="true" fetchpriority="high" />
        @endif
    </section>
</v-video-banner>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-video-banner-template"
    >
        <section
            class="uf-vbanner"
            :class="{ 'uf-vbanner-clickable': config.link }"
            @click="visit"
            aria-label="@lang('shop::app.components.video-banner.label')"
        >
            <!-- Poster: shown until the video is actually playing / when scrolled away -->
            <img
                v-if="config.poster"
                class="uf-vbanner-poster"
                :class="{ 'uf-vbanner-hidden': isPlaying }"
                :src="config.poster"
                alt=""
                aria-hidden="true"
                fetchpriority="high"
            />

            <!-- Video (source injected only once the banner scrolls into view) -->
            <video
                ref="video"
                class="uf-vbanner-video"
                muted
                loop
                playsinline
                webkit-playsinline
                preload="none"
                :poster="config.poster || null"
            >
                <template v-if="loaded">
                    <source v-if="config.mobile" :src="config.mobile" media="(max-width: 767px)" />
                    <source v-if="config.desktop" :src="config.desktop" />
                </template>
            </video>

            <!-- Legibility scrim -->
            <div class="uf-vbanner-scrim" aria-hidden="true"></div>

            <!-- Overlay content -->
            <div class="uf-vbanner-overlay">
                <img
                    v-if="config.logo"
                    class="uf-vbanner-logo"
                    :src="config.logo"
                    alt="@lang('shop::app.components.video-banner.logo-alt')"
                />

                <h2 v-if="config.title" class="uf-vbanner-title">@{{ config.title }}</h2>

                <p v-if="config.desc" class="uf-vbanner-desc">@{{ config.desc }}</p>

                <p v-if="config.price" class="uf-vbanner-price">@{{ config.price }}</p>

                <a
                    v-if="config.link"
                    :href="config.link"
                    class="uf-vbanner-cta"
                    @click.stop
                >
                    @lang('shop::app.components.video-banner.shop-now')
                </a>
            </div>

            <!-- Sound control -->
            <button
                v-if="loaded"
                type="button"
                class="uf-vbanner-sound"
                :aria-label="isMuted
                    ? '@lang('shop::app.components.video-banner.unmute')'
                    : '@lang('shop::app.components.video-banner.mute')'"
                :aria-pressed="(! isMuted).toString()"
                @click.stop="toggleMute"
                @keydown.enter.stop
            >
                <!-- Muted icon -->
                <svg v-if="isMuted" class="uf-vbanner-sound-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/>
                    <path d="m16 9 5 6m0-6-5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>

                <!-- Speaker-on icon -->
                <svg v-else class="uf-vbanner-sound-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/>
                    <path d="M16 8.5a4.5 4.5 0 0 1 0 7M18.5 6a8 8 0 0 1 0 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                </svg>
            </button>
        </section>
    </script>

    <script type="module">
        app.component('v-video-banner', {
            template: '#v-video-banner-template',

            props: ['config'],

            data() {
                return {
                    loaded: false,
                    isPlaying: false,
                    isMuted: true,
                    observer: null,
                };
            },

            mounted() {
                if (! this.config.desktop && ! this.config.mobile) {
                    return;
                }

                this.observer = new IntersectionObserver(this.onIntersect, { threshold: 0.35 });

                this.observer.observe(this.$el);
            },

            beforeUnmount() {
                this.observer?.disconnect();
            },

            methods: {
                onIntersect(entries) {
                    const entry = entries[0];

                    if (entry.isIntersecting) {
                        this.start();
                    } else {
                        this.stop();
                    }
                },

                start() {
                    /* Lazy-attach the <source> only the first time we need it. */
                    if (! this.loaded) {
                        this.loaded = true;

                        this.$nextTick(() => {
                            this.$refs.video?.load();
                            this.play();
                        });

                        return;
                    }

                    this.play();
                },

                play() {
                    const video = this.$refs.video;

                    if (! video) {
                        return;
                    }

                    const promise = video.play();

                    if (promise !== undefined) {
                        promise.then(() => { this.isPlaying = true; }).catch(() => {});
                    } else {
                        this.isPlaying = true;
                    }
                },

                stop() {
                    const video = this.$refs.video;

                    if (video && ! video.paused) {
                        video.pause();
                    }

                    this.isPlaying = false;
                },

                toggleMute() {
                    const video = this.$refs.video;

                    if (! video) {
                        return;
                    }

                    video.muted = ! video.muted;
                    this.isMuted = video.muted;

                    /* Unmuting on a paused (out-of-view) banner shouldn't blast audio. */
                    if (! video.muted && video.paused) {
                        this.play();
                    }
                },

                visit() {
                    if (this.config.link) {
                        window.location.href = this.config.link;
                    }
                },
            },
        });
    </script>
@endPushOnce
