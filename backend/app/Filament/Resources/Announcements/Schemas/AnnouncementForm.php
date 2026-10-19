<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_en')->required(),
                TextInput::make('title_bn'),
                Textarea::make('message_en')->required()->columnSpanFull(),
                Textarea::make('message_bn')->columnSpanFull(),
                Select::make('priority')
                    ->options(['info' => 'Info', 'warning' => 'Warning', 'urgent' => 'Urgent'])
                    ->default('info')
                    ->required(),
                Toggle::make('show_on_booking')->default(false),
                Toggle::make('is_active')->default(true),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('cta_label_en'),
                TextInput::make('cta_url'),
            ]);
    }
}
