#!/bin/bash
# PetPulse — update script for the classic (non-Docker) deployment.
#
#   cd /var/www/petpulse && ./deploy/deploy.sh
#
# Pulls the latest code, installs dependencies, builds assets, migrates,
# rebuilds caches and restarts the long-running processes. Safe to re-run.
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan down --retry=10 || true

git pull --ff-only
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --no-audit --no-fund
npm run build

php artisan migrate --force --no-interaction
php artisan optimize          # config, routes, views and events cache
php artisan storage:link >/dev/null 2>&1 || true

sudo systemctl restart petpulse-reverb petpulse-queue
sudo systemctl reload php8.4-fpm

php artisan up
echo "PetPulse deployed: $(git rev-parse --short HEAD)"
