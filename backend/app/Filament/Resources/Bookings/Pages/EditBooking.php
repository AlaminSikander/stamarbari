<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\Concerns\SavesReservations;
use App\Filament\Resources\Bookings\Support\ReservationPayload;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBooking extends EditRecord
{
    use SavesReservations;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();
        $data['room_selections'] = ReservationPayload::selectionsFor($booking);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            /** @var Booking $record */
            return app(BookingService::class)->updateReservation(
                $record,
                $this->reservationPayload($data),
                auth()->id(),
                $this->overrideReason($data),
            );
        } catch (BookingException $e) {
            $this->rejectReservation($e);
        }
    }
}
