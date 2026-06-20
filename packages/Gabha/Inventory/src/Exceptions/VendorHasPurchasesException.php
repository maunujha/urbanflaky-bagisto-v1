<?php

declare(strict_types=1);

namespace Gabha\Inventory\Exceptions;

use Exception;

/**
 * Thrown when a vendor that still has linked purchase records is deleted.
 *
 * Lets the service layer enforce the "no delete while purchases exist" business
 * rule and lets the controller translate the failure into an HTTP response
 * without leaking persistence concerns.
 */
class VendorHasPurchasesException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'The vendor cannot be deleted because purchase records exist.';
}
