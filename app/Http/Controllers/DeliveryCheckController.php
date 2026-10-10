<?php

namespace App\Http\Controllers;

use App\Services\Shipping\DeliveryRules;
use App\Services\Shiprocket\ServiceabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product page "Check delivery": courier serviceability for a pincode plus
 * the store's shipping and COD promise, all from admin settings.
 */
class DeliveryCheckController extends Controller
{
    public function __construct(
        protected ServiceabilityService $serviceability,
        protected DeliveryRules $rules,
    ) {}

    public function check(Request $request): JsonResponse
    {
        $pincode = $request->validate(['pincode' => 'required|digits:6'])['pincode'];

        $result  = $this->serviceability->check($pincode);
        $summary = $this->rules->summary();

        /* Shiprocket unreachable: promise nothing we can't keep. */
        if ($result === null) {
            return response()->json([
                'deliverable' => true,
                'days'        => '5–7 days',
                'cod'         => false,
                'cod_unknown' => true,
            ] + $summary);
        }

        if (! $result['serviceable']) {
            return response()->json(['deliverable' => false]);
        }

        $codEnabled = (bool) core()->getConfigData('sales.payment_methods.cashondelivery.active');

        return response()->json([
            'deliverable' => true,
            'days'        => $result['etd']
                ? Carbon::parse($result['etd'])->format('D, d M')
                : ($result['days'] ? $result['days'].' days' : '5–7 days'),
            'cod'         => $codEnabled && $result['cod'],
            'cod_max'     => $codEnabled ? $this->rules->codMaximum() : null,
        ] + $summary);
    }
}
