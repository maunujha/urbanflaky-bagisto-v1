@php
    $currencySymbol = core()->getBaseCurrency()->symbol ?? '';
    $hasVendors = $vendors->isNotEmpty();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.purchases.create.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.inventory.purchases.store')"
        enctype="multipart/form-data"
    >
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('inventory::app.admin.purchases.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.inventory.purchases.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('inventory::app.admin.purchases.create.back-btn')
                </a>

                @if ($hasVendors)
                    <button type="submit" class="primary-button">
                        @lang('inventory::app.admin.purchases.create.save-btn')
                    </button>
                @endif
            </div>
        </div>

        <!-- Server-side validation summary (covers items / file errors too) -->
        @if ($errors->any())
            <div class="mt-3.5 rounded border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                <ul class="list-inside list-disc text-sm text-red-600 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @unless ($hasVendors)
            <div class="mt-3.5 box-shadow rounded bg-white p-6 text-center dark:bg-gray-900">
                <p class="mb-3 text-gray-600 dark:text-gray-300">
                    @lang('inventory::app.admin.purchases.create.no-vendors')
                </p>

                <a href="{{ route('admin.inventory.vendors.create') }}" class="primary-button inline-block">
                    @lang('inventory::app.admin.purchases.create.create-vendor')
                </a>
            </div>
        @else
            <div class="mt-3.5 flex flex-col gap-2.5">
                <!-- Step 1: Vendor -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('inventory::app.admin.purchases.create.step-vendor')
                    </p>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('inventory::app.admin.purchases.create.vendor')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="vendor_id"
                            rules="required"
                            :value="old('vendor_id')"
                            :label="trans('inventory::app.admin.purchases.create.vendor')"
                        >
                            <option value="">@lang('inventory::app.admin.purchases.create.vendor-placeholder')</option>

                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }} — {{ $vendor->mobile }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="vendor_id" />
                    </x-admin::form.control-group>
                </div>

                <!-- Step 2: Purchase Information -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('inventory::app.admin.purchases.create.step-info')
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                        <!-- Purchase Date -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('inventory::app.admin.purchases.create.purchase-date')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="date"
                                name="purchase_date"
                                rules="required"
                                :value="old('purchase_date', now()->format('Y-m-d'))"
                                :label="trans('inventory::app.admin.purchases.create.purchase-date')"
                            />

                            <x-admin::form.control-group.error control-name="purchase_date" />
                        </x-admin::form.control-group>

                        <!-- Invoice Number -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('inventory::app.admin.purchases.create.invoice-number')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="invoice_number"
                                :value="old('invoice_number')"
                                :label="trans('inventory::app.admin.purchases.create.invoice-number')"
                                :placeholder="trans('inventory::app.admin.purchases.create.invoice-number-placeholder')"
                            />

                            <x-admin::form.control-group.error control-name="invoice_number" />
                        </x-admin::form.control-group>
                    </div>

                    <!-- Bill Upload -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('inventory::app.admin.purchases.create.bill')
                        </x-admin::form.control-group.label>

                        <input
                            type="file"
                            name="bill_file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full cursor-pointer rounded-md border border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-300"
                        />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            @lang('inventory::app.admin.purchases.create.bill-info')
                        </p>

                        <x-admin::form.control-group.error control-name="bill_file" />
                    </x-admin::form.control-group>

                    <!-- Notes -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('inventory::app.admin.purchases.create.notes')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="notes"
                            :value="old('notes')"
                            :label="trans('inventory::app.admin.purchases.create.notes')"
                        />

                        <x-admin::form.control-group.error control-name="notes" />
                    </x-admin::form.control-group>
                </div>

                <!-- Step 3: Add Products -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('inventory::app.admin.purchases.create.step-products')
                    </p>

                    <v-purchase-items currency="{{ $currencySymbol }}"></v-purchase-items>
                </div>
            </div>
        @endunless
    </x-admin::form>

    @include('inventory::admin.purchases.partials.items-component')
</x-admin::layouts>
