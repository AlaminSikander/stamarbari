<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name'),
                TextColumn::make('discount_type'),
                TextColumn::make('discount_value'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([...VisibilityBulkActions::make('is_active'), DeleteBulkAction::make()])]);
    }
}
