<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.vendors.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('inventory::app.admin.vendors.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.inventory.vendors.index')" />

            @if (bouncer()->hasPermission('inventory.vendors.create'))
                <a
                    href="{{ route('admin.inventory.vendors.create') }}"
                    class="primary-button"
                >
                    @lang('inventory::app.admin.vendors.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.inventory.vendors.index')" />
</x-admin::layouts>
