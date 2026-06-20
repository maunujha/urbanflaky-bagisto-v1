<?php

namespace App\Http\Controllers\Shop;

use App\Models\CookieConsent;
use App\Support\CookieConsent as CookieConsentHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CookieConsentController extends Controller
{
    /**
     * Persist a visitor's consent choice.
     *
     * The browser always keeps the authoritative copy in localStorage; this
     * endpoint additionally mirrors the choice to the database for logged-in
     * customers so it survives across devices. Guests are accepted and no-op'd.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'analytics'   => 'required|boolean',
            'marketing'   => 'required|boolean',
            'preferences' => 'required|boolean',
        ]);

        $user = auth()->guard('customer')->user();

        if ($user) {
            CookieConsent::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'analytics'       => $data['analytics'],
                    'marketing'       => $data['marketing'],
                    'preferences'     => $data['preferences'],
                    'consent_version' => CookieConsentHelper::version(),
                ]
            );
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
