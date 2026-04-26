<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerGroup;

class OtpCustomerService
{
    public function handleVerifiedPhone(string $phone): Customer
    {
        $channelId = core()->getCurrentChannel()->id;

        $customer = Customer::where('phone', $phone)
            ->where(function ($q) use ($channelId) {
                $q->where('channel_id', $channelId)->orWhereNull('channel_id');
            })
            ->first();

        if ($customer) {
            /* Ensure channel_id is set for legacy records */
            if (! $customer->channel_id) {
                $customer->update(['channel_id' => $channelId]);
            }

            Auth::guard('customer')->login($customer);

            return $customer;
        }

        $customer = Customer::create([
            'first_name'        => 'Guest',
            'last_name'         => '',
            'email'             => null,
            'phone'             => $phone,
            'password'          => bcrypt(Str::random(24)),
            'phone_verified_at' => now(),
            'password_set'      => false,
            'is_verified'       => 1,
            'customer_group_id' => $this->getDefaultGroupId(),
            'channel_id'        => $channelId,
        ]);

        Auth::guard('customer')->login($customer);

        return $customer;
    }

    private function getDefaultGroupId(): int
    {
        return CustomerGroup::where('code', 'general')->value('id') ?? 2;
    }
}
