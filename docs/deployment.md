# Deployment

## Vercel (one project, two services)

`vercel.json` at the repo root defines two services that deploy together:

| Service | Root | Runs as | Public paths |
| --- | --- | --- | --- |
| `frontend` | `frontend/` | Next.js | everything else (`/(.*)`) |
| `backend` | `backend/` | Laravel container (`backend/Dockerfile.vercel`) | `/api/*`, `/admin`, `/livewire/*`, `/filament/*`, `/css|js|fonts/filament/*` |

`frontend` has a binding to `backend`: Vercel injects `BACKEND_INTERNAL_URL` into the Next.js server functions, and server-rendered pages call Laravel through it. The browser calls the same-origin `/api/v1`. Leave `NEXT_PUBLIC_API_URL` and `BACKEND_ORIGIN` unset on Vercel.

The image sets `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `TRUSTED_PROXIES=*`, `QUEUE_CONNECTION=sync` and `FILE_STORAGE=s3`.

Environment variables to set in the Vercel project:

| Variable | Notes |
| --- | --- |
| `APP_KEY`, `APP_URL`, `FRONTEND_URL` | `APP_URL` and `FRONTEND_URL` are the public site origin |
| `DB_*` | A hosted MySQL database. Run `php artisan migrate --force` against it yourself; the container does not migrate on start |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_ENDPOINT` | S3-compatible storage (`AWS_ENDPOINT` only for non-AWS providers such as Cloudflare R2) |
| `AWS_BUCKET`, `AWS_URL` | Public bucket for admin uploads and its public base URL |
| `AWS_PRIVATE_BUCKET` | Private bucket for invoices (guest data; must not be publicly readable) |
| `NEXT_PUBLIC_MEDIA_URL` | Same value as `AWS_URL`; the site builds image URLs from it (build time) |
| `CRON_SECRET` | Random string. Vercel sends it to the scheduler endpoint |
| Mail, bKash, WhatsApp, SMS | As in `backend/.env.example` |

Uploads use public object visibility, so the public bucket must accept public-read uploads (on AWS, enable object ACLs) or be publicly readable as a whole.

Queued jobs run inside the request (`sync`), after the surrounding transaction commits. The scheduler (`routes/console.php`) runs through a Vercel cron that calls `GET /api/v1/cron/schedule` every minute with `Authorization: Bearer $CRON_SECRET`. Per-minute crons need a Vercel Pro plan.

The database backup job needs `mysqldump`, which the image does not include, and writes to the ephemeral disk; use your database provider's backups on Vercel.

Local check of the whole setup: `vercel dev` (needs the Vercel CLI and Docker for the backend container).

## Self-hosted

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
