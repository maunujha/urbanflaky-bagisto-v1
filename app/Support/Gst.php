<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Indian GST helper.
 *
 * Bagisto's tax engine applies a single tax rate per item (a flat 5% "GST 5%"
 * rate for apparel). Indian GST law requires that rate to be presented as
 * CGST + SGST for intra-state supply (buyer in the seller's state) and as a
 * single IGST line for inter-state supply. CGST and SGST are, by definition,
 * each exactly half of the total GST, so the split below is a presentation of
 * the amount the engine already calculated — it never changes the total tax.
 */
class Gst
{
    /**
     * Indian state (2-letter code) => GST state numeric code.
     */
    public const STATE_GST_CODES = [
        'JK' => '01', 'HP' => '02', 'PB' => '03', 'CH' => '04', 'UT' => '05',
        'HR' => '06', 'DL' => '07', 'RJ' => '08', 'UP' => '09', 'BR' => '10',
        'SK' => '11', 'AR' => '12', 'NL' => '13', 'MN' => '14', 'MZ' => '15',
        'TR' => '16', 'ML' => '17', 'AS' => '18', 'WB' => '19', 'JH' => '20',
        'OR' => '21', 'CT' => '22', 'MP' => '23', 'GJ' => '24', 'DD' => '25',
        'DN' => '26', 'MH' => '27', 'KA' => '29', 'GA' => '30', 'LD' => '31',
        'KL' => '32', 'TN' => '33', 'PY' => '34', 'AN' => '35', 'TG' => '36',
        'AP' => '37', 'LA' => '38',
    ];

    /**
     * Seller's state code (place of supply origin). Defaults to Rajasthan.
     */
    public static function sellerState(): string
    {
        return strtoupper((string) (core()->getConfigData('sales.shipping.origin.state') ?: 'RJ'));
    }

    /**
     * GST numeric state code for a 2-letter state code, e.g. "RJ" => "08".
     */
    public static function gstStateCode(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return self::STATE_GST_CODES[strtoupper($code)] ?? null;
    }

    /**
     * Seller's GST numeric state code. Prefers the first two digits of the
     * configured GSTIN, then falls back to the seller state map.
     */
    public static function sellerGstStateCode(): ?string
    {
        $gstin = (string) core()->getConfigData('sales.shipping.origin.vat_number');

        if (strlen($gstin) >= 2 && ctype_digit(substr($gstin, 0, 2))) {
            return substr($gstin, 0, 2);
        }

        return self::gstStateCode(self::sellerState());
    }

    /**
     * Full state name for a state code (cached DB lookup), e.g. "RJ" => "Rajasthan".
     */
    public static function stateName(?string $code, string $country = 'IN'): ?string
    {
        if (! $code) {
            return null;
        }

        static $cache = [];

        $key = $country.'|'.$code;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = DB::table('country_states')
                ->where('country_code', $country)
                ->where('code', $code)
                ->value('default_name');
        }

        return $cache[$key] ?: $code;
    }

    /**
     * Is the GST breakup display enabled in admin?
     */
    public static function showBreakup(): bool
    {
        return (bool) core()->getConfigData('sales.taxes.gst.show_breakup');
    }

    /**
     * Default HSN/SAC code printed on the invoice.
     */
    public static function hsnCode(): string
    {
        return (string) (core()->getConfigData('sales.taxes.gst.hsn_code') ?: '');
    }

    /**
     * Intra-state supply = same country (IN) and same state as the seller.
     */
    public static function isIntraState(?string $state, ?string $country = 'IN'): bool
    {
        if (strtoupper((string) $country) !== 'IN') {
            return false;
        }

        return $state !== null
            && $state !== ''
            && strtoupper((string) $state) === self::sellerState();
    }

    /**
     * Build the GST breakup for a tax amount given the place of supply.
     *
     * @return array<int, array{code: string, label: string, percent: float|null, amount: float}>
     */
    public static function breakup(float $taxAmount, float $taxableValue, ?string $state, ?string $country = 'IN'): array
    {
        $taxAmount = round($taxAmount, 2);

        if ($taxAmount <= 0) {
            return [];
        }

        $rate = $taxableValue > 0
            ? round(($taxAmount / $taxableValue) * 100, 2)
            : null;

        /**
         * No place of supply yet (e.g. cart before an address is set) or a
         * non-Indian destination: show a single neutral GST line.
         */
        if (empty($state) || strtoupper((string) $country) !== 'IN') {
            return [[
                'code'    => 'gst',
                'label'   => 'GST',
                'percent' => $rate,
                'amount'  => $taxAmount,
            ]];
        }

        if (self::isIntraState($state, $country)) {
            $half     = round($taxAmount / 2, 2);
            $halfRate = $rate !== null ? round($rate / 2, 2) : null;

            return [
                [
                    'code'    => 'cgst',
                    'label'   => 'CGST',
                    'percent' => $halfRate,
                    'amount'  => $half,
                ],
                [
                    'code'    => 'sgst',
                    'label'   => 'SGST',
                    'percent' => $halfRate,
                    'amount'  => round($taxAmount - $half, 2), // absorbs any rounding remainder
                ],
            ];
        }

        return [[
            'code'    => 'igst',
            'label'   => 'IGST',
            'percent' => $rate,
            'amount'  => $taxAmount,
        ]];
    }

    /**
     * Human label for a breakup line, e.g. "CGST (2.5%)".
     */
    public static function label(array $line): string
    {
        if (! isset($line['percent']) || $line['percent'] === null) {
            return $line['label'];
        }

        // Cast to float so trailing zeros are dropped: 2.5 -> "2.5", 5.0 -> "5".
        return $line['label'].' ('.(float) $line['percent'].'%)';
    }
}
