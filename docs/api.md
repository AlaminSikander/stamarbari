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
| POST | `/bookings` | public | Creates 15-min hold, prices server-side |
| GET | `/bookings/{number}` | public | Booking number is the guest token |
| POST | `/bookings/{number}/payments` | public | Starts bKash |
| POST | `/payments/callback` | public | Server verification |
| GET | `/pages/{slug}` | public | CMS |
| POST | `/contact` | public | Throttled, honeypot `website` |
| POST | `/inquiries` | public | Group inquiries |

Full request examples live alongside Form Requests in `backend/app/Http/Requests/Api`.
