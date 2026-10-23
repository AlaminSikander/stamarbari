<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function start(string $bookingNumber, PaymentService $payments)
    {
        if (! config('booking.online_payment_enabled')) {
            return ApiResponse::error('Online payment is temporarily unavailable. The resort will contact you to confirm your booking.', [], 409);
        }

        $booking = Booking::query()->where('booking_number', $bookingNumber)->firstOrFail();

        try {
            $payment = $payments->initiate($booking);
        } catch (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        }

        return ApiResponse::success([
            'payment_reference' => $payment->payment_reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status->value,
            'payment_url' => $payment->gateway_response['payment_url'] ?? null,
        ], 'Continue to payment.');
    }

    public function callback(Request $request, PaymentService $payments)
    {
        $booking = Booking::query()->where('booking_number', $request->input('booking'))->firstOrFail();

        try {
            $payment = $payments->handleCallback($booking, $request->all());
        } catch (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        }

        return ApiResponse::success([
            'booking_number' => $booking->fresh()->booking_number,
            'booking_status' => $booking->fresh()->status->value,
            'payment_status' => $payment->status->value,
            'amount' => (float) $payment->amount,
        ]);
    }
}
