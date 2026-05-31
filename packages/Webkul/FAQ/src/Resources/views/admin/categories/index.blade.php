<x-admin::layouts>
    <x-slot:title>
        @lang('faq::app.admin.categories.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('faq::app.admin.categories.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.faqs.categories.index')" />

            @if (bouncer()->hasPermission('cms.faq_categories.create'))
                <a
                    href="{{ route('admin.faqs.categories.create') }}"
                    class="primary-button"
                >
                    @lang('faq::app.admin.categories.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.faqs.categories.index')" />
</x-admin::layouts>
