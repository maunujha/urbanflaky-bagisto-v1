<?php

namespace Gabha\Notifications\Http\Controllers\Shop;

use Gabha\Notifications\Models\EmailSuppression;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;

/**
 * Signed opt-out links. GET only shows a confirmation (link scanners must not
 * unsubscribe anyone); POST unsubscribes, which is also what mail clients send
 * for RFC 8058 one-click unsubscribe.
 */
class EmailPreferenceController extends Controller
{
    public function show(Request $request)
    {
        [$email, $scope] = $this->target($request);

        return view('notifications::shop.unsubscribe', [
            'email' => $email,
            'scope' => $scope,
            'done'  => false,
        ]);
    }

    public function unsubscribe(Request $request)
    {
        [$email, $scope] = $this->target($request);

        EmailSuppression::suppress($email, $scope);

        /*
         * Marketing consent is the newsletter opt-in: withdraw it as well, model
         * by model so the consent ledger records it, and clear the account toggle.
         */
        if ($scope === EmailSuppression::SCOPE_MARKETING) {
            SubscribersList::where('email', $email)->where('is_subscribed', 1)->get()->each->update(['is_subscribed' => 0]);

            Customer::where('email', $email)->update(['subscribed_to_news_letter' => 0]);
        }

        if (! $request->hasSession() || $request->has('List-Unsubscribe')) {
            return response('Unsubscribed', 200);
        }

        return view('notifications::shop.unsubscribe', [
            'email' => $email,
            'scope' => $scope,
            'done'  => true,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function target(Request $request): array
    {
        $scope = (string) $request->query('scope');

        abort_unless(in_array($scope, EmailSuppression::SCOPES, true), 404);

        return [mb_strtolower((string) $request->query('email')), $scope];
    }
}
