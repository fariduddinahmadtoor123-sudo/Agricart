# Agricart — Production Deployment

This guide covers deploying Agricart (Laravel 13, Filament admin, online store at site root) on **shared hosting (Hostinger)**, **VPS**, **dedicated servers**, and future **Docker** setups. The same codebase is intended to work everywhere without Hostinger-only hacks.

---

## Architecture overview

| Layer | Location | Notes |
|--------|-----------|--------|
| Web document root | `public/` | Only this directory should be web-accessible. |
| Application code | `/` (project root) | Must not be served directly by Apache/Nginx. |
| Private files | `storage/app/private` | Local disk; not exposed via `/storage`. |
| Public uploads | `storage/app/public` | Products, categories, logos, documents, etc. |
| Public URL | `{APP_URL}/storage/{path}` | Symlink **or** Laravel route (see below). |
| Compiled assets | `public/build/` | **Committed to Git** for Hostinger / no-npm deploys; run `npm run build` locally when CSS/JS changes and commit. |
| Admin panel | `{APP_URL}/admin` | Filament |
| Storefront | `{APP_URL}/` | Online store routes |

### Deployment targets

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ Local (Laragon) │     │ Hostinger Shared │     │ VPS / Dedicated │
│ symlink + dev   │     │ auto app serve   │     │ symlink + Redis   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
         │                         │                        │
         └─────────────────────────┴────────────────────────┘
                    Same repo, .env differs
```

---

## Document root (critical)

**VPS / Laragon:** point the vhost document root to **`/path/to/agricart/public`**.

**Hostinger (fixed `public_html`):** use Laravel’s split layout — repo **outside** `public_html`, sync `public/` with `php artisan hostinger:sync-public`. Full guide: **[HOSTINGER_SHARED_HOSTING.md](./HOSTINGER_SHARED_HOSTING.md)**.

### Hostinger (hPanel) — summary

1. Clone Git to e.g. `/home/username/agricart/` (never into `public_html` alone).
2. Set `HOSTINGER_PUBLIC_HTML` in `.env` to your `public_html` path.
3. After each deploy: `php artisan hostinger:sync-public` and `php artisan hostinger:verify`.
4. `public/index.php` resolves the app root automatically (same file works on VPS when docroot is `public/`).

If custom document root **is** available, you may still point it at `agricart/public` and skip sync — optional.

Never expose `storage/`, `.env`, or `vendor/` as browsable URLs.

---

## Environment (`.env`)

Copy `.env.example` on the server. Minimum production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:...   # php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true   # or omit; auto when APP_URL is https

TRUSTED_PROXIES=10.0.0.0/8   # prefer provider-specific CIDRs
ALLOW_WILDCARD_TRUSTED_PROXIES=false
SCHEDULER_CONFIGURED=true
QUEUE_WORKER_CONFIGURED=true
SECURITY_CSP_MODE=enforce # required (explicit); defaults to enforce in production

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

FILESYSTEM_DISK=local
FILESYSTEM_PUBLIC_SERVE=auto
FILESYSTEM_MEDIA_DISK=public

CACHE_STORE=database         # or redis on VPS
QUEUE_CONNECTION=database    # or redis; run a worker on VPS
```

### Storage-related variables

| Variable | Values | Purpose |
|----------|--------|---------|
| `FILESYSTEM_PUBLIC_SERVE` | `auto` (default), `true`, `false` | How `/storage/*` is served when PHP `symlink()`/`link()` is unavailable. |
| `FILESYSTEM_MEDIA_DISK` | `public` (default), `s3` | Disk name for uploads (`config/filesystems.php`). |

**`auto` (recommended):** If `public/storage` is **not** a symlink, Laravel registers a route to serve files from `storage/app/public` with cache headers. If `php artisan storage:link` succeeded (typical VPS), the web server serves files directly and the route is **not** registered.

**`false`:** VPS-only mode; requires a working `public/storage` symlink.

**`true`:** Always register the application route (fallback when symlink is missing).

Hostinger often disables PHP `exec`, `shell_exec`, `symlink`, and `link`. **`storage:link` may fail**; use `FILESYSTEM_PUBLIC_SERVE=auto` and skip relying on symlinks.

---

## Storage & media architecture

### Disks (`config/filesystems.php`)

- **`local`** — `storage/app/private` (backups, non-public files).
- **`public`** — `storage/app/public` (all user-facing uploads).
- **`s3`** — optional object storage (requires `composer require league/flysystem-aws-s3-v3` and `AWS_*` env vars).

