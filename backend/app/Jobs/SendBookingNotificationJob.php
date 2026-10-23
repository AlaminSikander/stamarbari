<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId, public string $templateKey) {}

    public function handle(NotificationService $notifications): void
    {
        $booking = Booking::query()->with(['guests', 'category', 'rooms.category', 'rooms.room'])->find($this->bookingId);
        if ($booking) {
            $notifications->notifyBooking($booking, $this->templateKey);
        }
    }
}
