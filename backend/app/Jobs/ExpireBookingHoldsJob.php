<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireBookingHoldsJob implements ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookings): void
    {
        $bookings->expireHolds();
    }
}
