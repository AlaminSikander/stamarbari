<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Support\ReservationSummary;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\RoomCategory;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Stay')
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 5])
                    ->schema([
                        DatePicker::make('check_in')->required()->live(),
                        DatePicker::make('check_out')->required()->after('check_in')->live(),
                        TextInput::make('adults')->integer()->required()->minValue(1)->maxValue(80)->default(2)->live(onBlur: true),
                        TextInput::make('children')->integer()->required()->minValue(0)->maxValue(80)->default(0)->live(onBlur: true),
                        Select::make('meal_plan_id')->label('Meal plan')->relationship('mealPlan', 'name_en')->preload()->live(),
                    ]),
                Section::make('Rooms')
                    ->description('Add each room category and how many rooms of it. Physical rooms are assigned automatically from what is free for every night of the stay.')
                    ->schema([
                        Repeater::make('room_selections')
                            ->hiddenLabel()
                            ->columns(['default' => 1, 'sm' => 4])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(20)
                            ->reorderable(false)
                            ->addActionLabel('Add another room')
                            ->live()
                            ->schema([
                                Select::make('room_category_id')
                                    ->label('Room')
                                    ->options(fn (?Booking $record): array => self::roomOptions($record))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->columnSpan(['default' => 1, 'sm' => 3]),
                                TextInput::make('quantity')
                                    ->label('Rooms')
                                    ->integer()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->default(1)
                                    ->live(onBlur: true),
                            ]),
                        TextEntry::make('reservation_summary')
                            ->hiddenLabel()
                            ->state(fn (Get $get, ?Booking $record) => ReservationSummary::render($get, $record))
                            ->html(),
                    ]),
                Section::make('Guest & payment')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('guest_id')
                            ->label('Guest')
                            ->relationship('guest', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (Guest $guest): string => "{$guest->full_name} · {$guest->phone}")
                            ->searchable(['full_name', 'phone', 'email'])
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('full_name')->required()->maxLength(120),
                                TextInput::make('phone')->tel()->required()->maxLength(30),
                                TextInput::make('email')->email()->maxLength(255),
                            ]),
                        TextInput::make('booking_number')
                            ->helperText('Leave empty to generate automatically.')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true)
                            ->visibleOn('create'),
                        Select::make('status')
                            ->options(fn (string $operation) => collect($operation === 'create'
                                ? [BookingStatus::Confirmed, BookingStatus::PendingConfirmation, BookingStatus::Pending]
                                : BookingStatus::cases())->mapWithKeys(fn (BookingStatus $case) => [$case->value => $case->getLabel()]))
                            ->default(BookingStatus::Confirmed->value)
                            ->helperText(fn (string $operation) => $operation === 'create'
                                ? 'Pending Confirmation does not reserve the rooms. Pending Payment holds them only for the payment hold time. Confirmed keeps them reserved.'
                                : 'Pending Confirmation, cancelled, expired or completed bookings do not reserve rooms.')
                            ->required(),
                        Select::make('source')
                            ->options(collect(BookingSource::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                            ->default(BookingSource::Phone->value)
                            ->required(),
                        TextInput::make('amount_paid')
                            ->label('Amount paid')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('৳')
                            ->default(0)
                            ->live(onBlur: true),
                        Textarea::make('special_requests')->columnSpanFull(),
                        Textarea::make('internal_notes')
                            ->helperText('Only visible to staff. Never shown to the guest.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Availability override')
                    ->description('Only for rooms closed by a maintenance block. Rooms held by another booking can never be double-booked.')
                    ->visible(fn (): bool => (bool) auth()->user()?->can('manage_rooms'))
                    ->collapsed()
                    ->schema([
                        Toggle::make('override_room_blocks')
                            ->label('Allow rooms that are closed by a maintenance block')
                            ->live()
                            ->dehydrated(),
                        Textarea::make('override_reason')
                            ->label('Reason')
                            ->required(fn (Get $get): bool => (bool) $get('override_room_blocks'))
                            ->visible(fn (Get $get): bool => (bool) $get('override_room_blocks'))
                            ->maxLength(255),
                        Checkbox::make('override_confirmed')
                            ->label('I confirm this room is ready for guests despite its maintenance block.')
                            ->accepted(fn (Get $get): bool => (bool) $get('override_room_blocks'))
                            ->visible(fn (Get $get): bool => (bool) $get('override_room_blocks')),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function roomOptions(?Booking $record = null): array
    {
        $current = $record ? $record->roomLines()->pluck('room_category_id')->all() : [];

        return RoomCategory::withTrashed()
            ->with('basePrice')
            ->where(fn ($query) => $query->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))->orWhereIn('id', $current))
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (RoomCategory $category) => [
                $category->id => collect([
                    $category->name_en,
                    $category->room_type?->getLabel(),
                    $category->capacityLabel(),
                    $category->basePrice ? '৳'.number_format((float) $category->basePrice->amount).'/night' : null,
                ])->filter()->implode(' · ').($category->is_active && ! $category->trashed() ? '' : ' (not bookable)'),
            ])
            ->all();
    }
}
