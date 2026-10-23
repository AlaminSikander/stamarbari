<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
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
        return $this->belongsTo(RoomCategory::class, 'room_category_id')->withTrashed();
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

    /**
     * Secret given to the guest with their booking, needed to read it back through the
     * public API. Derived from the app key, so it is never stored and stays the same
     * across retries of the same request.
     */
    public function accessToken(): string
    {
        return substr(hash_hmac('sha256', 'booking-access|'.$this->booking_number, (string) config('app.key')), 0, 40);
    }

    public function hasAccessToken(?string $token): bool
    {
        return filled($token) && hash_equals($this->accessToken(), (string) $token);
    }

    /**
     * @return 'unpaid'|'partially_paid'|'paid'
     */
    public function paymentStatus(): string
    {
        $paid = (float) $this->amount_paid;

        return match (true) {
            $paid <= 0 => 'unpaid',
            $paid + 0.005 < (float) $this->grand_total => 'partially_paid',
            default => 'paid',
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->paymentStatus()) {
            'unpaid' => 'Not paid',
            'partially_paid' => 'Partially paid',
            'paid' => 'Paid',
        };
    }

    /**
     * Rooms grouped by category, with the prices stored when they were booked.
     *
     * @return Collection<int, array{room_category_id:int, name:string, room_type:?string, room_type_label:?string, quantity:int, nightly_rate:float, nights:int, subtotal:float, units:list<string>}>
     */
    public function roomLines(string $locale = 'en'): Collection
    {
        $this->loadMissing(['rooms.category', 'rooms.room', 'category']);

        if ($this->rooms->isEmpty()) {
            if (! $this->category) {
                return collect();
            }

            return collect([[
                'room_category_id' => $this->room_category_id,
                'name' => $this->category->localizedName($locale),
                'room_type' => $this->category->room_type?->value,
                'room_type_label' => $this->category->room_type?->localizedLabel($locale),
                'quantity' => 1,
                'nightly_rate' => round((float) $this->room_subtotal / max(1, $this->nights()), 2),
                'nights' => $this->nights(),
                'subtotal' => (float) $this->room_subtotal,
                'units' => [],
            ]]);
        }

        return $this->rooms
            ->sortBy('id')
            ->groupBy(fn (BookingRoom $row) => $row->room_category_id ?? $row->room?->room_category_id ?? $this->room_category_id)
            ->map(function (Collection $rows, $categoryId) use ($locale) {
                $category = $rows->first()->category ?? $rows->first()->room?->category ?? $this->category;
                $subtotal = (float) $rows->sum(fn (BookingRoom $row) => (float) $row->subtotal);

                return [
                    'room_category_id' => (int) $categoryId,
                    'name' => $category?->localizedName($locale) ?? 'Room',
                    'room_type' => $category?->room_type?->value,
                    'room_type_label' => $category?->room_type?->localizedLabel($locale),
                    'quantity' => $rows->count(),
                    'nightly_rate' => (float) $rows->first()->nightly_rate,
                    'nights' => (int) ($rows->first()->nights ?: $this->nights()),
                    'subtotal' => round($subtotal, 2),
                    'units' => $rows->map(fn (BookingRoom $row) => (string) $row->room?->code)->filter()->values()->all(),
                ];
            })
            ->values();
    }

    public function roomSummary(string $locale = 'en'): string
    {
        return $this->roomLines($locale)
            ->map(fn (array $line) => $line['quantity'] > 1 ? "{$line['quantity']} × {$line['name']}" : $line['name'])
            ->implode(', ');
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
