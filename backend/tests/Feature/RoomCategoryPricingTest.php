<?php

namespace Tests\Feature;

use App\Enums\RoomType;
use App\Filament\Resources\RoomCategories\Pages\CreateRoomCategory;
use App\Filament\Resources\RoomCategories\Pages\EditRoomCategory;
use App\Models\RoomCategory;
use App\Models\RoomPrice;
use App\Models\User;
use App\Services\PricingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RoomCategoryPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::query()->whereHas('roles', fn ($q) => $q->where('name', 'owner'))->firstOrFail());
    }

    public function test_edit_form_loads_and_updates_prices(): void
    {
        $category = RoomCategory::query()->where('slug', 'nokkhotro')->firstOrFail();

        Livewire::test(EditRoomCategory::class, ['record' => $category->getRouteKey()])
            ->assertSchemaStateSet([
                'name_en' => 'Nokkhotro',
                'room_type' => RoomType::Couple,
                'basePrice.amount' => '2500.00',
            ])
            ->fillForm([
                'basePrice.amount' => 2800,
                'weekendPrice.amount' => 3200,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2800.00', $category->basePrice()->value('amount'));
        $this->assertSame('3200.00', $category->weekendPrice()->value('amount'));
        $this->assertSame(1, RoomPrice::query()->where('room_category_id', $category->id)->where('type', 'base')->count());

        Livewire::test(EditRoomCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['weekendPrice.amount' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($category->weekendPrice()->first());
        $saturday = Carbon::parse('next saturday');
        $this->assertSame(2800.0, app(PricingService::class)->nightlyRate($category->fresh(), $saturday));
    }

    public function test_create_form_saves_room_type_price_and_physical_room(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => 'Lake Suite',
                'room_type' => RoomType::DoubleBed->value,
                'slug' => 'lake-suite',
                'max_adults' => 4,
                'max_children' => 0,
                'basePrice.amount' => 6000,
                'weekendPrice.amount' => 6500,
            ])
            ->set('data.rooms', [['code' => 'LAKE-1', 'is_active' => true]])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = RoomCategory::query()->where('slug', 'lake-suite')->firstOrFail();

        $this->assertSame(RoomType::DoubleBed, $category->room_type);
        $this->assertSame('6000.00', $category->basePrice()->value('amount'));
        $this->assertSame('6500.00', $category->weekendPrice()->value('amount'));
        $this->assertTrue((bool) $category->basePrice()->value('is_active'));
        $this->assertSame(['LAKE-1'], $category->rooms()->pluck('code')->all());
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => '',
                'room_type' => null,
                'slug' => 'no-price-room',
                'max_adults' => 0,
                'basePrice.amount' => null,
            ])
            ->set('data.rooms', [['code' => '', 'is_active' => true]])
            ->call('create')
            ->assertHasFormErrors([
                'name_en' => 'required',
                'room_type' => 'required',
                'max_adults' => 'min',
                'basePrice.amount' => 'required',
            ]);

        $this->assertDatabaseMissing('room_categories', ['slug' => 'no-price-room']);
    }

    public function test_room_type_must_be_a_known_type(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => 'Odd Room',
                'room_type' => 'penthouse',
                'slug' => 'odd-room',
                'max_adults' => 2,
                'max_children' => 0,
                'basePrice.amount' => 2000,
            ])
            ->set('data.rooms', [['code' => 'ODD', 'is_active' => true]])
            ->call('create')
            ->assertHasFormErrors(['room_type']);
    }

    public function test_room_name_must_be_unique(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => 'Keya',
                'room_type' => RoomType::DoubleBed->value,
                'slug' => 'keya-2',
                'max_adults' => 4,
                'max_children' => 0,
                'basePrice.amount' => 3500,
            ])
            ->set('data.rooms', [['code' => 'KEYA-2', 'is_active' => true]])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'unique']);
    }
}
