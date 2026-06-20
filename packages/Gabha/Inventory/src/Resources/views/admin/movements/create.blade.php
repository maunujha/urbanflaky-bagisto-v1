<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.movements.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.inventory.movements.store')">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('inventory::app.admin.movements.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.inventory.movements.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('inventory::app.admin.movements.create.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('inventory::app.admin.movements.create.save-btn')
                </button>
            </div>
        </div>

        <!-- Server-side validation summary -->
        @if ($errors->any())
            <div class="mt-3.5 rounded border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                <ul class="list-inside list-disc text-sm text-red-600 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('inventory::app.admin.movements.create.general')
                    </p>

                    <!-- Product Variant (Vue-managed) -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('inventory::app.admin.movements.create.product-variant')
                        </x-admin::form.control-group.label>

                        <v-movement-variant
                            old-id="{{ old('product_variant_id') }}"
                        ></v-movement-variant>

                        <x-admin::form.control-group.error control-name="product_variant_id" />
                    </x-admin::form.control-group>

                    <!-- Movement Type -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('inventory::app.admin.movements.create.movement-type')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="movement_type"
                            rules="required"
                            :value="old('movement_type')"
                            :label="trans('inventory::app.admin.movements.create.movement-type')"
                        >
                            <option value="">@lang('inventory::app.admin.movements.create.movement-type-placeholder')</option>

                            @foreach ($movementTypes as $type)
                                <option value="{{ $type->value }}" {{ old('movement_type') === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="movement_type" />
                    </x-admin::form.control-group>

                    <!-- Quantity -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('inventory::app.admin.movements.create.quantity')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="quantity"
                            rules="required|numeric|min_value:1"
                            :value="old('quantity')"
                            :label="trans('inventory::app.admin.movements.create.quantity')"
                        />

                        <x-admin::form.control-group.error control-name="quantity" />
                    </x-admin::form.control-group>

                    <!-- Notes -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('inventory::app.admin.movements.create.notes')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="notes"
                            :value="old('notes')"
                            :label="trans('inventory::app.admin.movements.create.notes')"
                        />

                        <x-admin::form.control-group.error control-name="notes" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-movement-variant-template"
        >
            <div>
                <!-- Hidden field submitted with the form -->
                <input type="hidden" name="product_variant_id" :value="selected ? selected.id : ''" />

                <!-- Selected variant card -->
                <div
                    v-if="selected"
                    class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950"
                >
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white">@{{ selected.label }}</p>

                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-300">
                            <span>@{{ "@lang('inventory::app.admin.movements.create.sku')".replace(':sku', selected.sku) }}</span>
                            <span class="font-semibold text-green-600">
                                @lang('inventory::app.admin.movements.create.current-stock'): @{{ selected.current_stock }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="secondary-button shrink-0 text-xs"
                        @click="clearSelection"
                    >
                        @lang('inventory::app.admin.movements.create.change-variant')
                    </button>
                </div>

                <!-- Search -->
                <div v-else class="relative">
                    <input
                        type="text"
                        v-model="searchTerm"
                        v-debounce="400"
                        placeholder="@lang('inventory::app.admin.movements.create.search-placeholder')"
                        class="block w-full rounded-lg border bg-white py-2 leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                    />

                    <span class="icon-search pointer-events-none absolute top-2 flex items-center text-2xl text-gray-500 ltr:right-3 rtl:left-3"></span>

                    <div
                        v-if="showDropdown"
                        class="absolute z-10 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div v-if="isSearching" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-300">
                            @lang('inventory::app.admin.movements.create.searching')
                        </div>

                        <template v-else>
                            <div v-if="results.length">
                                <div
                                    v-for="result in results"
                                    :key="result.id"
                                    class="cursor-pointer border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800"
                                    @click="selectVariant(result)"
                                >
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">@{{ result.label }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">
                                        @{{ "@lang('inventory::app.admin.movements.create.sku')".replace(':sku', result.sku) }}
                                        · @lang('inventory::app.admin.movements.create.current-stock'): @{{ result.current_stock }}
                                    </p>
                                </div>
                            </div>

                            <div v-else class="px-3 py-2 text-sm text-gray-500 dark:text-gray-300">
                                @lang('inventory::app.admin.movements.create.no-results')
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-movement-variant', {
                template: '#v-movement-variant-template',

                props: {
                    oldId: {
                        type: [String, Number],
                        default: '',
                    },
                },

                data() {
                    return {
                        searchTerm: '',
                        results: [],
                        isSearching: false,
                        showDropdown: false,
                        selected: null,
                    };
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

                    selectVariant(result) {
                        this.selected = result;
                        this.searchTerm = '';
                        this.results = [];
                        this.showDropdown = false;
                    },

                    clearSelection() {
                        this.selected = null;
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
