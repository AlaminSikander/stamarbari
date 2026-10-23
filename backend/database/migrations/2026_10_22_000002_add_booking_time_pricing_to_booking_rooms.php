<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each booking_rooms row is one physical room held by a booking. It now records the
 * room category and the price that applied when it was booked, so a booking can hold
 * rooms from several categories and later price changes never alter it.
 * Existing rows are backfilled from each booking's stored totals, not from current prices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->foreignId('room_category_id')->nullable()->after('room_id')->constrained();
            $table->decimal('nightly_rate', 12, 2)->default(0)->after('room_category_id');
            $table->unsignedSmallInteger('nights')->default(0)->after('nightly_rate');
            $table->decimal('subtotal', 12, 2)->default(0)->after('nights');
            $table->json('nightly')->nullable()->after('subtotal');
        });

        DB::table('bookings')
            ->whereIn('id', DB::table('booking_rooms')->select('booking_id'))
            ->orderBy('id')
            ->chunkById(200, function ($bookings) {
                foreach ($bookings as $booking) {
                    $rows = DB::table('booking_rooms')
                        ->join('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
                        ->where('booking_rooms.booking_id', $booking->id)
                        ->select('booking_rooms.id', 'rooms.room_category_id')
                        ->get();
                    $count = max(1, $rows->count());
                    $nights = max(1, (int) Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out)));
                    $breakdown = json_decode((string) $booking->price_breakdown, true) ?: [];
                    $perRoom = round((float) $booking->room_subtotal / $count, 2);

                    foreach ($rows as $row) {
                        DB::table('booking_rooms')->where('id', $row->id)->update([
                            'room_category_id' => $row->room_category_id ?? $booking->room_category_id,
                            'nightly_rate' => round($perRoom / $nights, 2),
                            'nights' => $nights,
                            'subtotal' => $perRoom,
                            'nightly' => isset($breakdown['nightly']) ? json_encode($breakdown['nightly']) : null,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_category_id');
            $table->dropColumn(['nightly_rate', 'nights', 'subtotal', 'nightly']);
        });
    }
};
