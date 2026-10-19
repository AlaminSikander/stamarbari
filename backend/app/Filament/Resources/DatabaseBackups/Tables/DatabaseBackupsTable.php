<?php

namespace App\Filament\Resources\DatabaseBackups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DatabaseBackupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('disk'),
                TextColumn::make('size_bytes')->label('Size'),
                TextColumn::make('completed_at')->dateTime()->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
