<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GalleryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('gallery_category_id')
                    ->relationship('category', 'name_en')
                    ->required()
                    ->searchable()
                    ->preload(),
                FileUpload::make('path')
                    ->label('Photo')
                    ->image()
                    ->required()
                    ->directory('gallery')
                    ->disk('public')
                    ->visibility('public')
                    ->columnSpanFull(),
                TextInput::make('type')->default('image')->required(),
                TextInput::make('alt_en')->label('Alt text (English)'),
                TextInput::make('alt_bn')->label('Alt text (Bangla)'),
                TextInput::make('caption_en')->label('Caption (English)'),
                TextInput::make('caption_bn')->label('Caption (Bangla)'),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_published')->default(true),
            ]);
    }
}
