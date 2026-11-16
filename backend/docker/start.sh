#!/bin/sh
# Container start-up: runs on every deploy/restart, after Render injects env vars.
set -e

# Render mounts secret files read-only for root; copy Aiven's CA certificate somewhere
# the web server (www-data) can read it, and point the database config at the copy.
if [ -n "$MYSQL_ATTR_SSL_CA" ] && [ -f "$MYSQL_ATTR_SSL_CA" ]; then
    cp "$MYSQL_ATTR_SSL_CA" /var/www/html/storage/db-ca.pem
    chmod 644 /var/www/html/storage/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
fi

# Cache config and routes now that the real environment variables are available.
# (Both are local and take a second; no database needed.)
php artisan config:cache
php artisan route:cache

# Database work runs in the background, so Apache can open the port straight away.
# Render waits only a few minutes for the port; the first seed of the catalog against a
# remote database (hundreds of queries) can take longer than that.
# Until it finishes, /api/health already answers, but catalog pages may be empty for a moment.
(
    # --force is required in production (no confirmation prompt).
    php artisan migrate --force

    # Load/refresh the demo catalog, sellers, sample reviews, discount codes and homepage
    # blocks. The seeders are idempotent, so this is safe on every start.
    php artisan db:seed --force

    # Optional: make ADMIN_EMAIL's account an admin (Render's free plan has no shell to run
    # the command by hand). It only works once that account has registered, so a failure
    # here is expected on the first deploy.
    if [ -n "$ADMIN_EMAIL" ]; then
        php artisan app:make-admin "$ADMIN_EMAIL" || true
    fi

    echo "start.sh: database ready"
) &

# Hand the process over to Apache (exec = Apache becomes PID 1 and receives stop signals).
exec apache2-foreground
