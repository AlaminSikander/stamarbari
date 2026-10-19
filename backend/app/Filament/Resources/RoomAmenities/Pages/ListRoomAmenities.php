<?php

namespace App\Filament\Resources\RoomAmenities\Pages;

use App\Filament\Resources\RoomAmenities\RoomAmenityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoomAmenities extends ListRecords
{
    protected static string $resource = RoomAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
