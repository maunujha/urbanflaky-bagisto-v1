<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Exceptions;

use DomainException;

/**
 * Raised when a redemption requests more coins than a wallet can spend.
 */
class InsufficientCoinsException extends DomainException
{
    /**
     * Build the exception from the requested vs. available balance.
     *
     * @param  int  $requested  Coins the customer attempted to redeem.
     * @param  int  $available  Coins actually spendable on the wallet.
     * @return self
     */
    public static function for(int $requested, int $available): self
    {
        return new self(sprintf(
            'Attempted to redeem %d coins but only %d are available.',
            $requested,
            $available
        ));
    }
}
