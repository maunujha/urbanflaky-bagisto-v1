<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Shop\Services\SmsAlertService;

class SendRegistrationSms implements ShouldQueue
{
    public int $tries = 2;

    public function __construct(protected SmsAlertService $sms) {}

    public function handle($customer): void
    {
        if (! $customer->phone) return;

        $name = trim($customer->first_name . ' ' . $customer->last_name) ?: 'Customer';

        $this->sms->sendWelcome($customer->phone);
        $this->sms->sendRegistration($customer->phone, $name);
    }
}
