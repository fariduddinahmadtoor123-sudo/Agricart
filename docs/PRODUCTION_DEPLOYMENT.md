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
| Compiled assets | `public/build/` | Vite manifest; build on deploy (not in git). |
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

**Industry standard:** point the vhost document root to **`/path/to/agricart/public`**.

### Hostinger (hPanel)

1. Upload the full project **outside** `public_html` when possible, e.g. `/home/username/agricart/`.
2. Set the domain document root to `agricart/public` (Hostinger allows custom document roots on many plans).
3. If you must use `public_html`, either:
   - Move only `public/*` into `public_html` and adjust `index.php` paths (not recommended), or
   - Place the app above `public_html` and symlink `public_html` → `agricart/public` **only if** `symlink()` is allowed in hPanel (often it is for user symlinks, while PHP `link()` may still be disabled).

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

TRUSTED_PROXIES=*            # behind Hostinger / Cloudflare SSL termination

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

## Deploy steps (checklist)

### 1. Prepare release (local or CI)

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Upload/deploy the project (git pull, FTP, or Hostinger Git). **Include `public/build/`** if the server has no Node.js.

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
| `php artisan deploy:preflight` | Migration identifier audit + storefront/storage/assets checks |
| `php artisan store:preflight` | Store channel, APP_URL, storage mode, Vite manifest |
| `php artisan migrations:audit-identifiers` | MySQL 64-char name audit |
| `php artisan storage:link` | VPS symlink `public/storage` |

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Images 404 on Hostinger | `FILESYSTEM_PUBLIC_SERVE=auto`; clear route cache; confirm files exist under `storage/app/public` |
| `storage:link` fails | Expected on some shared hosts; use auto serve |
| Mixed content / wrong URLs | Set `APP_URL` to `https://...`; `TRUSTED_PROXIES=*` |
| 419 on admin | Session domain, `SESSION_SECURE_COOKIE`, database sessions table migrated |
| Vite error | Run `npm run build`; upload `public/build` |
| Migration fails on FK name | Run `migrations:audit-identifiers`; apply short names |

---

## Production checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
- [ ] `APP_URL` matches live URL (https)
- [ ] MySQL credentials and `migrate --force` completed
- [ ] `storage` and `bootstrap/cache` writable
- [ ] Storage mode OK (`deploy:preflight`)
- [ ] `public/build/manifest.json` present
- [ ] HTTPS and secure cookies
- [ ] Online store channel enabled (admin or `--prepare-testing`)
- [ ] Test checkout → order in admin
- [ ] Backups configured (`local` disk / Hostinger backups)
- [ ] Cron for scheduler (VPS)

---

*Last updated for Agricart deployment tooling: public disk `serve`, `PublicStorage`, `deploy:preflight`, `migrations:audit-identifiers`.*
