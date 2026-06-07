{{--
    Checkout coin-redemption widget (drop-in partial).

    Include where the custom checkout shows order summary:
        @include('reward-coins::shop.checkout.coins-widget')

    Self-contained: it resolves the customer's balance and the redeemable cap
    itself, renders nothing when the program is off / guest / nothing to redeem.
    Applying stages the coins server-side (session) and previews the rupee value
    with vanilla JS — no page reload, no Vite build required.
--}}
@php
    $rewardCustomer  = auth()->guard('customer')->user();
    $rewardActive    = $rewardCustomer && \Gabha\RewardCoins\Models\CoinSetting::isEnabled();
    $rewardBalance   = 0;
    $rewardRedeemable = 0;
    $rewardRupeePerCoin = (float) config('reward_coins.rupee_per_coin', 1);
    $rewardApplied   = (int) session('reward_coins.applied.coins', 0);

    if ($rewardActive) {
        $rewardRedemption = app(\Gabha\RewardCoins\Services\CoinRedemptionService::class);
        $rewardCart   = \Webkul\Checkout\Facades\Cart::getCart();
        $rewardTotal  = (float) ($rewardCart?->base_grand_total ?? 0);

        // Add back any coin discount already folded into the cart so the cap is
        // measured against the true pre-coin order value (mirrors the controller).
        $rewardEffective = (int) session(\Gabha\RewardCoins\Checkout\CoinDiscount::EFFECTIVE_KEY, 0);
        if ($rewardEffective > 0) {
            $rewardTotal += $rewardRedemption->getDiscountValue($rewardEffective);
        }

        $rewardBalance = app(\Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface::class)
            ->getBalance((int) $rewardCustomer->id);
        $rewardRedeemable = $rewardRedemption->getRedeemableCoins((int) $rewardCustomer->id, $rewardTotal);
    }
@endphp

