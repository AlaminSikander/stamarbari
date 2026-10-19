<?php

namespace App\Filament\Resources\RoomBlocks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoomBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')->relationship('room', 'code')->required()->searchable()->preload(),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required(),
                TextInput::make('reason')->required(),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
