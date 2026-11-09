{{-- Marks the end of the first screen; the button appears once this has scrolled past. --}}
<div
    id="uf-back-to-top-sentinel"
    class="pointer-events-none absolute left-0 top-[100vh] h-px w-px"
    aria-hidden="true"
></div>

<button
    type="button"
    id="uf-back-to-top"
    data-visible="false"
    aria-label="Back to top"
    class="uf-back-to-top invisible fixed right-4 z-[9] flex h-11 w-11 translate-y-3 items-center justify-center rounded-full border border-white/15 bg-uf-surface text-uf-text opacity-0 shadow-[0_8px_22px_rgba(0,0,0,0.45)] transition-[opacity,transform,visibility,color,border-color] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:border-uf-accent hover:text-uf-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-uf-accent data-[visible=true]:visible data-[visible=true]:translate-y-0 data-[visible=true]:opacity-100 motion-reduce:transition-none md:right-8"
>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path d="M12 19V5M5 12l7-7 7 7"/>
    </svg>
</button>

@pushOnce('scripts')
    <script>
        (function () {
            var button   = document.getElementById('uf-back-to-top');
            var sentinel = document.getElementById('uf-back-to-top-sentinel');

            if (! button || ! sentinel || typeof IntersectionObserver === 'undefined') return;

            new IntersectionObserver(function (entries) {
                var entry = entries[entries.length - 1];

                button.dataset.visible = (! entry.isIntersecting && entry.boundingClientRect.top < 0).toString();
            }).observe(sentinel);

            button.addEventListener('click', function () {
                var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
            });
        })();
    </script>
@endPushOnce
