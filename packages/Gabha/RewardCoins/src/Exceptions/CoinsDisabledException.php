<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Exceptions;

use RuntimeException;

/**
 * Raised when a coin operation is attempted while the program is switched off
 * (master config flag or the per-store `is_active` setting).
 */
class CoinsDisabledException extends RuntimeException
{
    /**
     * Build the exception with the standard disabled-program message.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self('The reward coins program is currently disabled.');
    }
}
