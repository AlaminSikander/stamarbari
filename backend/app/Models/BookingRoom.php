<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One physical room held by a booking, with the price that applied at booking time.
 */
class BookingRoom extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'nightly_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'nights' => 'integer',
            'nightly' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id')->withTrashed();
    }
}
