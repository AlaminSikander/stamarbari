<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ContentPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_en')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('title_bn'),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                RichEditor::make('body_en')
                    ->label('Body (English)')
                    ->columnSpanFull()
                    ->minHeight('18rem')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('pages')
                    ->fileAttachmentsVisibility('public'),
                RichEditor::make('body_bn')
                    ->label('Body (Bangla)')
                    ->columnSpanFull()
                    ->minHeight('18rem')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('pages')
                    ->fileAttachmentsVisibility('public'),
                Textarea::make('excerpt_en'),
                Textarea::make('excerpt_bn'),
                FileUpload::make('hero_image')->image()->directory('pages')->disk('public')->visibility('public'),
                Toggle::make('is_published')->default(true),
                Toggle::make('indexable')->default(true),
                TextInput::make('seo_title'),
                Textarea::make('meta_description'),
            ]);
    }
}
