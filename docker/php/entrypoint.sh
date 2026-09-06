#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------------------
# Only run the full bootstrap sequence for the long-running server process.
# One-off commands (`docker compose run --rm app php artisan ...`) skip it.
# ---------------------------------------------------------------------------
if [ "$1" != "php-fpm" ]; then
    exec "$@"
fi

# Discover packages (skipped during image build with --no-scripts) and drop
# any config cache from a previous run so migrations/seeders see current env.
php artisan package:discover --ansi || true
php artisan config:clear || true

# APP_KEY must be provided via the environment (.env). It is never generated
# automatically in production - a changing key would invalidate every session
# and all encrypted data.
if [ -z "${APP_KEY}" ]; then
    echo "ERROR: APP_KEY is not set. Generate one and add it to .env:"
    echo "  docker compose run --rm app php artisan key:generate --show"
    exit 1
fi

echo "Waiting for database ${DB_HOST}:${DB_PORT} ..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int) getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; do
    sleep 2
done
echo "Database is up."

php artisan migrate --force

# Seed roles / categories / initial admin on first boot (idempotent seeders).
php artisan db:seed --force || true

# Cache framework config, routes and views for performance.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure the storage symlink exists (used only for the 'public' disk).
php artisan storage:link || true

exec "$@"
