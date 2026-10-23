<?php

namespace App\Filament\Resources\RoomCategories\Schemas;

use App\Enums\RoomType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoomCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Room details')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('name_en')
                            ->label('Room name')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $slug = Str::slug((string) $state);
                                $set('slug', $slug);
                                $rooms = $get('rooms') ?? [];
                                if (count($rooms) === 1 && blank(reset($rooms)['code'] ?? null)) {
                                    $set('rooms.'.array_key_first($rooms).'.code', Str::upper($slug));
                                }
                            }),
                        Select::make('room_type')
                            ->label('Room type')
                            ->options(RoomType::class)
                            ->required(),
                        TextInput::make('max_adults')
                            ->label('Max adults')
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(12)
                            ->default(2),
                        TextInput::make('max_children')
                            ->label('Max children')
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->maxValue(12)
                            ->default(0),
                        Group::make([
                            TextInput::make('amount')
                                ->label('Price per night')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(1000000)
                                ->prefix('৳')
                                ->suffix('BDT')
                                ->required()
                                ->helperText('Charged for every night unless a weekend or seasonal price applies. Changing it never alters existing bookings.'),
                        ])
                            ->relationship('basePrice')
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'type' => 'base', 'is_active' => true])
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'is_active' => true]),
                        Group::make([
                            TextInput::make('amount')
                                ->label('Weekend price per night')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(1000000)
                                ->prefix('৳')
                                ->suffix('BDT')
                                ->helperText('Optional. Used for Friday and Saturday nights. Leave empty to charge the regular price.'),
                        ])
                            ->relationship('weekendPrice', condition: fn (?array $state): bool => filled($state['amount'] ?? null))
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'type' => 'weekend', 'is_active' => true])
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => [...$data, 'is_active' => true]),
                    ]),
                Section::make('Physical rooms')
                    ->description('Each unit can hold one booking per night. Most named rooms have exactly one unit. Turn a unit off to stop taking bookings for it; its booking history is kept.')
                    ->schema([
                        Repeater::make('rooms')
                            ->relationship()
                            ->hiddenLabel()
                            ->columns(['default' => 1, 'sm' => 2])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->deletable(false)
                            ->reorderable(false)
                            ->addActionLabel('Add another unit')
                            ->schema([
                                TextInput::make('code')
                                    ->label('Unit code')
                                    ->required()
                                    ->maxLength(30)
                                    ->distinct()
                                    ->placeholder('e.g. door number'),
                                Toggle::make('is_active')
                                    ->label('Bookable')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
                Section::make('Website listing')
                    ->columns(['default' => 1, 'md' => 2])
                    ->collapsible()
                    ->schema([
                        TextInput::make('name_bn')->label('Room name (Bangla)')->maxLength(100),
                        TextInput::make('slug')
                            ->label('URL slug')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Textarea::make('description_en')->label('Description (English)')->maxLength(2000)->columnSpanFull(),
                        Textarea::make('description_bn')->label('Description (Bangla)')->maxLength(2000)->columnSpanFull(),
                        TextInput::make('view_en')->label('View (English)')->maxLength(100),
                        TextInput::make('view_bn')->label('View (Bangla)')->maxLength(100),
                        Select::make('amenities')->multiple()->relationship('amenities', 'name_en')->preload()->columnSpanFull(),
                        Toggle::make('is_active')->label('Visible on website')->default(true),
                        TextInput::make('sort_order')->integer()->minValue(0)->default(0),
                    ]),
                Section::make('Photos')
                    ->collapsible()
                    ->schema([
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
                    ]),
            ]);
    }
}
