<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')->label('Name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('discount_type'),
                TextColumn::make('discount_value'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([...VisibilityBulkActions::make('is_active'), DeleteBulkAction::make()])]);
    }
}
