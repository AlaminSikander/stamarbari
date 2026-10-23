<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoomType;
use App\Exceptions\BookingException;
use App\Filament\Resources\RoomCategories\Pages\EditRoomCategory;
use App\Filament\Resources\RoomCategories\Pages\ListRoomCategories;
use App\Filament\Resources\RoomCategories\RoomCategoryResource;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomPrice;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoomCategoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED = [
        ['Nokkhotro', RoomType::Couple, '2 Adults + 1 Child', 2500],
        ['Ganglota', RoomType::DoubleBed, '4 Adults', 3000],
        ['Pampan', RoomType::DoubleBed, '4 Adults', 3000],
        ['Nongor', RoomType::DoubleBed, '4 Adults', 3000],
        ['Jahaji', RoomType::DoubleBed, '4 Adults', 3000],
        ['Jotsna', RoomType::Couple, '2 Adults + 1 Child', 3000],
        ['Gangchil', RoomType::DoubleBed, '4 Adults', 3500],
        ['Keya', RoomType::DoubleBed, '4 Adults', 3500],
        ['Sea Pearl', RoomType::DoubleBed, '4 Adults', 3500],
        ['Jolchhaya', RoomType::DoubleBed, '4 Adults', 3500],
        ['Projapoti 🦋', RoomType::Couple, '2 Adults + 1 Child', 3000],
        ['Probal', RoomType::DoubleBed, '4 Adults', 3500],
        ['Coral', RoomType::DoubleBed, '4 Adults', 3500],
        ['Jhinuk', RoomType::DoubleBed, '4 Adults', 3500],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::query()->whereHas('roles', fn ($q) => $q->where('name', 'owner'))->firstOrFail());
    }

    public function test_catalog_has_all_fourteen_rooms_with_configured_prices(): void
    {
        $categories = RoomCategory::query()->with('basePrice')->orderBy('sort_order')->get();

        $this->assertCount(14, $categories);
        foreach (self::EXPECTED as $i => [$name, $type, $capacity, $price]) {
            $category = $categories[$i];
            $this->assertSame($name, $category->name_en);
            $this->assertSame($type, $category->room_type);
            $this->assertSame($capacity, $category->capacityLabel());
            $this->assertSame($price, (int) $category->basePrice->amount);
            $this->assertSame(0, $category->weekendPrice()->count(), "{$name} must not have an invented weekend price");
            $this->assertSame(1, $category->rooms()->where('is_active', true)->count(), "{$name} must have one bookable unit");
        }

        $this->assertSame(3, RoomCategory::query()->where('room_type', RoomType::Couple)->count());
        $this->assertSame(11, RoomCategory::query()->where('room_type', RoomType::DoubleBed)->count());
    }

    public function test_reseeding_does_not_overwrite_admin_edits_or_duplicate_rooms(): void
    {
        $keya = RoomCategory::query()->where('slug', 'keya')->firstOrFail();
        $keya->basePrice()->update(['amount' => 3900]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(14, RoomCategory::query()->count());
        $this->assertSame(14, Room::query()->count());
        $this->assertSame('3900.00', $keya->basePrice()->value('amount'));
    }

    public function test_public_rooms_api_lists_each_room_once_with_type_capacity_and_price(): void
    {
        $response = $this->getJson('/api/v1/rooms')->assertOk();
        $rooms = collect($response->json('data'));

        $this->assertCount(14, $rooms);
        $this->assertSame(array_column(self::EXPECTED, 0), $rooms->pluck('name')->all());
        $this->assertSame(14, $rooms->pluck('slug')->unique()->count());

        $jotsna = $rooms->firstWhere('slug', 'jotsna');
        $this->assertSame('couple', $jotsna['room_type']);
        $this->assertSame('Couple Room', $jotsna['room_type_label']);
        $this->assertSame(2, $jotsna['max_adults']);
        $this->assertSame(1, $jotsna['max_children']);
        $this->assertEquals(3000, $jotsna['starting_price']);
        $this->assertSame(1, $jotsna['inventory']);

        $bn = collect($this->getJson('/api/v1/rooms?locale=bn')->json('data'))->firstWhere('slug', 'coral');
        $this->assertSame('ডাবল বেড রুম', $bn['room_type_label']);
    }

    public function test_hidden_and_archived_categories_are_not_public(): void
    {
        RoomCategory::query()->where('slug', 'keya')->update(['is_active' => false]);
        RoomCategory::query()->where('slug', 'coral')->firstOrFail()->delete();

        $slugs = collect($this->getJson('/api/v1/rooms')->json('data'))->pluck('slug');
        $this->assertCount(12, $slugs);
        $this->assertNotContains('keya', $slugs);
        $this->assertNotContains('coral', $slugs);

        $this->getJson('/api/v1/rooms/coral')->assertNotFound();
    }

    public function test_quote_uses_the_category_price(): void
    {
        $gangchil = RoomCategory::query()->where('slug', 'gangchil')->firstOrFail();

        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(13)->toDateString(),
            'adults' => 4,
            'room_category_id' => $gangchil->id,
        ])->assertOk();

        $this->assertSame('double_bed', $response->json('data.rooms.0.room_type'));
        $this->assertEquals(3500, $response->json('data.rooms.0.quote.nightly_rate'));
        $this->assertEquals(10500, $response->json('data.rooms.0.quote.room_subtotal'));
        $this->assertEquals(5250, $response->json('data.rooms.0.quote.advance_amount'));
    }

    public function test_capacity_is_enforced(): void
    {
        $couple = RoomCategory::query()->where('slug', 'nokkhotro')->firstOrFail();

        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(11)->toDateString(),
            'adults' => 3,
            'room_category_id' => $couple->id,
        ])->assertOk();
        $this->assertFalse($response->json('data.rooms.0.fits_guests'));
        $this->assertNull($response->json('data.rooms.0.quote'));

        $this->expectException(BookingException::class);
        app(BookingService::class)->createHold($this->holdPayload('nokkhotro', ['adults' => 3]));
    }

    public function test_single_unit_room_cannot_be_double_booked_but_other_rooms_stay_free(): void
    {
        $first = app(BookingService::class)->createHold($this->holdPayload('keya'));
        app(BookingService::class)->confirm($first);

        $keya = RoomCategory::query()->where('slug', 'keya')->firstOrFail();
        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(21)->toDateString(),
            'check_out' => now()->addDays(23)->toDateString(),
            'adults' => 2,
        ])->assertOk();
        $rooms = collect($response->json('data.rooms'))->keyBy('slug');
        $this->assertSame(0, $rooms['keya']['available_rooms']);
        $this->assertSame(1, $rooms['coral']['available_rooms']);

        $nonOverlapping = app(BookingService::class)->createHold($this->holdPayload('keya', [
            'check_in' => now()->addDays(22)->toDateString(),
            'check_out' => now()->addDays(24)->toDateString(),
        ]));
        $this->assertSame($keya->id, $nonOverlapping->room_category_id);

        try {
            app(BookingService::class)->createHold($this->holdPayload('keya'));
            $this->fail('An overlapping booking for a single-unit room must be rejected.');
        } catch (BookingException) {
            $this->assertSame(2, Booking::query()->where('room_category_id', $keya->id)->count());
        }
    }

    public function test_price_change_does_not_change_existing_confirmed_booking(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload('probal'));
        $booking = app(BookingService::class)->confirm($booking);
        $snapshot = $booking->only(['room_subtotal', 'grand_total', 'advance_amount', 'balance_amount', 'price_breakdown']);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertEquals(7000, (float) $booking->room_subtotal);

        $probal = RoomCategory::query()->where('slug', 'probal')->firstOrFail();
        Livewire::test(EditRoomCategory::class, ['record' => $probal->getRouteKey()])
            ->fillForm(['basePrice.amount' => 5000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('5000.00', $probal->basePrice()->value('amount'));
        $this->assertSame($snapshot, $booking->fresh()->only(array_keys($snapshot)));

        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(40)->toDateString(),
            'check_out' => now()->addDays(41)->toDateString(),
            'adults' => 2,
            'room_category_id' => $probal->id,
        ]);
        $this->assertEquals(5000, $response->json('data.rooms.0.quote.room_subtotal'));
    }

    public function test_admin_table_lists_searches_and_filters_room_categories(): void
    {
        $all = RoomCategory::query()->get();
        $couples = $all->where('room_type', RoomType::Couple);

        Livewire::test(ListRoomCategories::class)
            ->assertCanSeeTableRecords($all)
            ->assertCountTableRecords(14)
            ->assertTableColumnFormattedStateSet('basePrice.amount', '৳2,500', $all->firstWhere('slug', 'nokkhotro'))
            ->assertTableColumnFormattedStateSet('max_adults', '4 Adults', $all->firstWhere('slug', 'keya'))
            ->searchTable('Sea Pearl')
            ->assertCanSeeTableRecords($all->where('slug', 'sea-pearl'))
            ->assertCountTableRecords(1)
            ->searchTable(null)
            ->filterTable('room_type', RoomType::Couple->value)
            ->assertCanSeeTableRecords($couples)
            ->assertCanNotSeeTableRecords($all->where('room_type', RoomType::DoubleBed))
            ->assertCountTableRecords(3);
    }

    public function test_category_without_bookings_can_be_deleted(): void
    {
        $coral = RoomCategory::query()->where('slug', 'coral')->firstOrFail();

        Livewire::test(ListRoomCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($coral));

        $this->assertSoftDeleted($coral);
    }

    public function test_category_with_upcoming_booking_cannot_be_deleted(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload('jhinuk'));
        app(BookingService::class)->confirm($booking);
        $jhinuk = RoomCategory::query()->where('slug', 'jhinuk')->firstOrFail();

        Livewire::test(ListRoomCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($jhinuk))
            ->assertNotified('Room category not deleted');
        $this->assertNotSoftDeleted($jhinuk);

        Livewire::test(ListRoomCategories::class)
            ->selectTableRecords([$jhinuk->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified('Nothing was deleted');
        $this->assertNotSoftDeleted($jhinuk);

        $this->assertFalse($jhinuk->fresh()->delete());
        $this->assertNotSoftDeleted($jhinuk);
    }

    public function test_category_with_only_past_bookings_can_be_archived_but_not_purged(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload('pampan'));
        app(BookingService::class)->confirm($booking);
        $booking->update(['check_in' => now()->subDays(5)->toDateString(), 'check_out' => now()->subDays(3)->toDateString()]);
        $pampan = RoomCategory::query()->where('slug', 'pampan')->firstOrFail();

        Livewire::test(EditRoomCategory::class, ['record' => $pampan->getRouteKey()])
            ->callAction(DeleteAction::class);
        $this->assertSoftDeleted($pampan);

        Livewire::test(EditRoomCategory::class, ['record' => $pampan->getRouteKey()])
            ->callAction(ForceDeleteAction::class)
            ->assertNotified('Room category not deleted');

        $this->assertDatabaseHas('room_categories', ['id' => $pampan->id]);
        $this->assertSame('Pampan', $booking->fresh()->category->name_en);
        $this->assertSame(1, $booking->rooms()->count());
    }

    public function test_only_users_who_manage_rooms_can_open_room_categories(): void
    {
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->assignRole('receptionist');
        $this->actingAs($receptionist);
        $this->assertFalse(RoomCategoryResource::canAccess());

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');
        $this->actingAs($manager);
        $this->assertTrue(RoomCategoryResource::canAccess());
    }

    public function test_migration_archives_legacy_categories_without_losing_bookings(): void
    {
        $migration = require database_path('migrations/2026_10_22_000001_add_room_type_and_named_room_categories.php');
        $migration->down();

        $legacy = RoomCategory::query()->create(['slug' => 'deluxe-couple', 'name_en' => 'Deluxe Couple', 'max_adults' => 2, 'max_children' => 1, 'is_active' => true]);
        RoomPrice::query()->create(['room_category_id' => $legacy->id, 'type' => 'base', 'amount' => 4500, 'is_active' => true]);
        Room::query()->create(['room_category_id' => $legacy->id, 'code' => '101', 'is_active' => true]);
        $busy = RoomCategory::query()->create(['slug' => 'family-room', 'name_en' => 'Family Room', 'max_adults' => 4, 'max_children' => 2, 'is_active' => true]);
        RoomPrice::query()->create(['room_category_id' => $busy->id, 'type' => 'base', 'amount' => 7500, 'is_active' => true]);
        Room::query()->create(['room_category_id' => $busy->id, 'code' => '201', 'is_active' => true]);
        $past = app(BookingService::class)->createHold($this->holdPayload('deluxe-couple'));
        $past->update(['status' => BookingStatus::Completed, 'check_in' => now()->subDays(9)->toDateString(), 'check_out' => now()->subDays(7)->toDateString()]);
        $upcoming = app(BookingService::class)->confirm(app(BookingService::class)->createHold($this->holdPayload('family-room')));
        $pastSubtotal = $past->fresh()->room_subtotal;

        $migration->up();

        $this->assertSoftDeleted($legacy);
        $this->assertFalse($busy->fresh()->is_active);
        $this->assertNotSoftDeleted($busy);
        $this->assertSame(14, RoomCategory::query()->whereNotNull('room_type')->count());
        $this->assertSame('4500.00', RoomPrice::query()->where('room_category_id', $legacy->id)->value('amount'));
        $this->assertSame($pastSubtotal, $past->fresh()->room_subtotal);
        $this->assertSame('Deluxe Couple', $past->fresh()->category->name_en);
        $this->assertSame(BookingStatus::Confirmed, $upcoming->fresh()->status);
        $this->assertSame(1, $upcoming->rooms()->count());

        $public = collect($this->getJson('/api/v1/rooms')->json('data'))->pluck('slug');
        $this->assertCount(14, $public);
        $this->assertNotContains('family-room', $public);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function holdPayload(string $slug, array $overrides = []): array
    {
        return [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(22)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'room_category_id' => RoomCategory::withTrashed()->where('slug', $slug)->value('id'),
            'guest' => [
                'full_name' => 'Test Guest',
                'phone' => '01800000001',
                'email' => 'catalog@example.com',
            ],
            ...$overrides,
        ];
    }
}
