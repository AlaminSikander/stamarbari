<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('guest_name')->required(),
                TextInput::make('rating')->numeric()->minValue(1)->maxValue(5)->required(),
                Textarea::make('body')->required()->columnSpanFull(),
                TextInput::make('room_name'),
                DatePicker::make('stay_date'),
                TextInput::make('source'),
                Toggle::make('is_approved')->default(false),
            ]);
    }
}
