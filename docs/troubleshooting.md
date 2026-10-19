# Troubleshooting

| Symptom | Check |
| --- | --- |
| No rooms in search | Set base prices in Filament; confirm rooms are active and unblocked |
| Hold vanishes | `php artisan schedule:work` / expire job; `hold_expires_at` |
| Payment not confirming | bKash env, callback URL, amount mismatch, expired hold |
| Invoice missing | Queue worker; `storage/app/invoices` |
| WhatsApp silent | Token + phone number id; notification logs |
| Bangla font odd | Hind Siliguri is loaded; keep copy in `*_bn` fields |
| Admin 403 | User `is_active` and role `owner/manager/receptionist` |
