<?php

namespace App\Filament\Resources\RoomCategories\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RoomCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')->label('Name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('max_adults')->label('Adults'),
                TextColumn::make('max_children')->label('Children'),
                TextColumn::make('basePrice.amount')->money('BDT')->label('Price / night'),
                TextColumn::make('weekendPrice.amount')->money('BDT')->label('Weekend / night')->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable()->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...VisibilityBulkActions::make('is_active'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
