<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\ExpireBookingHoldsJob;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_availability_search_returns_priced_rooms(): void
    {
        $category = RoomCategory::query()->where('slug', 'deluxe-couple')->first();

        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'room_category_id' => $category->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data.rooms'));
        $this->assertGreaterThan(0, $response->json('data.rooms.0.quote.grand_total'));
    }

    public function test_double_booking_is_prevented(): void
    {
        $payload = $this->holdPayload();
        $first = app(BookingService::class)->createHold($payload);
        $this->assertSame(BookingStatus::Pending, $first->status);

        $category = RoomCategory::query()->where('slug', 'deluxe-couple')->first();
        $activeRooms = Room::query()->where('room_category_id', $category->id)->where('is_active', true)->count();

        for ($i = 1; $i < $activeRooms; $i++) {
            app(BookingService::class)->createHold($payload);
        }

        $this->expectException(\App\Exceptions\BookingException::class);
        app(BookingService::class)->createHold($payload);
    }

    public function test_expired_hold_releases_inventory(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload());
        $booking->update(['hold_expires_at' => now()->subMinute()]);

        (new ExpireBookingHoldsJob)->handle(app(BookingService::class));

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);

        $again = app(BookingService::class)->createHold($this->holdPayload());
        $this->assertSame(BookingStatus::Pending, $again->status);
    }

    public function test_room_block_hides_availability(): void
    {
        $category = RoomCategory::query()->where('slug', 'deluxe-couple')->first();
        foreach (Room::query()->where('room_category_id', $category->id)->get() as $room) {
            RoomBlock::query()->create([
                'room_id' => $room->id,
                'starts_on' => now()->addDays(10)->toDateString(),
                'ends_on' => now()->addDays(12)->toDateString(),
                'reason' => 'maintenance',
            ]);
        }

        $response = $this->postJson('/api/v1/availability', [
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'adults' => 2,
            'room_category_id' => $category->id,
        ]);

        $this->assertSame(0, $response->json('data.rooms.0.available_rooms'));
        $this->assertNull($response->json('data.rooms.0.quote'));
    }

    public function test_coupon_is_applied_server_side(): void
    {
        Coupon::query()->create([
            'code' => 'ISLAND10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $payload = $this->holdPayload();
        $payload['coupon_code'] = 'ISLAND10';
        $booking = app(BookingService::class)->createHold($payload);

        $this->assertGreaterThan(0, (float) $booking->discount_total);
        $this->assertSame('ISLAND10', $booking->coupon_code);
    }

    public function test_payment_callback_confirms_booking(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload());
        $payment = app(PaymentService::class)->initiate($booking);

        $response = $this->postJson('/api/v1/payments/callback', [
            'booking' => $booking->booking_number,
            'paymentID' => $payment->transaction_id,
            'status' => 'success',
            'amount' => $payment->amount,
        ]);

        $response->assertOk();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Successful, $payment->fresh()->status);
    }

    public function test_duplicate_payment_callback_does_not_double_charge(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload());
        $payment = app(PaymentService::class)->initiate($booking);
        $payload = [
            'booking' => $booking->booking_number,
            'paymentID' => $payment->transaction_id,
            'status' => 'success',
            'amount' => $payment->amount,
        ];

        $this->postJson('/api/v1/payments/callback', $payload)->assertOk();
        $paid = (float) $booking->fresh()->amount_paid;
        $this->postJson('/api/v1/payments/callback', $payload)->assertOk();

        $this->assertEquals($paid, (float) $booking->fresh()->amount_paid);
    }

    public function test_incorrect_amount_is_rejected(): void
    {
        $booking = app(BookingService::class)->createHold($this->holdPayload());
        $payment = app(PaymentService::class)->initiate($booking);

        $this->postJson('/api/v1/payments/callback', [
            'booking' => $booking->booking_number,
            'paymentID' => $payment->transaction_id,
            'status' => 'success',
            'amount' => 1,
        ])->assertStatus(422);

        $this->assertNotSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_client_cannot_set_price(): void
    {
        $payload = $this->holdPayload();
        $payload['grand_total'] = 1;
        $booking = app(BookingService::class)->createHold($payload);
        $this->assertGreaterThan(1, (float) $booking->grand_total);
    }

    public function test_receptionist_cannot_process_refunds_without_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('receptionist');
        $this->assertFalse($user->can('process_refunds'));
        $this->assertTrue($user->can('create_booking'));
    }

    /**
     * @return array<string, mixed>
     */
    private function holdPayload(): array
    {
        $category = RoomCategory::query()->where('slug', 'deluxe-couple')->first();

        return [
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(22)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'room_category_id' => $category->id,
            'guest' => [
                'full_name' => 'Test Guest',
                'phone' => '01800000000',
                'email' => 'guest@example.com',
            ],
        ];
    }
}
