<?php

namespace App\Filament\Resources\RoomCategories\Pages;

use App\Filament\Resources\RoomCategories\RoomCategoryResource;
use App\Filament\Resources\RoomCategories\Tables\RoomCategoriesTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRoomCategory extends EditRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RoomCategoriesTable::guardDelete(DeleteAction::make()),
            RoomCategoriesTable::guardDelete(ForceDeleteAction::make(), permanently: true),
            RestoreAction::make(),
        ];
    }
}
