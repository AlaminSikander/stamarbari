# Payment

Primary gateway: **bKash** tokenized checkout.

```
Create hold → Create payment → Guest pays → Callback
→ Execute/verify → Match amount + booking → Mark successful → Confirm
```

Idempotency: successful `transaction_id` is unique. Duplicate callbacks return the original successful payment.

Never confirm from frontend success.

Sandbox without credentials (local/testing only) uses a clearly isolated path so automated tests can run. Production **requires** `BKASH_*` env vars.

Refunds are tracked in admin (`requested → processing → completed/failed`). Manual/cash payments are for walk-in staff only.
