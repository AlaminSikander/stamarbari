<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('guest_name')->required(),
                Textarea::make('quote_en')->required()->columnSpanFull(),
                Textarea::make('quote_bn')->columnSpanFull(),
                Toggle::make('is_published')->default(false),
            ]);
    }
}
