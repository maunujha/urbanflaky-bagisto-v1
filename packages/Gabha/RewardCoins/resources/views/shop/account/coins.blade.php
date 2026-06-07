<x-shop::layouts.account>
    <x-slot:title>
        @lang('reward-coins::reward_coins.account.title')
    </x-slot>

    {{-- Account sidebar navigation --}}
    <x-shop::layouts.account.navigation />

    <div class="flex-1">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">
                @lang('reward-coins::reward_coins.account.title')
            </h1>

            <x-reward-coins::coin-badge :balance="$wallet->balance" size="md" />
        </div>

        {{-- Summary cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 p-5" style="border-left: 4px solid #c7eb31;">
                <p class="text-sm text-gray-500">@lang('reward-coins::reward_coins.account.available-balance')</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format((int) $wallet->balance) }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 p-5">
                <p class="text-sm text-gray-500">@lang('reward-coins::reward_coins.account.pending')</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format((int) $wallet->pending_balance) }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 p-5">
                <p class="text-sm text-gray-500">@lang('reward-coins::reward_coins.account.lifetime-earned')</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format((int) $wallet->lifetime_earned) }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 p-5">
                <p class="text-sm text-gray-500">@lang('reward-coins::reward_coins.account.expiring-soon')</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format((int) $expiringSoon) }}</p>
                <p class="text-xs text-gray-400">@lang('reward-coins::reward_coins.account.expiring-note')</p>
            </div>
        </div>

        {{-- Transaction history --}}
        <div class="mt-8">
            <h2 class="text-lg font-semibold">@lang('reward-coins::reward_coins.account.history')</h2>

            <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full min-w-[560px] text-left">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                            <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.account.date')</th>
                            <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.account.amount')</th>
                            <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.account.note')</th>
                            <th class="px-4 py-3 font-medium">@lang('reward-coins::reward_coins.account.status')</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $transaction)
                            <x-reward-coins::transaction-row :transaction="$transaction" />
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">
                                    @lang('reward-coins::reward_coins.account.empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        {{-- How to earn --}}
        <div class="mt-8 rounded-lg p-5" style="background-color: rgba(199, 235, 49, 0.12);">
            <h2 class="text-lg font-semibold">@lang('reward-coins::reward_coins.account.how-to-earn')</h2>
            <p class="mt-2 text-sm text-gray-600">@lang('reward-coins::reward_coins.account.how-to-earn-body')</p>
        </div>
    </div>
</x-shop::layouts.account>
