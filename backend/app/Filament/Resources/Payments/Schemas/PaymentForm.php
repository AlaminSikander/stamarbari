<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_id')->relationship('booking', 'booking_number')->required()->searchable()->preload(),
                TextInput::make('payment_reference')
                    ->default(fn () => 'PAY-'.Str::upper(Str::random(8)))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('transaction_id'),
                TextInput::make('gateway')->default('bkash')->required(),
                Select::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(PaymentStatus::Pending->value)
                    ->required(),
                TextInput::make('amount')->numeric()->prefix('৳')->required(),
                TextInput::make('currency')->default('BDT')->required(),
                TextInput::make('purpose')->default('advance')->required(),
            ]);
    }
}
