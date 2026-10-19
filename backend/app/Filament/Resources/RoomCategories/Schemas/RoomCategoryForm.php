<?php

namespace App\Filament\Resources\RoomCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoomCategoryForm
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
                TextInput::make('max_adults')->numeric()->required()->default(2),
                TextInput::make('max_children')->numeric()->required()->default(1),
                Group::make([
                    TextInput::make('amount')
                        ->label('Price per night')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('৳')
                        ->required()
                        ->helperText('Charged for every night unless a weekend or seasonal price applies.'),
                ])
                    ->relationship('basePrice')
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'type' => 'base', 'is_active' => true])
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'is_active' => true]),
                Group::make([
                    TextInput::make('amount')
                        ->label('Weekend price per night')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('৳')
                        ->helperText('Optional. Used for Friday and Saturday nights. Leave empty to charge the regular price.'),
                ])
                    ->relationship('weekendPrice', condition: fn (?array $state): bool => filled($state['amount'] ?? null))
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'type' => 'weekend', 'is_active' => true])
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'is_active' => true]),
                TextInput::make('view_en'),
                TextInput::make('view_bn'),
                FileUpload::make('featured_image')
                    ->label('Main photograph')
                    ->image()
                    ->directory('rooms')
                    ->disk('public')
                    ->visibility('public')
                    ->helperText('Used on room cards. The gallery below fills the large photo and two side photos on the room page.'),
                Repeater::make('images')
                    ->relationship()
                    ->label('Room photographs')
                    ->helperText('Add at least 3 photos: the first is the large image, the next two are the side images.')
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('path')
                            ->label('Photo')
                            ->image()
                            ->required()
                            ->directory('rooms')
                            ->disk('public')
                            ->visibility('public'),
                        TextInput::make('alt_en')->label('Alt text (English)'),
                        TextInput::make('alt_bn')->label('Alt text (Bangla)'),
                        Toggle::make('is_featured')->label('Use as card photo')->default(false),
                    ]),
                Select::make('amenities')->multiple()->relationship('amenities', 'name_en')->preload(),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
