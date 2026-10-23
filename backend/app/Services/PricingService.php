<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Exceptions\BookingException;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\MealPlan;
use App\Models\Offer;
use App\Models\RoomCategory;
use App\Models\RoomPrice;
use App\Models\SeasonalPrice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PricingService
{
    public function __construct(private SettingsService $settings) {}

    /**
     * @param  array<int, array{id:int, quantity?:int}>  $addonSelections
     * @return array<string, mixed>
     */
    public function quote(
        RoomCategory $category,
        Carbon $checkIn,
        Carbon $checkOut,
        int $adults,
        int $children = 0,
        ?MealPlan $mealPlan = null,
        array $addonSelections = [],
        ?string $couponCode = null,
        ?string $guestKey = null,
        int $roomCount = 1,
    ): array {
        return $this->quoteRooms(
            [['category' => $category, 'quantity' => $roomCount]],
            $checkIn,
            $checkOut,
            $adults,
            $children,
            $mealPlan,
            $addonSelections,
            $couponCode,
            $guestKey,
        );
    }

    /**
     * Price a stay across one or more room categories. Every line is priced night by night
     * from the configured rates; offers apply per category and coupons to eligible lines.
     *
     * @param  list<array{category: RoomCategory, quantity: int}>  $selections
     * @param  array<int, array{id:int, quantity?:int}>  $addonSelections
     * @param  Booking|null  $existing  booking being re-priced, so its own coupon use is not counted against it
     * @return array<string, mixed>
     */
    public function quoteRooms(
        array $selections,
        Carbon $checkIn,
        Carbon $checkOut,
        int $adults,
        int $children = 0,
        ?MealPlan $mealPlan = null,
        array $addonSelections = [],
        ?string $couponCode = null,
        ?string $guestKey = null,
        ?Booking $existing = null,
    ): array {
        $nights = (int) $checkIn->diffInDays($checkOut);
        if ($nights < 1) {
            throw new BookingException('Check-out date must be after check-in. Minimum stay is 1 night.');
        }
        if ($selections === []) {
            throw new BookingException('Please select at least one room.');
        }

        $items = [];
        $roomSubtotal = 0.0;
        $roomCount = 0;
        $combinedNightly = [];
        foreach ($selections as $selection) {
            $category = $selection['category'];
            $quantity = max(1, (int) $selection['quantity']);
            $perRoomNightly = [];
            $perRoomTotal = 0.0;
            foreach (CarbonPeriod::create($checkIn, $checkOut->copy()->subDay()) as $night) {
                $date = Carbon::parse($night)->toDateString();
                $amount = $this->nightlyRate($category, Carbon::parse($night));
                $perRoomNightly[] = ['date' => $date, 'amount' => $amount];
                $perRoomTotal += $amount;
                $combinedNightly[$date] = ($combinedNightly[$date] ?? 0) + $amount * $quantity;
            }
            if ($perRoomTotal <= 0) {
                throw new BookingException("{$category->name_en} does not have a bookable rate for the selected dates. Please contact the resort.");
            }

            $subtotal = round($perRoomTotal * $quantity, 2);
            $items[] = [
                'room_category_id' => $category->id,
                'name' => $category->name_en,
                'room_type' => $category->room_type?->value,
                'room_type_label' => $category->room_type?->getLabel(),
                'max_adults' => $category->max_adults,
                'max_children' => $category->max_children,
                'quantity' => $quantity,
                'nights' => $nights,
                'nightly_rate' => round($perRoomTotal / $nights, 2),
                'nightly' => $perRoomNightly,
                'per_room_total' => round($perRoomTotal, 2),
                'subtotal' => $subtotal,
                'offer_discount' => $this->applyOffers($category, $checkIn, $checkOut, $nights, $subtotal),
            ];
            $roomSubtotal += $subtotal;
            $roomCount += $quantity;
        }

        $mealTotal = 0.0;
        if ($mealPlan) {
            $mealTotal = ((float) $mealPlan->price_per_adult_per_night * $adults * $nights)
                + ((float) $mealPlan->price_per_child_per_night * $children * $nights);
        }

        $addonLines = [];
        $addonTotal = 0.0;
        foreach ($addonSelections as $selection) {
            $addon = Addon::query()->where('is_active', true)->find($selection['id'] ?? 0);
            if (! $addon) {
                continue;
            }
            $qty = max(1, (int) ($selection['quantity'] ?? 1));
            $line = (float) $addon->price * $qty;
            $addonLines[] = [
                'id' => $addon->id,
                'name' => $addon->name_en,
                'quantity' => $qty,
                'unit_price' => (float) $addon->price,
                'total' => $line,
            ];
            $addonTotal += $line;
        }

        $subtotalBeforeDiscount = $roomSubtotal + $mealTotal + $addonTotal;

        $offerDiscount = round(array_sum(array_column($items, 'offer_discount')), 2);
        $couponDiscount = 0.0;
        $coupon = null;
        if ($couponCode) {
            [$coupon, $couponDiscount] = $this->applyCoupon($couponCode, $items, $subtotalBeforeDiscount - $offerDiscount, $guestKey, $existing);
        }

        $discountTotal = round($offerDiscount + $couponDiscount, 2);
        $grandTotal = max(0, round($subtotalBeforeDiscount - $discountTotal, 2));
        $advancePercent = $this->settings->advancePercent();
        $advanceAmount = round($grandTotal * ($advancePercent / 100), 2);
        $balanceAmount = round($grandTotal - $advanceAmount, 2);

        ksort($combinedNightly);

        return [
            'nights' => $nights,
            'nightly_rate' => round($roomSubtotal / $nights / $roomCount, 2),
            'room_count' => $roomCount,
            'items' => $items,
            'nightly' => count($items) === 1
                ? $items[0]['nightly']
                : array_map(fn ($date, $amount) => ['date' => $date, 'amount' => round($amount, 2)], array_keys($combinedNightly), $combinedNightly),
            'room_subtotal' => round($roomSubtotal, 2),
            'meal_total' => round($mealTotal, 2),
            'addon_total' => round($addonTotal, 2),
            'addon_lines' => $addonLines,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'advance_percent' => $advancePercent,
            'advance_amount' => $advanceAmount,
            'balance_amount' => $balanceAmount,
            'currency' => 'BDT',
            'coupon' => $coupon?->code,
        ];
    }

    public function nightlyRate(RoomCategory $category, Carbon $night): float
    {
        $seasonal = SeasonalPrice::query()
            ->where('room_category_id', $category->id)
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', $night)
            ->whereDate('ends_on', '>=', $night)
            ->orderByDesc('priority')
            ->first();

        if ($seasonal) {
            return (float) $seasonal->amount;
        }

        $isWeekend = in_array($night->dayOfWeekIso, [5, 6], true);
        if ($isWeekend) {
            $weekend = RoomPrice::query()
                ->where('room_category_id', $category->id)
                ->where('type', 'weekend')
                ->where('is_active', true)
                ->first();
            if ($weekend) {
                return (float) $weekend->amount;
            }
        }

        $base = RoomPrice::query()
            ->where('room_category_id', $category->id)
            ->where('type', 'base')
            ->where('is_active', true)
            ->first();

        return (float) ($base?->amount ?? 0);
    }

    public function startingNightlyRate(RoomCategory $category): ?float
    {
        $base = RoomPrice::query()
            ->where('room_category_id', $category->id)
            ->where('type', 'base')
            ->where('is_active', true)
            ->value('amount');

        return $base !== null ? (float) $base : null;
    }

    private function applyOffers(RoomCategory $category, Carbon $checkIn, Carbon $checkOut, int $nights, float $roomSubtotal): float
    {
        $today = now($this->settings->timezone())->startOfDay();
        $offers = Offer::query()
            ->where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->whereDoesntHave('categories')
                    ->orWhereHas('categories', fn ($c) => $c->where('room_categories.id', $category->id));
            })
            ->get();

        $best = 0.0;
        foreach ($offers as $offer) {
            if ($offer->usage_limit && $offer->used_count >= $offer->usage_limit) {
                continue;
            }
            if ($offer->min_nights && $nights < $offer->min_nights) {
                continue;
            }
            if ($offer->booking_starts_on && $today->lt($offer->booking_starts_on)) {
                continue;
            }
            if ($offer->booking_ends_on && $today->gt($offer->booking_ends_on)) {
                continue;
            }
            if ($offer->stay_starts_on && $checkOut->lte($offer->stay_starts_on)) {
                continue;
            }
            if ($offer->stay_ends_on && $checkIn->gt($offer->stay_ends_on)) {
                continue;
            }
            $value = $offer->discount_type === DiscountType::Percent
                ? $roomSubtotal * ((float) $offer->discount_value / 100)
                : (float) $offer->discount_value;
            $best = max($best, $value);
        }

        return round($best, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{0: Coupon, 1: float}
     */
    private function applyCoupon(string $code, array $items, float $amount, ?string $guestKey, ?Booking $existing = null): array
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();
        if (! $coupon || ! $coupon->is_active) {
            throw new BookingException('This promo code is not valid.');
        }
        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            throw new BookingException('This promo code is not valid yet.');
        }
        if ($coupon->ends_at && $now->gt($coupon->ends_at)) {
            throw new BookingException('This promo code has expired.');
        }
        $reusing = $existing && $existing->coupon_id === $coupon->id;
        if (! $reusing && $coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new BookingException('This promo code has reached its usage limit.');
        }
        if ($coupon->minimum_amount && $amount < (float) $coupon->minimum_amount) {
            throw new BookingException('This promo code requires a higher booking amount.');
        }
        $restrictedTo = $coupon->categories()->pluck('room_categories.id')->all();
        if ($restrictedTo !== []) {
            $eligible = array_filter($items, fn (array $item) => in_array($item['room_category_id'], $restrictedTo, true));
            if ($eligible === []) {
                throw new BookingException('This promo code does not apply to the selected rooms.');
            }
            if (count($eligible) < count($items)) {
                $amount = array_sum(array_map(fn (array $item) => $item['subtotal'] - $item['offer_discount'], $eligible));
            }
        }
        if ($guestKey && $coupon->per_user_limit) {
            $used = Booking::query()
                ->where('coupon_id', $coupon->id)
                ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
                ->whereHas('guests', fn ($q) => $q->where('email', $guestKey)->orWhere('phone', $guestKey))
                ->whereIn('status', ['pending_confirmation', 'pending', 'paid', 'confirmed', 'checked_in', 'completed'])
                ->count();
            if ($used >= $coupon->per_user_limit) {
                throw new BookingException('This promo code has already been used.');
            }
        }

        $discount = $coupon->discount_type === DiscountType::Percent
            ? $amount * ((float) $coupon->discount_value / 100)
            : (float) $coupon->discount_value;

        return [$coupon, round(min($discount, $amount), 2)];
    }
}
