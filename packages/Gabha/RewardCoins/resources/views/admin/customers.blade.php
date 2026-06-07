<x-admin::layouts>
    <x-slot:title>
        @lang('reward-coins::reward_coins.admin.customers.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('reward-coins::reward_coins.admin.customers.title')
        </p>

        <a href="{{ route('admin.reward_coins.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            @lang('reward-coins::reward_coins.admin.dashboard.title')
        </a>
    </div>

    {{-- Filter tabs --}}
    @php
        $tabs = [
            'with_coins' => trans('reward-coins::reward_coins.admin.customers.tab-with-coins'),
            'pending'    => trans('reward-coins::reward_coins.admin.customers.tab-pending'),
        ];
    @endphp

    <div class="mt-5 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a
                href="{{ route('admin.reward_coins.customers', ['filter' => $key]) }}"
                class="rounded-md px-4 py-2 text-sm font-semibold"
                @if ($filter === $key)
                    style="background-color: #c7eb31; color: #0a0a0a;"
                @else
                    style="background-color: rgba(0,0,0,.05); color: #6b7280;"
                @endif
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-5 box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left" style="min-width: 680px;">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-800">
                        <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.admin.customers.customer')</th>
                        <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.general.balance')</th>
                        <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.general.pending')</th>
                        <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.general.lifetime-earned')</th>
                        <th class="px-4 py-3 text-right font-medium">@lang('reward-coins::reward_coins.admin.customers.actions')</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($wallets as $wallet)
                        <tr class="border-b border-gray-100 text-sm dark:border-gray-800">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.reward_coins.customer', $wallet->customer_id) }}"
                                   class="font-semibold" style="color: #6b8e00;">
                                    {{ optional($wallet->customer)->first_name }} {{ optional($wallet->customer)->last_name }}
                                </a>
                                <div class="text-xs text-gray-400">
                                    {{ optional($wallet->customer)->email ?? '#'.$wallet->customer_id }}
                                </div>
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-white">
                                {{ number_format((int) $wallet->balance) }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($wallet->pending_balance > 0)
                                    <span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:600;background-color:#fef3c7;color:#92400e;">
                                        {{ number_format((int) $wallet->pending_balance) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ number_format((int) $wallet->lifetime_earned) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                @if ($wallet->pending_balance > 0)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.reward_coins.customer.approve', $wallet->customer_id) }}"
                                        style="display:inline;"
                                        onsubmit="return confirm('@lang('reward-coins::reward_coins.admin.customers.approve-confirm')')"
                                    >
                                        @csrf
                                        <button type="submit" class="primary-button">
                                            @lang('reward-coins::reward_coins.admin.customers.approve')
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.reward_coins.customer', $wallet->customer_id) }}"
                                       class="text-sm text-gray-500 underline">
                                        @lang('reward-coins::reward_coins.admin.customers.view')
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">
                                @lang('reward-coins::reward_coins.admin.customers.empty')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($wallets->hasPages())
            <div class="mt-4">
                {{ $wallets->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>
