@props(['options'])

<v-carousel :images="{{ json_encode($options['images'] ?? []) }}">
    <div class="uf-hero">
        <div class="uf-hero-shimmer shimmer"></div>
    </div>
</v-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-carousel-template"
    >
        <div
            class="uf-hero"
            @mouseenter="pauseAutoplay"
            @mouseleave="resumeAutoplay"
        >
            <!-- Track -->
            <div class="uf-hero-track" ref="sliderContainer">
                <div
                    class="uf-hero-slide"
                    v-for="(image, index) in images"
                    :key="index"
                    @click="visitLink(image)"
                    ref="slide"
                >
                    <picture>
                        <source
                            v-if="image.mobile_image"
                            media="(max-width: 767px)"
                            :srcset="image.mobile_image"
                        />
                        <img
                            :src="image.image"
                            :alt="image?.title || 'Carousel Image ' + (index + 1)"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                            :fetchpriority="index === 0 ? 'high' : 'low'"
                            :decoding="index === 0 ? 'sync' : 'async'"
                            tabindex="0"
                        />
                    </picture>
                </div>
            </div>

            <!-- Arrows (desktop, on hover) -->
            <button
                type="button"
                class="uf-hero-arrow uf-prev"
                aria-label="@lang('shop::components.carousel.previous')"
                v-if="images?.length >= 2"
                @click.stop="navigate('prev')"
            >
                <span class="icon-arrow-left"></span>
            </button>

            <button
                type="button"
                class="uf-hero-arrow uf-next"
                aria-label="@lang('shop::components.carousel.next')"
                v-if="images?.length >= 2"
                @click.stop="navigate('next')"
            >
                <span class="icon-arrow-right"></span>
            </button>

            <!-- Segmented progress indicator -->
            <div class="uf-hero-bars" v-if="images?.length >= 2">
                <div
                    v-for="(image, index) in images"
                    :key="index"
                    class="uf-hero-bar"
                    :class="{
                        'uf-active': index === Math.abs(currentIndex),
                        'uf-done':   index <  Math.abs(currentIndex),
                        'uf-paused': isPaused && index === Math.abs(currentIndex)
                    }"
                    role="button"
                    tabindex="0"
                    :aria-label="'Go to slide ' + (index + 1)"
                    @click.stop="navigateByPagination(index)"
                    @keydown.enter="navigateByPagination(index)"
                    @keydown.space.prevent="navigateByPagination(index)"
                >
                    <span class="uf-hero-bar-fill" :key="'fill-' + index + '-' + barKey"></span>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component("v-carousel", {
            template: '#v-carousel-template',

            props: ['images'],

            data() {
                return {
                    isDragging: false,
                    startPos: 0,
                    currentTranslate: 0,
                    prevTranslate: 0,
                    animationID: 0,
                    currentIndex: 0,
                    slider: '',
                    slides: [],
                    autoPlayInterval: null,
                    direction: 'ltr',
                    startFrom: 1,
                    isPaused: false,
                    barKey: 0, // bump to restart the CSS keyframe animation on the active bar
                };
            },

            mounted() {
                this.slider = this.$refs.sliderContainer;

                if (
                    this.$refs.slide
                    && typeof this.$refs.slide[Symbol.iterator] === 'function'
                ) {
                    this.slides = Array.from(this.$refs.slide);
                }

                if ('requestIdleCallback' in window) {
                    requestIdleCallback(() => {
                        this.init();
                        setTimeout(() => {
                            this.play();
                        }, 4000);
                    });
                } else {
                    setTimeout(() => {
                        this.init();
                        setTimeout(() => {
                            this.play();
                        }, 4000);
                    });
                }
            },

            beforeUnmount() {
                this.cleanup();
            },

            methods: {
                init() {
                    this.direction = document.dir;

                    if (this.direction == 'rtl') {
                        this.startFrom = -1;
                    }

                    this.slides.forEach((slide) => {
                        slide.querySelector('img')?.addEventListener('dragstart', (e) => e.preventDefault());
                        slide.addEventListener('mousedown', this.handleDragStart);
                        slide.addEventListener('touchstart', this.handleDragStart, { passive: true });
                        slide.addEventListener('mouseup', this.handleDragEnd);
                        slide.addEventListener('mouseleave', this.handleDragEnd);
                        slide.addEventListener('touchend', this.handleDragEnd, { passive: true });
                        slide.addEventListener('mousemove', this.handleDrag);
                        slide.addEventListener('touchmove', this.handleDrag, { passive: true });
                    });

                    window.addEventListener('resize', this.setPositionByIndex);
                },

                handleDragStart(event) {
                    this.startPos = event.type === 'mousedown' ? event.clientX : event.touches[0].clientX;
                    this.isDragging = true;
                    this.animationID = requestAnimationFrame(this.animation);
                },

                handleDrag(event) {
                    if (!this.isDragging) return;

                    const currentPosition = event.type === 'mousemove' ? event.clientX : event.touches[0].clientX;
                    this.currentTranslate = this.prevTranslate + currentPosition - this.startPos;
                },

                handleDragEnd() {
                    clearInterval(this.autoPlayInterval);
                    cancelAnimationFrame(this.animationID);
                    this.isDragging = false;

                    const movedBy = this.currentTranslate - this.prevTranslate;

                    if (this.direction == 'ltr') {
                        if (movedBy < -100 && this.currentIndex < this.slides.length - 1) this.currentIndex += 1;
                        if (movedBy >  100 && this.currentIndex > 0)                       this.currentIndex -= 1;
                    } else {
                        if (movedBy >  100 && this.currentIndex < this.slides.length - 1) {
                            if (Math.abs(this.currentIndex) != this.slides.length - 1) this.currentIndex -= 1;
                        }
                        if (movedBy < -100 && this.currentIndex < 0) this.currentIndex += 1;
                    }

                    this.setPositionByIndex();
                    this.barKey++;
                    this.play();
                },

                animation() {
                    this.setSliderPosition();
                    if (this.isDragging) requestAnimationFrame(this.animation);
                },

                setPositionByIndex() {
                    const w = this.slides[0]?.offsetWidth || window.innerWidth;
                    this.currentTranslate = this.currentIndex * -w;
                    this.prevTranslate = this.currentTranslate;
                    this.setSliderPosition();
                },

                setSliderPosition() {
                    if (this.slider) this.slider.style.transform = `translateX(${this.currentTranslate}px)`;
                },

                visitLink(image) {
                    if (image.link) window.location.href = image.link;
                },

                navigate(type) {
                    clearInterval(this.autoPlayInterval);

                    if (this.direction === 'rtl') {
                        type === 'next' ? this.prev() : this.next();
                    } else {
                        type === 'next' ? this.next() : this.prev();
                    }

                    this.setPositionByIndex();
                    this.barKey++;
                    this.play();
                },

                next() {
                    this.currentIndex = (this.currentIndex + this.startFrom) % this.images.length;
                },

                prev() {
                    this.currentIndex = this.direction == 'ltr'
                        ? this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1
                        : this.currentIndex < 0 ? this.currentIndex + 1 : -(this.images.length - 1);
                },

                navigateByPagination(index) {
                    this.direction == 'rtl' ? index = -index : '';

                    clearInterval(this.autoPlayInterval);
                    this.currentIndex = index;
                    this.setPositionByIndex();
                    this.barKey++;
                    this.play();
                },

                play() {
                    clearInterval(this.autoPlayInterval);
                    this.isPaused = false;

                    this.autoPlayInterval = setInterval(() => {
                        this.currentIndex = (this.currentIndex + this.startFrom) % this.images.length;
                        this.setPositionByIndex();
                        this.barKey++;
                    }, 5000);
                },

                pauseAutoplay() {
                    clearInterval(this.autoPlayInterval);
                    this.isPaused = true;
                },

                resumeAutoplay() {
                    this.isPaused = false;
                    this.barKey++;
                    this.play();
                },

                cleanup() {
                    clearInterval(this.autoPlayInterval);
                    cancelAnimationFrame(this.animationID);

                    if (this.slides) {
                        this.slides.forEach(slide => {
                            slide.removeEventListener('mousedown', this.handleDragStart);
                            slide.removeEventListener('touchstart', this.handleDragStart);
                            slide.removeEventListener('mouseup', this.handleDragEnd);
                            slide.removeEventListener('mouseleave', this.handleDragEnd);
                            slide.removeEventListener('touchend', this.handleDragEnd);
                            slide.removeEventListener('mousemove', this.handleDrag);
                            slide.removeEventListener('touchmove', this.handleDrag);
                        });
                    }

                    window.removeEventListener('resize', this.setPositionByIndex);
                },
            },
        });
    </script>
@endpushOnce
