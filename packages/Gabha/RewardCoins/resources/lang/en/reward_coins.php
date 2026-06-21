<?php

return [
    'general' => [
        'coins'             => 'Coins',
        'reward-coins'      => 'Reward Coins',
        'balance'           => 'Balance',
        'pending'           => 'Pending',
        'lifetime-earned'   => 'Lifetime earned',
        'lifetime-redeemed' => 'Lifetime redeemed',
    ],

    'transaction' => [
        'types' => [
            'earned'   => 'Earned',
            'redeemed' => 'Redeemed',
            'expired'  => 'Expired',
            'adjusted' => 'Adjusted',
            'reversed' => 'Reversed',
            'revoked'  => 'Revoked (Refund)',
            'refunded' => 'Restored (Refund)',
        ],

        'statuses' => [
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'expired'   => 'Expired',
            'cancelled' => 'Cancelled',
            'reversed'  => 'Reversed',
        ],
    ],

    'account' => [
        'title'             => 'My Coins',
        'available-balance' => 'Available Balance',
        'pending'           => 'Pending',
        'lifetime-earned'   => 'Lifetime Earned',
        'expiring-soon'     => 'Expiring Soon',
        'expiring-note'     => 'in the next 30 days',
        'history'           => 'Transaction History',
        'date'              => 'Date',
        'amount'            => 'Coins',
        'note'              => 'Details',
        'status'            => 'Status',
        'empty'             => 'You have no coin activity yet.',
        'available-when'    => 'Available :when',
        'how-to-earn'       => 'How to earn coins',
        'how-to-earn-body'  => 'Earn coins on every eligible order. Coins are confirmed after your order is delivered and the return window closes, then can be redeemed for discounts on future purchases.',
    ],

    'checkout' => [
        'available'  => 'You have :coins coins available.',
        'apply'      => 'Apply Coins',
        'remove'     => 'Remove coins',
        'applied'    => 'Applied :coins coins to your order.',
        'removed'    => 'Coins removed from your order.',
        'you-save'   => 'You save :amount with coins',
        'new-total'  => 'New order total: :amount',
        'max-hint'   => 'You can redeem up to :coins coins on this order.',
    ],

    'admin' => [
        'menu' => [
            'title' => 'Reward Coins',
        ],

        'dashboard' => [
            'title'                => 'Reward Coins',
            'coins-in-circulation' => 'Coins in Circulation',
            'pending-coins'        => 'Pending Coins',
            'customers-with-coins' => 'Customers with Coins',
            'redeemed-today'       => 'Redeemed Today',
            'redeemed-month'       => 'Redeemed This Month',
            'manage-settings'      => 'Manage Settings',
            'view-list'            => 'View list',
        ],

        'customers' => [
            'title'           => 'Customers with Coins',
            'tab-with-coins'  => 'With coins',
            'tab-pending'     => 'Pending approval',
            'customer'        => 'Customer',
            'actions'         => 'Actions',
            'approve'         => 'Approve coins',
            'approve-confirm' => 'Approve all pending coins for this customer? They become spendable immediately.',
            'approved'        => 'Approved :coins coins — now spendable.',
            'nothing-pending' => 'This customer has no pending coins to approve.',
            'view'            => 'View',
            'empty'           => 'No customers found for this view.',
        ],

        'settings' => [
            'title'                     => 'Coin Settings',
            'earning'                   => 'Earning',
            'redemption'                => 'Redemption',
            'lifecycle'                 => 'Lifecycle',
            'earning_rate'              => 'Earning rate (spend per unit)',
            'coins_per_unit'            => 'Coins per unit',
            'min_order_amount'          => 'Minimum order amount',
            'max_redemption_per_order'  => 'Max redemption per order',
            'max_redemption_percent'    => 'Max redemption percent',
            'expiry_days'               => 'Expiry (days)',
            'pending_confirmation_days' => 'Pending confirmation (days)',
            'exclude_discounted_items'  => 'Exclude discounted items',
            'is_active'                 => 'Program active',
            'save'                      => 'Save Settings',
            'updated'                   => 'Coin settings updated successfully.',
        ],

        'customer' => [
            'title'   => 'Customer Coins',
            'wallet'  => 'Wallet',
            'history' => 'Transaction History',
        ],

        'grant' => [
            'title'        => 'Manual Adjustment',
            'amount'       => 'Coins',
            'action'       => 'Action',
            'add'          => 'Add',
            'deduct'       => 'Deduct',
            'note'         => 'Note',
            'submit'       => 'Apply Adjustment',
            'default-note' => 'Manual admin adjustment',
            'success'      => 'Customer wallet adjusted successfully.',
        ],
    ],

    'errors' => [
        'disabled'           => 'The reward coins program is currently unavailable.',
        'insufficient-coins' => 'You do not have enough coins for this redemption.',
        'no-cart'            => 'Your cart is empty.',
        'generic'            => 'Something went wrong. Please try again.',
    ],

    'redeem' => [
        'feature-disabled'     => 'Coin redemption is currently unavailable.',
        'not-authenticated'    => 'Please log in to redeem coins.',
        'order-too-small'      => 'Coins can be redeemed on orders of :min or more. Add a little more to your cart to use them.',
        'no-coins'             => 'You do not have any coins available to redeem.',
        'insufficient-coins'   => 'You only have :balance coins available. Please lower the amount.',
        'exceeds-max-coverage' => 'You can use up to :coins coins on this order — that is :percent% of the order value (:amount).',
        'would-be-free'        => 'Coins cannot cover the entire order. You can use up to :coins coins here.',
    ],
];
