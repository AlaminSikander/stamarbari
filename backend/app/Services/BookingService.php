<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\BookingStatusLog;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\MealPlan;
use App\Models\Room;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private AvailabilityService $availability,
        private PricingService $pricing,
        private SettingsService $settings,
    ) {}

    public const MAX_ROOMS_PER_BOOKING = 20;

    /**
     * Price a room selection without holding anything. Throws when a room is unavailable
     * or the guests do not fit, with per-category availability in the exception errors.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function quoteSelection(array $payload): array
    {
        $stay = $this->prepareStay($payload);
        $this->allocate($stay, lock: false);

        return $this->quoteStay($stay, $payload['guest']['email'] ?? $payload['guest']['phone'] ?? null);
    }

    /**
     * Read-only preview for the admin form: free rooms per category (ignoring the booking
     * being edited), whether the selection can be saved, and the price it would be saved at.
     * A null quote means the stay is unchanged and the stored booking-time prices still apply.
     *
     * @param  array<string, mixed>  $payload
     * @return array{availability: array<int, int>, error: ?string, quote: ?array<string, mixed>}
     */
    public function previewReservation(array $payload, ?Booking $current = null, bool $ignoreBlocks = false): array
    {
        $stay = $this->prepareStay($payload);
        $current?->loadMissing(['rooms', 'addons']);

        $free = $this->availability->availableRoomsFor(
            array_map(fn ($s) => $s['category']->id, $stay['selections']),
            $stay['check_in'],
            $stay['check_out'],
            ignoreBookingId: $current?->id,
            ignoreBlocks: $ignoreBlocks,
        );
        $error = null;
        try {
            $this->allocate($stay, lock: false, current: $current, ignoreBlocks: $ignoreBlocks);
        } catch (BookingException $e) {
            $error = $e->getMessage();
        }

        $quote = null;
        if (! $current || $this->stayChanged($current, $stay)) {
            $quote = $this->quoteStay(
                $stay,
                null,
                $current?->addons->map(fn ($line) => ['id' => $line->addon_id, 'quantity' => $line->quantity])->all(),
                $current?->coupon_code,
                $current,
            );
        }

        return [
            'availability' => $free->map(fn ($rooms) => $rooms->count())->all(),
            'error' => $error,
            'quote' => $quote,
        ];
    }

    /**
     * Website booking: the selected rooms are held while the guest pays the advance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createHold(array $payload): Booking
    {
        return $this->createOnline($payload, BookingStatus::Pending, now()->addMinutes($this->settings->holdMinutes()), 'Hold created');
    }

    /**
     * Website booking request while online payment is disabled. The rooms must be free
     * when the request is made, but nothing is held or confirmed: staff confirm it later,
     * which re-checks availability. Re-sending the same request key returns the booking
     * already saved for it, so double submits and retries never create a second booking.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createRequest(array $payload): Booking
    {
        $existing = $this->findDuplicateRequest($payload);
        if ($existing) {
            return $existing;
        }

        try {
            $booking = $this->createOnline($payload, BookingStatus::PendingConfirmation, null, 'Booking request submitted without payment');
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->findDuplicateRequest($payload);
            if (! $existing) {
                throw $e;
            }

            return $existing;
        }
        $booking->wasRecentlyCreated = true;

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findDuplicateRequest(array $payload): ?Booking
    {
        $phone = trim((string) ($payload['guest']['phone'] ?? ''));
        $relations = ['rooms.room', 'rooms.category', 'guests', 'addons.addon', 'category', 'mealPlan'];

        if (filled($payload['request_key'] ?? null)) {
            $booking = Booking::query()->with($relations)->where('request_key', $payload['request_key'])->first();
            if ($booking && $booking->guests->first()?->phone !== $phone) {
                throw new BookingException('This booking request could not be saved. Please refresh the page and try again.', 409);
            }

            return $booking;
        }

        // Without a request key, an identical request from the same phone a few minutes
        // ago is treated as a double submit.
        $requested = collect($this->resolveSelections($payload))
            ->mapWithKeys(fn ($s) => [$s['category']->id => $s['quantity']])
            ->sortKeys()
            ->all();

        return Booking::query()
            ->with($relations)
            ->where('status', BookingStatus::PendingConfirmation)
            ->whereDate('check_in', Carbon::parse($payload['check_in'])->toDateString())
            ->whereDate('check_out', Carbon::parse($payload['check_out'])->toDateString())
            ->where('adults', (int) $payload['adults'])
            ->where('children', (int) ($payload['children'] ?? 0))
            ->where('created_at', '>=', now()->subMinutes(10))
            ->whereHas('guests', fn ($q) => $q->where('is_primary', true)->where('phone', $phone))
            ->latest('id')
            ->get()
            ->first(fn (Booking $booking) => $booking->rooms->countBy('room_category_id')->sortKeys()->all() === $requested);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createOnline(array $payload, BookingStatus $status, ?Carbon $holdExpiresAt, string $note): Booking
    {
        $stay = $this->prepareStay($payload);
        $guestInfo = $payload['guest'] ?? [];
        if (empty($guestInfo['full_name']) || empty($guestInfo['phone'])) {
            throw new BookingException('Guest name and phone are required.');
        }

        return DB::transaction(function () use ($payload, $stay, $guestInfo, $status, $holdExpiresAt, $note) {
            $allocation = $this->allocate($stay, lock: true);
            $quote = $this->quoteStay($stay, $guestInfo['email'] ?? $guestInfo['phone']);
            $guest = $this->upsertGuest($guestInfo);

            $booking = Booking::query()->create([
                ...$this->stayColumns($stay, $quote),
                'request_key' => $payload['request_key'] ?? null,
                'status' => $status,
                'source' => $payload['source'] ?? BookingSource::Online->value,
                'guest_id' => $guest->id,
                'amount_paid' => 0,
                'coupon_id' => isset($payload['coupon_code'])
                    ? Coupon::query()->where('code', strtoupper($payload['coupon_code']))->value('id')
                    : null,
                'coupon_code' => isset($payload['coupon_code']) ? strtoupper($payload['coupon_code']) : null,
                'special_requests' => $payload['special_requests'] ?? null,
                'pickup_required' => collect($payload['addons'] ?? [])->contains(function ($row) {
                    $addon = Addon::query()->find($row['id'] ?? 0);

                    return $addon?->is_pickup;
                }),
                'hold_expires_at' => $holdExpiresAt,
                'locale' => $payload['locale'] ?? 'en',
                'created_by' => $payload['created_by'] ?? null,
            ]);

            $this->assignRooms($booking, $allocation, $quote);
            $this->recordGuest($booking, $guestInfo);

            foreach ($quote['addon_lines'] as $line) {
                BookingAddon::query()->create([
                    'booking_id' => $booking->id,
                    'addon_id' => $line['id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);
            }

            $this->transition($booking, $status, $note);

            return $booking->fresh(['rooms.room', 'rooms.category', 'guests', 'addons.addon', 'category', 'mealPlan']);
        });
    }

    /**
     * Staff reservation from the admin panel. Availability is always enforced; with an
     * override reason, rooms closed by a maintenance block may be used, but never a room
     * that another booking holds for the same nights.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createReservation(array $payload, ?int $userId = null, ?string $overrideReason = null): Booking
    {
        $stay = $this->prepareStay($payload);
        $guestInfo = $this->guestInfoFor($payload);
        $status = BookingStatus::tryFrom((string) ($payload['status'] ?? '')) ?? BookingStatus::Confirmed;
        if (! in_array($status, [BookingStatus::PendingConfirmation, BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            throw new BookingException('New reservations start as pending or confirmed.');
        }

        return DB::transaction(function () use ($payload, $stay, $guestInfo, $status, $userId, $overrideReason) {
            $allocation = $this->allocate($stay, lock: true, ignoreBlocks: filled($overrideReason));
            $quote = $this->quoteStay($stay, null);
            $paid = $this->validatedAmountPaid($payload, $quote['grand_total']);
            $guest = isset($payload['guest_id']) ? Guest::query()->findOrFail($payload['guest_id']) : $this->upsertGuest($guestInfo);

            $initial = $status === BookingStatus::PendingConfirmation ? $status : BookingStatus::Pending;
            $booking = Booking::query()->create([
                ...$this->stayColumns($stay, $quote),
                'booking_number' => filled($payload['booking_number'] ?? null) ? $payload['booking_number'] : null,
                'status' => $initial,
                'source' => $payload['source'] ?? BookingSource::Phone->value,
                'guest_id' => $guest->id,
                'amount_paid' => $paid,
                'balance_amount' => round($quote['grand_total'] - $paid, 2),
                'special_requests' => $payload['special_requests'] ?? null,
                'internal_notes' => $payload['internal_notes'] ?? null,
                'hold_expires_at' => $initial === BookingStatus::Pending ? now()->addMinutes($this->settings->holdMinutes()) : null,
                'locale' => $payload['locale'] ?? 'en',
                'created_by' => $userId,
            ]);

            $this->assignRooms($booking, $allocation, $quote);
            $this->recordGuest($booking, $guestInfo);
            $this->transition($booking, $initial, $this->staffNote('Reservation created by staff', $overrideReason), $userId);

            if ($status === BookingStatus::Confirmed) {
                $booking->confirmed_at = now();
                $booking->hold_expires_at = null;
                $this->transition($booking, BookingStatus::Confirmed, 'Confirmed by staff', $userId);
            }

            return $booking->fresh(['rooms.room', 'rooms.category', 'guests', 'category', 'mealPlan']);
        });
    }

    /**
     * Staff edit. Dates, guests, meal plan or room changes re-check availability (ignoring
     * this booking's own rooms) and re-price the stay at current rates. Other edits keep the
     * stored booking-time prices. Cancelled or expired bookings release their rooms.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateReservation(Booking $booking, array $payload, ?int $userId = null, ?string $overrideReason = null): Booking
    {
        $stay = $this->prepareStay($payload);

        return DB::transaction(function () use ($booking, $payload, $stay, $userId, $overrideReason) {
            $booking = Booking::query()->with(['rooms', 'addons'])->lockForUpdate()->findOrFail($booking->id);
            $from = $booking->status;
            $to = BookingStatus::tryFrom((string) ($payload['status'] ?? '')) ?? $from;
            $statusChanged = $to !== $from;
            $stayChanged = $this->stayChanged($booking, $stay);

            $holdsRooms = in_array($to, [BookingStatus::Paid, BookingStatus::Confirmed, BookingStatus::CheckedIn], true)
                || ($to === BookingStatus::Pending && ($statusChanged || $stayChanged || $booking->holdIsValid()));
            // A request does not hold rooms, but a changed stay must still be free and re-priced.
            $checksRooms = $holdsRooms || ($to === BookingStatus::PendingConfirmation && $stayChanged);
            if ($stayChanged && ! $checksRooms) {
                throw new BookingException('Rooms and dates can only be changed on an active booking. Set the status to pending or confirmed first.');
            }

            if ($checksRooms) {
                $allocation = $this->allocate($stay, lock: true, current: $booking, ignoreBlocks: filled($overrideReason));
                if ($stayChanged) {
                    $quote = $this->quoteStay(
                        $stay,
                        $booking->guests()->value('email') ?? $booking->guests()->value('phone'),
                        $booking->addons->map(fn ($line) => ['id' => $line->addon_id, 'quantity' => $line->quantity])->all(),
                        $booking->coupon_code,
                        $booking,
                    );
                    $booking->fill($this->stayColumns($stay, $quote));
                    $booking->rooms()->delete();
                    $this->assignRooms($booking, $allocation, $quote);
                } else {
                    $this->reassignRooms($booking, $allocation);
                }
            }

            $paid = $this->validatedAmountPaid($payload + ['amount_paid' => $booking->amount_paid], (float) $booking->grand_total);
            $booking->fill([
                'source' => $payload['source'] ?? $booking->source,
                'guest_id' => $payload['guest_id'] ?? $booking->guest_id,
                'special_requests' => array_key_exists('special_requests', $payload) ? $payload['special_requests'] : $booking->special_requests,
                'internal_notes' => array_key_exists('internal_notes', $payload) ? $payload['internal_notes'] : $booking->internal_notes,
                'amount_paid' => $paid,
                'balance_amount' => max(0, round((float) $booking->grand_total - $paid, 2)),
            ]);
            if ($to === BookingStatus::Pending && ($statusChanged || $stayChanged)) {
                $booking->hold_expires_at = now()->addMinutes($this->settings->holdMinutes());
            }

            if ($statusChanged) {
                match ($to) {
                    BookingStatus::Confirmed => $booking->confirmed_at ??= now(),
                    BookingStatus::CheckedIn => $booking->checked_in_at ??= now(),
                    BookingStatus::Completed => $booking->checked_out_at ??= now(),
                    BookingStatus::Cancelled => $booking->cancelled_at ??= now(),
                    default => null,
                };
                if (in_array($to, [BookingStatus::Confirmed, BookingStatus::PendingConfirmation], true)) {
                    $booking->hold_expires_at = null;
                }
                $this->transition($booking, $to, $this->staffNote('Status changed by staff', $overrideReason), $userId);
            } else {
                $booking->save();
                if ($stayChanged) {
                    BookingStatusLog::query()->create([
                        'booking_id' => $booking->id,
                        'from_status' => $from->value,
                        'to_status' => $to->value,
                        'user_id' => $userId,
                        'note' => $this->staffNote('Stay or rooms changed by staff and re-priced', $overrideReason),
                    ]);
                }
            }

            return $booking->fresh(['rooms.room', 'rooms.category', 'guests', 'category', 'mealPlan']);
        });
    }

    /**
     * Staff status change that keeps the stay as booked. Moving to a status that holds
     * rooms (confirmed, checked in) re-checks availability under lock and fails if
     * another booking already has the rooms.
     */
    public function changeStatus(Booking $booking, BookingStatus $to, ?int $userId = null): Booking
    {
        return $this->updateReservation($booking, [...$this->payloadFor($booking), 'status' => $to->value], $userId);
    }

    /**
     * Whether the booking's rooms are free for its dates, ignoring the booking itself.
     * Returns null when they are, or the reason when they are not.
     */
    public function availabilityProblem(Booking $booking): ?string
    {
        return $this->previewReservation($this->payloadFor($booking), $booking)['error'];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Booking $booking): array
    {
        return [
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'adults' => (int) $booking->adults,
            'children' => (int) $booking->children,
            'meal_plan_id' => $booking->meal_plan_id,
            'rooms' => $booking->roomLines()
                ->map(fn (array $line) => ['room_category_id' => $line['room_category_id'], 'quantity' => $line['quantity']])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{check_in: Carbon, check_out: Carbon, adults: int, children: int, child_ages: mixed, selections: list<array{category: RoomCategory, quantity: int}>, meal_plan: ?MealPlan, addons: array<int, mixed>, coupon_code: ?string}
     */
    private function prepareStay(array $payload): array
    {
        $checkIn = Carbon::parse($payload['check_in'])->startOfDay();
        $checkOut = Carbon::parse($payload['check_out'])->startOfDay();
        $adults = (int) $payload['adults'];
        $children = (int) ($payload['children'] ?? 0);

        if ($adults < 1) {
            throw new BookingException('Please select at least one adult.');
        }
        if ($checkOut->lte($checkIn)) {
            throw new BookingException('Check-out date must be after check-in. Minimum stay is 1 night.');
        }

        $selections = $this->resolveSelections($payload);
        $this->assertGuestsFit($selections, $adults, $children);

        $mealPlan = isset($payload['meal_plan_id'])
            ? MealPlan::query()->where('is_active', true)->find($payload['meal_plan_id'])
            : MealPlan::query()->where('slug', 'room-only')->where('is_active', true)->first();

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'child_ages' => $payload['child_ages'] ?? null,
            'selections' => $selections,
            'meal_plan' => $mealPlan,
            'addons' => $payload['addons'] ?? [],
            'coupon_code' => $payload['coupon_code'] ?? null,
        ];
    }

    /**
     * Accepts `rooms: [{room_category_id, quantity}]`, or the older single
     * `room_category_id` + `rooms_count`. Repeated categories are merged; quantity 0 removes one.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{category: RoomCategory, quantity: int}>
     */
    private function resolveSelections(array $payload): array
    {
        $rows = $payload['rooms'] ?? null;
        if (! is_array($rows) || $rows === []) {
            $rows = isset($payload['room_category_id'])
                ? [['room_category_id' => $payload['room_category_id'], 'quantity' => $payload['rooms_count'] ?? 1]]
                : [];
        }

        $quantities = [];
        foreach ($rows as $row) {
            $id = (int) ($row['room_category_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 1);
            if ($id > 0 && $quantity > 0) {
                $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
            }
        }
        if ($quantities === []) {
            throw new BookingException('Please select at least one room.', 422, ['rooms' => ['Please select at least one room.']]);
        }
        if (array_sum($quantities) > self::MAX_ROOMS_PER_BOOKING) {
            throw new BookingException('A single booking can include up to '.self::MAX_ROOMS_PER_BOOKING.' rooms. Please send a group inquiry for larger groups.');
        }

        $categories = RoomCategory::query()->where('is_active', true)->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        $selections = [];
        foreach ($quantities as $id => $quantity) {
            $category = $categories->get($id);
            if (! $category) {
                throw new BookingException('The selected room is not available.', 422, ['rooms' => ['The selected room is not available.'], 'availability' => [$id => 0]]);
            }
            $selections[] = ['category' => $category, 'quantity' => $quantity];
        }

        return $selections;
    }

    /**
     * @param  list<array{category: RoomCategory, quantity: int}>  $selections
     */
    private function assertGuestsFit(array $selections, int $adults, int $children): void
    {
        $rooms = array_sum(array_column($selections, 'quantity'));
        $maxAdults = array_sum(array_map(fn ($s) => $s['category']->max_adults * $s['quantity'], $selections));
        $maxChildren = array_sum(array_map(fn ($s) => $s['category']->max_children * $s['quantity'], $selections));
        $single = $rooms === 1;

        if ($rooms > $adults) {
            throw new BookingException("Each room needs at least one adult. You selected {$rooms} rooms for {$adults} ".Str::plural('adult', $adults).'.', 422, ['guests' => ['Each room needs at least one adult.']]);
        }
        if ($adults > $maxAdults) {
            $message = $single
                ? 'This room cannot accommodate that many adults.'
                : "The selected rooms sleep up to {$maxAdults} adults. Add a room or reduce the number of adults.";
            throw new BookingException($message, 422, ['guests' => [$message]]);
        }
        if ($children > $maxChildren) {
            $message = $single
                ? 'This room cannot accommodate that many children.'
                : ($maxChildren === 0
                    ? 'The selected rooms do not take children. Add a couple room or contact the resort.'
                    : "The selected rooms take up to {$maxChildren} ".Str::plural('child', $maxChildren).'. Add a room or reduce the number of children.');
            throw new BookingException($message, 422, ['guests' => [$message]]);
        }
    }

    /**
     * Picks free physical rooms for every selected category across the whole stay.
     *
     * @param  array<string, mixed>  $stay
     * @return array<int, Collection<int, Room>>
     */
    private function allocate(array $stay, bool $lock, ?Booking $current = null, bool $ignoreBlocks = false): array
    {
        $selections = $stay['selections'];
        $free = $this->availability->availableRoomsFor(
            array_map(fn ($s) => $s['category']->id, $selections),
            $stay['check_in'],
            $stay['check_out'],
            $lock,
            $current?->id,
            $ignoreBlocks,
        );
        $mine = $current ? $current->rooms->pluck('room_id')->all() : [];

        $allocation = [];
        $messages = [];
        $availability = [];
        foreach ($selections as $selection) {
            $category = $selection['category'];
            $rooms = $free->get($category->id, collect())
                ->sortBy(fn (Room $room) => [in_array($room->id, $mine, true) ? 0 : 1, $room->id])
                ->values();
            $availability[$category->id] = $rooms->count();
            if ($rooms->count() < $selection['quantity']) {
                $messages[] = $rooms->isEmpty()
                    ? "{$category->name_en} is not available for these dates."
                    : "Only {$rooms->count()} {$category->name_en} ".Str::plural('room', $rooms->count())." available for these dates (you asked for {$selection['quantity']}).";

                continue;
            }
            $allocation[$category->id] = $rooms->take($selection['quantity']);
        }

        if ($messages !== []) {
            $message = count($selections) === 1 && $availability[$selections[0]['category']->id] === 0
                ? 'This room is no longer available. Please select another room.'
                : implode(' ', $messages);
            throw new BookingException($message, 422, ['rooms' => $messages, 'availability' => $availability]);
        }

        return $allocation;
    }

    /**
     * @param  array<string, mixed>  $stay
     * @param  array<int, mixed>|null  $addons
     * @return array<string, mixed>
     */
    private function quoteStay(array $stay, ?string $guestKey, ?array $addons = null, ?string $couponCode = null, ?Booking $existing = null): array
    {
        return $this->pricing->quoteRooms(
            $stay['selections'],
            $stay['check_in'],
            $stay['check_out'],
            $stay['adults'],
            $stay['children'],
            $stay['meal_plan'],
            $addons ?? $stay['addons'],
            $couponCode ?? $stay['coupon_code'],
            $guestKey,
            $existing,
        );
    }

    /**
     * @param  array<string, mixed>  $stay
     * @param  array<string, mixed>  $quote
     * @return array<string, mixed>
     */
    private function stayColumns(array $stay, array $quote): array
    {
        return [
            'room_category_id' => $stay['selections'][0]['category']->id,
            'check_in' => $stay['check_in']->toDateString(),
            'check_out' => $stay['check_out']->toDateString(),
            'adults' => $stay['adults'],
            'children' => $stay['children'],
            'child_ages' => $stay['child_ages'],
            'meal_plan_id' => $stay['meal_plan']?->id,
            'room_subtotal' => $quote['room_subtotal'],
            'meal_total' => $quote['meal_total'],
            'addon_total' => $quote['addon_total'],
            'discount_total' => $quote['discount_total'],
            'grand_total' => $quote['grand_total'],
            'advance_amount' => $quote['advance_amount'],
            'balance_amount' => $quote['balance_amount'],
            'currency' => 'BDT',
            'price_breakdown' => $quote,
        ];
    }

    /**
     * @param  array<int, Collection<int, Room>>  $allocation
     * @param  array<string, mixed>  $quote
     */
    private function assignRooms(Booking $booking, array $allocation, array $quote): void
    {
        foreach ($quote['items'] as $item) {
            foreach ($allocation[$item['room_category_id']] as $room) {
                BookingRoom::query()->create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'room_category_id' => $item['room_category_id'],
                    'nightly_rate' => $item['nightly_rate'],
                    'nights' => $item['nights'],
                    'subtotal' => $item['per_room_total'],
                    'nightly' => $item['nightly'],
                ]);
            }
        }
    }

    /**
     * Swaps physical rooms within the same categories, keeping the stored booking-time prices.
     *
     * @param  array<int, Collection<int, Room>>  $allocation
     */
    private function reassignRooms(Booking $booking, array $allocation): void
    {
        $wanted = collect($allocation)->flatten()->pluck('id')->all();
        $current = $booking->rooms->pluck('room_id')->all();
        sort($wanted);
        sort($current);
        if ($wanted === $current) {
            return;
        }

        $pricing = $booking->rooms->groupBy(fn (BookingRoom $row) => $row->room_category_id)
            ->map(fn ($rows) => $rows->first()->only(['nightly_rate', 'nights', 'subtotal', 'nightly']));
        $booking->rooms()->delete();
        foreach ($allocation as $categoryId => $rooms) {
            foreach ($rooms as $room) {
                BookingRoom::query()->create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'room_category_id' => $categoryId,
                    ...($pricing->get($categoryId) ?? []),
                ]);
            }
        }
        $booking->unsetRelation('rooms');
    }

    /**
     * @param  array<string, mixed>  $stay
     */
    private function stayChanged(Booking $booking, array $stay): bool
    {
        $current = $booking->rooms->isEmpty()
            ? [$booking->room_category_id => 1]
            : $booking->rooms->countBy(fn (BookingRoom $row) => $row->room_category_id)->all();
        $requested = collect($stay['selections'])->mapWithKeys(fn ($s) => [$s['category']->id => $s['quantity']])->all();
        ksort($current);
        ksort($requested);

        return $current !== $requested
            || ! $booking->check_in->isSameDay($stay['check_in'])
            || ! $booking->check_out->isSameDay($stay['check_out'])
            || (int) $booking->adults !== $stay['adults']
            || (int) $booking->children !== $stay['children']
            || ($booking->meal_plan_id ? (int) $booking->meal_plan_id : MealPlan::query()->where('slug', 'room-only')->value('id')) !== $stay['meal_plan']?->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatedAmountPaid(array $payload, float $grandTotal): float
    {
        $paid = round((float) ($payload['amount_paid'] ?? 0), 2);
        if ($paid < 0 || $paid > $grandTotal) {
            throw new BookingException('Amount paid must be between ৳0 and the booking total of ৳'.number_format($grandTotal).'.', 422, ['amount_paid' => ['Amount paid cannot exceed the booking total.']]);
        }

        return $paid;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function guestInfoFor(array $payload): array
    {
        if (isset($payload['guest_id'])) {
            $guest = Guest::query()->findOrFail($payload['guest_id']);

            return $guest->only(['full_name', 'phone', 'whatsapp', 'email', 'id_type', 'id_number', 'country', 'address']);
        }
        $info = $payload['guest'] ?? [];
        if (empty($info['full_name']) || empty($info['phone'])) {
            throw new BookingException('Guest name and phone are required.');
        }

        return $info;
    }

    /**
     * @param  array<string, mixed>  $guestInfo
     */
    private function upsertGuest(array $guestInfo): Guest
    {
        return Guest::query()->updateOrCreate(
            ['phone' => $guestInfo['phone']],
            [
                'full_name' => $guestInfo['full_name'],
                'whatsapp' => $guestInfo['whatsapp'] ?? $guestInfo['phone'],
                'email' => $guestInfo['email'] ?? null,
                'id_type' => $guestInfo['id_type'] ?? null,
                'id_number' => $guestInfo['id_number'] ?? null,
                'country' => $guestInfo['country'] ?? 'Bangladesh',
                'address' => $guestInfo['address'] ?? null,
                'marketing_opt_in' => (bool) ($guestInfo['marketing_opt_in'] ?? false),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $guestInfo
     */
    private function recordGuest(Booking $booking, array $guestInfo): void
    {
        BookingGuest::query()->create([
            'booking_id' => $booking->id,
            'full_name' => $guestInfo['full_name'],
            'phone' => $guestInfo['phone'],
            'whatsapp' => $guestInfo['whatsapp'] ?? $guestInfo['phone'],
            'email' => $guestInfo['email'] ?? null,
            'id_type' => $guestInfo['id_type'] ?? null,
            'id_number' => $guestInfo['id_number'] ?? null,
            'country' => $guestInfo['country'] ?? null,
            'address' => $guestInfo['address'] ?? null,
            'is_primary' => true,
        ]);
    }

    private function staffNote(string $note, ?string $overrideReason): string
    {
        return filled($overrideReason) ? "{$note}. Room block override: {$overrideReason}" : $note;
    }

    public function expireHolds(): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->get();

        foreach ($expired as $booking) {
            $this->transition($booking, BookingStatus::Expired, 'Hold expired');
        }

        return $expired->count();
    }

    public function confirm(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Paid], true)) {
            throw new BookingException('This booking cannot be confirmed.');
        }

        $booking->confirmed_at = now();
        $this->transition($booking, BookingStatus::Confirmed, 'Payment verified');

        if ($booking->coupon_id) {
            Coupon::query()->where('id', $booking->coupon_id)->increment('used_count');
        }

        return $booking->fresh();
    }

    public function markPaid(Booking $booking, float $amount): Booking
    {
        $booking->amount_paid = round((float) $booking->amount_paid + $amount, 2);
        $booking->balance_amount = max(0, round((float) $booking->grand_total - (float) $booking->amount_paid, 2));
        $this->transition($booking, BookingStatus::Paid, 'Payment recorded');

        return $this->confirm($booking);
    }

    public function cancel(Booking $booking, string $reason, ?int $userId = null): Booking
    {
        if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Expired, BookingStatus::Completed], true)) {
            throw new BookingException('This booking cannot be cancelled.');
        }
        $booking->cancelled_at = now();
        $booking->cancel_reason = $reason;
        $this->transition($booking, BookingStatus::Cancelled, $reason, $userId);

        return $booking;
    }

    public function checkIn(Booking $booking, ?int $userId = null): Booking
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw new BookingException('Only confirmed bookings can be checked in.');
        }
        $booking->checked_in_at = now();
        $this->transition($booking, BookingStatus::CheckedIn, 'Checked in', $userId);

        return $booking;
    }

    public function complete(Booking $booking, ?int $userId = null): Booking
    {
        if (! in_array($booking->status, [BookingStatus::CheckedIn, BookingStatus::Confirmed], true)) {
            throw new BookingException('This booking cannot be completed.');
        }
        $booking->checked_out_at = now();
        $this->transition($booking, BookingStatus::Completed, 'Checked out', $userId);

        return $booking;
    }

    public function failPayment(Booking $booking, string $reason): Booking
    {
        $this->transition($booking, BookingStatus::PaymentFailed, $reason);

        return $booking;
    }

    public function assertPayable(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw new BookingException('This booking is not awaiting payment.');
        }
        if (! $booking->hold_expires_at || $booking->hold_expires_at->isPast()) {
            $this->expireHolds();
            throw new BookingException('Your booking hold has expired. Please search again.');
        }
    }

    private function transition(Booking $booking, BookingStatus $to, ?string $note = null, ?int $userId = null): void
    {
        $from = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        $booking->status = $to;
        $booking->save();

        BookingStatusLog::query()->create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to->value,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
