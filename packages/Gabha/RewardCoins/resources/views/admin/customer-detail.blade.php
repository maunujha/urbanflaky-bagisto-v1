<x-admin::layouts>
    <x-slot:title>
        @lang('reward-coins::reward_coins.admin.customer.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('reward-coins::reward_coins.admin.customer.title') #{{ $customerId }}
        </p>

        <a href="{{ route('admin.reward_coins.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            @lang('reward-coins::reward_coins.admin.dashboard.title')
        </a>
    </div>

    {{-- Wallet summary --}}
    @php
        $walletCards = [
            ['label' => trans('reward-coins::reward_coins.general.balance'),           'value' => $wallet->balance],
            ['label' => trans('reward-coins::reward_coins.general.pending'),           'value' => $wallet->pending_balance],
            ['label' => trans('reward-coins::reward_coins.general.lifetime-earned'),   'value' => $wallet->lifetime_earned],
            ['label' => trans('reward-coins::reward_coins.general.lifetime-redeemed'), 'value' => $wallet->lifetime_redeemed],
        ];
    @endphp

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($walletCards as $card)
            <div class="box-shadow rounded-lg bg-white p-5 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-300">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format((int) $card['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Manual adjustment --}}
    <div class="mt-6 box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
        <p class="mb-3 text-lg font-semibold text-gray-800 dark:text-white">
            @lang('reward-coins::reward_coins.admin.grant.title')
        </p>

        <form method="POST" action="{{ route('admin.reward_coins.customer.grant', $customerId) }}">
            @csrf

            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm text-gray-600 dark:text-gray-300">@lang('reward-coins::reward_coins.admin.grant.amount')</label>
                    <input type="number" name="amount" min="1" required class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:bg-gray-800 dark:text-white" />
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-600 dark:text-gray-300">@lang('reward-coins::reward_coins.admin.grant.action')</label>
                    <select name="action" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:bg-gray-800 dark:text-white">
                        <option value="add">@lang('reward-coins::reward_coins.admin.grant.add')</option>
                        <option value="deduct">@lang('reward-coins::reward_coins.admin.grant.deduct')</option>
                    </select>
                </div>

                <div class="flex-1" style="min-width: 200px;">
                    <label class="mb-1 block text-sm text-gray-600 dark:text-gray-300">@lang('reward-coins::reward_coins.admin.grant.note')</label>
                    <input type="text" name="note" maxlength="255" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:bg-gray-800 dark:text-white" />
                </div>

                <button type="submit" class="primary-button">
                    @lang('reward-coins::reward_coins.admin.grant.submit')
                </button>
            </div>
        </form>
    </div>

    {{-- History --}}
    <div class="mt-6 box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
        <p class="mb-3 text-lg font-semibold text-gray-800 dark:text-white">
            @lang('reward-coins::reward_coins.admin.customer.history')
        </p>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left" style="min-width: 560px;">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-800">
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
</x-admin::layouts>
