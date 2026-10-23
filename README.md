# S T Amar Bari Resort

Premium Saint Martin beach resort website and booking platform.

**Your Home on the Island.**

## Stack

- Next.js 16 (public site, TypeScript, i18n)
- Laravel 13 REST API (`/api/v1`)
- MySQL
- Laravel Filament 4 admin, served by the same site at `/admin`
- bKash (currently switched off: website bookings are requests confirmed by staff)
- WhatsApp / email / SMS notification abstraction

## Requirements

- PHP 8.4+
- Composer
- Node.js 20+
- MySQL 8 (or Docker)

## Installation

```bash
git clone <repo>
cd "St Amar Bari"
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env.local
```

Set `APP_KEY` by running `php artisan key:generate` inside `backend/`.

### Database

```bash
docker compose up -d mysql
cd backend
composer install
php artisan migrate --seed
```

Default owner login (change immediately):

- Email: `owner@stamarbariresort.com`
- Password: `password`

Migrations and the seeder create the resort's 14 named rooms (Room Categories) with their configured nightly prices. Re-seeding never overwrites prices or details edited in the admin.

### Queue and scheduler

```bash
php artisan queue:work
php artisan schedule:work
```

Cron (production):

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Run the app

One command starts everything (Next.js site, Laravel and the queue worker):

```bash
cd frontend
npm install
npm run dev:all
```

- Site: http://localhost:3000
- Admin: http://localhost:3000/admin
- API (used by the site): http://localhost:8000/api/v1

The admin is part of the same site: Next.js proxies `/admin` and the Filament/Livewire assets to Laravel, which runs in the background on `BACKEND_ORIGIN` (default `http://127.0.0.1:8000`). If port 8000 is taken, the script stops with an error; pick another port with `BACKEND_ORIGIN=http://127.0.0.1:8010 npm run dev:all`.

You can still start the parts separately: `cd backend && php artisan serve` and `cd frontend && npm run dev`.

After pulling these changes, run `php artisan migrate` in `backend/`.

## Booking requests (payment temporarily disabled)

With `BOOKING_ONLINE_PAYMENT_ENABLED=false` (the default), **Book Now** saves a booking request with status **Pending Confirmation**. No payment gateway opens and nothing is confirmed or reserved automatically. The guest sees a toast and a success screen with the booking reference and a WhatsApp button. Staff confirm requests in `/admin/bookings`, which re-checks availability. Details: `docs/booking-system.md`, `docs/admin.md`.

## Payments

Online payment is off. The bKash integration is kept; set `BOOKING_ONLINE_PAYMENT_ENABLED=true` to turn it back on.

Never confirm a booking from the browser. bKash callbacks are verified server-side.

Use sandbox credentials until the full test matrix in `docs/payment.md` passes.

## WhatsApp

**Guest click-to-chat (booking success screen):** set `NEXT_PUBLIC_WHATSAPP_NUMBER` in `frontend/.env.local` (and in the deployment environment) to the resort's WhatsApp number in international format: country code first, digits only, no `+`, no spaces, no leading `0` — for example `8801XXXXXXXXX`. It is read at build time, so restart `next dev` or rebuild after changing it. When it is missing or invalid, the WhatsApp button is shown disabled and guests are asked to call instead. The button opens WhatsApp with the booking details filled in; the guest must press Send.

**Automatic messages (WhatsApp Business Platform):** set `WHATSAPP_API_KEY` and `WHATSAPP_PHONE_NUMBER_ID`. Templates are editable in admin. Invoice PDFs generate after confirmed payment.

## Environment variables added for booking requests

| Variable | Where | Purpose |
| --- | --- | --- |
| `BOOKING_ONLINE_PAYMENT_ENABLED` | `backend/.env` | `false` (default): bookings are requests without payment. `true`: bKash hold + payment flow |
| `TRUSTED_PROXIES` | `backend/.env` | Proxies Laravel trusts for `X-Forwarded-*` (default `127.0.0.1,::1`, the local Next.js server) |
| `APP_URL` | `backend/.env` | Set to the public site origin (`http://localhost:3000` locally) so emails and queued jobs link to the admin on the site |
| `NEXT_PUBLIC_WHATSAPP_NUMBER` | `frontend/.env.local` | Resort WhatsApp number for the click-to-chat button, international format |
| `BACKEND_ORIGIN` | `frontend/.env.local` | Laravel origin that `/admin` is proxied to. Defaults to the origin of `NEXT_PUBLIC_API_URL`. Fixed at build time |

## Testing

```bash
cd backend && php artisan test
cd frontend && npm run lint && npm run build
```

## Documentation

See `docs/` for architecture, database, API, booking, payment, admin, SEO, security, testing, deployment and troubleshooting.

Do not commit `.env` files, API keys, or payment secrets.
