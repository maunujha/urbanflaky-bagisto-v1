<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Http\Controllers\Admin;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Models\CustomerCoinWallet;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * Back-office screens for the coin program: overview dashboard, settings form,
 * and per-customer detail with a manual grant/deduct action.
 */
class CoinAdminController extends Controller
{
    public function __construct(
        private readonly CoinTransactionRepositoryInterface $transactions,
        private readonly CoinWalletRepositoryInterface $wallets,
        private readonly CoinWalletService $walletService,
    ) {
    }

    /**
     * Program overview with circulation/redemption reporting aggregates.
     *
     * (These are read-only reporting roll-ups, computed inline rather than
     * through the transactional repositories.)
     *
     * @return View
     */
    public function index(): View
    {
        $startOfMonth = now()->startOfMonth();

        $stats = [
            'coins_in_circulation' => (int) CustomerCoinWallet::query()->sum('balance'),
            'pending_coins'        => (int) CustomerCoinWallet::query()->sum('pending_balance'),
            'customers_with_coins' => (int) CustomerCoinWallet::query()->where('balance', '>', 0)->count(),
            'redeemed_today'       => (int) CoinTransaction::query()
                ->where('type', TransactionType::Redeemed->value)
                ->whereDate('created_at', today())
                ->sum('amount'),
            'redeemed_month'       => (int) CoinTransaction::query()
                ->where('type', TransactionType::Redeemed->value)
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
        ];

        return view('reward-coins::admin.index', compact('stats'));
    }

    /**
     * Settings form.
     *
     * @return View
     */
    public function settings(): View
    {
        return view('reward-coins::admin.settings', [
            'settings' => CoinSetting::active(),
        ]);
    }

    /**
     * Persist the settings form and bust the settings cache.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'earning_rate'              => 'required|numeric|min:0.01',
            'coins_per_unit'            => 'required|integer|min:1',
            'min_order_amount'          => 'required|numeric|min:0',
            'max_redemption_per_order'  => 'required|numeric|min:0',
            'max_redemption_percent'    => 'required|integer|min:0|max:100',
            'expiry_days'               => 'required|integer|min:1',
            'pending_confirmation_days' => 'required|integer|min:0',
        ]);

        $data['exclude_discounted_items'] = $request->boolean('exclude_discounted_items');
        $data['is_active']                = $request->boolean('is_active');

        $settings = CoinSetting::query()->firstOrNew([]);
        $settings->fill($data)->save();

        CoinSetting::flushCache();

        session()->flash('success', trans('reward-coins::reward_coins.admin.settings.updated'));

        return redirect()->route('admin.reward_coins.settings');
    }

    /**
     * List customers holding coins, filtered to spendable or pending balances.
     *
     * Drives the two clickable dashboard cards: `with_coins` (spendable balance)
     * and `pending` (awaiting approval).
     *
     * @param  Request  $request
     * @return View
     */
    public function customers(Request $request): View
    {
        $filter = $request->query('filter') === 'pending' ? 'pending' : 'with_coins';

        $column = $filter === 'pending' ? 'pending_balance' : 'balance';

        $wallets = CustomerCoinWallet::query()
            ->with('customer')
            ->where($column, '>', 0)
            ->orderByDesc($column)
            ->paginate(20)
            ->withQueryString();

        return view('reward-coins::admin.customers', compact('wallets', 'filter'));
    }

    /**
     * Approve (confirm) all of a customer's pending coins, moving them to the
     * spendable balance without waiting for the order to reach `completed`.
     *
     * @param  int  $id  Customer id.
     * @return RedirectResponse
     */
    public function approveCoins(int $id): RedirectResponse
    {
        try {
            $confirmed = $this->walletService->confirmAllForCustomer($id);

            $confirmed > 0
                ? session()->flash('success', trans('reward-coins::reward_coins.admin.customers.approved', ['coins' => $confirmed]))
                : session()->flash('info', trans('reward-coins::reward_coins.admin.customers.nothing-pending'));
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.reward_coins.customers', ['filter' => 'pending']);
    }

    /**
     * A single customer's wallet and ledger, with the manual grant form.
     *
     * @param  int  $id  Customer id.
     * @return View
     */
    public function customerDetail(int $id): View
    {
        return view('reward-coins::admin.customer-detail', [
            'customerId'   => $id,
            'wallet'       => $this->wallets->getOrCreate($id),
            'transactions' => $this->transactions->getForCustomer($id),
        ]);
    }

    /**
     * Manually add or deduct coins for a customer.
     *
     * @param  int  $id  Customer id.
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function grantCoins(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'action' => 'required|in:add,deduct',
            'note'   => 'nullable|string|max:255',
        ]);

        $note = $data['note'] ?: trans('reward-coins::reward_coins.admin.grant.default-note');

        try {
            $data['action'] === 'add'
                ? $this->walletService->credit($id, (int) $data['amount'], TransactionType::Adjusted, null, $note, TransactionStatus::Confirmed)
                : $this->walletService->debit($id, (int) $data['amount'], TransactionType::Adjusted, null, $note, TransactionStatus::Confirmed);

            session()->flash('success', trans('reward-coins::reward_coins.admin.grant.success'));
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.reward_coins.customer', $id);
    }
}
