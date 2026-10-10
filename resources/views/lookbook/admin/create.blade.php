<x-admin::layouts>
    <x-slot:title>
        @lang('lookbook::app.admin.create.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.lookbook.store')"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.lookbook.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('lookbook::app.admin.create.back')
                </a>

                <button type="submit" class="primary-button">
                    @lang('lookbook::app.admin.create.save-btn')
                </button>
            </div>
        </div>

        @include('lookbook::admin._form')
    </x-admin::form>
</x-admin::layouts>
