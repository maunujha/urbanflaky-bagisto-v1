<?php

namespace Gabha\Notifications\Services;

/**
 * Reads Admin → Configure → Email → Automated Emails (emails.automation.*),
 * falling back to config('notifications.defaults') until the page is saved.
 */
class AutomationSettings
{
    /**
     * @param  string  $key  e.g. "shipping.delivered"
     */
    public function enabled(string $key): bool
    {
        return (bool) $this->value($key);
    }

    /**
     * A positive whole number (timings); a blank or invalid value falls back
     * to the default rather than firing at zero.
     */
    public function number(string $key): int
    {
        $value = $this->value($key);

        if (! is_numeric($value) || (int) $value < 1) {
            $value = config('notifications.defaults.'.$key);
        }

        return max(1, (int) $value);
    }

    public function text(string $key): string
    {
        return trim((string) $this->value($key));
    }

    protected function value(string $key): mixed
    {
        $value = core()->getConfigData('emails.automation.'.$key);

        return $value === null || $value === '' ? config('notifications.defaults.'.$key) : $value;
    }
}