Upload paths in use (all on the **public** disk today):

| Area | Path prefix |
|------|-------------|
| Products | `products/`, `products/gallery/` |
| Categories | `categories/` |
| Brands | `brands/` |
| Store / company logos | `store/` |
| Customers | `customers/photos/` |
| Suppliers | (via `SupplierService` directories) |
| Staff / users | `users/profile-photos/`, `users/cnic/` |
| Purchases | `purchases/vendor-bills/` |
| Online orders | `online-orders/shipping-proofs/{id}/` |

URLs are generated with `Storage::disk('public')->url($path)` → `{APP_URL}/storage/{path}`.

### Serving files (`App\Support\PublicStorage` + Laravel disk `serve`)

Laravel 13 registers `GET /storage/{path}` on the **public** disk when `disks.public.serve` is true (see `config/filesystems.php` and `FILESYSTEM_PUBLIC_SERVE`).

- **VPS:** Run `php artisan storage:link`. With `FILESYSTEM_PUBLIC_SERVE=auto`, `serve` stays false when a symlink exists — Apache/Nginx serves files directly.
- **Shared hosting:** Set `FILESYSTEM_PUBLIC_SERVE=auto`. When `symlink()`/`link()` is unavailable, `serve` is true and Laravel serves `storage/app/public` safely (public visibility only).

The **local** (private) disk has `serve => false` so backups and private files are never exposed under `/storage`.

Helper: `App\Support\MediaDisk` reads `FILESYSTEM_MEDIA_DISK` for future S3 migration.

### Scalability (100k+ images, large documents)

1. **Short term:** Local `storage/app/public` + symlink or app route; enable **Redis** cache on VPS; `php artisan config:cache`.
2. **Medium term:** Move `FILESYSTEM_MEDIA_DISK=s3` (or compatible object storage), set `AWS_URL` / CDN in front of bucket; run one-time migration of files from `storage/app/public` to bucket; update env only (gradual code adoption via `MediaDisk` helper).
3. **Documents:** Keep vendor bills and CNIC on private disk if policy requires; today most uploads are public disk — review compliance before go-live.
4. **Workers:** Use `QUEUE_CONNECTION=redis` + `php artisan queue:work` for heavy jobs on VPS; database queue is acceptable on shared hosting at moderate volume.

---

## Security

- **Exposure:** Only `public/` and intentional `/storage/*` URLs are world-readable. Do not store secrets under `storage/app/public`.
- **Traversal:** Laravel `ServeFile` blocks path traversal on served disks.
- **Validation:** Upload forms use Livewire file validation (images, size limits) in respective Form components and services — keep rules strict when adding new upload fields.
- **Permissions:** Directories `storage`, `bootstrap/cache` writable by PHP user (typically `755` dirs, `644` files; Hostinger docs vary).
- **Production:** `APP_DEBUG=false`, strong `APP_KEY`, HTTPS, secure session cookies.

---

## Performance

- Run `php artisan config:cache`, `route:cache`, `view:cache` after deploy (**set `FILESYSTEM_PUBLIC_SERVE` before `route:cache`** so the storage route is included or excluded correctly).
- Prefer **symlink + direct web server** on VPS for static media.
- On shared hosting, app-served media adds PHP overhead — acceptable for test/staging; move to CDN/S3 for high traffic.
- Use **Redis** for cache/session on VPS when available.

---

## Hardened deployment sequence

Use `deploy/hostinger/post-deploy.sh` as the reference sequence. It takes a
portable directory lock, runs `deploy:preflight --environment-only` before
migrations or public sync, creates a private database safety backup, enters
maintenance mode, migrates, publishes, caches, restarts queue workers, exits
maintenance, runs full preflight, and checks `HEALTHCHECK_URL`. Its trap restores
service and releases the lock after failures. It never performs an automatic
database restore.

`deploy:preflight` blocks production when environment/debug/HTTPS/cookie/logging/
proxy/scheduler/queue/storage requirements are unsafe. `TRUSTED_PROXIES=*` is
accepted only with `ALLOW_WILDCARD_TRUSTED_PROXIES=true`; explicit provider CIDRs
are preferred.

Storefront language selection is cookie/session based. Locale switch endpoints
redirect back to the current page and are not stable, crawlable locale URLs, so
the storefront intentionally omits `hreflang` alternates until locale-prefixed
canonical routes are implemented.

