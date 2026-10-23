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
        return $this->availableRoomsFor([$category->id], $checkIn, $checkOut, $lock)->get($category->id, collect());
    }

    /**
     * Free physical rooms per category for the whole stay.
     *
     * When $lock is true the candidate rooms are locked first, so concurrent bookings for
     * the same categories run one after another. The occupancy reads are locking reads too,
     * so they see the latest committed bookings rather than an earlier transaction snapshot.
     * Overlapping bookings are never ignored; $ignoreBlocks only skips maintenance blocks.
     *
     * @param  array<int, int>  $categoryIds
     * @return Collection<int, Collection<int, Room>>
     */
    public function availableRoomsFor(
        array $categoryIds,
        Carbon|string $checkIn,
        Carbon|string $checkOut,
        bool $lock = false,
        ?int $ignoreBookingId = null,
        bool $ignoreBlocks = false,
    ): Collection {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        sort($categoryIds);

        $query = Room::query()
            ->whereIn('room_category_id', $categoryIds)
            ->where('is_active', true)
            ->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $rooms = $query->get();

        $taken = DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->whereNull('bookings.deleted_at')
            ->whereIn('booking_rooms.room_id', $rooms->modelKeys())
            ->where('bookings.check_in', '<', $checkOut)
            ->where('bookings.check_out', '>', $checkIn)
            ->when($ignoreBookingId, fn ($q) => $q->where('bookings.id', '!=', $ignoreBookingId))
            ->where(function ($q) {
                $q->whereIn('bookings.status', ['paid', 'confirmed', 'checked_in'])
                    ->orWhere(function ($pending) {
                        $pending->where('bookings.status', 'pending')
                            ->where('bookings.hold_expires_at', '>', now());
                    });
            })
            ->when($lock, fn ($q) => $q->sharedLock())
            ->pluck('booking_rooms.room_id')
            ->all();

        $blocked = $ignoreBlocks ? [] : RoomBlock::query()
            ->whereIn('room_id', $rooms->modelKeys())
            ->where('starts_on', '<', $checkOut)
            ->where('ends_on', '>', $checkIn)
            ->when($lock, fn ($q) => $q->sharedLock())
            ->pluck('room_id')
            ->all();

        $unavailable = array_flip([...$taken, ...$blocked]);
        $free = $rooms->reject(fn (Room $room) => isset($unavailable[$room->id]))->groupBy('room_category_id');

        return collect($categoryIds)->mapWithKeys(fn (int $id) => [$id => $free->get($id, collect())->values()]);
    }

    public function availableCount(RoomCategory $category, Carbon|string $checkIn, Carbon|string $checkOut): int
    {
        return $this->availableRooms($category, $checkIn, $checkOut)->count();
    }
}
