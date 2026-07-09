#!/usr/bin/env bash
# Hostinger post-deploy — run from the Laravel application root (NOT public_html).
set -euo pipefail

BRANCH="${DEPLOY_BRANCH:-developer}"

git pull origin "$BRANCH"
composer install --no-dev --optimize-autoloader

if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "npm not found — ensure public/build was built before deploy."
fi

php artisan migrate --force
php artisan hostinger:sync-public
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan deploy:preflight
