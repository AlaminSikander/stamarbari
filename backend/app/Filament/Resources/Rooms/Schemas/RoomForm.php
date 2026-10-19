<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_category_id')
                    ->relationship('category', 'name_en')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('code')->required(),
                TextInput::make('name'),
                Toggle::make('is_active')->default(true),
                Toggle::make('extra_bed_allowed')->default(true),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
