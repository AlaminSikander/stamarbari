<?php

namespace App\Filament\Resources\RoomBlocks;

use App\Filament\Resources\RoomBlocks\Pages\CreateRoomBlock;
use App\Filament\Resources\RoomBlocks\Pages\EditRoomBlock;
use App\Filament\Resources\RoomBlocks\Pages\ListRoomBlocks;
use App\Filament\Resources\RoomBlocks\Schemas\RoomBlockForm;
use App\Filament\Resources\RoomBlocks\Tables\RoomBlocksTable;
use App\Models\RoomBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomBlockResource extends Resource
{
    protected static ?string $model = RoomBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    public static function form(Schema $schema): Schema
    {
        return RoomBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomBlocksTable::configure($table);
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
            'index' => ListRoomBlocks::route('/'),
            'create' => CreateRoomBlock::route('/create'),
            'edit' => EditRoomBlock::route('/{record}/edit'),
        ];
    }
}
