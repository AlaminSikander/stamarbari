<?php

namespace App\Filament\Resources\DatabaseBackups;

use App\Filament\Resources\DatabaseBackups\Pages\CreateDatabaseBackup;
use App\Filament\Resources\DatabaseBackups\Pages\EditDatabaseBackup;
use App\Filament\Resources\DatabaseBackups\Pages\ListDatabaseBackups;
use App\Filament\Resources\DatabaseBackups\Schemas\DatabaseBackupForm;
use App\Filament\Resources\DatabaseBackups\Tables\DatabaseBackupsTable;
use App\Models\DatabaseBackup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DatabaseBackupResource extends Resource
{
    protected static ?string $model = DatabaseBackup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return DatabaseBackupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DatabaseBackupsTable::configure($table);
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
            'index' => ListDatabaseBackups::route('/'),
            'create' => CreateDatabaseBackup::route('/create'),
            'edit' => EditDatabaseBackup::route('/{record}/edit'),
        ];
    }
}
