#!/bin/sh
set -e

# Config is cached at startup, not at build, because Vercel injects the environment at runtime.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
