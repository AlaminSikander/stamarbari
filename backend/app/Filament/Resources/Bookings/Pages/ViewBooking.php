<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Support\BookingActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingActions::confirm(),
            BookingActions::checkAvailability(),
            BookingActions::updateStatus(),
            BookingActions::whatsAppGuest(),
            ActionGroup::make([
                BookingActions::callGuest(),
                BookingActions::addNote(),
                EditAction::make(),
            ]),
        ];
    }
}
