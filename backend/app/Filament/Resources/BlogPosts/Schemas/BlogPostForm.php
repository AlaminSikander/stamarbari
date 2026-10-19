<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogCategory;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_en')
                    ->label('Title (English)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('title_bn')
                    ->label('Title (Bangla)')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('blog_category_id')
                    ->label('Category')
                    ->options(fn () => BlogCategory::query()->pluck('name_en', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('author_id')
                    ->label('Author')
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->nullable(),
                RichEditor::make('content_en')
                    ->label('Content (English)')
                    ->columnSpanFull()
                    ->minHeight('22rem')
                    ->stickyToolbar()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog')
                    ->fileAttachmentsVisibility('public'),
                RichEditor::make('content_bn')
                    ->label('Content (Bangla)')
                    ->columnSpanFull()
                    ->minHeight('22rem')
                    ->stickyToolbar()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog')
                    ->fileAttachmentsVisibility('public'),
                FileUpload::make('featured_image')
                    ->image()
                    ->directory('blog')
                    ->disk('public')
                    ->visibility('public'),
                TextInput::make('tags')
                    ->helperText('Comma-separated, for example: Rooms, Garden view'),
                Toggle::make('is_published')
                    ->label('Published')
                    ->default(false),
                DateTimePicker::make('published_at')
                    ->seconds(false),
                TextInput::make('seo_title')->maxLength(255),
                Textarea::make('meta_description')->rows(3),
            ]);
    }
}
