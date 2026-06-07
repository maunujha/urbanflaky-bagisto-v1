@props(['balance' => 0, 'size' => 'sm'])

@php
    $sizeClasses = [
        'sm' => 'px-3 py-1 text-sm',
        'md' => 'px-4 py-1.5 text-base',
        'lg' => 'px-5 py-2 text-lg',
    ][$size] ?? 'px-3 py-1 text-sm';
@endphp

{{-- Brand colours inlined (coins-yellow / coins-black) so the badge renders identically whether or not the package Tailwind config is compiled. --}}
<span
    {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full font-semibold leading-none $sizeClasses"]) }}
    style="background-color: #c7eb31; color: #000000;"
>
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <circle cx="12" cy="12" r="4"></circle>
    </svg>

    {{ number_format((int) $balance) }} {{ __('reward-coins::reward_coins.general.coins') }}
</span>
