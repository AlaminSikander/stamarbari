<?php

namespace App\Filament\Resources\RoomCategories;

use App\Filament\Resources\RoomCategories\Pages\CreateRoomCategory;
use App\Filament\Resources\RoomCategories\Pages\EditRoomCategory;
use App\Filament\Resources\RoomCategories\Pages\ListRoomCategories;
use App\Filament\Resources\RoomCategories\Schemas\RoomCategoryForm;
use App\Filament\Resources\RoomCategories\Tables\RoomCategoriesTable;
use App\Models\RoomCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomCategoryResource extends Resource
{
    protected static ?string $model = RoomCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Room Categories';

    protected static ?string $modelLabel = 'room category';

    protected static ?string $pluralModelLabel = 'Room Categories';

    protected static ?string $recordTitleAttribute = 'name_en';

    protected static ?string $slug = 'rooms';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage_rooms');
    }

    public static function form(Schema $schema): Schema
    {
        return RoomCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomCategoriesTable::configure($table);
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
            'index' => ListRoomCategories::route('/'),
            'create' => CreateRoomCategory::route('/create'),
            'edit' => EditRoomCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
