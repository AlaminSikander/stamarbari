<?php

namespace App\Filament\Resources\RoomAmenities;

use App\Filament\Resources\RoomAmenities\Pages\CreateRoomAmenity;
use App\Filament\Resources\RoomAmenities\Pages\EditRoomAmenity;
use App\Filament\Resources\RoomAmenities\Pages\ListRoomAmenities;
use App\Filament\Resources\RoomAmenities\Schemas\RoomAmenityForm;
use App\Filament\Resources\RoomAmenities\Tables\RoomAmenitiesTable;
use App\Models\RoomAmenity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomAmenityResource extends Resource
{
    protected static ?string $model = RoomAmenity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

    public static function form(Schema $schema): Schema
    {
        return RoomAmenityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomAmenitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoomAmenities::route('/'),
            'create' => CreateRoomAmenity::route('/create'),
            'edit' => EditRoomAmenity::route('/{record}/edit'),
        ];
    }
}
