<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Guest;
use App\Services\BookingService;
use App\Support\PhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Booking request actions shared by the bookings table and the booking page.
 */
class BookingActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [
            self::confirm(),
            self::checkAvailability(),
            self::updateStatus(),
            self::whatsAppGuest(),
            self::callGuest(),
            self::addNote(),
        ];
    }

    public static function checkAvailability(): Action
    {
        return Action::make('checkAvailability')
            ->label('Check availability')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->color('gray')
            ->visible(fn (Booking $record): bool => ! $record->trashed() && ! in_array($record->status, [BookingStatus::Completed, BookingStatus::Refunded], true))
            ->action(function (Booking $record): void {
                try {
                    $problem = app(BookingService::class)->availabilityProblem($record);
                } catch (BookingException $e) {
                    $problem = $e->getMessage();
                }

                if ($problem) {
                    Notification::make()->danger()->title('Rooms not available')->body($problem)->persistent()->send();

                    return;
                }
                Notification::make()
                    ->success()
                    ->title('Rooms are available')
                    ->body("{$record->roomSummary()} is free from {$record->check_in->format('j M Y')} to {$record->check_out->format('j M Y')}.")
                    ->send();
            });
    }

    public static function confirm(): Action
    {
        return Action::make('confirmBooking')
            ->label('Confirm booking')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Booking $record): bool => ! $record->trashed() && in_array($record->status, [BookingStatus::PendingConfirmation, BookingStatus::Pending], true))
            ->requiresConfirmation()
            ->modalHeading(fn (Booking $record): string => "Confirm {$record->booking_number}?")
            ->modalDescription('Availability is checked again before confirming. The rooms are then reserved for these dates. No payment is collected here.')
            ->modalSubmitActionLabel('Confirm booking')
            ->action(fn (Booking $record) => self::moveTo($record, BookingStatus::Confirmed));
    }

    public static function updateStatus(): Action
    {
        return Action::make('updateStatus')
            ->label('Update status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (Booking $record): bool => ! $record->trashed())
            ->fillForm(fn (Booking $record): array => ['status' => $record->status->value])
            ->schema([
                Select::make('status')
                    ->options(fn (Booking $record): array => collect([...BookingStatus::workflow(), $record->status])
                        ->unique()
                        ->mapWithKeys(fn (BookingStatus $status) => [$status->value => $status->getLabel()])
                        ->all())
                    ->helperText('Confirmed and Checked In re-check room availability. Cancelled releases the rooms.')
                    ->required(),
            ])
            ->modalSubmitActionLabel('Update status')
            ->action(fn (Booking $record, array $data) => self::moveTo($record, BookingStatus::from($data['status'])));
    }

    public static function whatsAppGuest(): Action
    {
        return Action::make('whatsAppGuest')
            ->label('WhatsApp guest')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (Booking $record): ?string => PhoneNumber::whatsAppUrl(
                self::guest($record)?->whatsapp ?: self::guest($record)?->phone,
                'Hello '.(self::guest($record)?->full_name ?? '').", this is S T Amar Bari Resort about your booking request {$record->booking_number}.",
            ))
            ->openUrlInNewTab()
            ->visible(fn (Booking $record): bool => PhoneNumber::forWhatsApp(self::guest($record)?->whatsapp ?: self::guest($record)?->phone) !== null);
    }

    public static function callGuest(): Action
    {
        return Action::make('callGuest')
            ->label(fn (Booking $record): string => 'Call '.(self::guest($record)?->phone ?? 'guest'))
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->url(fn (Booking $record): ?string => PhoneNumber::telUrl(self::guest($record)?->phone))
            ->visible(fn (Booking $record): bool => filled(self::guest($record)?->phone));
    }

    public static function addNote(): Action
    {
        return Action::make('addNote')
            ->label('Add internal note')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->schema([
                Textarea::make('note')
                    ->label('Note')
                    ->helperText('Only visible to staff.')
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->modalSubmitActionLabel('Save note')
            ->action(function (Booking $record, array $data): void {
                $entry = '['.now()->format('j M Y, H:i').' · '.(auth()->user()?->name ?? 'Staff').'] '.trim($data['note']);
                $record->internal_notes = filled($record->internal_notes) ? $record->internal_notes."\n\n".$entry : $entry;
                $record->save();

                Notification::make()->success()->title('Note added')->send();
            });
    }

    public static function guest(Booking $record): BookingGuest|Guest|null
    {
        $record->loadMissing(['guests', 'guest']);

        return $record->guests->firstWhere('is_primary', true) ?? $record->guests->first() ?? $record->guest;
    }

    private static function moveTo(Booking $record, BookingStatus $to): void
    {
        if ($record->status === $to) {
            return;
        }

        try {
            app(BookingService::class)->changeStatus($record, $to, auth()->id());
        } catch (BookingException $e) {
            Notification::make()->danger()->title('Status not changed')->body($e->getMessage())->persistent()->send();

            return;
        }
        $record->refresh();

        Notification::make()->success()->title("{$record->booking_number} is now {$to->getLabel()}")->send();
    }
}
