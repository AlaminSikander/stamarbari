<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuoteBookingRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\RoomCategory;
use App\Services\BookingService;
use App\Services\SettingsService;
use App\Support\ApiResponse;
use App\Support\BookingWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public const REQUEST_SUBMITTED = 'Your booking request has been submitted successfully! We will contact you shortly to confirm your booking.';

    public function store(StoreBookingRequest $request, BookingService $bookings)
    {
        $data = $request->validated();
        $paymentsEnabled = (bool) config('booking.online_payment_enabled');

        try {
            $booking = $paymentsEnabled ? $bookings->createHold($data) : $bookings->createRequest($data);
        } catch (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        }

        $payload = $this->payload($booking, $data['locale'] ?? 'en');
        if (! $paymentsEnabled) {
            return ApiResponse::success($payload, self::REQUEST_SUBMITTED, $booking->wasRecentlyCreated ? 201 : 200);
        }

        $count = $booking->rooms->count();

        return ApiResponse::success(
            $payload,
            $count > 1 ? "Your {$count} rooms are held. Please complete payment to confirm." : 'Your room is held. Please complete payment to confirm.',
            201,
        );
    }

    public function quote(QuoteBookingRequest $request, BookingService $bookings)
    {
        try {
            $quote = $bookings->quoteSelection($request->validated());
        } catch (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        }

        $locale = $request->validated('locale') ?? 'en';
        $categories = RoomCategory::query()->whereIn('id', array_column($quote['items'], 'room_category_id'))->get()->keyBy('id');
        $quote['items'] = array_map(function (array $item) use ($categories, $locale) {
            $category = $categories->get($item['room_category_id']);

            return [
                ...$item,
                'name' => $category?->localizedName($locale) ?? $item['name'],
                'room_type_label' => $category?->room_type?->localizedLabel($locale) ?? $item['room_type_label'],
            ];
        }, $quote['items']);

        return ApiResponse::success($quote);
    }

    /**
     * Guest details are private: the booking is only returned with the access token
     * that was given to the guest when the booking was made.
     */
    public function show(string $bookingNumber, Request $request)
    {
        $booking = Booking::query()
            ->where('booking_number', $bookingNumber)
            ->with(['category', 'mealPlan', 'rooms.room', 'rooms.category', 'addons.addon', 'guests', 'payments'])
            ->first();
        if (! $booking || ! $booking->hasAccessToken($request->query('token'))) {
            return ApiResponse::error('Booking not found.', [], 404);
        }

        return ApiResponse::success($this->payload($booking, $request->query('locale', 'en')));
    }

    public function policies(SettingsService $settings)
    {
        return ApiResponse::success([
            'check_in_time' => $settings->checkInTime(),
            'check_out_time' => $settings->checkOutTime(),
            'child_policy_age' => $settings->childPolicyAge(),
            'cancellation_days' => $settings->cancellationDays(),
            'cancellation_refund_percent' => $settings->cancellationRefundPercent(),
            'missed_ship_refund' => $settings->missedShipRefund(),
            'advance_percent' => $settings->advancePercent(),
            'hold_minutes' => $settings->holdMinutes(),
            'online_payment_enabled' => (bool) config('booking.online_payment_enabled'),
            'missed_ship_policy' => 'If a guest misses their ship, no refund is issued.',
        ]);
    }

    public function invoice(string $bookingNumber, Request $request)
    {
        $booking = Booking::query()->where('booking_number', $bookingNumber)->first();
        if (! $booking || ! $booking->hasAccessToken($request->query('token'))) {
            return ApiResponse::error('Booking not found.', [], 404);
        }
        if (! $booking->invoice_path || ! Storage::disk('local')->exists($booking->invoice_path)) {
            return ApiResponse::error('Invoice is not ready yet.', [], 404);
        }

        return Storage::disk('local')->download($booking->invoice_path);
    }

    private function payload(Booking $booking, string $locale = 'en'): array
    {
        $lines = $booking->roomLines($locale);
        $guest = $booking->guests->firstWhere('is_primary', true) ?? $booking->guests->first();
        $paymentsEnabled = (bool) config('booking.online_payment_enabled');

        return [
            'booking_number' => $booking->booking_number,
            'access_token' => $booking->accessToken(),
            'status' => $booking->status->value,
            'status_label' => $booking->status->getLabel(),
            'check_in' => $booking->check_in?->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'adults' => $booking->adults,
            'children' => $booking->children,
            'room' => $booking->roomSummary($locale),
            'room_count' => (int) $lines->sum('quantity'),
            'rooms' => $lines->map(fn (array $line) => collect($line)->except('units')->all())->values(),
            'nights' => $booking->nights(),
            'room_subtotal' => (float) $booking->room_subtotal,
            'meal_plan' => $booking->mealPlan?->name_en,
            'addons' => $booking->addons->map(fn (BookingAddon $line) => [
                'name' => $line->addon?->name_en,
                'quantity' => (int) $line->quantity,
                'total' => (float) $line->total,
            ])->values(),
            'special_requests' => $booking->special_requests,
            'guest' => $guest?->only(['full_name', 'phone', 'whatsapp', 'email']),
            'grand_total' => (float) $booking->grand_total,
            'advance_amount' => (float) $booking->advance_amount,
            'amount_paid' => (float) $booking->amount_paid,
            'balance_amount' => (float) $booking->balance_amount,
            'payment_status' => $booking->paymentStatus(),
            'online_payment_enabled' => $paymentsEnabled,
            'payment_required' => $paymentsEnabled && $booking->holdIsValid(),
            'hold_expires_at' => $booking->hold_expires_at?->toIso8601String(),
            'price_breakdown' => $booking->price_breakdown,
            'invoice_available' => (bool) $booking->invoice_path,
            'whatsapp_message' => BookingWhatsAppMessage::for($booking),
            'created_at' => $booking->created_at?->toIso8601String(),
        ];
    }
}