`SECURITY_CSP_MODE` is mandatory in production (an explicit value is required by
the deploy preflight) and now defaults to `enforce`. The shipped policy allows
the app's known third parties (Google Analytics/Tag Manager, Facebook) and the
Google/OpenStreetMap map-embed hosts used by the storefront footer. It still
permits `'unsafe-inline'`/`'unsafe-eval'` for the current inline scripts, so
enforcing does not break them; tighten to nonces/hashes as a later hardening
step. Temporarily set `report-only` if you introduce a new external resource and
want to observe violations before allow-listing it.

## Backup restore safety

Imported ZIPs are fully enumerated before extraction. Absolute/drive/traversal
paths, links/special files, duplicate critical files, unsupported paths, excessive
entry counts and sizes, and unsafe compression ratios are rejected. Approved
entries are streamed into a random `storage/app/private` work directory.

Production login preservation defaults to `BACKUP_PRODUCTION_LOGIN_MODE=authoritative`:
captured local logins replace same-email archive login rows and missing local
accounts are restored. Local development uses merge-missing behavior. Every
preserved-login restore writes a private JSON audit report. Keep the safety backup
enabled and require an authenticated Settings operator.

## Retention policy

Raw analytics (90 days), visitor identities (760), rollups (1095), inquiry request
metadata (90), closed inquiries (730), AI logs (90), resolved system errors (90),
and media trash (30) are configurable in `.env` and pruned by the scheduler.
Open inquiries, open system errors, orders, invoices, payments, inventory,
accounting, payroll, and other legal/business records are not pruned by these jobs.

## Deploy steps (checklist)

### 1. Prepare release (local or CI)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # optional on server if public/build is committed in Git
```

Upload/deploy the project (git pull). **`public/build/` is in the repository** so shared hosting works without Node.js. After changing `resources/css` or `resources/js`, rebuild locally and commit `public/build`.

### 2. Server bootstrap

```bash
cp .env.example .env
# Edit .env (production values)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=...   # only if needed for fresh env
```

### 3. Storage & assets

```bash
# VPS (when symlink works):
php artisan storage:link

# Shared hosting: often skip link; ensure:
# FILESYSTEM_PUBLIC_SERVE=auto
```

Ensure writable:

- `storage/` (recursive)
- `bootstrap/cache/`

### 4. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache   # if used
```

### 5. Verify

```bash
php artisan deploy:preflight
# Optional test store:
php artisan deploy:preflight --prepare-testing
```

Upload a product image in admin and open the storefront URL — image must load.

### 6. Scheduler & queue (VPS)

Crontab:

```cron
* * * * * cd /path/to/agricart && php artisan schedule:run >> /dev/null 2>&1
```

Queue worker (supervisor recommended):

```bash
php artisan queue:work --sleep=3 --tries=3
```

Hostinger shared: often cron only; long-running workers may not be available — use `QUEUE_CONNECTION=database` and cron-based `queue:work --stop-when-empty` if supported.

Install exactly one Hostinger scheduler cron:

