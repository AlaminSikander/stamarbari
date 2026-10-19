# Security

- Secrets only in environment variables
- CSRF on admin session
- API throttling on contact/inquiry
- SQL via Eloquent; no raw user SQL
- Prices/availability/payment status never trusted from the client
- Payment amount verified against stored advance
- Duplicate payment callbacks are idempotent
- Admin 2FA (TOTP)
- Activity logs for staff actions (Spatie)
- Backup files stored off the web root (`storage/app/backups`)
- Do not commit `.env`, bKash keys, or WhatsApp tokens
