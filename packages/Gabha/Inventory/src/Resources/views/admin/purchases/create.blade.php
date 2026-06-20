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

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-purchase-items-template"
        >
            <div>
                <!-- Variant Search -->
                <div class="relative mb-4">
                    <input
                        type="text"
                        v-model="searchTerm"
                        v-debounce="400"
                        placeholder="@lang('inventory::app.admin.purchases.create.search-placeholder')"
                        class="block w-full rounded-lg border bg-white py-2 leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                        @focus="showDropdownIfReady"
                    />

                    <span class="icon-search pointer-events-none absolute top-2 flex items-center text-2xl text-gray-500 ltr:right-3 rtl:left-3"></span>

                    <!-- Results Dropdown -->
                    <div
                        v-if="showDropdown"
                        class="absolute z-10 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div v-if="isSearching" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-300">
                            @lang('inventory::app.admin.purchases.create.searching')
                        </div>

                        <template v-else>
                            <div v-if="results.length">
                                <div
                                    v-for="result in results"
                                    :key="result.id"
                                    class="cursor-pointer border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800"
                                    @click="addVariant(result)"
                                >
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">@{{ result.label }}</p>
                                </div>
                            </div>

                            <div v-else class="px-3 py-2 text-sm text-gray-500 dark:text-gray-300">
                                @lang('inventory::app.admin.purchases.create.no-results')
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Items Table -->
                <div v-if="items.length" class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b dark:border-gray-800">
                                <th class="px-2.5 py-2.5 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.create.product-variant')
                                </th>
                                <th class="w-28 px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.create.quantity')
                                </th>
                                <th class="w-32 px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.create.unit-cost')
                                </th>
                                <th class="w-32 px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.create.line-total')
                                </th>
                                <th class="w-12 px-2.5 py-2.5"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(item, index) in items"
                                :key="item.product_variant_id"
                                class="border-b dark:border-gray-800"
                            >
                                <td class="px-2.5 py-2.5 align-top text-sm text-gray-800 dark:text-white">
                                    @{{ item.label }}

                                    <input
                                        type="hidden"
                                        :name="`items[${index}][product_variant_id]`"
                                        :value="item.product_variant_id"
                                    />
                                </td>

                                <td class="px-2.5 py-2.5 text-right">
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        :name="`items[${index}][quantity]`"
                                        v-model.number="item.quantity"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-right dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </td>

                                <td class="px-2.5 py-2.5 text-right">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :name="`items[${index}][unit_cost]`"
                                        v-model.number="item.unit_cost"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-right dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </td>

                                <td class="px-2.5 py-2.5 text-right text-sm text-gray-800 dark:text-white">
                                    @{{ currency }}@{{ formatMoney(lineTotal(item)) }}
                                </td>

                                <td class="px-2.5 py-2.5 text-center">
                                    <span
                                        class="icon-delete cursor-pointer text-xl text-red-600"
                                        @click="removeItem(index)"
                                    ></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="mt-4 flex flex-wrap justify-end gap-x-10 gap-y-2 border-t pt-4 dark:border-gray-800">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('inventory::app.admin.purchases.create.total-quantity'):
                            <span class="font-semibold text-gray-800 dark:text-white">@{{ totalQuantity }}</span>
                        </div>

                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('inventory::app.admin.purchases.create.grand-total'):
                            <span class="font-semibold text-gray-800 dark:text-white">@{{ currency }}@{{ formatMoney(grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <p v-else class="rounded border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300">
                    @lang('inventory::app.admin.purchases.create.no-products')
                </p>
            </div>
        </script>

        <script type="module">
            app.component('v-purchase-items', {
                template: '#v-purchase-items-template',

                props: {
                    currency: {
                        type: String,
                        default: '',
                    },
                },

                data() {
                    return {
                        searchTerm: '',
                        results: [],
                        isSearching: false,
                        showDropdown: false,
                        items: [],
                    };
                },

                computed: {
                    totalQuantity() {
                        return this.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
                    },

                    grandTotal() {
                        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
                    },
                },

                watch: {
                    searchTerm() {
                        this.search();
                    },
                },

                methods: {
                    search() {
                        if (this.searchTerm.trim().length < 2) {
                            this.results = [];
                            this.showDropdown = false;

                            return;
                        }

                        this.isSearching = true;
                        this.showDropdown = true;

                        let self = this;

                        this.$axios.get("{{ route('admin.inventory.variants.search') }}", {
                                params: { query: this.searchTerm },
                            })
                            .then(function (response) {
                                self.isSearching = false;
                                self.results = response.data.data;
                            })
                            .catch(function () {
                                self.isSearching = false;
                                self.results = [];
                            });
                    },

                    showDropdownIfReady() {
                        if (this.results.length) {
                            this.showDropdown = true;
                        }
                    },

                    addVariant(result) {
                        if (! this.items.some(item => item.product_variant_id === result.id)) {
                            this.items.push({
                                product_variant_id: result.id,
                                label: result.label,
                                sku: result.sku,
                                quantity: 1,
                                unit_cost: 0,
                            });
                        }

                        this.searchTerm = '';
                        this.results = [];
                        this.showDropdown = false;
                    },

                    removeItem(index) {
                        this.items.splice(index, 1);
                    },

                    lineTotal(item) {
                        return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);
                    },

                    formatMoney(value) {
                        return Number(value).toFixed(2);
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
