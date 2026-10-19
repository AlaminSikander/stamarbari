<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PaymentFailed = 'payment_failed';
    case Expired = 'expired';

    public function isInventoryBlocking(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Paid,
            self::Confirmed,
            self::CheckedIn,
        ], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'success',
            self::Pending => 'warning',
            self::CheckedIn => 'info',
            self::Cancelled, self::PaymentFailed, self::Expired => 'danger',
            self::Completed, self::Paid, self::Refunded => 'gray',
        };
    }
}