@if ($rewardActive && $rewardRedeemable > 0)
    <div
        id="reward-coins-widget"
        class="rounded-lg border border-gray-200 p-4"
        data-rupee-per-coin="{{ $rewardRupeePerCoin }}"
        data-max-coins="{{ $rewardRedeemable }}"
        data-apply-url="{{ route('shop.checkout.coins.apply') }}"
        data-remove-url="{{ route('shop.checkout.coins.remove') }}"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="font-semibold">@lang('reward-coins::reward_coins.general.reward-coins')</p>
                <p class="text-xs text-gray-500">
                    @lang('reward-coins::reward_coins.checkout.available', ['coins' => number_format($rewardBalance)])
                </p>
            </div>

            <x-reward-coins::coin-badge :balance="$rewardBalance" />
        </div>

        <p class="mt-3 text-xs text-gray-500">
            @lang('reward-coins::reward_coins.checkout.max-hint', ['coins' => number_format($rewardRedeemable)])
        </p>

        <div class="mt-2 flex items-center gap-3">
            <input
                type="range"
                id="reward-coins-range"
                min="0"
                max="{{ $rewardRedeemable }}"
                value="{{ min($rewardApplied, $rewardRedeemable) }}"
                class="h-2 w-full cursor-pointer appearance-none rounded-full bg-gray-200"
                style="accent-color: #c7eb31;"
            >

            <input
                type="number"
                id="reward-coins-input"
                min="0"
                max="{{ $rewardRedeemable }}"
                value="{{ min($rewardApplied, $rewardRedeemable) }}"
                class="w-20 rounded-md border border-gray-300 px-2 py-1 text-sm"
            >
        </div>

        <p id="reward-coins-savings" class="mt-2 text-sm font-medium text-green-600"></p>

        <p id="reward-coins-total" class="text-xs text-gray-500 {{ $rewardApplied > 0 ? '' : 'hidden' }}"></p>

        <p id="reward-coins-error" class="mt-2 hidden text-sm font-medium text-red-600"></p>

        <div class="mt-3 flex items-center gap-3">
            <button
                type="button"
                id="reward-coins-apply"
                class="rounded-md px-4 py-2 text-sm font-semibold"
                style="background-color: #c7eb31; color: #000000;"
            >
                @lang('reward-coins::reward_coins.checkout.apply')
            </button>

            <button
                type="button"
                id="reward-coins-remove"
                class="text-sm text-gray-500 underline {{ $rewardApplied > 0 ? '' : 'hidden' }}"
            >
                @lang('reward-coins::reward_coins.checkout.remove')
            </button>
        </div>
    </div>

    <script>
        (function () {
            const widget = document.getElementById('reward-coins-widget');

            if (! widget) {
                return;
            }

            const rupeePerCoin = parseFloat(widget.dataset.rupeePerCoin) || 1;
            let   maxCoins     = parseInt(widget.dataset.maxCoins, 10) || 0;
            const csrf         = '{{ csrf_token() }}';

            const range    = document.getElementById('reward-coins-range');
            const input    = document.getElementById('reward-coins-input');
            const savings  = document.getElementById('reward-coins-savings');
            const total    = document.getElementById('reward-coins-total');
            const error    = document.getElementById('reward-coins-error');
            const applyBtn = document.getElementById('reward-coins-apply');
            const removeBtn = document.getElementById('reward-coins-remove');

            const clamp = (value) => Math.max(0, Math.min(maxCoins, parseInt(value || 0, 10) || 0));

            const formatRupees = (amount) =>
                '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function renderSavings() {
                const coins = clamp(input.value);
                savings.textContent = coins > 0
                    ? '{{ __('reward-coins::reward_coins.checkout.you-save', ['amount' => 'AMT']) }}'.replace('AMT', formatRupees(coins * rupeePerCoin))
                    : '';
            }

            function sync(value) {
                const coins = clamp(value);
                range.value = coins;
                input.value = coins;
                renderSavings();
            }

            function post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: body ? JSON.stringify(body) : null,
                });
            }

            range.addEventListener('input', (e) => sync(e.target.value));
            input.addEventListener('input', (e) => sync(e.target.value));

            const showError = (message) => {
                error.textContent = message || '';
                error.classList.toggle('hidden', ! message);
            };

            // Re-cap the slider/input to a server-provided redeemable maximum and
            // clamp the current selection down to it.
            const applyServerMax = (max) => {
                if (max === undefined || max === null || max < 0) {
                    return;
                }

                maxCoins  = max;
                range.max = max;
                input.max = max;
                sync(max);
            };

            applyBtn.addEventListener('click', function () {
                const coins = clamp(input.value);

                if (coins <= 0) {
                    return;
                }

                applyBtn.disabled = true;
                showError('');

                post(widget.dataset.applyUrl, { coins: coins })
                    .then((res) => res.json())
                    .then((data) => {
                        if (! data.success) {
                            // When the server reports a cap, re-clamp the control so
                            // the customer can immediately retry within the limit.
                            if (data.error_code === 'exceeds_max_coverage' || data.error_code === 'order_would_be_free') {
                                applyServerMax(data.max_coins);
                            }

                            showError(data.message);

                            return;
                        }

                        // Reflect the server-clamped count and confirmed saving.
                        sync(data.coins);

                        savings.textContent =
                            '{{ __('reward-coins::reward_coins.checkout.you-save', ['amount' => 'AMT']) }}'
                                .replace('AMT', data.discount);

                        total.textContent = '{{ __('reward-coins::reward_coins.checkout.new-total', ['amount' => 'AMT']) }}'
                            .replace('AMT', data.newTotal);
                        total.classList.remove('hidden');

                        removeBtn.classList.remove('hidden');
                    })
                    .catch(() => showError('{{ __('reward-coins::reward_coins.errors.generic') }}'))
                    .finally(() => { applyBtn.disabled = false; });
            });

            removeBtn.addEventListener('click', function () {
                showError('');

                post(widget.dataset.removeUrl)
                    .then((res) => res.json())
                    .then(() => {
                        sync(0);
                        total.textContent = '';
                        total.classList.add('hidden');
                        removeBtn.classList.add('hidden');
                    });
            });

            renderSavings();
        })();
    </script>
@endif
