<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guest_name')->searchable()->sortable(),
                TextColumn::make('rating')->sortable(),
                TextColumn::make('body')->limit(60),
                IconColumn::make('is_approved')->boolean(),
                TextColumn::make('stay_date')->date()->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([...VisibilityBulkActions::make('is_approved'), DeleteBulkAction::make()])]);
    }
}
