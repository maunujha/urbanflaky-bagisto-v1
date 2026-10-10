<x-admin::layouts>
    <x-slot:title>
        Choose courier · Order #{{ $order->increment_id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Choose courier for order #{{ $order->increment_id }}
            </p>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Rates are Shiprocket's quote for this parcel ({{ $shipment->is_cod ? 'COD' : 'prepaid' }}).
                @if ($shipment->hasAwb())
                    The current AWB {{ $shipment->awb_code }} ({{ $shipment->courier_name }}) is released when you pick another courier.
                @endif
            </p>
        </div>

        <a href="{{ route('admin.sales.orders.view', $order->id) }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            Back to order
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('admin.sales.orders.shiprocket.assign', $order->id) }}"
        class="mt-5 box-shadow rounded bg-white p-4 dark:bg-gray-900"
    >
        @csrf

        @if (empty($couriers))
            <p class="text-sm text-gray-600 dark:text-gray-300">
                No courier serves this pincode for this parcel right now.
            </p>
        @else
            <div class="grid gap-2">
                @foreach ($couriers as $courier)
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-md border p-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                        <span class="flex items-center gap-3">
                            <input
                                type="radio"
                                name="courier_id"
                                value="{{ $courier['id'] }}"
                                @checked($courier['id'] === (string) $shipment->courier_company_id || ($loop->first && ! $shipment->courier_company_id))
                                required
                            >

                            <span>
                                <span class="block font-semibold text-gray-800 dark:text-white">
                                    {{ $courier['name'] }}

                                    @if ($courier['recommended'])
                                        <span class="label-active ml-1">Recommended</span>
                                    @endif

                                    @if ($courier['id'] === (string) $shipment->courier_company_id)
                                        <span class="label-info ml-1">Current</span>
                                    @endif
                                </span>

                                <span class="block text-xs text-gray-600 dark:text-gray-300">
                                    {{ $courier['etd'] ? 'Delivery by '.\Carbon\Carbon::parse($courier['etd'])->format('D, d M') : '' }}
                                    @if ($courier['days']) · {{ $courier['days'] }} days @endif
                                    @if ($courier['rating']) · rated {{ $courier['rating'] }}/5 @endif
                                    @if (! $courier['cod']) · no COD @endif
                                </span>
                            </span>
                        </span>

                        <span class="font-semibold text-gray-800 dark:text-white">₹{{ number_format($courier['rate'], 2) }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="primary-button">Assign selected courier</button>
            </div>
        @endif
    </form>
</x-admin::layouts>
