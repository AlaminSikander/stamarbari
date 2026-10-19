<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Enums\DiscountType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OfferForm
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
                TextInput::make('kind')->default('promo')->required(),
                Select::make('discount_type')
                    ->options([
                        DiscountType::Percent->value => 'Percent',
                        DiscountType::Fixed->value => 'Fixed amount',
                    ])
                    ->default(DiscountType::Percent->value)
                    ->required(),
                TextInput::make('discount_value')->numeric()->default(0)->required()
                    ->helperText('Use 0 unless this offer should change the booking quote.'),
                DatePicker::make('booking_starts_on'),
                DatePicker::make('booking_ends_on'),
                DatePicker::make('stay_starts_on'),
                DatePicker::make('stay_ends_on'),
                TextInput::make('min_nights')->numeric(),
                FileUpload::make('image')->image()->directory('offers')->disk('public')->visibility('public'),
                Select::make('categories')->multiple()->relationship('categories', 'name_en')->preload(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
