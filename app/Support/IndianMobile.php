<?php

namespace App\Support;

/**
 * The single definition of a valid Indian mobile number for OTP flows.
 *
 * Normalises a few honest input shapes (+91 / 91 / 0 prefixes, spaces,
 * dashes) to the bare 10 digits and rejects everything else — notably
 * padded inputs like "5555555555" + "9876543210", which a last-10-digits
 * comparison would wrongly accept.
 */
class IndianMobile
{
    public const PATTERN = '/^[6-9]\d{9}$/';

    /**
     * The normalised 10-digit number, or null when the input is not a valid
     * Indian mobile.
     */
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        /* Only separators humans type — any other character is a reject. */
        if ($value === '' || ! preg_match('/^\+?[\d\s\-]+$/', $value)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match(self::PATTERN, $digits) ? $digits : null;
    }

    public static function isValid(mixed $value): bool
    {
        return self::normalize($value) !== null;
    }

    /**
     * Non-reversible tag for logs — never write a raw phone number to logs.
     */
    public static function logTag(?string $phone): string
    {
        return $phone ? 'ph_'.substr(hash_hmac('sha256', $phone, (string) config('app.key')), 0, 12) : 'ph_none';
    }
}
