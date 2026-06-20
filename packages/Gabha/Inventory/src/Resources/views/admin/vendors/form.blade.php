@php
    $isEdit = ! empty($vendor);
@endphp

<div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
    <p class="text-xl font-bold text-gray-800 dark:text-white">
        {{ $isEdit ? trans('inventory::app.admin.vendors.edit.title') : trans('inventory::app.admin.vendors.create.title') }}
    </p>

    <div class="flex items-center gap-x-2.5">
        <a
            href="{{ route('admin.inventory.vendors.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('inventory::app.admin.vendors.create.back-btn')
        </a>

        <button type="submit" class="primary-button">
            {{ $isEdit ? trans('inventory::app.admin.vendors.edit.save-btn') : trans('inventory::app.admin.vendors.create.save-btn') }}
        </button>
    </div>
</div>

<div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
    <!-- Left -->
    <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('inventory::app.admin.vendors.create.general')
            </p>

            <!-- Name -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('inventory::app.admin.vendors.create.name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="name"
                    rules="required"
                    :value="old('name', $isEdit ? $vendor->name : '')"
                    :label="trans('inventory::app.admin.vendors.create.name')"
                    :placeholder="trans('inventory::app.admin.vendors.create.name-placeholder')"
                />

                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            <!-- Mobile -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('inventory::app.admin.vendors.create.mobile')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="mobile"
                    rules="required"
                    :value="old('mobile', $isEdit ? $vendor->mobile : '')"
                    :label="trans('inventory::app.admin.vendors.create.mobile')"
                    :placeholder="trans('inventory::app.admin.vendors.create.mobile-placeholder')"
                />

                <x-admin::form.control-group.error control-name="mobile" />
            </x-admin::form.control-group>

            <!-- Address -->
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label class="required">
                    @lang('inventory::app.admin.vendors.create.address')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="textarea"
                    name="address"
                    rules="required"
                    :value="old('address', $isEdit ? $vendor->address : '')"
                    :label="trans('inventory::app.admin.vendors.create.address')"
                    :placeholder="trans('inventory::app.admin.vendors.create.address-placeholder')"
                />

                <x-admin::form.control-group.error control-name="address" />
            </x-admin::form.control-group>
        </div>
    </div>
</div>
