<?php

namespace App\Filament\Pages;

use App\Support\HomepageContent;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class HomepageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Homepage & Imagery';

    protected static ?string $title = 'Homepage & Imagery';

    protected static ?int $navigationSort = 89;

    protected string $view = 'filament.pages.site-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(HomepageContent::raw());
    }

    private static function image(string $name, string $label, string $directory = 'homepage'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->imageEditor()
            ->maxSize(8192)
            ->directory($directory)
            ->disk('public')
            ->visibility('public')
            ->helperText('Landscape, at least 2000px wide. Leave empty to use the built-in photograph.');
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    private static function bilingual(string $name, string $label, bool $long = false): array
    {
        $make = fn (string $suffix, string $lang) => $long
            ? Textarea::make("{$name}_{$suffix}")->label("{$label} ({$lang})")->rows(3)
            : TextInput::make("{$name}_{$suffix}")->label("{$label} ({$lang})");

        return [$make('en', 'English'), $make('bn', 'Bangla')];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Homepage')->tabs([
                Tab::make('Hero')->icon('heroicon-o-photo')->schema([
                    Radio::make('hero.media_type')
                        ->label('Background')
                        ->options(['image' => 'Photograph', 'video' => 'Video (with poster image)'])
                        ->default('image')
                        ->inline()
                        ->live(),
                    Grid::make(2)->schema([
                        self::image('hero.image', 'Hero photograph'),
                        FileUpload::make('hero.video')
                            ->label('Hero video (MP4, muted loop, under 15 MB)')
                            ->acceptedFileTypes(['video/mp4', 'video/webm'])
                            ->maxSize(15360)
                            ->directory('homepage/video')
                            ->disk('public')
                            ->visibility('public')
                            ->visible(fn (Get $get) => $get('hero.media_type') === 'video'),
                        self::image('hero.poster', 'Video poster (first frame)')
                            ->visible(fn (Get $get) => $get('hero.media_type') === 'video'),
                    ]),
                    Section::make('Headline')
                        ->description('Leave empty to keep the default copy. The accent is shown in gold italic on its own line.')
                        ->schema([
                            ...self::bilingual('hero.eyebrow', 'Small label above the title'),
                            ...self::bilingual('hero.title_lead', 'Title'),
                            ...self::bilingual('hero.title_accent', 'Title accent'),
                            ...self::bilingual('hero.body', 'Supporting sentence', true),
                        ])
                        ->columns(2),
                ]),
                Tab::make('Welcome')->icon('heroicon-o-home-modern')->schema([
                    self::image('welcome.image', 'Welcome section photograph'),
                    Repeater::make('welcome.stats')
                        ->label('Facts strip (keep these true)')
                        ->schema([
                            TextInput::make('value')->label('Value, e.g. 5 or 2 min')->required(),
                            TextInput::make('label_en')->label('Label (English)'),
                            TextInput::make('label_bn')->label('Label (Bangla)'),
                        ])
                        ->columns(3)
                        ->maxItems(4)
                        ->reorderable()
                        ->defaultItems(0),
                ]),
                Tab::make('Experiences')->icon('heroicon-o-sun')->schema([
                    Repeater::make('experiences')
                        ->label('Island experiences (up to 6 cards)')
                        ->schema([
                            self::image('image', 'Photograph'),
                            Grid::make(2)->schema([
                                ...self::bilingual('title', 'Title'),
                                ...self::bilingual('body', 'Short description', true),
                            ]),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['title_en'] ?? null)
                        ->collapsible()
                        ->maxItems(6)
                        ->reorderable()
                        ->defaultItems(0),
                ]),
                Tab::make('Imagery')->icon('heroicon-o-squares-2x2')->schema([
                    Grid::make(2)->schema([
                        self::image('saint_martin.image_primary', 'Saint Martin section, large image'),
                        self::image('saint_martin.image_secondary', 'Saint Martin section, inset image'),
                        self::image('final_cta.image', 'Closing banner image'),
                    ]),
                ]),
                Tab::make('Page headers')->icon('heroicon-o-rectangle-stack')->schema([
                    Grid::make(3)->schema(
                        collect(HomepageContent::PAGE_HEROES)
                            ->map(fn (string $route) => self::image(
                                "page_heroes.{$route}",
                                Str::of($route)->replace('-', ' ')->title()->toString(),
                                'page-heroes',
                            ))
                            ->all()
                    ),
                ]),
            ])->persistTabInQueryString(),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save changes')->action('save'),
        ];
    }

    public function save(): void
    {
        HomepageContent::save($this->form->getState());
        Notification::make()->title('Homepage saved')->body('Changes appear on the website within two minutes.')->success()->send();
    }
}
