<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Resources\Bookings\Support\BookingActions;
use App\Models\Booking;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['guest', 'guests', 'category', 'rooms.category', 'rooms.room']))
            ->columns([
                TextColumn::make('booking_number')->label('Reference')->searchable()->sortable()->copyable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('guest.full_name')->label('Guest')->searchable(),
                TextColumn::make('guest.phone')->label('Phone')->searchable(),
                TextColumn::make('rooms_summary')
                    ->label('Rooms')
                    ->state(fn (Booking $record): string => $record->roomSummary())
                    ->wrap(),
                TextColumn::make('check_in')->date()->sortable(),
                TextColumn::make('check_out')->date()->sortable(),
                TextColumn::make('grand_total')->label('Estimated total')->money('BDT')->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->state(fn (Booking $record): string => $record->paymentStatusLabel())
                    ->badge()
                    ->color(fn (Booking $record): string => BookingInfolist::paymentColor($record)),
                TextColumn::make('amount_paid')->money('BDT')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Requested')->since()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $status) => [$status->value => $status->getLabel()])->all()),
                Filter::make('check_in')
                    ->schema([
                        DatePicker::make('check_in_from')->label('Check-in from'),
                        DatePicker::make('check_in_until')->label('Check-in until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['check_in_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('check_in', '>=', $date))
                        ->when($data['check_in_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('check_in', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['check_in_from'] ?? null) {
                            $indicators[] = 'Check-in from '.Carbon::parse($data['check_in_from'])->format('j M Y');
                        }
                        if ($data['check_in_until'] ?? null) {
                            $indicators[] = 'Check-in until '.Carbon::parse($data['check_in_until'])->format('j M Y');
                        }

                        return $indicators;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make(BookingActions::all()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
