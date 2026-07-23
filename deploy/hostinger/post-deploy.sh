#!/usr/bin/env bash
# Hostinger post-deploy — run from the Laravel application root (NOT public_html).
set -euo pipefail

BRANCH="${DEPLOY_BRANCH:-developer}"
LOCK_DIR="storage/framework/agricart-deploy.lock"
MAINTENANCE=0

if ! mkdir "$LOCK_DIR" 2>/dev/null; then
    echo "Another deployment holds $LOCK_DIR; aborting."
    exit 1
fi

cleanup() {
    status=$?
    if [ "$MAINTENANCE" -eq 1 ]; then
        php artisan up || true
    fi
    rmdir "$LOCK_DIR" 2>/dev/null || true
    exit "$status"
}
trap cleanup EXIT INT TERM

git pull origin "$BRANCH"
composer install --no-dev --optimize-autoloader

# Skip when public/build is committed (default). Uncomment if you stopped tracking build output:
# npm ci && npm run build

php artisan deploy:preflight --environment-only
php artisan backup:create --type=database --source=deploy
php artisan down --retry=60
MAINTENANCE=1

php artisan migrate --force
php artisan hostinger:sync-public --clean
php artisan storage:link
php artisan hostinger:sync-storage --dry-run --delete-stale
php artisan hostinger:sync-storage --delete-stale

# Harden public uploads against PHP webshells (Apache).
if [ -f deploy/hostinger/storage-public.htaccess ]; then
    cp deploy/hostinger/storage-public.htaccess storage/app/public/.htaccess
    if [ -n "${HOSTINGER_PUBLIC_HTML:-}" ] && [ -d "${HOSTINGER_PUBLIC_HTML}/storage" ]; then
        cp deploy/hostinger/storage-public.htaccess "${HOSTINGER_PUBLIC_HTML}/storage/.htaccess"
    elif [ -d public_html/storage ]; then
        cp deploy/hostinger/storage-public.htaccess public_html/storage/.htaccess
    elif [ -d ../public_html/storage ]; then
        cp deploy/hostinger/storage-public.htaccess ../public_html/storage/.htaccess
    fi
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
MAINTENANCE=0
php artisan deploy:preflight

HEALTHCHECK_URL="${HEALTHCHECK_URL:-$(php -r '
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$configured = config("deployment.healthcheck_url");
echo is_string($configured) && $configured !== ""
    ? $configured
    : rtrim((string) config("app.url"), "/")."/up";
')}"

curl --fail --silent --show-error --max-time 20 "$HEALTHCHECK_URL" >/dev/null
echo "Deployment and health verification completed."
