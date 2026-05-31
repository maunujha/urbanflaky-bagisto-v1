<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang("shop::app.errors.{$errorCode}.title")
    </x-slot>

    <!-- Error page Information -->
	<div class="container absolute left-1/2 top-0 -translate-x-1/2 px-[60px] max-lg:px-8 max-sm:px-4">
		<div class="grid h-[100vh] w-full">
			<div class="wrapper-404 max-868:!text-[294px] max-md:!text-[140px]">
				<div class="glow-404">
                    {{ $errorCode }}
                </div>

				<div class="glow-shadow-404">
                    {{ $errorCode }}
                </div>
			</div>

            <div class="absolute left-1/2 top-[74%] mt-10 -translate-x-1/2 -translate-y-1/2 text-center max-868:w-full max-md:top-[60%]">
                <h1 class="text-3xl font-semibold max-md:text-xl">
                    @lang("shop::app.errors.{$errorCode}.title")
                </h1>

                <p class="mt-4 text-lg text-zinc-500 max-md:text-sm">
                    {{ 
                        $errorCode === 503 && core()->getCurrentChannel()->maintenance_mode_text != ""
                        ? core()->getCurrentChannel()->maintenance_mode_text : trans("shop::app.errors.{$errorCode}.description")
                    }}
                </p>

                <a
                    href="{{ route('shop.home.index') }}"
                    class="m-auto mt-8 block w-max cursor-pointer rounded-[45px] bg-uf-accent px-10 py-4 text-center text-base font-semibold text-black transition hover:brightness-105 max-sm:mb-10 max-sm:px-6 max-sm:text-sm"
                >
                    @lang('shop::app.errors.go-to-home')
                </a>

                @if ($errorCode === 500)
                    <p
                        id="uf-500-countdown"
                        class="mt-6 hidden text-sm text-zinc-400 max-md:text-xs"
                    ></p>
                @endif
            </div>
		</div>
	</div>

    @if ($errorCode === 500)
        <script>
            (function () {
                var MAX_RETRIES = 3;     // stop after this many auto-reloads
                var DELAY_MS    = 3000;  // wait before reloading
                var WINDOW_MS   = 30000; // reset the counter if last retry was older than this
                var key = 'uf-500-retries:' + window.location.pathname;

                var now   = Date.now();
                var state = {};
                try { state = JSON.parse(sessionStorage.getItem(key)) || {}; } catch (e) {}

                // Fresh burst of 500s? Start counting again.
                if (!state.last || (now - state.last) > WINDOW_MS) {
                    state = { count: 0 };
                }

                if (state.count < MAX_RETRIES) {
                    sessionStorage.setItem(key, JSON.stringify({ count: state.count + 1, last: now }));

                    var el        = document.getElementById('uf-500-countdown');
                    var remaining = Math.ceil(DELAY_MS / 1000);

                    var render = function () {
                        if (!el) return;
                        el.classList.remove('hidden');
                        el.textContent = remaining > 0
                            ? 'Retrying in ' + remaining + 's…'
                            : 'Reloading…';
                    };

                    render();
                    var ticker = setInterval(function () {
                        remaining -= 1;
                        render();
                        if (remaining <= 0) clearInterval(ticker);
                    }, 1000);

                    setTimeout(function () {
                        window.location.reload();
                    }, DELAY_MS);
                } else {
                    // Hit the cap — stop reloading so we don't loop forever.
                    sessionStorage.removeItem(key);
                }
            })();
        </script>
    @endif
</x-shop::layouts>