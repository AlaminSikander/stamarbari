<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['booking.online_payment_enabled' => false]);
    }

    public function test_booking_is_saved_without_payment_as_pending_confirmation(): void
    {
        $response = $this->submit()->assertCreated();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your booking request has been submitted successfully! We will contact you shortly to confirm your booking.')
            ->assertJsonPath('data.status', 'pending_confirmation')
            ->assertJsonPath('data.status_label', 'Pending Confirmation')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.payment_required', false)
            ->assertJsonPath('data.guest.full_name', 'Rahim Uddin')
            ->assertJsonPath('data.nights', 2);

        $booking = Booking::query()->where('booking_number', $response->json('data.booking_number'))->firstOrFail();
        $this->assertSame(BookingStatus::PendingConfirmation, $booking->status);
        $this->assertNull($booking->hold_expires_at, 'A request must not be a payment hold that expires.');
        $this->assertNull($booking->confirmed_at);
        $this->assertEquals(0, (float) $booking->amount_paid);
        $this->assertSame(0, Payment::query()->count(), 'No payment may be started.');
        $this->assertMatchesRegularExpression('/^AMR-[A-Z0-9]{6}$/', $booking->booking_number);
        $this->assertDatabaseHas('booking_status_logs', ['booking_id' => $booking->id, 'to_status' => 'pending_confirmation']);

        app(BookingService::class)->expireHolds();
        $this->assertSame(BookingStatus::PendingConfirmation, $booking->fresh()->status, 'The hold-expiry job must leave requests alone.');
    }

    public function test_each_booking_gets_a_unique_reference(): void
    {
        $first = $this->submit(['rooms' => [['room_category_id' => $this->category('keya')->id, 'quantity' => 1]]])->assertCreated();
        $second = $this->submit(['rooms' => [['room_category_id' => $this->category('coral')->id, 'quantity' => 1]], 'request_key' => (string) Str::uuid()])->assertCreated();

        $this->assertNotSame($first->json('data.booking_number'), $second->json('data.booking_number'));
        $this->assertSame(2, Booking::query()->count());
    }

    public function test_amounts_are_calculated_on_the_server(): void
    {
        $response = $this->submit([
            'grand_total' => 1,
            'room_subtotal' => 1,
            'price' => 1,
            'rooms' => [
                ['room_category_id' => $this->category('keya')->id, 'quantity' => 1, 'nightly_rate' => 1],
                ['room_category_id' => $this->category('nokkhotro')->id, 'quantity' => 1],
            ],
            'adults' => 3,
        ])->assertCreated();

        $this->assertEquals((3500 + 2500) * 2, $response->json('data.grand_total'));
        $this->assertEquals((3500 + 2500) * 2, (float) Booking::query()->firstOrFail()->grand_total);
    }

    public function test_whatsapp_message_is_built_from_the_saved_booking(): void
    {
        $response = $this->submit([
            'special_requests' => 'Late arrival by ship',
            'guest' => ['full_name' => 'Rahim Uddin', 'phone' => '01712345678', 'email' => 'rahim@example.com'],
        ])->assertCreated();
        $number = $response->json('data.booking_number');
        $message = $response->json('data.whatsapp_message');

        $checkIn = now()->addDays(20);
        $checkOut = now()->addDays(22);
        foreach ([
            'New Booking Request',
            "Booking Reference: {$number}",
            'Guest Name: Rahim Uddin',
            'Phone: 01712345678',
            'Email: rahim@example.com',
            'Rooms: Keya',
            'Check-in: '.$checkIn->format('D, j M Y'),
            'Check-out: '.$checkOut->format('D, j M Y'),
            'Nights: 2',
            'Adults: 2',
            'Children: 0',
            'Add-ons: None',
            'Estimated Total: ৳7,000',
            'Special Requests: Late arrival by ship',
            'Booking Status: Pending Confirmation',
            'Payment Status: Not Paid — Payment Disabled Temporarily',
        ] as $line) {
            $this->assertStringContainsString($line, $message);
        }
    }

    public function test_duplicate_submission_with_the_same_request_key_returns_the_first_booking(): void
    {
        $key = (string) Str::uuid();
        $first = $this->submit(['request_key' => $key])->assertCreated();
        $again = $this->submit(['request_key' => $key])->assertOk();

        $this->assertSame($first->json('data.booking_number'), $again->json('data.booking_number'));
        $this->assertSame($first->json('data.access_token'), $again->json('data.access_token'));
        $this->assertSame(1, Booking::query()->count());

        $this->submit(['request_key' => $key, 'guest' => ['full_name' => 'Someone Else', 'phone' => '01899999999']])
            ->assertStatus(409)
            ->assertJsonMissingPath('data');
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_identical_request_without_a_key_is_not_saved_twice(): void
    {
        $payload = $this->payload();
        unset($payload['request_key']);

        $first = $this->postJson('/api/v1/bookings', $payload)->assertCreated();
        $again = $this->postJson('/api/v1/bookings', $payload)->assertOk();

        $this->assertSame($first->json('data.booking_number'), $again->json('data.booking_number'));
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_pending_requests_do_not_reserve_rooms_and_confirmation_rechecks_availability(): void
    {
        $first = $this->submit()->assertCreated();
        $second = $this->submit(['request_key' => (string) Str::uuid(), 'guest' => ['full_name' => 'Second Guest', 'phone' => '01822222222']])->assertCreated();
        $this->assertSame(2, Booking::query()->count(), 'A request does not hold the room, so another guest can still ask for it.');

        $service = app(BookingService::class);
        $a = Booking::query()->where('booking_number', $first->json('data.booking_number'))->firstOrFail();
        $b = Booking::query()->where('booking_number', $second->json('data.booking_number'))->firstOrFail();

        $this->assertNull($service->availabilityProblem($a));
        $service->changeStatus($a, BookingStatus::Confirmed);
        $this->assertSame(BookingStatus::Confirmed, $a->fresh()->status);

        $this->assertStringContainsString('no longer available', (string) $service->availabilityProblem($b));
        try {
            $service->changeStatus($b, BookingStatus::Confirmed);
            $this->fail('Two overlapping bookings must never both be confirmed.');
        } catch (BookingException $e) {
            $this->assertStringContainsString('no longer available', $e->getMessage());
        }
        $this->assertSame(BookingStatus::PendingConfirmation, $b->fresh()->status);
    }

    public function test_request_for_a_room_that_is_already_confirmed_is_rejected(): void
    {
        $service = app(BookingService::class);
        $taken = $service->createRequest([...$this->payload(), 'request_key' => null]);
        $service->changeStatus($taken, BookingStatus::Confirmed);

        $this->submit(['request_key' => (string) Str::uuid(), 'guest' => ['full_name' => 'Late Guest', 'phone' => '01833333333']])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This room is no longer available. Please select another room.');
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_invalid_booking_details_are_rejected(): void
    {
        $this->submit(['guest' => ['full_name' => '', 'phone' => '']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest.full_name', 'guest.phone']);
        $this->submit(['guest' => ['full_name' => 'Rahim', 'phone' => 'call me']])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Please enter a valid phone number.');
        $this->submit(['check_in' => now()->subDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Check-in date cannot be in the past.');
        $this->submit(['check_out' => now()->addDays(20)->toDateString()])
            ->assertStatus(422);
        $this->submit(['adults' => 9])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This room cannot accommodate that many adults.');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_failed_save_returns_an_error_and_leaves_nothing_behind(): void
    {
        Schema::drop('booking_guests');

        $response = $this->submit();

        $response->assertStatus(500);
        $this->assertNotTrue($response->json('success'));
        $this->assertSame(0, Booking::query()->count(), 'A failed save must not leave a partial booking.');
    }

    public function test_guest_details_need_the_access_token(): void
    {
        $created = $this->submit()->assertCreated();
        $number = $created->json('data.booking_number');

        $this->getJson("/api/v1/bookings/{$number}")->assertNotFound()->assertJsonMissingPath('data');
        $this->getJson("/api/v1/bookings/{$number}?token=wrong")->assertNotFound()->assertDontSee('Rahim');
        $this->getJson("/api/v1/bookings/{$number}/invoice")->assertNotFound();

        $this->getJson("/api/v1/bookings/{$number}?token=".$created->json('data.access_token'))
            ->assertOk()
            ->assertJsonPath('data.guest.phone', '01712345678')
            ->assertJsonMissingPath('data.internal_notes');
    }

    public function test_payment_cannot_be_started_while_online_payment_is_disabled(): void
    {
        $number = $this->submit()->json('data.booking_number');

        $this->postJson("/api/v1/bookings/{$number}/payments")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $this->assertSame(0, Payment::query()->count());
        $this->getJson('/api/v1/bookings/policies')->assertJsonPath('data.online_payment_enabled', false);
    }

    public function test_admin_pages_require_an_authorised_user(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get('/admin/bookings')->assertForbidden();

        $this->actingAsRole('receptionist');
        $this->get('/admin/bookings')->assertOk();
        $this->get('/admin/rooms')->assertForbidden();
        $this->actingAsRole('manager');
        $this->get('/admin/rooms')->assertOk();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_admin_can_search_filter_and_view_booking_requests(): void
    {
        $this->actingAsRole('receptionist');
        $request = Booking::query()->where('booking_number', $this->submit()->json('data.booking_number'))->firstOrFail();
        $confirmed = app(BookingService::class)->createReservation([
            ...$this->payload(['rooms' => [['room_category_id' => $this->category('coral')->id, 'quantity' => 1]]]),
            'guest' => ['full_name' => 'Karim Phone', 'phone' => '01944444444'],
            'status' => 'confirmed',
        ]);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$request, $confirmed])
            ->searchTable('01712345678')
            ->assertCanSeeTableRecords([$request])
            ->assertCanNotSeeTableRecords([$confirmed])
            ->searchTable($confirmed->booking_number)
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$request])
            ->searchTable('Rahim')
            ->assertCanSeeTableRecords([$request]);

        Livewire::test(ListBookings::class)
            ->filterTable('status', ['pending_confirmation'])
            ->assertCanSeeTableRecords([$request])
            ->assertCanNotSeeTableRecords([$confirmed]);

        Livewire::test(ListBookings::class)
            ->filterTable('check_in', ['check_in_from' => now()->addDays(25)->toDateString()])
            ->assertCanNotSeeTableRecords([$request, $confirmed]);

        Livewire::test(ViewBooking::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertSee($request->booking_number)
            ->assertSee('Pending Confirmation')
            ->assertSee('Rahim Uddin')
            ->assertSee('01712345678')
            ->assertSee('Not paid')
            ->assertSee('https://wa.me/8801712345678', false)
            ->assertActionVisible('confirmBooking');
    }

    public function test_admin_confirms_updates_status_and_adds_notes(): void
    {
        $this->actingAsRole('receptionist');
        $booking = Booking::query()->where('booking_number', $this->submit()->json('data.booking_number'))->firstOrFail();

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('checkAvailability')
            ->assertNotified('Rooms are available')
            ->callAction('addNote', ['note' => 'Called guest, will confirm tomorrow.'])
            ->assertNotified('Note added')
            ->callAction('confirmBooking')
            ->assertNotified("{$booking->booking_number} is now Confirmed");

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertStringContainsString('Called guest, will confirm tomorrow.', (string) $booking->internal_notes);

        Livewire::test(ListBookings::class)
            ->callAction(TestAction::make('updateStatus')->table($booking), ['status' => 'checked_in'])
            ->assertNotified("{$booking->booking_number} is now Checked In");
        $this->assertSame(BookingStatus::CheckedIn, $booking->fresh()->status);

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('updateStatus', ['status' => 'completed']);
        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_admin_cannot_confirm_a_request_whose_rooms_were_taken(): void
    {
        $this->actingAsRole('receptionist');
        $first = Booking::query()->where('booking_number', $this->submit()->json('data.booking_number'))->firstOrFail();
        $second = Booking::query()->where('booking_number', $this->submit([
            'request_key' => (string) Str::uuid(),
            'guest' => ['full_name' => 'Second Guest', 'phone' => '01822222222'],
        ])->json('data.booking_number'))->firstOrFail();

        Livewire::test(ViewBooking::class, ['record' => $first->getRouteKey()])->callAction('confirmBooking');

        Livewire::test(ViewBooking::class, ['record' => $second->getRouteKey()])
            ->callAction('checkAvailability')
            ->assertNotified('Rooms not available')
            ->callAction('confirmBooking')
            ->assertNotified('Status not changed');
        $this->assertSame(BookingStatus::PendingConfirmation, $second->fresh()->status);

        Livewire::test(ViewBooking::class, ['record' => $second->getRouteKey()])
            ->callAction('updateStatus', ['status' => 'cancelled']);
        $this->assertSame(BookingStatus::Cancelled, $second->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = [])
    {
        return $this->postJson('/api/v1/bookings', $this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(22)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'rooms' => [['room_category_id' => $this->category('keya')->id, 'quantity' => 1]],
            'request_key' => '6f1c1f9e-3c51-4a59-9a43-2f8f0c1d0a11',
            'guest' => ['full_name' => 'Rahim Uddin', 'phone' => '01712345678'],
            ...$overrides,
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
