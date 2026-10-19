<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function handle(InvoiceService $invoices): void
    {
        $booking = $this->booking->fresh();
        if ($booking && $booking->status !== BookingStatus::Expired) {
            $invoices->generate($booking);
        }
    }
}
