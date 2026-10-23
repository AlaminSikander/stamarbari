<?php

namespace App\Filament\Resources\RoomBlocks\Schemas;

use App\Models\Room;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoomBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'code', fn ($query) => $query
                        ->whereHas('category', fn ($category) => $category->withoutTrashed())
                        ->with(['category' => fn ($category) => $category->withCount('rooms')]))
                    ->getOptionLabelFromRecordUsing(fn (Room $record): string => self::label($record))
                    ->required()
                    ->searchable(['code', 'name'])
                    ->preload(),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required(),
                TextInput::make('reason')->required(),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function label(Room $room): string
    {
        $category = $room->category;
        if (! $category) {
            return $room->code;
        }

        return ($category->rooms_count ?? $category->rooms()->count()) > 1
            ? "{$category->name_en} · {$room->code}"
            : $category->name_en;
    }
}
