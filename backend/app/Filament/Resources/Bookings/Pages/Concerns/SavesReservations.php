<?php

namespace App\Filament\Resources\Bookings\Pages\Concerns;

use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\Support\ReservationPayload;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait SavesReservations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function reservationPayload(array $data): array
    {
        return ReservationPayload::fromFormState($data);
    }

    /**
     * The block override is honoured only for users who manage rooms, with a reason and
     * an explicit confirmation. It never allows rooms held by other bookings.
     *
     * @param  array<string, mixed>  $data
     */
    protected function overrideReason(array $data): ?string
    {
        if (! ($data['override_room_blocks'] ?? false)) {
            return null;
        }
        if (! auth()->user()?->can('manage_rooms')) {
            throw ValidationException::withMessages(['data.override_room_blocks' => 'You are not allowed to override room availability.']);
        }
        if (blank($data['override_reason'] ?? null) || ! ($data['override_confirmed'] ?? false)) {
            throw ValidationException::withMessages(['data.override_reason' => 'Give a reason and confirm the override.']);
        }

        return trim((string) $data['override_reason']);
    }

    protected function rejectReservation(BookingException $e): never
    {
        Notification::make()
            ->danger()
            ->title('Reservation not saved')
            ->body($e->getMessage())
            ->persistent()
            ->send();

        $field = match (true) {
            isset($e->errors['amount_paid']) => 'data.amount_paid',
            isset($e->errors['guests']) => 'data.adults',
            default => 'data.room_selections',
        };

        throw ValidationException::withMessages([$field => $e->getMessage()]);
    }
}
