<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RoomCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'room_type' => RoomType::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (RoomCategory $category): bool {
            return $category->deletionBlockedReason($category->isForceDeleting()) === null;
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(RoomAmenity::class, 'room_amenity_room_category');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function basePrice(): HasOne
    {
        return $this->hasOne(RoomPrice::class)->where('type', 'base');
    }

    public function weekendPrice(): HasOne
    {
        return $this->hasOne(RoomPrice::class)->where('type', 'weekend');
    }

    public function seasonalPrices(): HasMany
    {
        return $this->hasMany(SeasonalPrice::class);
    }

    public function localizedName(string $locale = 'en'): string
    {
        return $locale === 'bn' && $this->name_bn ? $this->name_bn : $this->name_en;
    }

    public function capacityLabel(): string
    {
        $label = $this->max_adults.' '.Str::plural('Adult', $this->max_adults);
        if ($this->max_children > 0) {
            $label .= ' + '.$this->max_children.' '.($this->max_children === 1 ? 'Child' : 'Children');
        }

        return $label;
    }

    /**
     * Bookings that still hold this category's inventory today or later, plus booking
     * requests still waiting for confirmation.
     */
    public function upcomingBookingsCount(): int
    {
        return $this->bookings()
            ->where(fn ($query) => $query->blockingInventory()->orWhere('status', BookingStatus::PendingConfirmation))
            ->whereDate('check_out', '>=', today())
            ->count();
    }

    public function deletionBlockedReason(bool $permanently = false): ?string
    {
        $upcoming = $this->upcomingBookingsCount();
        if ($upcoming > 0) {
            return "{$this->name_en} has {$upcoming} current or upcoming ".Str::plural('booking', $upcoming).'. Cancel or move them before deleting this room category.';
        }

        if ($permanently && $this->bookings()->withTrashed()->exists()) {
            return "{$this->name_en} has booking history. Keep it archived instead of deleting it permanently so past reservations keep their room details.";
        }

        return null;
    }
}
