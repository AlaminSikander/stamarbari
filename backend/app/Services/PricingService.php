<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Exceptions\BookingException;
use App\Models\Addon;
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
        bool $extraBed = false,
        ?string $couponCode = null,
        ?string $guestKey = null,
        int $roomCount = 1,
        int $extraBeds = 0,
    ): array {
        $nights = (int) $checkIn->diffInDays($checkOut);
        if ($nights < 1) {
            throw new BookingException('Check-out date must be after check-in. Minimum stay is 1 night.');
        }

        $nightly = [];
        $roomSubtotal = 0.0;

        foreach (CarbonPeriod::create($checkIn, $checkOut->copy()->subDay()) as $night) {
            $amount = $this->nightlyRate($category, Carbon::parse($night));
            $nightly[] = [
                'date' => Carbon::parse($night)->toDateString(),
                'amount' => $amount,
            ];
            $roomSubtotal += $amount;
        }

        $roomCount = max(1, $roomCount);
        $nightlyRate = $nights > 0 ? $roomSubtotal / $nights : 0;
        $roomSubtotal *= $roomCount;

        if ($roomSubtotal <= 0) {
            throw new BookingException('This room does not have a bookable rate for the selected dates. Please contact the resort.');
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

        $extraBedTotal = 0.0;
        $beds = $extraBeds > 0 ? $extraBeds : ($extraBed ? 1 : 0);
        if ($beds > 0 && $this->settings->extraBedAvailable()) {
            $extraBedTotal = $this->settings->extraBedPrice() * $beds * $nights;
        }

        $subtotalBeforeDiscount = $roomSubtotal + $mealTotal + $addonTotal + $extraBedTotal;

        $offerDiscount = $this->applyOffers($category, $checkIn, $checkOut, $nights, $roomSubtotal);
        $couponDiscount = 0.0;
        $coupon = null;
        if ($couponCode) {
            [$coupon, $couponDiscount] = $this->applyCoupon($couponCode, $category, $subtotalBeforeDiscount - $offerDiscount, $guestKey);
        }

        $discountTotal = round($offerDiscount + $couponDiscount, 2);
        $grandTotal = max(0, round($subtotalBeforeDiscount - $discountTotal, 2));
        $advancePercent = $this->settings->advancePercent();
        $advanceAmount = round($grandTotal * ($advancePercent / 100), 2);
        $balanceAmount = round($grandTotal - $advanceAmount, 2);

        return [
            'nights' => $nights,
            'nightly_rate' => round($nightlyRate, 2),
            'room_count' => $roomCount,
            'extra_beds' => $beds,
            'nightly' => $nightly,
            'room_subtotal' => round($roomSubtotal, 2),
            'meal_total' => round($mealTotal, 2),
            'addon_total' => round($addonTotal, 2),
            'addon_lines' => $addonLines,
            'extra_bed_total' => round($extraBedTotal, 2),
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
     * @return array{0: Coupon, 1: float}
     */
    private function applyCoupon(string $code, RoomCategory $category, float $amount, ?string $guestKey): array
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
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new BookingException('This promo code has reached its usage limit.');
        }
        if ($coupon->minimum_amount && $amount < (float) $coupon->minimum_amount) {
            throw new BookingException('This promo code requires a higher booking amount.');
        }
        if ($coupon->categories()->exists() && ! $coupon->categories()->where('room_categories.id', $category->id)->exists()) {
            throw new BookingException('This promo code does not apply to the selected room.');
        }
        if ($guestKey && $coupon->per_user_limit) {
            $used = \App\Models\Booking::query()
                ->where('coupon_id', $coupon->id)
                ->whereHas('guests', fn ($q) => $q->where('email', $guestKey)->orWhere('phone', $guestKey))
                ->whereIn('status', ['pending', 'paid', 'confirmed', 'checked_in', 'completed'])
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
