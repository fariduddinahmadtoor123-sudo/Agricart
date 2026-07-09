#!/usr/bin/env bash
# Hostinger post-deploy — run from the Laravel application root (NOT public_html).
set -euo pipefail

BRANCH="${DEPLOY_BRANCH:-developer}"

git pull origin "$BRANCH"
composer install --no-dev --optimize-autoloader

# Skip when public/build is committed (default). Uncomment if you stopped tracking build output:
# npm ci && npm run build

php artisan migrate --force
php artisan hostinger:sync-public
php artisan hostinger:sync-storage
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan deploy:preflight
