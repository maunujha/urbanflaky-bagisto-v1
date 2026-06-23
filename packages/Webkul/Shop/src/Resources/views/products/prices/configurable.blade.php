@unless (core()->getConfigData('general.catalog_mode.settings.enabled') && core()->getConfigData('general.catalog_mode.settings.hide_prices'))
    <p class="price-label text-sm text-zinc-500 max-sm:text-xs">
        @lang('shop::app.products.prices.configurable.as-low-as')
    </p>

    <p class="regular-price text-lg font-semibold text-gray-500 line-through"></p>

    <p class="final-price font-semibold">
        {{ $prices['regular']['formatted_price'] }}
    </p>
@endunless
