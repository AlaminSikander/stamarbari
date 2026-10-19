<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\SettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, BookingService $bookings)
    {
        try {
            $booking = $bookings->createHold($request->validated());
        } catch (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        }

        return ApiResponse::success($this->payload($booking), 'Your room is held. Please complete payment to confirm.', 201);
    }

    public function show(string $bookingNumber)
    {
        $booking = Booking::query()
            ->where('booking_number', $bookingNumber)
            ->with(['category', 'mealPlan', 'rooms.room', 'addons.addon', 'guests', 'payments'])
            ->firstOrFail();

        return ApiResponse::success($this->payload($booking));
    }

    public function policies(SettingsService $settings)
    {
        return ApiResponse::success([
            'check_in_time' => $settings->checkInTime(),
            'check_out_time' => $settings->checkOutTime(),
            'child_policy_age' => $settings->childPolicyAge(),
            'cancellation_hours' => $settings->cancellationHours(),
            'cancellation_refund_percent' => $settings->cancellationRefundPercent(),
            'missed_ship_refund' => $settings->missedShipRefund(),
            'advance_percent' => $settings->advancePercent(),
            'hold_minutes' => $settings->holdMinutes(),
            'extra_bed_available' => $settings->extraBedAvailable(),
            'extra_bed_price' => $settings->extraBedPrice(),
            'max_extra_beds_per_room' => 2,
            'missed_ship_policy' => 'If a guest misses their ship, no refund is issued.',
        ]);
    }

    public function invoice(string $bookingNumber)
    {
        $booking = Booking::query()->where('booking_number', $bookingNumber)->firstOrFail();
        if (! $booking->invoice_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($booking->invoice_path)) {
            return ApiResponse::error('Invoice is not ready yet.', [], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($booking->invoice_path);
    }

    private function payload(Booking $booking): array
    {
        return [
            'booking_number' => $booking->booking_number,
            'status' => $booking->status->value,
            'check_in' => $booking->check_in?->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'adults' => $booking->adults,
            'children' => $booking->children,
            'room' => $booking->category?->name_en,
            'meal_plan' => $booking->mealPlan?->name_en,
            'guest' => $booking->guests->first()?->only(['full_name', 'phone', 'email']),
            'grand_total' => (float) $booking->grand_total,
            'advance_amount' => (float) $booking->advance_amount,
            'amount_paid' => (float) $booking->amount_paid,
            'balance_amount' => (float) $booking->balance_amount,
            'hold_expires_at' => $booking->hold_expires_at?->toIso8601String(),
            'price_breakdown' => $booking->price_breakdown,
            'invoice_available' => (bool) $booking->invoice_path,
        ];
    }
}
