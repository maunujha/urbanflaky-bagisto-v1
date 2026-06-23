<div class="max-md:[&>*]:leading-6 max-sm:[&>*]:leading-4 grid gap-1.5 max-md:flex">
    @if (core()->getConfigData('general.catalog_mode.settings.enabled') && core()->getConfigData('general.catalog_mode.settings.hide_prices'))
        {{-- Catalog mode: prices hidden storefront-wide --}}
    @elseif ($prices['from']['regular']['price'] != $prices['from']['final']['price'])
        <p class="flex items-center gap-4 max-sm:flex-col max-sm:items-start max-sm:gap-0.5 max-sm:text-sm">
            <span
                class="final-price text-zinc-500 line-through max-sm:text-sm"
                aria-label="{{ $prices['from']['regular']['formatted_price'] }}"
            >
                {{ $prices['from']['regular']['formatted_price'] }}
            </span>
            
            {{ $prices['from']['final']['formatted_price'] }}
        </p>
    @else
        <p class="final-price flex items-center gap-4 max-sm:text-sm">
            {{ $prices['from']['regular']['formatted_price'] }}
        </p>
    @endif

    @if (
        ! (core()->getConfigData('general.catalog_mode.settings.enabled') && core()->getConfigData('general.catalog_mode.settings.hide_prices'))
        && (
            $prices['from']['regular']['price'] != $prices['to']['regular']['price']
            || $prices['from']['final']['price'] != $prices['to']['final']['price']
        )
    )
        <p class="text-base font-normal max-sm:text-sm">To</p>
        
        @if ($prices['to']['regular']['price'] != $prices['to']['final']['price'])
            <p class="flex items-center gap-4 max-sm:flex-col max-sm:items-start max-sm:gap-0.5 max-sm:text-sm">
                <span
                    class="final-price text-zinc-500 line-through max-sm:text-sm"
                    aria-label="{{ $prices['to']['regular']['formatted_price'] }}"
                >
                    {{ $prices['to']['regular']['formatted_price'] }}
                </span>
                
                {{ $prices['to']['final']['formatted_price'] }}
            </p>
        @else
            <p class="final-price flex items-center gap-4 max-sm:text-sm">
                {{ $prices['to']['regular']['formatted_price'] }}
            </p>
        @endif
    @endif
</div>