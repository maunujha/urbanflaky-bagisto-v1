@props(['transaction'])

@php
    $isCredit = $transaction->type->isCredit();

    $statusClass = match ($transaction->status->value) {
        'confirmed' => 'bg-green-100 text-green-700',
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'expired'   => 'bg-gray-100 text-gray-600',
        'cancelled' => 'bg-red-100 text-red-600',
        default     => 'bg-gray-100 text-gray-600',
    };
@endphp

<tr class="border-b border-gray-100 transition-colors hover:bg-gray-50">
    <td class="px-4 py-3 text-sm text-gray-600">
        {{ $transaction->created_at?->format('d M Y') }}
    </td>

    <td class="px-4 py-3 text-sm whitespace-nowrap">
        <span class="font-semibold {{ $isCredit ? 'text-green-600' : 'text-red-500' }}">
            {{ $isCredit ? '+' : '-' }}{{ number_format((int) $transaction->amount) }}
        </span>
        <span class="ml-1 text-xs text-gray-400">{{ $transaction->type->label() }}</span>
    </td>

    <td class="px-4 py-3 text-sm text-gray-500">
        {{ $transaction->note }}
    </td>

    <td class="px-4 py-3">
        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
            {{ $transaction->status->label() }}
        </span>

        @if ($transaction->status->value === 'pending' && $transaction->available_at)
            <span class="mt-1 block text-xs text-gray-500">
                @lang('reward-coins::reward_coins.account.available-when', ['when' => $transaction->available_at->diffForHumans()])
            </span>
        @endif
    </td>
</tr>
