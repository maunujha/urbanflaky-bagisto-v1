<x-admin::layouts>
    <x-slot:title>
        @lang('blog::app.admin.blogs.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('blog::app.admin.blogs.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.blogs.index')" />

            @if (bouncer()->hasPermission('cms.blogs.create'))
                <a
                    href="{{ route('admin.blogs.create') }}"
                    class="primary-button"
                >
                    @lang('blog::app.admin.blogs.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.blogs.index')" />
</x-admin::layouts>
