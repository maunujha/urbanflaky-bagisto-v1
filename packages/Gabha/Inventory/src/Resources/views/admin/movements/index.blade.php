<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.movements.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('inventory::app.admin.movements.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.inventory.movements.index')" />

            @if (bouncer()->hasPermission('inventory.movements.create'))
                <a
                    href="{{ route('admin.inventory.movements.create') }}"
                    class="primary-button"
                >
                    @lang('inventory::app.admin.movements.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.inventory.movements.index')" />
</x-admin::layouts>
