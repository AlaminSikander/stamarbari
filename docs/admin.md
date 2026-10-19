# Admin (Filament `/admin`)

Roles: Owner, Manager, Receptionist (Spatie permissions).

2FA (TOTP) is available on all admin accounts via Filament app authentication. Owners should enable it.

Modules generated under `backend/app/Filament/Resources` include bookings, rooms, prices (via room categories), payments, refunds, coupons, CMS, gallery, blog, FAQs, reviews (moderation — do not fabricate), inquiries, backups, users.

Site rules (check-in, child policy, missed-ship refund, emergency numbers) live in **Site Settings**. Leave unknown emergency numbers empty.

Walk-in bookings: create a booking in Filament with source `walk_in` and record a manual payment.
