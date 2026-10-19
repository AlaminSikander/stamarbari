<?php

namespace App\Services;

use App\Enums\RefundStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function request(Booking $booking, float $amount, string $reason, ?int $userId = null, ?int $paymentId = null): Refund
    {
        if ($amount <= 0 || $amount > (float) $booking->amount_paid) {
            throw new BookingException('Refund amount is not valid.');
        }

        return DB::transaction(function () use ($booking, $amount, $reason, $userId, $paymentId) {
            return Refund::query()->create([
                'booking_id' => $booking->id,
                'payment_id' => $paymentId ?? $booking->payments()->latest()->value('id'),
                'amount' => $amount,
                'status' => RefundStatus::Requested,
                'reason' => $reason,
                'processed_by' => $userId,
            ]);
        });
    }

    public function updateStatus(Refund $refund, RefundStatus $status, ?string $gatewayReference = null, ?string $notes = null): Refund
    {
        $refund->status = $status;
        if ($gatewayReference) {
            $refund->gateway_reference = $gatewayReference;
        }
        if ($notes) {
            $refund->notes = $notes;
        }
        $refund->save();

        if ($status === RefundStatus::Completed) {
            $booking = $refund->booking;
            $booking->amount_paid = max(0, (float) $booking->amount_paid - (float) $refund->amount);
            $booking->save();
        }

        return $refund;
    }
}
