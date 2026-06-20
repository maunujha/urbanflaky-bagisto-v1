@php
    use Gabha\Inventory\Support\VariantPresenter;

    $currencySymbol = core()->getBaseCurrency()->symbol ?? '';
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.purchases.view.title', ['number' => $purchase->purchase_number]) }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ trans('inventory::app.admin.purchases.view.title', ['number' => $purchase->purchase_number]) }}
        </p>

        <a
            href="{{ route('admin.inventory.purchases.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('inventory::app.admin.purchases.view.back-btn')
        </a>
    </div>

    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <!-- Left: items -->
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('inventory::app.admin.purchases.view.items')
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b dark:border-gray-800">
                                <th class="px-2.5 py-2.5 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.view.product-variant')
                                </th>
                                <th class="px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.view.quantity')
                                </th>
                                <th class="px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.view.unit-cost')
                                </th>
                                <th class="px-2.5 py-2.5 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('inventory::app.admin.purchases.view.line-total')
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($purchase->items as $item)
                                <tr class="border-b dark:border-gray-800">
                                    <td class="px-2.5 py-2.5 text-gray-800 dark:text-white">
                                        {{ $item->variant ? VariantPresenter::label($item->variant) : trans('inventory::app.admin.purchases.view.deleted-variant') }}
                                    </td>
                                    <td class="px-2.5 py-2.5 text-right text-gray-800 dark:text-white">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-2.5 py-2.5 text-right text-gray-800 dark:text-white">
                                        {{ core()->formatBasePrice($item->unit_cost) }}
                                    </td>
                                    <td class="px-2.5 py-2.5 text-right text-gray-800 dark:text-white">
                                        {{ core()->formatBasePrice($item->total_cost) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="font-semibold text-gray-800 dark:text-white">
                                <td class="px-2.5 py-2.5 text-right" colspan="1">
                                    @lang('inventory::app.admin.purchases.view.total-quantity')
                                </td>
                                <td class="px-2.5 py-2.5 text-right">
                                    {{ $purchase->total_quantity }}
                                </td>
                                <td class="px-2.5 py-2.5 text-right">
                                    @lang('inventory::app.admin.purchases.view.grand-total')
                                </td>
                                <td class="px-2.5 py-2.5 text-right">
                                    {{ core()->formatBasePrice($purchase->total_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: meta -->
        <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="grid gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.vendor')</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $purchase->vendor?->name ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.purchase-date')</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ core()->formatDate($purchase->purchase_date, 'd M Y') }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.invoice-number')</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $purchase->invoice_number ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.bill')</p>
                        @if ($purchase->bill_file)
                            <a
                                href="{{ route('admin.inventory.purchases.bill', $purchase->id) }}"
                                class="font-semibold text-blue-600 hover:underline"
                            >
                                @lang('inventory::app.admin.purchases.view.download-bill')
                            </a>
                        @else
                            <p class="font-semibold text-gray-800 dark:text-white">@lang('inventory::app.admin.purchases.view.no-bill')</p>
                        @endif
                    </div>

                    @if ($purchase->notes)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-300">@lang('inventory::app.admin.purchases.view.notes')</p>
                            <p class="text-gray-800 dark:text-white">{{ $purchase->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
