# Payment

**Currently disabled.** `BOOKING_ONLINE_PAYMENT_ENABLED=false` (the default) makes website bookings plain requests with no payment (see `booking-system.md`). The bKash gateway, payment tables, callback route and frontend payment redirect are kept. To turn payment back on, set `BOOKING_ONLINE_PAYMENT_ENABLED=true` with working `BKASH_*` credentials and run the test matrix below.

Primary gateway: **bKash** tokenized checkout.

```
Create hold → Create payment → Guest pays → Callback
→ Execute/verify → Match amount + booking → Mark successful → Confirm
```

Idempotency: successful `transaction_id` is unique. Duplicate callbacks return the original successful payment.

Never confirm from frontend success.

Sandbox without credentials (local/testing only) uses a clearly isolated path so automated tests can run. Production **requires** `BKASH_*` env vars.

Refunds are tracked in admin (`requested → processing → completed/failed`). Manual/cash payments are for walk-in staff only.
