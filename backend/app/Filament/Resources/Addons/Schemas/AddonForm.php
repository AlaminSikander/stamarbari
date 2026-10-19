<?php

namespace App\Filament\Resources\Addons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AddonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('name_bn'),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description_en')->columnSpanFull(),
                Textarea::make('description_bn')->columnSpanFull(),
                TextInput::make('price')->numeric()->prefix('৳')->default(0)->required(),
                Select::make('pricing_unit')
                    ->options([
                        'per_booking' => 'Per booking',
                        'per_night' => 'Per night',
                        'per_person' => 'Per person',
                    ])
                    ->default('per_booking')
                    ->required(),
                Toggle::make('is_pickup')->default(false),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
