# Booking system

Online payment is switched by `BOOKING_ONLINE_PAYMENT_ENABLED` (backend `.env`, default `false`).

Payment disabled (current):

```
Search → Select rooms → Guest details → Booking request saved (pending_confirmation)
→ Success screen + toast → Guest sends details on WhatsApp (optional)
→ Staff check availability and confirm in /admin
```

Payment enabled (bKash, kept for later):

```
Search → Available rooms → Select → Guest / meal / add-ons
→ Server hold (15 min) → Payment → Verify → Confirmed
```

## Booking requests (payment disabled)

- `POST /bookings` validates the request, checks that the rooms are free, re-prices everything on the server and saves the booking as `pending_confirmation` with `amount_paid = 0`, no `hold_expires_at` and no payment record. Prices sent by the browser are ignored.
- A request does not reserve rooms and is never expired by the hold job. Another guest can still request the same room. Confirming (or checking in) re-checks availability under a row lock, so two overlapping bookings can never both be confirmed; the second confirmation fails with the conflict.
- A request for rooms already held by a confirmed, paid, checked-in or valid pending-payment booking is rejected with 422 and nothing is saved.
- Duplicate submissions: the site sends a random `request_key` with every booking attempt. Re-sending the same key returns the booking already saved (200 instead of 201); `bookings.request_key` is unique, so concurrent duplicates collapse to one row. Without a key, an identical request from the same phone within 10 minutes is treated as a duplicate. The submit button is also locked while a request is in flight.
- The response includes `access_token`. `GET /bookings/{number}` and the invoice download need `?token=`; without it they return 404, so guest details are never exposed by booking number alone. Internal notes are never returned.
- `whatsapp_message` in the response is built by `App\Support\BookingWhatsAppMessage` from the saved booking. The success screen opens `https://wa.me/<NEXT_PUBLIC_WHATSAPP_NUMBER>?text=…` so the guest can review and send it. Nothing is sent automatically. To send automatically later, pass the same text to the WhatsApp Business Platform sender in `NotificationService`.

## Rooms

Admin manages rooms in one place: **Room Categories**. Each category is one named room (for example Nokkhotro or Keya) with a room type (`couple` or `double_bed`), capacity (`max_adults`, `max_children`), and a base nightly price in BDT (`room_prices`, type `base`).

Each category owns one or more physical units in `rooms`. These are edited inside the category form, not in a separate table. Availability counts free active units, so a category can never be booked more times per night than it has units. `booking_rooms` and `room_blocks` point at units.

Deleting a category:

- is refused while it has current or upcoming bookings (booking requests, pending holds, paid, confirmed, checked in);
- archives it (soft delete) when it has only past bookings, so history keeps its room name;
- is refused permanently (force delete) when any booking references it.

Bookings store their totals and `price_breakdown` at creation, so changing a category's price only affects new quotes.

## Multiple rooms per booking

A booking can hold rooms from several categories, and several rooms of one category when it has more than one unit. Each `booking_rooms` row is one assigned physical room with its `room_category_id` and the booking-time `nightly_rate`, `nights`, `subtotal` and per-night `nightly` prices. Quantity per category is the number of rows. `bookings.room_category_id` keeps the first selected category for older reports.

- API: `POST /bookings/quote` and `POST /bookings` accept `rooms: [{room_category_id, quantity}]`. The single `room_category_id` + `rooms_count` form still works. Availability results include `units_available`.
- Guests: each room needs at least one adult; adults and children must fit the combined capacity of the selected rooms.
- Allocation locks the candidate rooms and re-reads occupancy with locking reads inside one transaction. Every selected category must have enough free rooms for every night, or nothing is created.
- Admin: Bookings → New/Edit has a room repeater (category + quantity) with a live summary of availability, prices, total, advance and balance. Editing dates, guests, meal plan or rooms re-checks availability (ignoring the booking's own rooms) and re-prices at current rates; other edits keep booking-time prices. Cancelled, expired and completed bookings release their rooms; reactivating one re-checks availability.
- Override: users with `manage_rooms` can use rooms closed by a maintenance block after giving a reason and confirming. The reason is logged. Rooms held by other bookings are never assignable.

## Hold

Only when online payment is enabled. `hold_expires_at` is set from `booking_hold_minutes` (default 15). Availability queries ignore expired holds. Allocation uses `lockForUpdate` inside a transaction.

## Status

Booking requests: `pending_confirmation → confirmed → checked_in → completed` (or `cancelled`).

With online payment: `pending → paid → confirmed → checked_in → completed`

Only `pending` (with a valid hold), `paid`, `confirmed` and `checked_in` reserve rooms.

Terminal: `cancelled`, `refunded`, `payment_failed`, `expired`

Every transition writes `booking_status_logs`.

## Pricing

Server-only. Nightly: seasonal/holiday (priority) → weekend → base. Then offers, coupons, meals, add-ons. Extra mattresses are not offered. A 50% advance is required to confirm a booking; while online payment is disabled the resort collects it outside the website.

## Policies (settings)

Check-in 12:00, check-out 10:00, child policy age 7, cancellation 4 or more days before check-in refunds 70% of the advance (30% kept as a cancellation fee); less than 4 days before check-in is non-refundable (`cancellation_days`, `cancellation_refund_percent`, `RefundService::policyRefundAmount`). Missed ship: no refund.
