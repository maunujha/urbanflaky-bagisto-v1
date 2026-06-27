@props(['count' => 4, 'navigationLink' => false])

<section class="uf-prod-section" aria-hidden="true">
    <div class="uf-prod-container">
        <div class="uf-prod-head">
            <span class="shimmer h-8 w-[220px] rounded max-sm:h-6 max-sm:w-[160px]"></span>

            <div class="flex items-center gap-3">
                <span class="shimmer h-11 w-11 rounded-full max-lg:hidden"></span>
                <span class="shimmer h-11 w-11 rounded-full max-lg:hidden"></span>
            </div>
        </div>

        <div class="uf-prod-grid">
            <x-shop::shimmer.products.cards.grid
                class="!min-w-0"
                :count="$count"
            />
        </div>

        @if ($navigationLink)
            <span class="shimmer mx-auto mt-10 block h-12 w-[170px] rounded-full max-lg:hidden"></span>
        @endif
    </div>
</section>
