#!/bin/bash
set -e

# Railway injects a dynamic $PORT - Apache must listen on it
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# Generate APP_KEY on first boot if missing.
# NOTE: we deliberately do NOT use `php artisan key:generate` here - that
# command reads and rewrites a physical .env file on disk, which doesn't
# exist in this deployment (Railway injects config as real env vars, not
# a .env file). Generating the key directly and exporting it works fine
# since Laravel reads APP_KEY from the process environment either way.
if [ -z "$APP_KEY" ]; then
  export APP_KEY="base64:$(openssl rand -base64 32)"
  echo "=================================================================="
  echo "No APP_KEY was set. Generated one for THIS BOOT ONLY:"
  echo "APP_KEY=${APP_KEY}"
  echo "Copy this into Railway's Variables tab so it persists across"
  echo "deploys - otherwise a new key generates every deploy and"
  echo "invalidates all existing sessions and encrypted data."
  echo "=================================================================="
fi

# Storage symlink (safe to re-run)
php artisan storage:link || true

# Company logo / avatar uploads are saved to resources/assets/uploads by the
# app, which sits outside public/ (our docroot). Symlink it in so uploads
# are actually reachable over HTTP. Idempotent.
mkdir -p public/resources/assets
if [ ! -e public/resources/assets/uploads ]; then
  ln -s ../../../resources/assets/uploads public/resources/assets/uploads
fi

# Run pending migrations. Set RUN_MIGRATIONS=false in Railway to skip.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

# Cache config/routes/views for production speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
