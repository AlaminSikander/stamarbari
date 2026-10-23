<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case PendingConfirmation = 'pending_confirmation';
    case Pending = 'pending';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PaymentFailed = 'payment_failed';
    case Expired = 'expired';

    /**
     * The statuses staff move a booking request through.
     *
     * @return list<self>
     */
    public static function workflow(): array
    {
        return [self::PendingConfirmation, self::Confirmed, self::CheckedIn, self::Completed, self::Cancelled];
    }

    public function isInventoryBlocking(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Paid,
            self::Confirmed,
            self::CheckedIn,
        ], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Pending Confirmation',
            self::Pending => 'Pending Payment',
            self::Paid => 'Paid',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked In',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::PaymentFailed => 'Payment Failed',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'success',
            self::Pending, self::PendingConfirmation => 'warning',
            self::CheckedIn => 'info',
            self::Cancelled, self::PaymentFailed, self::Expired => 'danger',
            self::Completed, self::Paid, self::Refunded => 'gray',
        };
    }
}
