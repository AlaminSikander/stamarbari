<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvailabilityRequest;
use App\Models\RoomCategory;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Support\ApiResponse;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function search(AvailabilityRequest $request, AvailabilityService $availability, PricingService $pricing)
    {
        $checkIn = Carbon::parse($request->validated('check_in'));
        $checkOut = Carbon::parse($request->validated('check_out'));
        $adults = (int) $request->validated('adults');
        $children = (int) $request->validated('children', 0);
        $roomCount = max(1, (int) $request->integer('rooms_count', 1));
        $locale = $request->query('locale', 'en');

        $query = RoomCategory::query()
            ->where('is_active', true)
            ->with(['amenities' => fn ($q) => $q->where('is_available', true)])
            ->orderBy('sort_order')
            ->orderBy('id');
        if ($request->filled('room_category_id')) {
            $query->where('id', $request->integer('room_category_id'));
        }

        $categories = $query->get();
        $free = $availability->availableRoomsFor($categories->modelKeys(), $checkIn, $checkOut);

        $results = $categories->map(function (RoomCategory $category) use ($free, $pricing, $checkIn, $checkOut, $adults, $children, $roomCount, $locale) {
            $count = $free->get($category->id, collect())->count();
            $fits = $adults <= $category->max_adults && $children <= $category->max_children;
            $enough = $count >= $roomCount;
            $quote = null;
            if ($enough && $fits) {
                try {
                    $quote = $pricing->quote(
                        $category,
                        $checkIn,
                        $checkOut,
                        $adults,
                        $children,
                        null,
                        [],
                        null,
                        null,
                        $roomCount,
                    );
                } catch (BookingException) {
                    $quote = null;
                }
            }

            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->localizedName($locale),
                'room_type' => $category->room_type?->value,
                'room_type_label' => $category->room_type?->localizedLabel($locale),
                'max_adults' => $category->max_adults,
                'max_children' => $category->max_children,
                'available_rooms' => $fits ? $count : 0,
                'units_available' => $count,
                'starting_price' => $pricing->startingNightlyRate($category),
                'fits_guests' => $fits,
                'quote' => $quote,
            ];
        })->values();

        return ApiResponse::success([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'rooms' => $results,
        ], $results->isEmpty() ? 'No rooms available for these dates.' : 'Available rooms found.');
    }
}
