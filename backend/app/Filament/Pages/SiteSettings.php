<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.site-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $keys = [
            'phone', 'whatsapp', 'email', 'address', 'maps_url', 'facebook_url', 'instagram_url',
            'check_in_time', 'check_out_time', 'child_policy_age', 'cancellation_hours',
            'cancellation_refund_percent', 'advance_percent', 'booking_hold_minutes', 'extra_bed_price',
            'google_reviews_url', 'tripadvisor_url', 'trade_license', 'tourism_registration',
            'wifi_status', 'electricity_notes', 'network_notes', 'drinking_water_notes',
            'emergency_medical', 'emergency_phone', 'coast_guard', 'local_emergency', 'resort_emergency',
        ];
        foreach ($keys as $key) {
            $this->data[$key] = Setting::getValue($key, '');
        }
        $this->data['missed_ship_refund'] = Setting::getValue('missed_ship_refund', '0') === '1';
        $this->data['extra_bed_available'] = Setting::getValue('extra_bed_available', '1') === '1';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact')->schema([
                TextInput::make('phone'),
                TextInput::make('whatsapp'),
                TextInput::make('email')->email(),
                Textarea::make('address'),
                TextInput::make('maps_url')->url()->nullable(),
                TextInput::make('facebook_url')->url()->nullable(),
                TextInput::make('instagram_url'),
            ])->columns(2),
            Section::make('Booking rules')->schema([
                TextInput::make('check_in_time'),
                TextInput::make('check_out_time'),
                TextInput::make('child_policy_age')->numeric(),
                TextInput::make('cancellation_hours')->numeric(),
                TextInput::make('cancellation_refund_percent')->numeric(),
                TextInput::make('advance_percent')->numeric(),
                TextInput::make('booking_hold_minutes')->numeric(),
                Toggle::make('extra_bed_available'),
                TextInput::make('extra_bed_price')->numeric(),
                Toggle::make('missed_ship_refund')->label('Refund if guest misses ship'),
            ])->columns(2),
            Section::make('Facilities & emergency')->schema([
                TextInput::make('wifi_status'),
                Textarea::make('electricity_notes'),
                Textarea::make('network_notes'),
                Textarea::make('drinking_water_notes'),
                Textarea::make('emergency_medical'),
                TextInput::make('emergency_phone'),
                TextInput::make('coast_guard'),
                TextInput::make('local_emergency'),
                TextInput::make('resort_emergency'),
                TextInput::make('google_reviews_url'),
                TextInput::make('tripadvisor_url'),
                TextInput::make('trade_license'),
                TextInput::make('tourism_registration'),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->action('save'),
        ];
    }

    public function save(): void
    {
        foreach ($this->data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::setValue($key, $value ?? '');
        }
        Notification::make()->title('Settings saved')->success()->send();
    }
}
