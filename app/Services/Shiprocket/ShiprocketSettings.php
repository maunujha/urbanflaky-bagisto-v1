<?php

namespace App\Services\Shiprocket;

/**
 * Admin-editable Shiprocket settings (Configure → Sales → Shiprocket), each
 * falling back to config/shiprocket.php until saved.
 */
class ShiprocketSettings
{
    public function autoPush(): bool
    {
        return (bool) $this->get('automation.auto_push');
    }

    public function autoAssignCourier(): bool
    {
        return (bool) $this->get('automation.auto_assign_courier');
    }

    public function autoSchedulePickup(): bool
    {
        return (bool) $this->get('automation.auto_schedule_pickup');
    }

    public function pickupLocation(): string
    {
        return (string) $this->get('pickup.location');
    }

    public function pickupPincode(): string
    {
        return (string) $this->get('pickup.pincode');
    }

    /**
     * @return array{length: float, breadth: float, height: float}
     */
    public function packageDimensions(): array
    {
        return [
            'length'  => max(0.5, (float) $this->get('package.length')),
            'breadth' => max(0.5, (float) $this->get('package.breadth')),
            'height'  => max(0.5, (float) $this->get('package.height')),
        ];
    }

    public function defaultItemWeightGrams(): float
    {
        return max(1.0, (float) $this->get('package.default_item_weight'));
    }

    public function hsn(): string
    {
        return (string) $this->get('package.hsn');
    }

    protected function get(string $field): mixed
    {
        $value = core()->getConfigData('sales.shiprocket.'.$field);

        /* A cleared text field stores "" — treat it as "use the default". */
        return $value === '' || $value === null ? config('shiprocket.'.$field) : $value;
    }
}
