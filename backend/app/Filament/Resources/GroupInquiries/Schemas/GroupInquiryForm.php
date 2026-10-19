<?php

namespace App\Filament\Resources\GroupInquiries\Schemas;

use App\Enums\InquiryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GroupInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(collect(InquiryStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name]))
                    ->default(InquiryStatus::New->value)
                    ->required(),
                TextInput::make('name')->required(),
                TextInput::make('organization'),
                TextInput::make('phone')->required(),
                TextInput::make('whatsapp'),
                TextInput::make('email')->email(),
                TextInput::make('group_size')->numeric(),
                TextInput::make('adults')->numeric(),
                TextInput::make('children')->numeric(),
                DatePicker::make('preferred_check_in'),
                DatePicker::make('preferred_check_out'),
                TextInput::make('rooms_needed')->numeric(),
                TextInput::make('meal_preference'),
                Textarea::make('requirements')->columnSpanFull(),
                Textarea::make('message')->columnSpanFull(),
            ]);
    }
}
