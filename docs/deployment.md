# Deployment

Suggested:

- `www` → Next.js (`frontend`, `output: "standalone"`)
- `api` / same origin `/api` → Laravel
- Admin on `{API}/admin` behind HTTPS

## Laravel

- PHP-FPM + nginx or Laravel Forge / similar
- `php artisan migrate --force`
- Queue worker supervisor
- Scheduler cron
- `APP_ENV=production`, `APP_DEBUG=false`
- Disk for invoices and images (`storage:link`)

## Next.js

- Vercel or Node `next start` with standalone output
- `NEXT_PUBLIC_API_URL` and `NEXT_PUBLIC_SITE_URL`

## SSL / domain

Terminate TLS. Never hard-code domains.

## Backups

Daily `RunDatabaseBackupJob`. Configure retention in Site Settings. Store credentials only in env.

## Monitoring

Hit `/up` for Laravel health. Watch queue failed jobs, backup `status=failed`, and payment callback error logs.
