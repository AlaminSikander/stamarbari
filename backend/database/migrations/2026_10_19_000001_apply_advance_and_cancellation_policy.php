<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('extra_bed');
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('extra_bed_allowed');
        });

        Setting::setValue('advance_percent', '50');
        Setting::setValue('cancellation_days', '4');
        Setting::setValue('cancellation_refund_percent', '70');
        foreach (['cancellation_hours', 'extra_bed_available', 'extra_bed_price'] as $key) {
            DB::table('settings')->where('key', $key)->delete();
            Cache::forget("setting.general.{$key}");
        }

        DB::table('content_pages')->where('slug', 'cancellation-policy')->update([
            'title_bn' => 'বাতিলকরণ নীতি',
            'body_en' => "Advance payment: a 50% advance payment is required to confirm a booking.\n\nCancellation 4 or more days before check-in: 30% of the advance payment is deducted as a cancellation fee, and the remaining 70% of the advance payment is refunded.\n\nCancellation less than 4 days before check-in: no refund is provided. The advance payment is non-refundable.\n\nIf a guest misses their ship: no refund.",
            'body_bn' => "অগ্রিম পরিশোধ: বুকিং নিশ্চিত করতে ৫০% অগ্রিম পরিশোধ করতে হবে।\n\nচেক-ইনের ৪ দিন বা তার বেশি আগে বাতিল করলে: অগ্রিমের ৩০% বাতিলকরণ ফি হিসেবে কাটা হবে এবং বাকি ৭০% ফেরত দেওয়া হবে।\n\nচেক-ইনের ৪ দিনের কম সময় আগে বাতিল করলে: কোনো অর্থ ফেরত দেওয়া হবে না। অগ্রিম অফেরতযোগ্য।\n\nজাহাজ মিস হলে কোনো অর্থ ফেরত দেওয়া হয় না।",
            'updated_at' => now(),
        ]);
        DB::table('content_pages')->where('slug', 'terms')->update([
            'body_en' => 'Bookings are subject to room availability, the cancellation policy, and payment of a 50% advance to confirm the booking. Remaining balance is payable at the resort unless otherwise agreed.',
            'updated_at' => now(),
        ]);
        DB::table('offers')->where('slug', 'family-rooms')->update([
            'description_en' => 'Family rooms sleep up to four adults, with an attached bathroom, hot water and a garden view.',
            'description_bn' => 'পারিবারিক রুমে চারজন প্রাপ্তবয়স্ক পর্যন্ত থাকতে পারেন — সংযুক্ত বাথরুম, গরম পানি ও বাগানের দৃশ্যসহ।',
            'updated_at' => now(),
        ]);

        foreach ([
            ['Do I need to pay an advance?', 'Yes. A 50% advance payment is required to confirm a booking. The remaining balance is paid at the resort.'],
            ['What is the cancellation policy?', 'If you cancel 4 or more days before check-in, 30% of the advance is kept as a cancellation fee and the remaining 70% of the advance is refunded. Cancellations less than 4 days before check-in are not refunded.'],
        ] as [$question, $answer]) {
            DB::table('faqs')->updateOrInsert(
                ['question_en' => $question],
                ['answer_en' => $answer, 'is_published' => true, 'sort_order' => 4, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('extra_bed')->default(false)->after('child_ages');
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('extra_bed_allowed')->default(true)->after('is_active');
        });

        Setting::setValue('advance_percent', '30');
        Setting::setValue('cancellation_hours', '24');
        Setting::setValue('cancellation_refund_percent', '100');
        Setting::setValue('extra_bed_available', '1');
        Setting::setValue('extra_bed_price', '0');
        DB::table('settings')->where('key', 'cancellation_days')->delete();
        Cache::forget('setting.general.cancellation_days');
    }
};
