# Database

Timezone: `Asia/Dhaka`. Money: `decimal(12,2)` BDT.

## ERD (logical)

```
RoomCategory 1—N Room 1—N RoomBlock
RoomCategory N—N RoomAmenity
RoomCategory 1—N RoomImage / RoomPrice / SeasonalPrice
Guest 1—N Booking
Booking 1—N BookingRoom / BookingGuest / BookingAddon / Payment / Refund / StatusLog
Coupon N—N RoomCategory
Offer N—N RoomCategory
```

Inventory is **physical rooms**, not a counter. A date range overlaps when:

`existing.check_in < request.check_out AND existing.check_out > request.check_in`

Same-day turnover is allowed (checkout morning, new check-in 12:00).

Blocking inventory statuses: `pending` with future `hold_expires_at`, `paid`, `confirmed`, `checked_in`.

Expired holds are ignored and then marked `expired` by `ExpireBookingHoldsJob`.
