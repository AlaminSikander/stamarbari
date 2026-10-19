<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public function overlappingBlocks(int $roomId, Carbon|string $checkIn, Carbon|string $checkOut): bool
    {
        return RoomBlock::query()
            ->where('room_id', $roomId)
            ->where('starts_on', '<', $checkOut)
            ->where('ends_on', '>', $checkIn)
            ->exists();
    }

    /**
     * @return Collection<int, Room>
     */
    public function availableRooms(
        RoomCategory $category,
        Carbon|string $checkIn,
        Carbon|string $checkOut,
        bool $lock = false,
    ): Collection {
        $query = Room::query()
            ->where('room_category_id', $category->id)
            ->where('is_active', true);

        if ($lock) {
            $query->lockForUpdate();
        }

        $rooms = $query->get();

        return $rooms->filter(function (Room $room) use ($checkIn, $checkOut) {
            if ($this->overlappingBlocks($room->id, $checkIn, $checkOut)) {
                return false;
            }

            $taken = DB::table('booking_rooms')
                ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                ->whereNull('bookings.deleted_at')
                ->where('booking_rooms.room_id', $room->id)
                ->where('bookings.check_in', '<', $checkOut)
                ->where('bookings.check_out', '>', $checkIn)
                ->where(function ($q) {
                    $q->whereIn('bookings.status', ['paid', 'confirmed', 'checked_in'])
                        ->orWhere(function ($pending) {
                            $pending->where('bookings.status', 'pending')
                                ->where('bookings.hold_expires_at', '>', now());
                        });
                })
                ->exists();

            return ! $taken;
        })->values();
    }

    public function availableCount(RoomCategory $category, Carbon|string $checkIn, Carbon|string $checkOut): int
    {
        return $this->availableRooms($category, $checkIn, $checkOut)->count();
    }
}
