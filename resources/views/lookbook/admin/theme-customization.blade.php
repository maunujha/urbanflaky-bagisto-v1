@php
    $options = $theme->translate($currentLocale->code)->options ?? [];
@endphp

<div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="mb-2.5 flex flex-col gap-1">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.menu.looks')
            </p>

            <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                @lang('lookbook::app.shop.subtitle')
            </p>
        </div>

        <!-- Section Title -->
        <x-admin::form.control-group class="mb-2.5 pt-4">
            <x-admin::form.control-group.label>
                @lang('lookbook::app.admin.form.title')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="{{ $currentLocale->code }}[options][title]"
                :value="$options['title'] ?? trans('lookbook::app.shop.title')"
                :label="trans('lookbook::app.admin.form.title')"
                :placeholder="trans('lookbook::app.shop.title')"
            />
        </x-admin::form.control-group>

        <!-- Subtitle -->
        <x-admin::form.control-group class="mb-2.5">
            <x-admin::form.control-group.label>
                Subtitle
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="textarea"
                name="{{ $currentLocale->code }}[options][subtitle]"
                :value="$options['subtitle'] ?? trans('lookbook::app.shop.subtitle')"
                label="Subtitle"
                :placeholder="trans('lookbook::app.shop.subtitle')"
            />
        </x-admin::form.control-group>

        <!-- Number of looks -->
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                Number of looks to show
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="{{ $currentLocale->code }}[options][limit]"
                rules="numeric"
                :value="$options['limit'] ?? 12"
                label="Number of looks to show"
                placeholder="12"
            />

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                Looks are managed under the <b>Lookbook</b> menu. Leave blank to show all active looks.
            </p>
        </x-admin::form.control-group>
    </div>
</div>
