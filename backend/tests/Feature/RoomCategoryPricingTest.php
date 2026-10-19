<?php

namespace Tests\Feature;

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
        $category = RoomCategory::query()->where('slug', 'deluxe-couple')->firstOrFail();

        Livewire::test(EditRoomCategory::class, ['record' => $category->getRouteKey()])
            ->assertSchemaStateSet([
                'basePrice.amount' => '4500.00',
                'weekendPrice.amount' => '5000.00',
            ])
            ->fillForm([
                'basePrice.amount' => 4800,
                'weekendPrice.amount' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('4800.00', $category->basePrice()->value('amount'));
        $this->assertSame(1, RoomPrice::query()->where('room_category_id', $category->id)->where('type', 'base')->count());
        $this->assertNull($category->weekendPrice()->first());

        $saturday = Carbon::parse('next saturday');
        $this->assertSame(4800.0, app(PricingService::class)->nightlyRate($category->fresh(), $saturday));
    }

    public function test_create_form_saves_base_and_weekend_prices(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => 'Lake Suite',
                'slug' => 'lake-suite',
                'max_adults' => 2,
                'max_children' => 1,
                'basePrice.amount' => 6000,
                'weekendPrice.amount' => 6500,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = RoomCategory::query()->where('slug', 'lake-suite')->firstOrFail();

        $this->assertSame('6000.00', $category->basePrice()->value('amount'));
        $this->assertSame('6500.00', $category->weekendPrice()->value('amount'));
        $this->assertTrue((bool) $category->basePrice()->value('is_active'));
    }

    public function test_base_price_is_required(): void
    {
        Livewire::test(CreateRoomCategory::class)
            ->fillForm([
                'name_en' => 'No Price Room',
                'slug' => 'no-price-room',
                'basePrice.amount' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['basePrice.amount' => 'required']);
    }
}
