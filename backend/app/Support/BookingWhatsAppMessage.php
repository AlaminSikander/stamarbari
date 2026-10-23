<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingAddon;

/**
 * Plain-text booking summary for WhatsApp, built only from the saved booking.
 * Used for the guest's click-to-chat link today; an automatic WhatsApp Business
 * Platform message can send the same text later.
 */
class BookingWhatsAppMessage
{
    public static function for(Booking $booking): string
    {
        $booking->loadMissing(['guests', 'addons.addon', 'mealPlan', 'rooms.category', 'rooms.room', 'category']);
        $guest = $booking->guests->firstWhere('is_primary', true) ?? $booking->guests->first();

        $addons = $booking->addons
            ->map(fn (BookingAddon $line) => ($line->quantity > 1 ? "{$line->quantity} × " : '').($line->addon?->name_en ?? 'Add-on'))
            ->implode(', ');

        $lines = [
            'New Booking Request',
            '',
            "Booking Reference: {$booking->booking_number}",
            '',
            'Guest Name: '.($guest?->full_name ?? '—'),
            'Phone: '.($guest?->phone ?? '—'),
        ];
        if ($guest?->whatsapp && $guest->whatsapp !== $guest->phone) {
            $lines[] = "WhatsApp: {$guest->whatsapp}";
        }
        if ($guest?->email) {
            $lines[] = "Email: {$guest->email}";
        }

        array_push(
            $lines,
            '',
            'Rooms: '.$booking->roomSummary(),
            'Check-in: '.$booking->check_in->format('D, j M Y'),
            'Check-out: '.$booking->check_out->format('D, j M Y'),
            'Nights: '.$booking->nights(),
            'Adults: '.$booking->adults,
            'Children: '.$booking->children,
            '',
        );
        if ($booking->mealPlan && $booking->mealPlan->slug !== 'room-only') {
            $lines[] = "Meal Plan: {$booking->mealPlan->name_en}";
        }
        $lines[] = 'Add-ons: '.($addons !== '' ? $addons : 'None');
        $lines[] = 'Estimated Total: '.self::money((float) $booking->grand_total);
        if (filled($booking->special_requests)) {
            $lines[] = 'Special Requests: '.trim((string) $booking->special_requests);
        }

        array_push(
            $lines,
            '',
            'Booking Status: '.$booking->status->getLabel(),
            'Payment Status: '.self::paymentLine($booking),
        );

        return implode("\n", $lines);
    }

    private static function paymentLine(Booking $booking): string
    {
        if ($booking->paymentStatus() !== 'unpaid') {
            return $booking->paymentStatusLabel().' ('.self::money((float) $booking->amount_paid).')';
        }

        return config('booking.online_payment_enabled') || $booking->status !== BookingStatus::PendingConfirmation
            ? 'Not Paid'
            : 'Not Paid — Payment Disabled Temporarily';
    }

    private static function money(float $amount): string
    {
        return '৳'.number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}
