<?php

namespace App\Filament\Resources\Refunds\Schemas;

use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Services\RefundService;
use App\Services\SettingsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = app(SettingsService::class);
        $policy = sprintf(
            'Policy: cancelling %d or more days before check-in refunds %d%% of the advance paid; later cancellations are non-refundable.',
            $settings->cancellationDays(),
            $settings->cancellationRefundPercent(),
        );

        return $schema
            ->components([
                Select::make('booking_id')
                    ->relationship('booking', 'booking_number')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $booking = $state ? Booking::query()->find($state) : null;
                        if ($booking) {
                            $set('amount', app(RefundService::class)->policyRefundAmount($booking));
                        }
                    }),
                Select::make('payment_id')->relationship('payment', 'payment_reference')->searchable()->preload(),
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->helperText($policy.' Filled automatically from the cancellation date (or today).'),
                Select::make('status')
                    ->options(collect(RefundStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(RefundStatus::Requested->value)
                    ->required(),
                TextInput::make('reason'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
