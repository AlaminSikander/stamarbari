<?php

namespace App\Filament\Resources\Guests\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GuestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')->required(),
                TextInput::make('phone')->required(),
                TextInput::make('whatsapp'),
                TextInput::make('email')->email(),
                TextInput::make('id_type'),
                TextInput::make('id_number'),
                TextInput::make('country'),
                Textarea::make('address')->columnSpanFull(),
                Toggle::make('marketing_opt_in')->default(false),
            ]);
    }
}
