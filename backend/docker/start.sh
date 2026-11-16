#!/bin/sh
# Container start-up: runs on every deploy/restart, after Render injects env vars.
set -e

# Cache config and routes now that the real environment variables are available.
php artisan config:cache
php artisan route:cache

# Apply new migrations. --force is required in production (no confirmation prompt).
php artisan migrate --force

# Load/refresh the demo catalog and discount codes. The seeders are idempotent: they
# update existing rows and only create keys for new products, so it's safe on every start.
php artisan db:seed --force

# Optional: make ADMIN_EMAIL's account an admin (Render's free plan has no shell to run the
# command by hand). It only works once that account has registered, so a failure here is
# expected on the first deploy and must not stop the container (hence "|| true").
if [ -n "$ADMIN_EMAIL" ]; then
    php artisan app:make-admin "$ADMIN_EMAIL" || true
fi

# Hand the process over to Apache (exec = Apache becomes PID 1 and receives stop signals).
exec apache2-foreground
