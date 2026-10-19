<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question_en')->required()->columnSpanFull(),
                TextInput::make('question_bn')->columnSpanFull(),
                Textarea::make('answer_en')->required()->rows(5)->columnSpanFull(),
                Textarea::make('answer_bn')->rows(5)->columnSpanFull(),
                TextInput::make('category'),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_published')->default(true),
            ]);
    }
}
