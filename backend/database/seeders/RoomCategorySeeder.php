<?php

namespace Database\Seeders;

use App\Enums\RoomType;
use App\Models\Room;
use App\Models\RoomAmenity;
use App\Models\RoomCategory;
use App\Models\RoomImage;
use App\Models\RoomPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates the resort's named rooms. Existing categories are matched by slug and
 * never overwritten, so re-running this seeder keeps prices and edits made in admin.
 */
class RoomCategorySeeder extends Seeder
{
    public const PLACEHOLDER_PHOTOS = [
        'rooms/placeholder-bedroom.jpg' => 'Bedroom with two beds',
        'rooms/placeholder-bathroom.jpg' => 'Attached bathroom',
    ];

    /**
     * @return list<array{name: string, type: RoomType, adults: int, children: int, price: int}>
     */
    public static function catalog(): array
    {
        $couple = fn (string $name, int $price) => ['name' => $name, 'type' => RoomType::Couple, 'adults' => 2, 'children' => 1, 'price' => $price];
        $double = fn (string $name, int $price) => ['name' => $name, 'type' => RoomType::DoubleBed, 'adults' => 4, 'children' => 0, 'price' => $price];

        return [
            $couple('Nokkhotro', 2500),
            $double('Ganglota', 3000),
            $double('Pampan', 3000),
            $double('Nongor', 3000),
            $double('Jahaji', 3000),
            $couple('Jotsna', 3000),
            $double('Gangchil', 3500),
            $double('Keya', 3500),
            $double('Sea Pearl', 3500),
            $double('Jolchhaya', 3500),
            $couple('Projapoti 🦋', 3000),
            $double('Probal', 3500),
            $double('Coral', 3500),
            $double('Jhinuk', 3500),
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(fn (array $room) => Str::slug($room['name']), self::catalog());
    }

    public function run(): void
    {
        $this->publishPlaceholderPhotos();

        $amenityIds = RoomAmenity::query()
            ->whereIn('slug', ['attached-bathroom', 'hot-water'])
            ->pluck('id');

        foreach (self::catalog() as $index => $room) {
            $slug = Str::slug($room['name']);
            $description = $room['type'] === RoomType::Couple
                ? 'A couple room for two adults and one child, with an attached bathroom and hot water.'
                : 'A double-bed room for up to four adults, with an attached bathroom and hot water.';

            $category = RoomCategory::withTrashed()->firstOrCreate(['slug' => $slug], [
                'name_en' => $room['name'],
                'room_type' => $room['type'],
                'description_en' => $description,
                'max_adults' => $room['adults'],
                'max_children' => $room['children'],
                'featured_image' => array_key_first(self::PLACEHOLDER_PHOTOS),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            RoomPrice::query()->firstOrCreate(
                ['room_category_id' => $category->id, 'type' => 'base'],
                ['amount' => $room['price'], 'currency' => 'BDT', 'is_active' => true],
            );

            if (! $category->rooms()->withTrashed()->exists()) {
                Room::query()->create([
                    'room_category_id' => $category->id,
                    'code' => Str::upper($slug),
                    'name' => $room['name'],
                    'is_active' => true,
                ]);
            }

            if (! $category->images()->exists()) {
                $sort = 0;
                foreach (self::PLACEHOLDER_PHOTOS as $path => $alt) {
                    RoomImage::query()->create([
                        'room_category_id' => $category->id,
                        'path' => $path,
                        'alt_en' => $alt,
                        'is_featured' => $sort === 0,
                        'sort_order' => $sort++,
                    ]);
                }
            }

            if ($amenityIds->isNotEmpty() && ! $category->amenities()->exists()) {
                $category->amenities()->sync($amenityIds);
            }
        }
    }

    private function publishPlaceholderPhotos(): void
    {
        $disk = Storage::disk('public');
        foreach (array_keys(self::PLACEHOLDER_PHOTOS) as $path) {
            $source = database_path('seeders/assets/'.$path);
            if (! $disk->exists($path) && is_file($source)) {
                $disk->put($path, (string) file_get_contents($source));
            }
        }
    }
}
