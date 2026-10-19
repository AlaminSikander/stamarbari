<?php

namespace App\Filament\Resources\Addons\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')->label('Name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('price')->money('BDT'),
                IconColumn::make('is_pickup')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([...VisibilityBulkActions::make('is_active'), DeleteBulkAction::make()])]);
    }
}
