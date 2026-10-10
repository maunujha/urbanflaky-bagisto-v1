@php
    use App\Services\Shiprocket\ShipmentStage;

    $shipment = \App\Models\ShiprocketOrder::with(['events' => fn ($q) => $q->limit(8)])
        ->where('order_id', $order->id)
        ->first();

    $stage = $shipment?->status;

    $badge = match (true) {
        $stage === null                                         => 'label-pending',
        $stage === ShipmentStage::PUSH_FAILED,
        $stage === ShipmentStage::LOST,
        $stage === ShipmentStage::UNDELIVERED                   => 'label-canceled',
        $stage === ShipmentStage::DELIVERED                     => 'label-active',
        in_array($stage, [ShipmentStage::CANCELED, ShipmentStage::RTO_INITIATED, ShipmentStage::RTO_DELIVERED], true) => 'label-closed',
        default                                                 => 'label-processing',
    };

    $badgeText = match ($stage) {
        null                       => 'Not sent',
        ShipmentStage::PUSH_FAILED => 'Push failed',
        default                    => $shipment->stageLabel(),
    };

    $canPush      = ! $shipment?->isPushed() && app(\App\Services\Shiprocket\ShipmentService::class)->shouldPush($order);
    $beforePickup = $shipment?->isPushed() && $shipment->isBeforePickup();
@endphp

