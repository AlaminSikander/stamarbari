# Admin (Filament `/admin`)

The admin runs inside the same site: open `http://localhost:3000/admin` (production: `https://<site>/admin`). Next.js proxies `/admin`, `/livewire/*`, `/filament/*`, the Filament assets and `/storage/*` to the Laravel app (`frontend/next.config.ts`, target `BACKEND_ORIGIN`). Laravel trusts the proxy (`TRUSTED_PROXIES`, default `127.0.0.1,::1`) so links and redirects stay on the site origin.

Every admin page and action is behind Filament login and `User::canAccessPanel()` (active users with the owner, manager or receptionist role). Room management also needs the `manage_rooms` permission.

Roles: Owner, Manager, Receptionist (Spatie permissions).

2FA (TOTP) is available on all admin accounts via Filament app authentication. Owners should enable it.

Modules generated under `backend/app/Filament/Resources` include bookings, rooms, prices (via room categories), payments, refunds, coupons, CMS, gallery, blog, FAQs, reviews (moderation — do not fabricate), inquiries, backups, users.

Main routes:

| Path | Page |
| --- | --- |
| `/admin` | Dashboard (login when signed out) |
| `/admin/bookings` | Booking requests and reservations |
| `/admin/bookings/{id}` | Booking details and actions |
| `/admin/rooms` | Rooms (room categories, units, prices) |
| `/admin/settings` | Site and booking settings |

## Booking requests

The Bookings menu shows a badge with the number of requests awaiting confirmation.

- List: search by reference, guest name or phone; filter by status (multiple) and check-in date range. Columns include estimated total and payment status.
- Booking page: guest contact (call, WhatsApp, email), stay, rooms, add-ons, special requests, amounts, internal notes and status history.
- Actions (booking page and row menu): **Confirm booking**, **Check availability**, **Update status** (Pending Confirmation, Confirmed, Checked In, Completed, Cancelled), **WhatsApp guest**, **Call guest**, **Add internal note**.
- Confirming or checking in re-checks room availability under lock and refuses if another booking has the rooms. Cancelling releases them.
- Internal notes are staff-only and never returned by the public API.
- No payment is collected in this workflow. `amount_paid` and the payment fields stay available for later use.

Site rules (check-in, child policy, missed-ship refund, emergency numbers) live in **Site Settings**. Leave unknown emergency numbers empty.

Walk-in bookings: create a booking in Filament with source `walk_in` and record a manual payment.
