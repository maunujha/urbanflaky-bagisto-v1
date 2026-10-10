<x-admin::layouts>
    <x-slot:title>
        @lang('lookbook::app.admin.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.lookbook.update', $look->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.lookbook.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('lookbook::app.admin.edit.back')
                </a>

                <button type="submit" class="primary-button">
                    @lang('lookbook::app.admin.edit.save-btn')
                </button>
            </div>
        </div>

        @include('lookbook::admin._form', ['look' => $look, 'taggedProducts' => $taggedProducts])
    </x-admin::form>
</x-admin::layouts>
