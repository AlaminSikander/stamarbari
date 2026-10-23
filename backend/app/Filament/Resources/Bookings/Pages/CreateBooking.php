<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\Concerns\SavesReservations;
use App\Services\BookingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBooking extends CreateRecord
{
    use SavesReservations;

    protected static string $resource = BookingResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(BookingService::class)->createReservation(
                $this->reservationPayload($data),
                auth()->id(),
                $this->overrideReason($data),
            );
        } catch (BookingException $e) {
            $this->rejectReservation($e);
        }
    }
}
