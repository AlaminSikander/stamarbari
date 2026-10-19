<?php

namespace App\Filament\Resources\GalleryItems\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->disk('public'),
                TextColumn::make('category.name_en')->label('Category')->sortable(),
                TextColumn::make('alt_en')->searchable(),
                IconColumn::make('is_published')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...VisibilityBulkActions::make('is_published'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
