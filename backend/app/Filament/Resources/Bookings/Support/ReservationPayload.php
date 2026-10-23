<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Models\Booking;

/**
 * Maps the admin booking form to the BookingService payload and back.
 */
class ReservationPayload
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromFormState(array $data): array
    {
        $rooms = collect($data['room_selections'] ?? [])
            ->filter(fn ($row) => filled($row['room_category_id'] ?? null))
            ->map(fn ($row) => [
                'room_category_id' => (int) $row['room_category_id'],
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
            ])
            ->values()
            ->all();

        return [
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'adults' => (int) ($data['adults'] ?? 0),
            'children' => (int) ($data['children'] ?? 0),
            'meal_plan_id' => filled($data['meal_plan_id'] ?? null) ? (int) $data['meal_plan_id'] : null,
            'rooms' => $rooms,
            'guest_id' => filled($data['guest_id'] ?? null) ? (int) $data['guest_id'] : null,
            'status' => $data['status'] ?? null,
            'source' => $data['source'] ?? null,
            'amount_paid' => $data['amount_paid'] ?? 0,
            'special_requests' => $data['special_requests'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'booking_number' => $data['booking_number'] ?? null,
        ];
    }

    /**
     * @return list<array{room_category_id: int, quantity: int}>
     */
    public static function selectionsFor(Booking $booking): array
    {
        return $booking->roomLines()
            ->map(fn (array $line) => ['room_category_id' => $line['room_category_id'], 'quantity' => $line['quantity']])
            ->all();
    }
}
