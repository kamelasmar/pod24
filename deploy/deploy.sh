#!/usr/bin/env bash
# Pod24 deployment — runs after every `git pull` to refresh the running app.
#
# Usage:
#   sudo bash /var/www/pod24/deploy/deploy.sh
#
# Idempotent. Safe to re-run.

set -euo pipefail

APP_DIR="/var/www/pod24"
APP_USER="pod24"

if [ "$EUID" -ne 0 ]; then
    echo "Run with sudo." >&2
    exit 1
fi

cd "${APP_DIR}"

echo "==> Pulling latest code..."
sudo -u "${APP_USER}" -- git pull --ff-only

echo "==> Installing PHP dependencies..."
sudo -u "${APP_USER}" -- composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing JS dependencies + building assets..."
sudo -u "${APP_USER}" -- npm ci
sudo -u "${APP_USER}" -- npm run build

echo "==> Running database migrations..."
sudo -u "${APP_USER}" -- php artisan migrate --force

echo "==> Re-seeding catalog (idempotent — uses updateOrCreate)..."
sudo -u "${APP_USER}" -- php artisan db:seed --class=RolesAndPermissionsSeeder --force
sudo -u "${APP_USER}" -- php artisan db:seed --class=Pod24ContentSeeder --force
sudo -u "${APP_USER}" -- php artisan db:seed --class=Pod24CatalogSeeder --force

echo "==> Caching config / routes / views for production..."
sudo -u "${APP_USER}" -- php artisan config:cache
sudo -u "${APP_USER}" -- php artisan route:cache
sudo -u "${APP_USER}" -- php artisan view:cache
sudo -u "${APP_USER}" -- php artisan event:cache

echo "==> Restarting services..."
systemctl restart php8.3-fpm
systemctl restart pod24-queue.service
systemctl reload nginx

echo "==> Fixing permissions..."
chown -R "${APP_USER}:www-data" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo
echo "================================================================"
echo "  Deploy complete. App should be live."
echo "================================================================"
