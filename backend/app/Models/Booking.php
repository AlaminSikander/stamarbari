<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'child_ages' => 'array',
            'price_breakdown' => 'array',
            'extra_bed' => 'boolean',
            'pickup_required' => 'boolean',
            'reminders_opt_out' => 'boolean',
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'room_subtotal' => 'decimal:2',
            'meal_total' => 'decimal:2',
            'addon_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (! $booking->booking_number) {
                $booking->booking_number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'AMR-'.Str::upper(Str::random(6));
        } while (self::query()->where('booking_number', $number)->exists());

        return $number;
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    public function holdIsValid(): bool
    {
        return $this->status === BookingStatus::Pending
            && $this->hold_expires_at
            && $this->hold_expires_at->isFuture();
    }

    public function scopeBlockingInventory(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereIn('status', [
                BookingStatus::Paid,
                BookingStatus::Confirmed,
                BookingStatus::CheckedIn,
            ])->orWhere(function (Builder $pending) {
                $pending->where('status', BookingStatus::Pending)
                    ->where('hold_expires_at', '>', now());
            });
        });
    }

    public function scopeOverlapping(Builder $query, $checkIn, $checkOut): Builder
    {
        return $query->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }
}
