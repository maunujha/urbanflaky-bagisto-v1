<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.stock.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('inventory::app.admin.stock.index.title')
        </p>

        <x-admin::datagrid.export :src="route('admin.inventory.stock.index')" />
    </div>

    <!-- Dashboard Cards -->
    <div class="mt-4 flex flex-wrap gap-4">
        <!-- Total Inventory Units -->
        <div class="box-shadow min-w-[220px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">
                @lang('inventory::app.admin.stock.index.cards.total-units')
            </p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total_units']) }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.stock.index.cards.units')</span>
            </p>
        </div>

        <!-- Total Inventory Value -->
        <div class="box-shadow min-w-[220px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">
                @lang('inventory::app.admin.stock.index.cards.total-value')
            </p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                {{ core()->formatBasePrice($stats['total_value']) }}
            </p>
        </div>

        <!-- Low Stock Products -->
        <div class="box-shadow min-w-[220px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">
                @lang('inventory::app.admin.stock.index.cards.low-stock')
            </p>
            <p class="mt-2 text-2xl font-bold text-amber-600">
                {{ number_format($stats['low_stock']) }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.stock.index.cards.products')</span>
            </p>
            <p class="mt-1 text-xs text-gray-400">
                {{ trans('inventory::app.admin.stock.index.cards.low-stock-hint', ['threshold' => $stats['threshold']]) }}
            </p>
        </div>

        <!-- Total Vendors -->
        <div class="box-shadow min-w-[220px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">
                @lang('inventory::app.admin.stock.index.cards.total-vendors')
            </p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                {{ number_format($stats['total_vendors']) }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.stock.index.cards.vendors')</span>
            </p>
        </div>
    </div>

    <!-- Inventory List -->
    <div class="mt-4">
        <x-admin::datagrid :src="route('admin.inventory.stock.index')" />
    </div>
</x-admin::layouts>
