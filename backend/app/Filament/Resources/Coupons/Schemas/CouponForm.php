<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\DiscountType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(50),
                TextInput::make('name'),
                Select::make('discount_type')
                    ->options([
                        DiscountType::Percent->value => 'Percent',
                        DiscountType::Fixed->value => 'Fixed amount',
                    ])
                    ->required(),
                TextInput::make('discount_value')->numeric()->required(),
                TextInput::make('minimum_amount')->numeric(),
                TextInput::make('usage_limit')->numeric(),
                TextInput::make('per_user_limit')->numeric()->default(1),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Select::make('categories')->multiple()->relationship('categories', 'name_en')->preload(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
