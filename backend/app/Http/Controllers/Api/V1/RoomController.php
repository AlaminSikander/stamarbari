<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\MealPlan;
use App\Models\RoomCategory;
use App\Services\PricingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request, PricingService $pricing)
    {
        $locale = $request->query('locale', 'en');
        $rooms = RoomCategory::query()
            ->where('is_active', true)
            ->with(['amenities' => fn ($q) => $q->where('is_available', true), 'images'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (RoomCategory $category) => $this->transform($category, $locale, $pricing));

        return ApiResponse::success($rooms);
    }

    public function show(string $slug, Request $request, PricingService $pricing)
    {
        $locale = $request->query('locale', 'en');
        $category = RoomCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['amenities' => fn ($q) => $q->where('is_available', true), 'images'])
            ->firstOrFail();

        return ApiResponse::success($this->transform($category, $locale, $pricing));
    }

    public function mealPlans(Request $request)
    {
        $locale = $request->query('locale', 'en');

        return ApiResponse::success(
            MealPlan::query()->where('is_active', true)->orderBy('sort_order')->get()->map(fn ($plan) => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $locale === 'bn' && $plan->name_bn ? $plan->name_bn : $plan->name_en,
                'description' => $locale === 'bn' && $plan->description_bn ? $plan->description_bn : $plan->description_en,
                'price_per_adult_per_night' => (float) $plan->price_per_adult_per_night,
                'price_per_child_per_night' => (float) $plan->price_per_child_per_night,
            ])
        );
    }

    public function addons(Request $request)
    {
        $locale = $request->query('locale', 'en');

        return ApiResponse::success(
            Addon::query()->where('is_active', true)->orderBy('sort_order')->get()->map(fn ($addon) => [
                'id' => $addon->id,
                'slug' => $addon->slug,
                'name' => $locale === 'bn' && $addon->name_bn ? $addon->name_bn : $addon->name_en,
                'description' => $locale === 'bn' && $addon->description_bn ? $addon->description_bn : $addon->description_en,
                'price' => (float) $addon->price,
                'is_pickup' => $addon->is_pickup,
            ])
        );
    }

    private function transform(RoomCategory $category, string $locale, PricingService $pricing): array
    {
        $starting = $pricing->startingNightlyRate($category);

        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->localizedName($locale),
            'room_type' => $category->room_type?->value,
            'room_type_label' => $category->room_type?->localizedLabel($locale),
            'description' => $locale === 'bn' && $category->description_bn ? $category->description_bn : $category->description_en,
            'max_adults' => $category->max_adults,
            'max_children' => $category->max_children,
            'view' => $locale === 'bn' && $category->view_bn ? $category->view_bn : $category->view_en,
            'tour_360_url' => $category->tour_360_url,
            'featured_image' => $category->featured_image,
            'starting_price' => $starting,
            'currency' => 'BDT',
            'amenities' => $category->amenities->map(fn ($a) => [
                'slug' => $a->slug,
                'name' => $locale === 'bn' && $a->name_bn ? $a->name_bn : $a->name_en,
            ]),
            'images' => $category->images->map(fn ($img) => [
                'path' => $img->path,
                'alt' => $locale === 'bn' && $img->alt_bn ? $img->alt_bn : $img->alt_en,
            ]),
            'inventory' => $category->rooms()->where('is_active', true)->count(),
        ];
    }
}
