# Booking system

```
Search → Available rooms → Select → Guest / meal / add-ons
→ Server hold (15 min) → Payment → Verify → Confirmed
```

## Hold

`hold_expires_at` is set from `booking_hold_minutes` (default 15). Availability queries ignore expired holds. Allocation uses `lockForUpdate` inside a transaction.

## Status

`pending → paid → confirmed → checked_in → completed`

Terminal: `cancelled`, `refunded`, `payment_failed`, `expired`

Every transition writes `booking_status_logs`.

## Pricing

Server-only. Nightly: seasonal/holiday (priority) → weekend → base. Then offers, coupons, meals, add-ons, extra bed. Advance default 30%.

## Policies (settings)

Check-in 12:00, check-out 10:00, child policy age 7, cancellation 24h / 100% (configurable). Missed ship: no refund.
