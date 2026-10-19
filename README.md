# S T Amar Bari Resort

Premium Saint Martin beach resort website and booking platform.

**Your Home on the Island.**

## Stack

- Next.js 16 (public site, TypeScript, i18n)
- Laravel 13 REST API (`/api/v1`)
- MySQL
- Laravel Filament 4 admin (`/admin`)
- bKash (sandbox until production keys are set)
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

Room rates are **not** seeded in production. Set prices in Filament, or locally:

```
SEED_DEMO_RATES=true
```

### Queue and scheduler

```bash
php artisan queue:work
php artisan schedule:work
```

Cron (production):

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Admin: http://localhost:8000/admin  
API: http://localhost:8000/api/v1  
Site: http://localhost:3000

Start Laravel with:

```bash
cd backend && php artisan serve
```

## Payments

Never confirm a booking from the browser. bKash callbacks are verified server-side.

Use sandbox credentials until the full test matrix in `docs/payment.md` passes.

## WhatsApp

Set `WHATSAPP_API_KEY` and `WHATSAPP_PHONE_NUMBER_ID`. Templates are editable in admin. Invoice PDFs generate after confirmed payment.

## Testing

```bash
cd backend && php artisan test
cd frontend && npm run lint && npm run build
```

## Documentation

See `docs/` for architecture, database, API, booking, payment, admin, SEO, security, testing, deployment and troubleshooting.

Do not commit `.env` files, API keys, or payment secrets.
