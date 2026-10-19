<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public function checkInTime(): string
    {
        return (string) $this->get('check_in_time', '12:00');
    }

    public function checkOutTime(): string
    {
        return (string) $this->get('check_out_time', '10:00');
    }

    public function childPolicyAge(): int
    {
        return (int) $this->get('child_policy_age', 7);
    }

    public function cancellationHours(): int
    {
        return (int) $this->get('cancellation_hours', 24);
    }

    public function cancellationRefundPercent(): int
    {
        return (int) $this->get('cancellation_refund_percent', 100);
    }

    public function missedShipRefund(): bool
    {
        return filter_var($this->get('missed_ship_refund', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function advancePercent(): int
    {
        return (int) $this->get('advance_percent', 30);
    }

    public function holdMinutes(): int
    {
        return (int) $this->get('booking_hold_minutes', 15);
    }

    public function extraBedAvailable(): bool
    {
        return filter_var($this->get('extra_bed_available', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function extraBedPrice(): float
    {
        return (float) $this->get('extra_bed_price', 0);
    }

    public function timezone(): string
    {
        return (string) $this->get('timezone', 'Asia/Dhaka');
    }

    public function now(): Carbon
    {
        return now($this->timezone());
    }
}
