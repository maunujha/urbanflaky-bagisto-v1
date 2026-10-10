<x-admin::layouts>
    <x-slot:title>
        @lang('lookbook::app.admin.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('lookbook::app.admin.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('lookbook.create'))
                <a
                    href="{{ route('admin.lookbook.create') }}"
                    class="primary-button"
                >
                    @lang('lookbook::app.admin.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.lookbook.index')" />
</x-admin::layouts>
