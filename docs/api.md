# API `/api/v1`

JSON envelope:

```json
{ "success": true, "message": "OK", "data": {} }
```

| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| GET | `/rooms` | public | Cached content |
| GET | `/rooms/{slug}` | public | |
| POST | `/availability` | public | Never cache |
| GET | `/bookings/policies` | public | Includes `online_payment_enabled` |
| POST | `/bookings` | public | Throttled. Prices server-side. Payment disabled: saves a `pending_confirmation` request (201; 200 when `request_key` repeats an earlier request). Payment enabled: creates a 15-min hold. Returns `access_token` and `whatsapp_message` |
| GET | `/bookings/{number}?token=` | token | 404 without the booking's `access_token` |
| GET | `/bookings/{number}/invoice?token=` | token | 404 without the token |
| POST | `/bookings/{number}/payments` | public | Starts bKash. 409 while online payment is disabled |
| POST | `/payments/callback` | public | Server verification |
| GET | `/pages/{slug}` | public | CMS |
| POST | `/contact` | public | Throttled, honeypot `website` |
| POST | `/inquiries` | public | Group inquiries |

Full request examples live alongside Form Requests in `backend/app/Http/Requests/Api`.
