<?php

namespace App\Filament\Resources\RoomAmenities\Pages;

use App\Filament\Resources\RoomAmenities\RoomAmenityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoomAmenity extends EditRecord
{
    protected static string $resource = RoomAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
