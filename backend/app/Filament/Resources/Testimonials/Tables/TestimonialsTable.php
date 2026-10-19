<?php

namespace App\Filament\Resources\Testimonials\Tables;

use App\Filament\Support\VisibilityBulkActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guest_name')->searchable()->sortable(),
                TextColumn::make('quote_en')->limit(80),
                IconColumn::make('is_published')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([...VisibilityBulkActions::make('is_published'), DeleteBulkAction::make()])]);
    }
}
