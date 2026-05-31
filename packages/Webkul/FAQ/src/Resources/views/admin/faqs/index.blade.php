<x-admin::layouts>
    <x-slot:title>
        @lang('faq::app.admin.faqs.index.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('faq::app.admin.faqs.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Export Modal -->
            <x-admin::datagrid.export :src="route('admin.faqs.index')" />

            @if (bouncer()->hasPermission('cms.faqs.create'))
                <a
                    href="{{ route('admin.faqs.create') }}"
                    class="primary-button"
                >
                    @lang('faq::app.admin.faqs.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.faqs.index')" />
</x-admin::layouts>
