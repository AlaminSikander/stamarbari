<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendBookingNotificationJob;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private BookingService $bookings,
        private PaymentGateway $gateway,
    ) {}

    public function initiate(Booking $booking): Payment
    {
        $this->bookings->assertPayable($booking);

        return DB::transaction(function () use ($booking) {
            $existing = Payment::query()
                ->where('booking_id', $booking->id)
                ->where('status', PaymentStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $reference = 'PAY-'.Str::upper(Str::random(10));
            $callback = rtrim((string) config('app.frontend_url', config('app.url')), '/').'/booking/verify?booking='.$booking->booking_number.'&token='.$booking->accessToken();

            $created = $this->gateway->createPayment(
                $reference,
                (float) $booking->advance_amount,
                $callback,
                'sale',
            );

            return Payment::query()->create([
                'booking_id' => $booking->id,
                'payment_reference' => $reference,
                'transaction_id' => $created['gateway_payment_id'],
                'idempotency_key' => $created['gateway_payment_id'],
                'gateway' => 'bkash',
                'status' => PaymentStatus::Pending,
                'amount' => $booking->advance_amount,
                'currency' => 'BDT',
                'purpose' => 'advance',
                'gateway_response' => $created['raw'] + ['payment_url' => $created['payment_url']],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(Booking $booking, array $payload): Payment
    {
        return DB::transaction(function () use ($booking, $payload) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $transactionId = $payload['trxID'] ?? $payload['paymentID'] ?? $payload['transaction_id'] ?? null;
            if ($transactionId) {
                $already = Payment::query()
                    ->where('transaction_id', $transactionId)
                    ->where('status', PaymentStatus::Successful)
                    ->lockForUpdate()
                    ->first();
                if ($already) {
                    return $already;
                }
            }

            if ($booking->status === BookingStatus::Confirmed) {
                $successful = Payment::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', PaymentStatus::Successful)
                    ->first();
                if ($successful) {
                    return $successful;
                }
            }

            $payment = Payment::query()
                ->where('booking_id', $booking->id)
                ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
                ->lockForUpdate()
                ->latest()
                ->first();

            if (! $payment) {
                throw new BookingException('No pending payment was found for this booking.');
            }

            if ($payment->status === PaymentStatus::Successful) {
                return $payment;
            }

            $verified = $this->gateway->verify($payload);
            $payment->gateway_response = array_merge($payment->gateway_response ?? [], $verified['raw']);
            $payment->transaction_id = $verified['transaction_id'] ?? $payment->transaction_id;

            if (! $verified['success']) {
                $status = match ($verified['status']) {
                    'cancelled' => PaymentStatus::Cancelled,
                    'expired' => PaymentStatus::Expired,
                    default => PaymentStatus::Failed,
                };
                $payment->status = $status;
                $payment->failure_reason = $verified['status'];
                $payment->save();
                $this->bookings->failPayment($booking, 'Payment '.$verified['status']);

                return $payment;
            }

            $amount = $verified['amount'] ?? (float) $payment->amount;
            if (abs($amount - (float) $payment->amount) > 0.009) {
                $payment->status = PaymentStatus::VerificationFailed;
                $payment->failure_reason = 'incorrect_amount';
                $payment->save();
                throw new BookingException('Payment amount could not be verified.');
            }

            $this->bookings->assertPayable($booking);

            $payment->status = PaymentStatus::Successful;
            $payment->verified_at = now();
            $payment->save();

            $this->bookings->markPaid($booking, (float) $payment->amount);

            GenerateInvoiceJob::dispatch($booking->fresh());
            SendBookingNotificationJob::dispatch($booking->id, 'booking_confirmed');

            return $payment->fresh();
        });
    }

    public function recordManual(Booking $booking, float $amount, string $method, int $userId, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($booking, $amount, $method, $userId, $note) {
            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'payment_reference' => 'MAN-'.Str::upper(Str::random(8)),
                'transaction_id' => 'MANUAL-'.Str::uuid(),
                'idempotency_key' => 'manual-'.$booking->id.'-'.Str::uuid(),
                'gateway' => $method,
                'status' => PaymentStatus::Successful,
                'amount' => $amount,
                'currency' => 'BDT',
                'purpose' => 'advance',
                'verified_at' => now(),
                'recorded_by' => $userId,
                'gateway_response' => ['note' => $note],
            ]);

            $this->bookings->markPaid($booking, $amount);

            return $payment;
        });
    }
}
