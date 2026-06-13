<v-shimmer-image {{ $attributes }}>
    <div {{ $attributes->merge(['class' => 'shimmer']) }}></div>
</v-shimmer-image>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-shimmer-image-template"
    >
        <div
            :id="'image-shimmer-' + $.uid"
            class="shimmer"
            v-bind="$attrs"
            v-if="isLoading"
        >
        </div>

        <!--
            WebP with original-format fallback: `src`/`srcset` carry the .webp
            URLs; `fallback` (when provided) is the same-basename JPG/PNG kept
            at upload time. Browsers without WebP support skip the <source>.
        -->
        <picture
            v-if="shouldLoad"
            v-show="! isLoading"
        >
            <source
                type="image/webp"
                :srcset="srcset || src"
                :sizes="sizes || null"
                v-if="hasFallback"
            >

            <img
                v-bind="$attrs"
                :src="hasFallback ? fallback : src"
                :srcset="hasFallback ? null : (srcset || null)"
                :sizes="hasFallback ? null : (sizes || null)"
                :id="'image-' + $.uid"
                decoding="async"
                @load="onLoad"
                v-on:error="onLoad" {{-- NOT @error: that is a Blade directive --}}
            >
        </picture>
    </script>

    <script type="module">
        app.component('v-shimmer-image', {
            template: '#v-shimmer-image-template',

            props: {
                lazy: {
                    type: Boolean,
                    default: true,
                },

                src: {
                    type: String,
                    default: '',
                },

                srcset: {
                    type: String,
                    default: '',
                },

                sizes: {
                    type: String,
                    default: '',
                },

                fallback: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    isLoading: true,

                    shouldLoad: ! this.lazy,
                };
            },

            computed: {
                hasFallback() {
                    return this.fallback !== '' && this.fallback !== this.src;
                },
            },

            mounted() {
                if (! this.lazy) {
                    return;
                }

                let observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (! entry.isIntersecting) {
                            return;
                        }

                        this.shouldLoad = true;

                        observer.disconnect();
                    });
                }, { rootMargin: '200px' });

                observer.observe(document.getElementById('image-shimmer-' + this.$.uid));
            },

            methods: {
                onLoad() {
                    this.isLoading = false;
                },
            },
        });
    </script>
@endPushOnce
