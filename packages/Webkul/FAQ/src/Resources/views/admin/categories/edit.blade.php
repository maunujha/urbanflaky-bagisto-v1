<x-admin::layouts>
    <x-slot:title>
        @lang('faq::app.admin.categories.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.faqs.categories.update', $category->id)"
        method="PUT"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('faq::app.admin.categories.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.faqs.categories.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('faq::app.admin.categories.create.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('faq::app.admin.categories.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('faq::app.admin.categories.create.general')
                    </p>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('faq::app.admin.categories.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :value="old('name', $category->name)"
                            :label="trans('faq::app.admin.categories.create.name')"
                            :placeholder="trans('faq::app.admin.categories.create.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Sort Order -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('faq::app.admin.categories.create.sort-order')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="sort_order"
                            rules="integer|min:0"
                            :value="old('sort_order', $category->sort_order)"
                            :label="trans('faq::app.admin.categories.create.sort-order')"
                            :placeholder="trans('faq::app.admin.categories.create.sort-order')"
                        />

                        <x-admin::form.control-group.error control-name="sort_order" />
                    </x-admin::form.control-group>

                    <!-- Status -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('faq::app.admin.categories.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            class="cursor-pointer"
                            value="1"
                            :checked="(bool) old('status', $category->status)"
                            :label="trans('faq::app.admin.categories.create.status')"
                        />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
