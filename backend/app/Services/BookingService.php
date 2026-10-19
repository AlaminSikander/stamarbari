<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\BookingStatusLog;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\MealPlan;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private AvailabilityService $availability,
        private PricingService $pricing,
        private SettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createHold(array $payload): Booking
    {
        $checkIn = Carbon::parse($payload['check_in'])->startOfDay();
        $checkOut = Carbon::parse($payload['check_out'])->startOfDay();
        $adults = (int) $payload['adults'];
        $children = (int) ($payload['children'] ?? 0);
        $roomCount = max(1, (int) ($payload['rooms_count'] ?? 1));
        $extraBeds = max(0, (int) ($payload['extra_beds'] ?? (($payload['extra_bed'] ?? false) ? 1 : 0)));

        if ($adults < 1) {
            throw new BookingException('Please select at least one adult.');
        }
        if ($checkOut->lte($checkIn)) {
            throw new BookingException('Check-out date must be after check-in. Minimum stay is 1 night.');
        }

        $category = RoomCategory::query()->where('is_active', true)->find($payload['room_category_id']);
        if (! $category) {
            throw new BookingException('The selected room is not available.');
        }
        if ($adults > $category->max_adults) {
            throw new BookingException('This room cannot accommodate that many adults.');
        }
        if ($children > $category->max_children) {
            throw new BookingException('This room cannot accommodate that many children.');
        }

        $mealPlan = isset($payload['meal_plan_id'])
            ? MealPlan::query()->where('is_active', true)->find($payload['meal_plan_id'])
            : MealPlan::query()->where('slug', 'room-only')->where('is_active', true)->first();

        $guestInfo = $payload['guest'] ?? [];
        if (empty($guestInfo['full_name']) || empty($guestInfo['phone'])) {
            throw new BookingException('Guest name and phone are required.');
        }

        return DB::transaction(function () use ($payload, $checkIn, $checkOut, $adults, $children, $roomCount, $extraBeds, $category, $mealPlan, $guestInfo) {
            $rooms = $this->availability->availableRooms($category, $checkIn, $checkOut, lock: true);
            if ($rooms->count() < $roomCount) {
                throw new BookingException('This room is no longer available. Please select another room.');
            }

            $quote = $this->pricing->quote(
                $category,
                $checkIn,
                $checkOut,
                $adults,
                $children,
                $mealPlan,
                $payload['addons'] ?? [],
                $extraBeds > 0,
                $payload['coupon_code'] ?? null,
                $guestInfo['email'] ?? $guestInfo['phone'],
                $roomCount,
                $extraBeds,
            );

            $guest = Guest::query()->updateOrCreate(
                ['phone' => $guestInfo['phone']],
                [
                    'full_name' => $guestInfo['full_name'],
                    'whatsapp' => $guestInfo['whatsapp'] ?? $guestInfo['phone'],
                    'email' => $guestInfo['email'] ?? null,
                    'id_type' => $guestInfo['id_type'] ?? null,
                    'id_number' => $guestInfo['id_number'] ?? null,
                    'country' => $guestInfo['country'] ?? 'Bangladesh',
                    'address' => $guestInfo['address'] ?? null,
                    'marketing_opt_in' => (bool) ($guestInfo['marketing_opt_in'] ?? false),
                ],
            );

            $booking = Booking::query()->create([
                'status' => BookingStatus::Pending,
                'source' => $payload['source'] ?? BookingSource::Online->value,
                'guest_id' => $guest->id,
                'room_category_id' => $category->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'adults' => $adults,
                'children' => $children,
                'child_ages' => $payload['child_ages'] ?? null,
                'extra_bed' => $extraBeds > 0,
                'meal_plan_id' => $mealPlan?->id,
                'coupon_id' => isset($payload['coupon_code'])
                    ? Coupon::query()->where('code', strtoupper($payload['coupon_code']))->value('id')
                    : null,
                'coupon_code' => isset($payload['coupon_code']) ? strtoupper($payload['coupon_code']) : null,
                'room_subtotal' => $quote['room_subtotal'],
                'meal_total' => $quote['meal_total'],
                'addon_total' => $quote['addon_total'] + $quote['extra_bed_total'],
                'discount_total' => $quote['discount_total'],
                'grand_total' => $quote['grand_total'],
                'advance_amount' => $quote['advance_amount'],
                'balance_amount' => $quote['balance_amount'],
                'amount_paid' => 0,
                'currency' => 'BDT',
                'price_breakdown' => $quote,
                'special_requests' => $payload['special_requests'] ?? null,
                'pickup_required' => collect($payload['addons'] ?? [])->contains(function ($row) {
                    $addon = Addon::query()->find($row['id'] ?? 0);

                    return $addon?->is_pickup;
                }),
                'hold_expires_at' => now()->addMinutes($this->settings->holdMinutes()),
                'locale' => $payload['locale'] ?? 'en',
                'created_by' => $payload['created_by'] ?? null,
            ]);

            foreach ($rooms->take($roomCount) as $room) {
                BookingRoom::query()->create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                ]);
            }

            BookingGuest::query()->create([
                'booking_id' => $booking->id,
                'full_name' => $guestInfo['full_name'],
                'phone' => $guestInfo['phone'],
                'whatsapp' => $guestInfo['whatsapp'] ?? $guestInfo['phone'],
                'email' => $guestInfo['email'] ?? null,
                'id_type' => $guestInfo['id_type'] ?? null,
                'id_number' => $guestInfo['id_number'] ?? null,
                'country' => $guestInfo['country'] ?? null,
                'address' => $guestInfo['address'] ?? null,
                'is_primary' => true,
            ]);

            foreach ($quote['addon_lines'] as $line) {
                BookingAddon::query()->create([
                    'booking_id' => $booking->id,
                    'addon_id' => $line['id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);
            }

            $this->transition($booking, BookingStatus::Pending, 'Hold created');

            return $booking->fresh(['rooms.room', 'guests', 'addons.addon', 'category', 'mealPlan']);
        });
    }

    public function expireHolds(): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->get();

        foreach ($expired as $booking) {
            $this->transition($booking, BookingStatus::Expired, 'Hold expired');
        }

        return $expired->count();
    }

    public function confirm(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Paid], true)) {
            throw new BookingException('This booking cannot be confirmed.');
        }

        $booking->confirmed_at = now();
        $this->transition($booking, BookingStatus::Confirmed, 'Payment verified');

        if ($booking->coupon_id) {
            Coupon::query()->where('id', $booking->coupon_id)->increment('used_count');
        }

        return $booking->fresh();
    }

    public function markPaid(Booking $booking, float $amount): Booking
    {
        $booking->amount_paid = round((float) $booking->amount_paid + $amount, 2);
        $booking->balance_amount = max(0, round((float) $booking->grand_total - (float) $booking->amount_paid, 2));
        $this->transition($booking, BookingStatus::Paid, 'Payment recorded');

        return $this->confirm($booking);
    }

    public function cancel(Booking $booking, string $reason, ?int $userId = null): Booking
    {
        if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Expired, BookingStatus::Completed], true)) {
            throw new BookingException('This booking cannot be cancelled.');
        }
        $booking->cancelled_at = now();
        $booking->cancel_reason = $reason;
        $this->transition($booking, BookingStatus::Cancelled, $reason, $userId);

        return $booking;
    }

    public function checkIn(Booking $booking, ?int $userId = null): Booking
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw new BookingException('Only confirmed bookings can be checked in.');
        }
        $booking->checked_in_at = now();
        $this->transition($booking, BookingStatus::CheckedIn, 'Checked in', $userId);

        return $booking;
    }

    public function complete(Booking $booking, ?int $userId = null): Booking
    {
        if (! in_array($booking->status, [BookingStatus::CheckedIn, BookingStatus::Confirmed], true)) {
            throw new BookingException('This booking cannot be completed.');
        }
        $booking->checked_out_at = now();
        $this->transition($booking, BookingStatus::Completed, 'Checked out', $userId);

        return $booking;
    }

    public function failPayment(Booking $booking, string $reason): Booking
    {
        $this->transition($booking, BookingStatus::PaymentFailed, $reason);

        return $booking;
    }

    public function assertPayable(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw new BookingException('This booking is not awaiting payment.');
        }
        if (! $booking->hold_expires_at || $booking->hold_expires_at->isPast()) {
            $this->expireHolds();
            throw new BookingException('Your booking hold has expired. Please search again.');
        }
    }

    private function transition(Booking $booking, BookingStatus $to, ?string $note = null, ?int $userId = null): void
    {
        $from = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        $booking->status = $to;
        $booking->save();

        BookingStatusLog::query()->create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to->value,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
