<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AbandonedBookingReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        $bookings = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where('reminders_opt_out', false)
            ->where('created_at', '<=', now()->subMinutes(8))
            ->where('hold_expires_at', '>', now())
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'successful'))
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->notificationLogs()->where('template_key', 'abandoned_booking')->exists()) {
                continue;
            }
            $notifications->notifyBooking($booking, 'abandoned_booking');
        }
    }
}
