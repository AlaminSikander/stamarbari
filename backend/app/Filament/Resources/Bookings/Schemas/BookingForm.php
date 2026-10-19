<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_number')
                    ->helperText('Leave empty to generate automatically.'),
                Select::make('status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(BookingStatus::Pending->value)
                    ->required(),
                Select::make('source')
                    ->options(collect(BookingSource::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(BookingSource::Online->value)
                    ->required(),
                Select::make('guest_id')->relationship('guest', 'full_name')->searchable()->preload(),
                Select::make('room_category_id')->relationship('category', 'name_en')->required()->searchable()->preload(),
                DatePicker::make('check_in')->required(),
                DatePicker::make('check_out')->required(),
                TextInput::make('adults')->numeric()->required()->default(2),
                TextInput::make('children')->numeric()->default(0),
                Toggle::make('extra_bed')->default(false),
                Select::make('meal_plan_id')->relationship('mealPlan', 'name_en')->searchable()->preload(),
                TextInput::make('grand_total')->numeric()->prefix('৳')->default(0),
                TextInput::make('advance_amount')->numeric()->prefix('৳')->default(0),
                TextInput::make('balance_amount')->numeric()->prefix('৳')->default(0),
                TextInput::make('amount_paid')->numeric()->prefix('৳')->default(0),
                Textarea::make('special_requests')->columnSpanFull(),
            ]);
    }
}
