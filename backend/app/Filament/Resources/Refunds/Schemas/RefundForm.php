<?php

namespace App\Filament\Resources\Refunds\Schemas;

use App\Enums\RefundStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_id')->relationship('booking', 'booking_number')->required()->searchable()->preload(),
                Select::make('payment_id')->relationship('payment', 'payment_reference')->searchable()->preload(),
                TextInput::make('amount')->numeric()->prefix('৳')->required(),
                Select::make('status')
                    ->options(collect(RefundStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(RefundStatus::Requested->value)
                    ->required(),
                TextInput::make('reason'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