<x-admin::accordion>
    <x-slot:header>
        <div class="flex w-full items-center justify-between gap-2 p-2.5">
            <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                Shiprocket
            </p>

            <span class="{{ $badge }}">{{ $badgeText }}</span>
        </div>
    </x-slot>

    <x-slot:content v-pre>
        <div class="flex flex-col gap-3 text-sm">
            @if ($shipment?->isPushed())
                <div class="grid gap-1.5 text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between gap-2">
                        <span>Shiprocket order</span>

                        <a
                            href="https://app.shiprocket.in/seller/orders/details/{{ $shipment->shiprocket_order_id }}"
                            target="_blank"
                            rel="noopener"
                            class="font-semibold text-blue-600 hover:underline"
                        >#{{ $shipment->shiprocket_order_id }}</a>
                    </div>

                    <div class="flex justify-between gap-2">
                        <span>Payment</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $shipment->is_cod ? 'COD' : 'Prepaid' }}</span>
                    </div>

                    <div class="flex justify-between gap-2">
                        <span>Courier</span>
                        <span class="text-right font-semibold text-gray-800 dark:text-white">{{ $shipment->courier_name ?: 'Not assigned' }}</span>
                    </div>

                    @if ($shipment->hasAwb())
                        <div class="flex justify-between gap-2">
                            <span>AWB</span>

                            <a
                                href="{{ $shipment->tracking_url }}"
                                target="_blank"
                                rel="noopener"
                                class="break-all font-mono font-semibold text-blue-600 hover:underline"
                            >{{ $shipment->awb_code }}</a>
                        </div>
                    @endif

                    @if ($shipment->current_status)
                        <div class="flex justify-between gap-2">
                            <span>Courier status</span>
                            <span class="text-right">{{ $shipment->current_status }}</span>
                        </div>
                    @endif

                    @if ($shipment->etd && ShipmentStage::isActive($stage))
                        <div class="flex justify-between gap-2">
                            <span>Expected delivery</span>
                            <span>{{ $shipment->etd->format('D, d M') }}</span>
                        </div>
                    @endif

                    @if ($shipment->pickup_scheduled_at)
                        <div class="flex justify-between gap-2">
                            <span>Pickup booked</span>
                            <span>{{ $shipment->pickup_scheduled_at->format('d M, h:i A') }}</span>
                        </div>
                    @endif

                    @if ($shipment->delivered_at)
                        <div class="flex justify-between gap-2">
                            <span>Delivered</span>
                            <span>{{ $shipment->delivered_at->format('d M, h:i A') }}</span>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-300">
                    @if ($stage === ShipmentStage::PUSH_FAILED)
                        Shiprocket did not accept this order after {{ $shipment->push_attempts }} attempts. Fix the cause below, then send it again.
                    @elseif ($canPush)
                        This order is not in Shiprocket yet.
                    @else
                        Nothing to send: this order is cancelled, awaiting payment, or already shipped another way.
                    @endif
                </p>
            @endif

            @if ($shipment?->last_error)
                <div class="rounded-md border border-red-200 bg-red-50 p-2.5 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                    <p class="font-semibold">Last error · {{ $shipment->last_error_at?->diffForHumans() }}</p>
                    <p class="mt-1 break-words">{{ $shipment->last_error }}</p>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-1.5">
                @if ($canPush)
                    <form method="POST" action="{{ route('admin.sales.orders.shiprocket.push', $order->id) }}">
                        @csrf
                        <button type="submit" class="primary-button">{{ $stage === ShipmentStage::PUSH_FAILED ? 'Retry push' : 'Push to Shiprocket' }}</button>
                    </form>
                @endif

                @if ($beforePickup && ! $shipment->hasAwb())
                    <form method="POST" action="{{ route('admin.sales.orders.shiprocket.assign', $order->id) }}">
                        @csrf
                        <button type="submit" class="primary-button">Assign recommended courier</button>
                    </form>
                @endif

                @if ($beforePickup)
                    <a href="{{ route('admin.sales.orders.shiprocket.couriers', $order->id) }}" class="secondary-button">
                        {{ $shipment->hasAwb() ? 'Change courier' : 'Choose courier' }}
                    </a>
                @endif

                @if ($beforePickup && $shipment->hasAwb() && ! $shipment->pickup_scheduled_at)
                    <form method="POST" action="{{ route('admin.sales.orders.shiprocket.pickup', $order->id) }}">
                        @csrf
                        <button type="submit" class="primary-button">Book pickup</button>
                    </form>
                @endif

                @if ($shipment?->hasAwb())
                    <a href="{{ route('admin.sales.orders.shiprocket.document', [$order->id, 'label']) }}" target="_blank" class="secondary-button">Label</a>
                    <a href="{{ route('admin.sales.orders.shiprocket.document', [$order->id, 'manifest']) }}" target="_blank" class="secondary-button">Manifest</a>
                @endif

                @if ($shipment?->isPushed())
                    <a href="{{ route('admin.sales.orders.shiprocket.document', [$order->id, 'invoice']) }}" target="_blank" class="secondary-button">Invoice</a>
                @endif

                @if ($shipment?->hasAwb() && ShipmentStage::isActive($stage))
                    <form method="POST" action="{{ route('admin.sales.orders.shiprocket.refresh', $order->id) }}">
                        @csrf
                        <button type="submit" class="secondary-button">Refresh tracking</button>
                    </form>
                @endif

                @if ($beforePickup)
                    <form
                        method="POST"
                        action="{{ route('admin.sales.orders.shiprocket.cancel', $order->id) }}"
                        onsubmit="return confirm('Cancel this shipment in Shiprocket? The Bagisto order is not changed.');"
                    >
                        @csrf
                        <button type="submit" class="secondary-button text-red-600">Cancel shipment</button>
                    </form>
                @endif
            </div>

            {{-- History --}}
            @if ($shipment?->events->isNotEmpty())
                <div class="border-t pt-3 dark:border-gray-800">
                    <p class="mb-2 text-xs font-semibold uppercase text-gray-500">History</p>

                    <ol class="grid gap-2">
                        @foreach ($shipment->events as $event)
                            <li class="text-xs text-gray-600 dark:text-gray-300">
                                <p class="font-semibold text-gray-800 dark:text-white">{{ $event->activity }}</p>
                                <p>
                                    {{ $event->event_at?->format('d M, h:i A') }}@if ($event->location) · {{ $event->location }}@endif
                                    · {{ $event->source }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </x-slot>
</x-admin::accordion>
