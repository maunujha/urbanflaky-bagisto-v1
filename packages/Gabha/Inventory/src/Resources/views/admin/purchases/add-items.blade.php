@php
    $currencySymbol = core()->getBaseCurrency()->symbol ?? '';
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.purchases.add-items.title', ['number' => $purchase->purchase_number]) }}
    </x-slot>

    <x-admin::form
        :action="route('admin.inventory.purchases.add-items.store', $purchase->id)"
    >
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ trans('inventory::app.admin.purchases.add-items.title', ['number' => $purchase->purchase_number]) }}
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.inventory.purchases.view', $purchase->id) }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('inventory::app.admin.purchases.add-items.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('inventory::app.admin.purchases.add-items.save-btn')
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

        <div class="mt-3.5 flex flex-col gap-2.5">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="mb-4 grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.vendor')</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $purchase->vendor?->name ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.purchase-date')</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ core()->formatDate($purchase->purchase_date, 'd M Y') }}</p>
                    </div>
                </div>

                <span class="mb-4 block w-full border-b dark:border-gray-800"></span>

                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('inventory::app.admin.purchases.add-items.step-products')
                </p>

                <v-purchase-items currency="{{ $currencySymbol }}"></v-purchase-items>
            </div>
        </div>
    </x-admin::form>

    @include('inventory::admin.purchases.partials.items-component')
</x-admin::layouts>
