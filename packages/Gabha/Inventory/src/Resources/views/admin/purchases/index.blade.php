<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.purchases.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('inventory::app.admin.purchases.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.inventory.purchases.index')" />

            @if (bouncer()->hasPermission('inventory.purchases.create'))
                <a
                    href="{{ route('admin.inventory.purchases.create') }}"
                    class="primary-button"
                >
                    @lang('inventory::app.admin.purchases.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.inventory.purchases.index')" />
</x-admin::layouts>