```cron
* * * * * cd /home/USERNAME/agricart && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/USERNAME/agricart && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

Set `SCHEDULER_CONFIGURED=true` and `QUEUE_WORKER_CONFIGURED=true` only after
these are active. Jobs use `withoutOverlapping`. Leave
`SCHEDULER_SHARED_LOCKS=false` for a single Hostinger scheduler; enable it only
when every scheduler node uses the same database/Redis cache and then
`onOneServer` locks are safe.

Do **not** set both `SCHEDULER_CONFIGURED=true` and `SCHEDULER_WEB_TICK=true`
unless you intentionally run cron and web fallback together; in that rare case set
`SCHEDULER_DUAL_MODE_ACKNOWLEDGE=true`. `deploy:preflight` fails closed otherwise.

Storefront checkout queues `SendOnlineOrderPlacedNotification` for order confirmation email;
run a queue worker (or Hostinger `--stop-when-empty` cron) so customers receive it. SMS uses
`LogSmsSender` when live SMS is not configured.

Daily database backups run automatically at `BACKUP_SCHEDULE_TIME` (default `01:30`)
via `backup:create --type=database --source=scheduled` when `BACKUP_SCHEDULE_ENABLED=true`.
A weekly **full** backup (database + public uploads) runs when
`BACKUP_WEEKLY_FULL_ENABLED=true` (default Sunday `02:00`; override with
`BACKUP_WEEKLY_FULL_DAY` / `BACKUP_WEEKLY_FULL_TIME`). Archives are stored
privately under `storage/app/private/agricart-backups/` and pruned by
`BACKUP_RETENTION_COUNT` (default 14). Without the one-per-minute `schedule:run`
cron, automatic backups will not run.

---

## Apache compatibility

`public/.htaccess` ships with Laravel defaults (front controller, Authorization headers). Requirements:

- `mod_rewrite` enabled
- `AllowOverride All` for `public/` (VPS)

---

## PHP & framework versions

- **PHP:** `^8.3` (8.4 compatible per project guidelines).
- **Laravel:** 13.x
- **Filament:** 4.x (admin)
- Use `php` from PATH on server; match Hostinger selected PHP version in hPanel.

---

## MySQL migrations (shared hosting)

MySQL identifier length limit is **64 characters**. Migrations with long table/column names use **explicit short** foreign key, index, and unique names.

Audit before deploy:

```bash
php artisan migrations:audit-identifiers
```

Fix any reported names before running `migrate --force` on production MySQL.

---

## Docker (future)

Standard pattern (not shipped yet):

- Image: `php-fpm` + `nginx` with root `/var/www/html/public`
- Volumes: `storage`, `.env`
- Run `storage:link` in entrypoint when symlinks work
- Or `FILESYSTEM_PUBLIC_SERVE=auto` in container if link omitted
- Separate `queue` and `scheduler` containers

No change to application code required beyond env and compose file.

---

## Migrating from shared hosting to VPS

1. Dump MySQL; rsync `storage/app/public` to VPS.
2. Deploy code; `.env` with `FILESYSTEM_PUBLIC_SERVE=false` or `auto`.
3. `php artisan storage:link` on VPS.
4. `php artisan migrate --force` if needed.
5. Enable Redis, queue worker, real cron.
6. Optional: move media to S3 + CDN; set `FILESYSTEM_MEDIA_DISK=s3`.

---

## Commands reference

| Command | Purpose |
|---------|---------|
| `php artisan agricart:wipe-local --keep-super-admins --force` | **Local/testing only.** Wipe business data; keep Super Admin users. Never run on production. |
| `php artisan backup:create --type=database` | Create a database backup zip |
| `php artisan backup:create --type=full` | Create full backup (database + public uploads) |
| `php artisan hostinger:sync-public` | Copy `public/` to `HOSTINGER_PUBLIC_HTML` (shared hosting) |
| `php artisan hostinger:verify` | Validate Laravel root from web directory |
| `php artisan store:preflight` | Store channel, APP_URL, storage mode, Vite manifest |
| `php artisan migrations:audit-identifiers` | MySQL 64-char name audit |
| `php artisan storage:link` | VPS symlink `public/storage` |

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Images 404 on Hostinger | Set `FILESYSTEM_PUBLIC_SERVE=true` (Apache often cannot follow `public/storage`); clear config/route cache; confirm files under `storage/app/public` |
| `storage:link` fails | Expected on some shared hosts; use auto serve |
| Mixed content / wrong URLs | Set `APP_URL` to `https://...`; set provider proxy CIDRs in `TRUSTED_PROXIES` (not `*` unless `ALLOW_WILDCARD_TRUSTED_PROXIES=true`) |
| 419 on admin | Session domain, `SESSION_SECURE_COOKIE`, database sessions table migrated |
| Vite error | Run `npm run build`; upload `public/build` |
| Migration fails on FK name | Run `migrations:audit-identifiers`; apply short names |

---

## Production checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
- [ ] `APP_URL` matches live URL (https)
- [ ] MySQL credentials and `migrate --force` completed
- [ ] `SESSION_DRIVER=database` (or Redis on VPS); sessions table migrated
- [ ] `QUEUE_CONNECTION` is **not** `sync` in production; queue worker or cron `queue:work --stop-when-empty` running; `QUEUE_WORKER_CONFIGURED=true` when verified
- [ ] `storage` and `bootstrap/cache` writable
- [ ] Storage mode OK (`deploy:preflight`)
- [ ] `public/build/manifest.json` present
- [ ] HTTPS and secure cookies
- [ ] Online store channel enabled (admin or `--prepare-testing`)
- [ ] Test checkout → order in admin
- [ ] Backups configured (`local` disk / Hostinger backups)
- [ ] Cron for scheduler (VPS / Hostinger); `SCHEDULER_CONFIGURED=true` when active
- [ ] `SCHEDULER_WEB_TICK=false` unless no system cron (shared hosting fallback only)
- [ ] Redis optional on VPS for cache/queue; database queue acceptable on shared hosting at moderate volume

---

*Last updated for Agricart deployment tooling: public disk `serve`, `PublicStorage`, `deploy:preflight`, `migrations:audit-identifiers`.*
