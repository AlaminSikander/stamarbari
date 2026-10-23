<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Support\BookingActions;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Support\PhoneNumber;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Booking request')
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('booking_number')->label('Reference')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('payment_status')
                            ->label('Payment')
                            ->state(fn (Booking $record): string => $record->paymentStatusLabel())
                            ->badge()
                            ->color(fn (Booking $record): string => self::paymentColor($record))
                            ->helperText(fn (): ?string => config('booking.online_payment_enabled') ? null : 'Online payment is disabled. No payment is collected through the website.'),
                        TextEntry::make('source')->formatStateUsing(fn ($state): string => ucfirst((string) ($state->value ?? $state))),
                        TextEntry::make('created_at')->label('Requested')->dateTime('j M Y, H:i'),
                        TextEntry::make('confirmed_at')->dateTime('j M Y, H:i')->placeholder('Not confirmed'),
                        TextEntry::make('cancelled_at')->dateTime('j M Y, H:i')->placeholder('—')->visible(fn (Booking $record): bool => $record->status === BookingStatus::Cancelled),
                        TextEntry::make('cancel_reason')->placeholder('—')->visible(fn (Booking $record): bool => $record->status === BookingStatus::Cancelled),
                    ]),
                Section::make('Guest')
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('guest_name')->label('Name')->state(fn (Booking $record): ?string => BookingActions::guest($record)?->full_name),
                        TextEntry::make('guest_phone')
                            ->label('Phone')
                            ->state(fn (Booking $record): ?string => BookingActions::guest($record)?->phone)
                            ->url(fn (Booking $record): ?string => PhoneNumber::telUrl(BookingActions::guest($record)?->phone))
                            ->copyable(),
                        TextEntry::make('guest_whatsapp')
                            ->label('WhatsApp')
                            ->state(fn (Booking $record): ?string => BookingActions::guest($record)?->whatsapp ?: BookingActions::guest($record)?->phone)
                            ->url(fn (Booking $record): ?string => PhoneNumber::whatsAppUrl(BookingActions::guest($record)?->whatsapp ?: BookingActions::guest($record)?->phone))
                            ->openUrlInNewTab(),
                        TextEntry::make('guest_email')
                            ->label('Email')
                            ->state(fn (Booking $record): ?string => BookingActions::guest($record)?->email)
                            ->url(fn (Booking $record): ?string => BookingActions::guest($record)?->email ? 'mailto:'.BookingActions::guest($record)->email : null)
                            ->placeholder('Not provided'),
                    ]),
                Section::make('Stay')
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('check_in')->date('D, j M Y'),
                        TextEntry::make('check_out')->date('D, j M Y'),
                        TextEntry::make('nights')->state(fn (Booking $record): int => $record->nights()),
                        TextEntry::make('guests_count')
                            ->label('Guests')
                            ->state(fn (Booking $record): string => "{$record->adults} adults, {$record->children} children"),
                        TextEntry::make('room_lines')
                            ->label('Rooms')
                            ->state(fn (Booking $record): array => $record->roomLines()->map(fn (array $line) => trim(
                                ($line['quantity'] > 1 ? "{$line['quantity']} × " : '').$line['name']
                                .' · ৳'.number_format($line['nightly_rate']).'/night'
                                .($line['units'] ? ' · '.implode(', ', $line['units']) : ''),
                            ))->all())
                            ->listWithLineBreaks()
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                        TextEntry::make('mealPlan.name_en')->label('Meal plan')->placeholder('Room only'),
                        TextEntry::make('addon_lines')
                            ->label('Add-ons')
                            ->state(fn (Booking $record): array => $record->addons->map(fn (BookingAddon $line) => ($line->quantity > 1 ? "{$line->quantity} × " : '').($line->addon?->name_en ?? 'Add-on'))->all())
                            ->listWithLineBreaks()
                            ->placeholder('None'),
                        TextEntry::make('special_requests')->placeholder('None')->columnSpanFull(),
                    ]),
                Section::make('Amounts')
                    ->description('Prices were calculated by the server from the room rates when the booking was made.')
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('room_subtotal')->money('BDT'),
                        TextEntry::make('discount_total')->label('Discount')->money('BDT'),
                        TextEntry::make('grand_total')->label('Estimated total')->money('BDT'),
                        TextEntry::make('advance_amount')->label('Advance (policy)')->money('BDT'),
                        TextEntry::make('amount_paid')->money('BDT'),
                        TextEntry::make('balance_amount')->label('Balance due')->money('BDT'),
                    ]),
                Section::make('Internal notes')
                    ->description('Only visible to staff.')
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (?string $state): string => nl2br(e((string) $state)))
                            ->html()
                            ->placeholder('No notes yet.'),
                    ]),
                Section::make('Status history')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('statusLogs')
                            ->hiddenLabel()
                            ->columns(['default' => 1, 'sm' => 4])
                            ->schema([
                                TextEntry::make('created_at')->label('When')->dateTime('j M Y, H:i'),
                                TextEntry::make('to_status')
                                    ->label('Status')
                                    ->formatStateUsing(fn (?string $state): string => BookingStatus::tryFrom((string) $state)?->getLabel() ?? (string) $state),
                                TextEntry::make('user.name')->label('By')->placeholder('Website / system'),
                                TextEntry::make('note')->placeholder('—'),
                            ]),
                    ]),
            ]);
    }

    public static function paymentColor(Booking $record): string
    {
        return match ($record->paymentStatus()) {
            'paid' => 'success',
            'partially_paid' => 'info',
            default => 'gray',
        };
    }
}
