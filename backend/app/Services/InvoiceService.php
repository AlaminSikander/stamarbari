<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generate(Booking $booking): string
    {
        $booking->load(['guests', 'category', 'mealPlan', 'addons.addon', 'rooms.room', 'payments']);
        $pdf = Pdf::loadView('invoices.booking', [
            'booking' => $booking,
            'resort' => [
                'name' => 'S T Amar Bari Resort',
                'address' => 'J8M9+46F, Poschim Para, Ctb Beach Road, Saint Martin Island, Bangladesh',
                'phone' => '+880 1839-425157',
                'email' => 'stamarbariresort@gmail.com',
            ],
        ]);

        $path = 'invoices/'.$booking->booking_number.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());
        $booking->invoice_path = $path;
        $booking->save();

        return $path;
    }
}
