# Architecture — S T Amar Bari Resort

## Brand

**S T Amar Bari Resort** — *Your Home on the Island.*  
Location: Poschim Para, CTB Beach Road, Saint Martin Island, Bangladesh.  
Timezone: `Asia/Dhaka`. Currency: BDT (৳).

## System shape

```text
Browser / Mobile
        ↓
Next.js (www)  —  i18n, SEO, booking UX
        ↓  REST /api/v1
Laravel API + Filament admin
        ↓
MySQL  +  queues  +  storage
        ↓
bKash  |  WhatsApp / SMS / Email (provider-abstracted)
```

Recommended hosts (all via env, never hard-coded):

| Surface | Typical host |
| --- | --- |
| Public site | `FRONTEND_URL` |
| API | `APP_URL` |
| Admin | `{APP_URL}/admin` |

## Applications

### `frontend/` — Next.js 16 App Router

- TypeScript, Tailwind CSS 4, Framer Motion
- `en` / `bn` with `hreflang`
- Server Components for content; client components for booking widgets
- Live availability, prices, and payment status are **never** trusted from the client
- Public content may be cached; booking/payment endpoints are not

### `backend/` — Laravel 13 + Filament 4

- Versioned JSON API: `/api/v1/*`
- Filament admin at `/admin`
- Domain services: Availability, Pricing, Booking, Payment, Refund, Notification
- Jobs for hold expiry, reminders, invoices, backups
- Spatie permission + activity log
- Admin 2FA (TOTP) via Filament app authentication

## Trust boundary

The browser may *suggest* dates, rooms, coupons, and add-ons. Laravel recalculates:

- inventory (with row locks)
- nightly price
- discounts
- advance amount
- payment verification

Bookings become `confirmed` only after a **server-verified** payment (or an authorized walk-in/manual payment recorded by staff).

## Explicit exclusions

No ship schedule manager, ship CRUD, or ship ticket module. Saint Martin travel copy may mention boat/ship travel in general terms and must stay admin-editable.

## Environments

`local` · `staging` · `production`  
bKash uses sandbox credentials until production keys are configured.
