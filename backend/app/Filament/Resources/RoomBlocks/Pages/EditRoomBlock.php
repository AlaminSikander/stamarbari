<?php

namespace App\Filament\Resources\RoomBlocks\Pages;

use App\Filament\Resources\RoomBlocks\RoomBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoomBlock extends EditRecord
{
    protected static string $resource = RoomBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
