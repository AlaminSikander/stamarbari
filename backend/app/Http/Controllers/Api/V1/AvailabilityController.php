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
        $extraBeds = max(0, (int) $request->integer('extra_beds', 0));
        $locale = $request->query('locale', 'en');

        $query = RoomCategory::query()->where('is_active', true)->with(['amenities' => fn ($q) => $q->where('is_available', true)]);
        if ($request->filled('room_category_id')) {
            $query->where('id', $request->integer('room_category_id'));
        }

        $results = $query->get()->map(function (RoomCategory $category) use ($availability, $pricing, $checkIn, $checkOut, $adults, $children, $roomCount, $extraBeds, $locale) {
            $count = $availability->availableCount($category, $checkIn, $checkOut);
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
                        $extraBeds > 0,
                        null,
                        null,
                        $roomCount,
                        $extraBeds,
                    );
                } catch (BookingException) {
                    $quote = null;
                }
            }

            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->localizedName($locale),
                'max_adults' => $category->max_adults,
                'max_children' => $category->max_children,
                'available_rooms' => $fits ? $count : 0,
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
