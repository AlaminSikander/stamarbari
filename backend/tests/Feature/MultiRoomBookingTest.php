<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\NotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MultiRoomBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_single_room_selection_stores_booking_time_price(): void
    {
        $booking = $this->hold([['keya', 1]]);

        $this->assertSame(1, $booking->rooms()->count());
        $row = $booking->rooms()->first();
        $this->assertSame($this->category('keya')->id, $row->room_category_id);
        $this->assertSame('3500.00', $row->nightly_rate);
        $this->assertSame(2, $row->nights);
        $this->assertSame('7000.00', $row->subtotal);
        $this->assertEquals(7000, (float) $booking->room_subtotal);
        $this->assertEquals(3500, (float) $booking->advance_amount);
    }

    public function test_multiple_different_categories_are_priced_and_assigned_together(): void
    {
        $booking = $this->hold([['keya', 1], ['nokkhotro', 1], ['coral', 1]], ['adults' => 6, 'children' => 1]);

        $this->assertSame(3, $booking->rooms()->count());
        $this->assertEqualsCanonicalizing(
            [$this->category('keya')->id, $this->category('nokkhotro')->id, $this->category('coral')->id],
            $booking->rooms()->pluck('room_category_id')->all(),
        );
        $this->assertEquals((3500 + 2500 + 3500) * 2, (float) $booking->room_subtotal);
        $this->assertEquals(9500, (float) $booking->advance_amount);
        $this->assertEquals(9500, (float) $booking->balance_amount);
        $this->assertCount(3, $booking->price_breakdown['items']);
        $this->assertSame('Keya, Nokkhotro, Coral', $booking->roomSummary());
    }

    public function test_multiple_rooms_from_the_same_category_get_distinct_physical_rooms(): void
    {
        $keya = $this->category('keya');
        Room::query()->create(['room_category_id' => $keya->id, 'code' => 'KEYA-2', 'is_active' => true]);

        $booking = $this->hold([['keya', 2]], ['adults' => 6]);

        $this->assertSame(2, $booking->rooms()->distinct()->count('room_id'));
        $this->assertEquals(14000, (float) $booking->room_subtotal);
        $this->assertSame('2 × Keya', $booking->roomSummary());
        $this->assertSame(0, app(AvailabilityService::class)->availableCount($keya, $booking->check_in, $booking->check_out));
    }

    public function test_quote_follows_quantity_changes_and_removed_rooms(): void
    {
        $keya = $this->category('keya');
        Room::query()->create(['room_category_id' => $keya->id, 'code' => 'KEYA-2', 'is_active' => true]);

        $one = $this->quote([['keya', 1]], ['adults' => 2])->assertOk();
        $two = $this->quote([['keya', 2]], ['adults' => 4])->assertOk();
        $removed = $this->quote([['keya', 1]], ['adults' => 2])->assertOk();
        $this->quote([['keya', 1], ['coral', 0]], ['adults' => 2])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Each selected room needs a quantity of at least 1.');

        $this->assertEquals(7000, $one->json('data.room_subtotal'));
        $this->assertEquals(14000, $two->json('data.room_subtotal'));
        $this->assertSame(2, $two->json('data.items.0.quantity'));
        $this->assertEquals(7000, $two->json('data.advance_amount'));
        $this->assertCount(1, $removed->json('data.items'));
    }

    public function test_quote_reflects_stay_duration(): void
    {
        $short = $this->quote([['jotsna', 1], ['gangchil', 1]], ['adults' => 2, 'nights' => 1])->assertOk();
        $long = $this->quote([['jotsna', 1], ['gangchil', 1]], ['adults' => 2, 'nights' => 4])->assertOk();

        $this->assertSame(1, $short->json('data.nights'));
        $this->assertEquals(6500, $short->json('data.grand_total'));
        $this->assertSame(4, $long->json('data.nights'));
        $this->assertEquals(26000, $long->json('data.grand_total'));
        $this->assertEquals(13000, $long->json('data.advance_amount'));
        $this->assertEquals(13000, $long->json('data.balance_amount'));
    }

    public function test_requesting_more_rooms_than_available_is_rejected_with_availability(): void
    {
        $response = $this->quote([['keya', 2]], ['adults' => 4])->assertStatus(422);

        $this->assertStringContainsString('Only 1 Keya room available', $response->json('message'));
        $this->assertSame(1, $response->json('errors.availability.'.$this->category('keya')->id));

        $this->expectException(BookingException::class);
        $this->hold([['keya', 2]], ['adults' => 4]);
    }

    public function test_guest_counts_are_validated_against_the_selection(): void
    {
        $this->quote([['keya', 1], ['coral', 1]], ['adults' => 1])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Each room needs at least one adult. You selected 2 rooms for 1 adult.');

        $this->quote([['nokkhotro', 1], ['jotsna', 1]], ['adults' => 5])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The selected rooms sleep up to 4 adults. Add a room or reduce the number of adults.');

        $this->quote([['keya', 1], ['coral', 1]], ['adults' => 4, 'children' => 1])
            ->assertStatus(422)
            ->assertJsonPath('errors.guests.0', 'The selected rooms do not take children. Add a couple room or contact the resort.');

        $this->quote([['keya', 1], ['nokkhotro', 1]], ['adults' => 4, 'children' => 1])->assertOk();
    }

    public function test_second_customer_cannot_take_the_same_last_room(): void
    {
        $first = $this->hold([['jhinuk', 1], ['probal', 1]], ['adults' => 4]);
        $this->assertSame(BookingStatus::Pending, $first->status);

        try {
            $this->hold([['probal', 1], ['keya', 1]], ['adults' => 4, 'phone' => '01700000002']);
            $this->fail('The second booking must not get the room already held.');
        } catch (BookingException $e) {
            $this->assertStringContainsString('Probal is not available', $e->getMessage());
        }

        $this->assertSame(1, Booking::query()->count(), 'A failed multi-room booking must not leave a partial reservation.');
        $this->assertSame(1, BookingRoom::query()->where('room_id', $this->category('probal')->rooms()->value('id'))->count());
    }

    public function test_store_endpoint_accepts_room_list_and_returns_lines(): void
    {
        config(['booking.online_payment_enabled' => true]);

        $response = $this->postJson('/api/v1/bookings', [
            'check_in' => now()->addDays(30)->toDateString(),
            'check_out' => now()->addDays(32)->toDateString(),
            'adults' => 4,
            'children' => 1,
            'rooms' => [
                ['room_category_id' => $this->category('nokkhotro')->id, 'quantity' => 1],
                ['room_category_id' => $this->category('ganglota')->id, 'quantity' => 1],
            ],
            'guest' => ['full_name' => 'Multi Guest', 'phone' => '01811111111'],
        ])->assertCreated();

        $this->assertSame('Your 2 rooms are held. Please complete payment to confirm.', $response->json('message'));
        $this->assertSame(2, $response->json('data.room_count'));
        $this->assertSame('Nokkhotro', $response->json('data.rooms.0.name'));
        $this->assertSame('Ganglota', $response->json('data.rooms.1.name'));
        $this->assertEquals(3000, $response->json('data.rooms.1.nightly_rate'));
        $this->assertEquals(11000, $response->json('data.grand_total'));
        $this->assertEquals(5500, $response->json('data.advance_amount'));

        $this->assertTrue($response->json('data.payment_required'));
        $this->getJson('/api/v1/bookings/'.$response->json('data.booking_number').'?token='.$response->json('data.access_token'))
            ->assertOk()
            ->assertJsonPath('data.room', 'Nokkhotro, Ganglota')
            ->assertJsonCount(2, 'data.rooms');
    }

    public function test_cancelling_releases_every_room(): void
    {
        $booking = app(BookingService::class)->confirm($this->hold([['keya', 1], ['coral', 1]], ['adults' => 4]));
        $free = fn () => app(AvailabilityService::class)->availableRoomsFor(
            [$this->category('keya')->id, $this->category('coral')->id],
            $booking->check_in,
            $booking->check_out,
        )->map->count()->values()->all();

        $this->assertSame([0, 0], $free());
        app(BookingService::class)->cancel($booking, 'Guest changed plans');
        $this->assertSame([1, 1], $free());

        $again = $this->hold([['keya', 1], ['coral', 1]], ['adults' => 4, 'phone' => '01700000003']);
        $this->assertSame(2, $again->rooms()->count());
    }

    public function test_price_changes_do_not_alter_existing_multi_room_booking(): void
    {
        $booking = app(BookingService::class)->confirm($this->hold([['keya', 1], ['nokkhotro', 1]], ['adults' => 3]));
        $this->category('keya')->basePrice()->update(['amount' => 9999]);
        $this->category('nokkhotro')->basePrice()->update(['amount' => 8888]);

        $lines = $booking->fresh()->roomLines();
        $this->assertSame([3500.0, 2500.0], $lines->pluck('nightly_rate')->all());
        $this->assertEquals(12000, (float) $booking->fresh()->room_subtotal);
    }

    public function test_confirmation_message_and_invoice_list_every_room(): void
    {
        $booking = app(BookingService::class)->confirm($this->hold([['keya', 1], ['jotsna', 1]], ['adults' => 3]));
        $booking->load(['guests', 'category', 'mealPlan', 'addons.addon', 'rooms.room', 'rooms.category', 'payments']);

        $html = view('invoices.booking', ['booking' => $booking, 'resort' => ['address' => 'x', 'phone' => 'y', 'email' => 'z']])->render();
        $this->assertStringContainsString('Rooms: Keya, Jotsna', $html);
        $this->assertStringContainsString('Keya (Double-Bed Room)', $html);
        $this->assertStringContainsString('Jotsna (Couple Room)', $html);
        $this->assertStringContainsString('7,000.00', $html);
        $this->assertStringContainsString('6,000.00', $html);
        $this->assertStringContainsString('13,000.00', $html);

        app(NotificationService::class)->notifyBooking($booking, 'booking_confirmed');
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'channel' => 'whatsapp']);
        $this->assertStringContainsString('Rooms: Keya, Jotsna', DB::table('notification_logs')->where('booking_id', $booking->id)->value('payload'));
    }

    public function test_admin_creates_a_multi_room_reservation(): void
    {
        $this->actingAsRole('manager');
        $guest = Guest::query()->create(['full_name' => 'Phone Guest', 'phone' => '01900000000']);

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'check_in' => now()->addDays(15)->toDateString(),
                'check_out' => now()->addDays(18)->toDateString(),
                'adults' => 5,
                'children' => 1,
                'guest_id' => $guest->id,
                'status' => BookingStatus::Confirmed->value,
                'source' => 'phone',
                'amount_paid' => 5000,
            ])
            ->set('data.room_selections', [
                ['room_category_id' => $this->category('nokkhotro')->id, 'quantity' => 1],
                ['room_category_id' => $this->category('sea-pearl')->id, 'quantity' => 1],
            ])
            ->assertSee('Advance required (50%)')
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(2, $booking->rooms()->count());
        $this->assertEquals((2500 + 3500) * 3, (float) $booking->grand_total);
        $this->assertEquals(9000, (float) $booking->advance_amount);
        $this->assertEquals(13000, (float) $booking->balance_amount);
        $this->assertSame('Nokkhotro, Sea Pearl', $booking->roomSummary());
    }

    public function test_admin_cannot_create_a_conflicting_reservation(): void
    {
        $this->actingAsRole('manager');
        app(BookingService::class)->confirm($this->hold([['sea-pearl', 1]], ['adults' => 2]));
        $guest = Guest::query()->create(['full_name' => 'Phone Guest', 'phone' => '01900000000']);

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'check_in' => now()->addDays(21)->toDateString(),
                'check_out' => now()->addDays(23)->toDateString(),
                'adults' => 4,
                'guest_id' => $guest->id,
                'status' => BookingStatus::Confirmed->value,
                'source' => 'phone',
            ])
            ->set('data.room_selections', [
                ['room_category_id' => $this->category('sea-pearl')->id, 'quantity' => 1],
                ['room_category_id' => $this->category('coral')->id, 'quantity' => 1],
            ])
            ->assertSee('Not available')
            ->call('create')
            ->assertHasFormErrors(['room_selections'])
            ->assertNotified('Reservation not saved');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_admin_edit_reprices_changed_stays_and_keeps_prices_otherwise(): void
    {
        $this->actingAsRole('manager');
        $booking = app(BookingService::class)->confirm($this->hold([['keya', 1]], ['adults' => 2]));
        $this->category('keya')->basePrice()->update(['amount' => 4000]);

        $edit = Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()]);
        $this->assertEquals([['room_category_id' => $this->category('keya')->id, 'quantity' => 1]], array_values($edit->get('data.room_selections')));
        $edit->assertSee('booking-time prices')
            ->fillForm(['special_requests' => 'Late arrival', 'amount_paid' => 3500])
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();
        $this->assertEquals(7000, (float) $booking->grand_total, 'Editing notes must not re-price the booking.');
        $this->assertEquals(3500, (float) $booking->balance_amount);
        $this->assertSame('Late arrival', $booking->special_requests);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->set('data.room_selections', [
                ['room_category_id' => $this->category('keya')->id, 'quantity' => 1],
                ['room_category_id' => $this->category('jotsna')->id, 'quantity' => 1],
            ])
            ->fillForm(['adults' => 3])
            ->assertSee('Saving will re-check availability')
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();
        $this->assertSame(2, $booking->rooms()->count());
        $this->assertEquals((4000 + 3000) * 2, (float) $booking->grand_total);
        $this->assertEquals(14000 - 3500, (float) $booking->balance_amount);
        $this->assertSame('Keya, Jotsna', $booking->roomSummary());
    }

    public function test_admin_edit_rejects_rooms_held_by_another_booking(): void
    {
        $this->actingAsRole('manager');
        $mine = app(BookingService::class)->confirm($this->hold([['keya', 1]], ['adults' => 2]));
        app(BookingService::class)->confirm($this->hold([['coral', 1]], ['adults' => 2, 'phone' => '01700000004']));

        Livewire::test(EditBooking::class, ['record' => $mine->getRouteKey()])
            ->set('data.room_selections', [
                ['room_category_id' => $this->category('keya')->id, 'quantity' => 1],
                ['room_category_id' => $this->category('coral')->id, 'quantity' => 1],
            ])
            ->fillForm(['adults' => 4])
            ->call('save')
            ->assertHasFormErrors(['room_selections']);

        $this->assertSame('Keya', $mine->fresh()->roomSummary());
    }

    public function test_admin_cancel_releases_rooms_and_reactivation_rechecks_them(): void
    {
        $this->actingAsRole('manager');
        $booking = app(BookingService::class)->confirm($this->hold([['gangchil', 1]], ['adults' => 2]));

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm(['status' => BookingStatus::Cancelled->value])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->cancelled_at);

        app(BookingService::class)->confirm($this->hold([['gangchil', 1]], ['adults' => 2, 'phone' => '01700000005']));

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm(['status' => BookingStatus::Confirmed->value])
            ->call('save')
            ->assertHasFormErrors(['room_selections']);
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_block_override_needs_permission_and_never_double_books(): void
    {
        $keya = $this->category('keya');
        RoomBlock::query()->create([
            'room_id' => $keya->rooms()->value('id'),
            'starts_on' => now()->addDays(20)->toDateString(),
            'ends_on' => now()->addDays(25)->toDateString(),
            'reason' => 'Painting',
        ]);
        $payload = $this->adminPayload([['keya', 1]]);

        try {
            app(BookingService::class)->createReservation($payload);
            $this->fail('A blocked room must not be booked without an override.');
        } catch (BookingException) {
        }

        $this->actingAsRole('receptionist');
        Livewire::test(CreateBooking::class)
            ->fillForm([...$this->adminForm(), 'override_room_blocks' => true, 'override_reason' => 'Painting done', 'override_confirmed' => true])
            ->set('data.room_selections', [['room_category_id' => $keya->id, 'quantity' => 1]])
            ->assertDontSee('Availability override')
            ->call('create')
            ->assertHasFormErrors(['room_selections']);
        $this->assertSame(0, Booking::query()->count());

        $this->actingAsRole('owner');
        Livewire::test(CreateBooking::class)
            ->fillForm([...$this->adminForm(), 'override_room_blocks' => true, 'override_reason' => 'Painting finished early', 'override_confirmed' => true])
            ->set('data.room_selections', [['room_category_id' => $keya->id, 'quantity' => 1]])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $booking->rooms()->count());
        $this->assertDatabaseHas('booking_status_logs', ['booking_id' => $booking->id, 'note' => 'Reservation created by staff. Room block override: Painting finished early']);

        $this->expectException(BookingException::class);
        app(BookingService::class)->createReservation($this->adminPayload([['keya', 1]]), null, 'Override again');
    }

    public function test_migration_backfills_existing_bookings_from_stored_totals(): void
    {
        $booking = app(BookingService::class)->confirm($this->hold([['keya', 1]], ['adults' => 2]));
        $booking->update(['room_subtotal' => 6400]);
        $migration = require database_path('migrations/2026_10_22_000002_add_booking_time_pricing_to_booking_rooms.php');

        $migration->down();
        $migration->up();

        $row = BookingRoom::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame($this->category('keya')->id, $row->room_category_id);
        $this->assertSame('6400.00', $row->subtotal);
        $this->assertSame('3200.00', $row->nightly_rate);
        $this->assertSame(2, $row->nights);
    }

    /**
     * @param  list<array{0: string, 1: int}>  $rooms
     * @param  array<string, mixed>  $overrides
     */
    private function hold(array $rooms, array $overrides = []): Booking
    {
        return app(BookingService::class)->createHold([
            ...$this->stay($rooms, $overrides),
            'guest' => ['full_name' => 'Test Guest', 'phone' => $overrides['phone'] ?? '01700000001'],
        ]);
    }

    /**
     * @param  list<array{0: string, 1: int}>  $rooms
     * @param  array<string, mixed>  $overrides
     */
    private function quote(array $rooms, array $overrides = [])
    {
        return $this->postJson('/api/v1/bookings/quote', $this->stay($rooms, $overrides));
    }

    /**
     * @param  list<array{0: string, 1: int}>  $rooms
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function stay(array $rooms, array $overrides): array
    {
        $nights = $overrides['nights'] ?? 2;

        return [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(20 + $nights)->toDateString(),
            'adults' => $overrides['adults'] ?? 2,
            'children' => $overrides['children'] ?? 0,
            'rooms' => array_map(fn ($room) => ['room_category_id' => $this->category($room[0])->id, 'quantity' => $room[1]], $rooms),
        ];
    }

    /**
     * @param  list<array{0: string, 1: int}>  $rooms
     * @return array<string, mixed>
     */
    private function adminPayload(array $rooms): array
    {
        return [
            ...$this->stay($rooms, []),
            'guest' => ['full_name' => 'Admin Guest', 'phone' => '01600000000'],
            'status' => 'confirmed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminForm(): array
    {
        return [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(22)->toDateString(),
            'adults' => 2,
            'guest_id' => Guest::query()->firstOrCreate(['phone' => '01500000000'], ['full_name' => 'Form Guest'])->id,
            'status' => BookingStatus::Confirmed->value,
            'source' => 'phone',
        ];
    }

    private function category(string $slug): RoomCategory
    {
        return RoomCategory::query()->where('slug', $slug)->firstOrFail();
    }

    private function actingAsRole(string $role): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
    }
}
