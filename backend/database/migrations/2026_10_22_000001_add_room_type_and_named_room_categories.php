<?php

use App\Models\RoomCategory;
use App\Models\Setting;
use Database\Seeders\RoomCategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Room Categories become the single admin table for rooms: one category per named room,
 * each with its own physical room unit for availability. Legacy demo categories are
 * archived (soft deleted) rather than removed, so their bookings, rooms and prices stay intact.
 */
return new class extends Migration
{
    private const ARCHIVED_KEY = 'room_catalog_archived_categories';

    public function up(): void
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->string('room_type', 20)->nullable()->after('name_bn')->index();
        });

        (new RoomCategorySeeder)->run();

        $archived = [];
        $legacy = RoomCategory::query()->whereNotIn('slug', RoomCategorySeeder::slugs())->get();
        foreach ($legacy as $category) {
            $archived[] = ['id' => $category->id, 'is_active' => $category->is_active];
            $category->forceFill(['is_active' => false])->save();

            $upcoming = $category->upcomingBookingsCount();
            if ($upcoming > 0) {
                $message = "Room category \"{$category->name_en}\" was hidden from guests but not archived because it has {$upcoming} upcoming booking(s). Reassign or cancel them, then delete it from Room Categories.";
                Log::warning($message);
                if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                    echo "  WARNING: {$message}\n";
                }

                continue;
            }

            $category->delete();
        }
        Setting::setValue(self::ARCHIVED_KEY, $archived, 'system');

        DB::table('blog_posts')
            ->where('slug', 'garden-view-rooms')
            ->where('content_en', 'like', '%Deluxe Couple and Garden Twin%')
            ->update([
                'content_en' => "S T Amar Bari has garden-view rooms with attached bathrooms and hot water.\n\nCouple rooms sleep two adults and one child. Double-bed rooms sleep up to four adults. We do not list sea-view rooms, air conditioning, Wi-Fi or television that we do not have.",
                'content_bn' => "এস টি আমার বাড়িতে সংযুক্ত বাথরুম ও গরম পানিসহ বাগান-দৃশ্যের রুম আছে।\n\nকাপল রুমে দুজন প্রাপ্তবয়স্ক ও একজন শিশু থাকতে পারেন। ডাবল বেড রুমে চারজন প্রাপ্তবয়স্ক পর্যন্ত। যে সুবিধা নেই তা আমরা লিখি না।",
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $archived = json_decode((string) Setting::getValue(self::ARCHIVED_KEY, '[]', 'system'), true) ?: [];
        foreach ($archived as $row) {
            DB::table('room_categories')->where('id', $row['id'])->update([
                'deleted_at' => null,
                'is_active' => (bool) $row['is_active'],
            ]);
        }
        Setting::query()->where('group', 'system')->where('key', self::ARCHIVED_KEY)->delete();

        $unbooked = RoomCategory::withTrashed()
            ->whereIn('slug', RoomCategorySeeder::slugs())
            ->whereDoesntHave('bookings', fn ($query) => $query->withTrashed())
            ->get();
        foreach ($unbooked as $category) {
            $category->forceDelete();
        }
        DB::table('room_categories')
            ->whereIn('slug', RoomCategorySeeder::slugs())
            ->whereNull('deleted_at')
            ->update(['is_active' => false]);

        Schema::table('room_categories', function (Blueprint $table) {
            $table->dropIndex(['room_type']);
            $table->dropColumn('room_type');
        });
    }
};
