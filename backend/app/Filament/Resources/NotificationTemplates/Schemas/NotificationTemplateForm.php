<?php

namespace App\Filament\Resources\NotificationTemplates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NotificationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')->required()->unique(ignoreRecord: true),
                TextInput::make('name')->required(),
                Select::make('channel')
                    ->options(['whatsapp' => 'WhatsApp', 'email' => 'Email', 'sms' => 'SMS'])
                    ->required(),
                TextInput::make('subject'),
                Textarea::make('body')->required()->rows(8)->columnSpanFull(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
