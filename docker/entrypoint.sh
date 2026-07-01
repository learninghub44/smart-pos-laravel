#!/bin/bash
set -e

# Railway injects a dynamic $PORT - Apache must listen on it
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# Generate APP_KEY on first boot if missing
if [ -z "$APP_KEY" ]; then
  echo "No APP_KEY set - generating one (set it as a Railway env var to persist across deploys)"
  php artisan key:generate --force
fi

# Storage symlink (safe to re-run)
php artisan storage:link || true

# Run pending migrations. Set RUN_MIGRATIONS=false in Railway to skip.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

# Cache config/routes/views for production speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
