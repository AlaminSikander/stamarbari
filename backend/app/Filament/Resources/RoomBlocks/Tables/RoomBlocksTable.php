<?php

namespace App\Filament\Resources\RoomBlocks\Tables;

use App\Filament\Resources\RoomBlocks\Schemas\RoomBlockForm;
use App\Models\RoomBlock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['room.category' => fn ($category) => $category->withCount('rooms')]))
            ->columns([
                TextColumn::make('room.code')
                    ->label('Room')
                    ->formatStateUsing(fn (RoomBlock $record): string => RoomBlockForm::label($record->room))
                    ->sortable(),
                TextColumn::make('starts_on')->date()->sortable(),
                TextColumn::make('ends_on')->date()->sortable(),
                TextColumn::make('reason')->searchable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
