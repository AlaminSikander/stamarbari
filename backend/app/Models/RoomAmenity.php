<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomAmenity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(RoomCategory::class, 'room_amenity_room_category');
    }
}
