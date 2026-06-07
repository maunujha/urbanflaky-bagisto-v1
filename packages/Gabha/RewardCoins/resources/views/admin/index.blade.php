<x-admin::layouts>
    <x-slot:title>
        @lang('reward-coins::reward_coins.admin.dashboard.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('reward-coins::reward_coins.admin.dashboard.title')
        </p>

        <a href="{{ route('admin.reward_coins.settings') }}" class="primary-button">
            @lang('reward-coins::reward_coins.admin.dashboard.manage-settings')
        </a>
    </div>

    @php
        $cards = [
            ['label' => trans('reward-coins::reward_coins.admin.dashboard.coins-in-circulation'), 'value' => $stats['coins_in_circulation'], 'accent' => true],
            ['label' => trans('reward-coins::reward_coins.admin.dashboard.pending-coins'),        'value' => $stats['pending_coins'],        'accent' => false, 'url' => route('admin.reward_coins.customers', ['filter' => 'pending'])],
            ['label' => trans('reward-coins::reward_coins.admin.dashboard.customers-with-coins'), 'value' => $stats['customers_with_coins'], 'accent' => false, 'url' => route('admin.reward_coins.customers', ['filter' => 'with_coins'])],
            ['label' => trans('reward-coins::reward_coins.admin.dashboard.redeemed-today'),       'value' => $stats['redeemed_today'],       'accent' => false],
            ['label' => trans('reward-coins::reward_coins.admin.dashboard.redeemed-month'),       'value' => $stats['redeemed_month'],       'accent' => false],
        ];
    @endphp

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            @php $clickable = ! empty($card['url']); @endphp

            <{{ $clickable ? 'a' : 'div' }}
                @if ($clickable) href="{{ $card['url'] }}" @endif
                class="box-shadow block rounded-lg bg-white p-5 dark:bg-gray-900 @if ($clickable) transition hover:shadow-lg @endif"
                @if ($card['accent']) style="border-left: 4px solid #c7eb31;" @endif
            >
                <p class="text-sm text-gray-500 dark:text-gray-300">{{ $card['label'] }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format((int) $card['value']) }}</p>

                @if ($clickable)
                    <p class="mt-2 text-xs font-semibold" style="color: #8bbf00;">
                        @lang('reward-coins::reward_coins.admin.dashboard.view-list') &rarr;
                    </p>
                @endif
            </{{ $clickable ? 'a' : 'div' }}>
        @endforeach
    </div>
</x-admin::layouts>
