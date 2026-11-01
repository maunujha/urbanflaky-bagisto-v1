<?php

namespace Gabha\Notifications\Services;

/**
 * Reads the admin switches under emails.automation.*, falling back to
 * config('notifications.defaults') until the admin page is first saved.
 */
class AutomationSettings
{
    /**
     * @param  string  $key  e.g. "shipping.delivered"
     */
    public function enabled(string $key): bool
    {
        $value = core()->getConfigData('emails.automation.'.$key);

        if ($value === null || $value === '') {
            return (bool) config('notifications.defaults.'.$key, false);
        }

        return (bool) $value;
    }
}
