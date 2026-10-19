<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResortStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now('Asia/Dhaka')->toDateString();

        return [
            Stat::make("Today's arrivals", Booking::query()->whereDate('check_in', $today)->whereIn('status', [BookingStatus::Confirmed, BookingStatus::CheckedIn])->count()),
            Stat::make("Today's departures", Booking::query()->whereDate('check_out', $today)->whereIn('status', [BookingStatus::CheckedIn, BookingStatus::Confirmed])->count()),
            Stat::make('Current guests', Booking::query()->where('status', BookingStatus::CheckedIn)->count()),
            Stat::make('Pending bookings', Booking::query()->where('status', BookingStatus::Pending)->count()),
            Stat::make('Pending payments', Payment::query()->where('status', 'pending')->count()),
            Stat::make('Confirmed bookings', Booking::query()->where('status', BookingStatus::Confirmed)->count()),
            Stat::make('Revenue (paid)', '৳'.number_format((float) Payment::query()->where('status', 'successful')->sum('amount'), 0)),
            Stat::make('Cancelled', Booking::query()->where('status', BookingStatus::Cancelled)->count()),
            Stat::make('Refunds', Refund::query()->count()),
        ];
    }
}
