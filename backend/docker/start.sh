#!/bin/sh
# Container start-up: runs on every deploy/restart, after Render injects env vars.
set -e

# Cache config and routes now that the real environment variables are available.
php artisan config:cache
php artisan route:cache

# Apply new migrations. --force is required in production (no confirmation prompt).
php artisan migrate --force

# Hand the process over to Apache (exec = Apache becomes PID 1 and receives stop signals).
exec apache2-foreground
