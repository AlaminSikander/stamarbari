<?php

namespace App\Filament\Resources\MealPlans\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MealPlanForm
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
                TextInput::make('price_per_adult_per_night')->numeric()->prefix('৳')->default(0)->required(),
                TextInput::make('price_per_child_per_night')->numeric()->prefix('৳')->default(0)->required(),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
