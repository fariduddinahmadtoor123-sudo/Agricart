# Hostinger shared hosting (fixed `public_html` document root)

Hostinger often **cannot** change the document root away from `public_html`. Laravel’s supported approach is the same as the [official shared hosting guidance](https://laravel.com/docs/deployment#shared-hosting): keep the **full application outside** the web root and expose only the contents of `public/`.

This project stays **one Git repo** with a standard `public/` folder for local dev and VPS. On Hostinger you **sync** `public/` into `public_html` after each deploy.

---

## Target layout on the server

Example (adjust paths to your Hostinger account):

```text
/home/USERNAME/
├── agricart/                    ← git clone / pull (NOT web-accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── vendor/
│   ├── storage/
│   ├── .env
│   └── public/                  ← source for sync only
└── domains/example.com/
    └── public_html/             ← document root (fixed)
        ├── index.php
        ├── .htaccess
        ├── resolve-laravel-root.php
        ├── agricart-laravel-root.php
        └── build/               ← committed in Git (no npm on server)
```

Common alternative: `public_html` at `/home/USERNAME/public_html` with repo at `/home/USERNAME/agricart`. The resolver walks upward and looks for a sibling folder named `agricart` (or `Agricart`).

**Never** clone the repository directly into `public_html` (that would expose `.env` and `vendor/`).

Optional: copy `deploy/hostinger/app-root.htaccess` to `agricart/.htaccess` so Apache denies HTTP access if the app folder is ever placed under a document root by mistake.

### Existing hPanel Git checkout inside `public_html`

If hPanel has already cloned the repository directly into `public_html`, the tracked
root `.htaccess` maps every public URL to `public/` and blocks private dot/config
files. In this compatibility layout:

- `/build/assets/*` is served from `public_html/public/build/assets/*`.
- `public_html/build` is stale duplication and must be removed.
- Run `php artisan hostinger:sync-public --clean`; the command detects the direct
  checkout, removes only the duplicate root build, and keeps `public/build`.
- Run `php artisan hostinger:verify` afterward.

Moving the repository outside `public_html` remains the preferred long-term layout.

---

## First-time setup

1. **SSH or Git → clone** the project outside `public_html`, e.g. `/home/USERNAME/agricart`.
2. **Install dependencies** (in `agricart/`):

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   Vite output (`public/build/`) is **tracked in Git** — you do not need Node.js on Hostinger. Rebuild locally only when frontend sources change.

3. **Environment:** copy `.env.example` → `.env`, set production values (MySQL, `APP_URL`, `FILESYSTEM_PUBLIC_SERVE=auto`, etc.).
4. **Application key & database:**

   ```bash
   php artisan key:generate
   php artisan migrate --force
   ```

5. **Configure sync target** in `.env`:

   ```env
   HOSTINGER_PUBLIC_HTML=/home/USERNAME/domains/example.com/public_html
   ```

6. **Publish web root:**

   ```bash
   php artisan hostinger:sync-public
   ```

7. **Verify path resolution:**

   ```bash
   php artisan hostinger:verify
   ```

8. If verify fails, copy `public/laravel-root.php.example` to **`public_html/laravel-root.php`** (on the server) and return the absolute path to `agricart/`:

   ```php
   <?php
   return '/home/USERNAME/agricart';
   ```

9. **Optimize:**

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan deploy:preflight
   ```

---

## Git deployment workflow (each release)

Run from the **application root** (`agricart/`), not from `public_html`. Prefer
the guarded `deploy/hostinger/post-deploy.sh` script. Configure
`HEALTHCHECK_URL=https://example.com/up`; the script locks against concurrent
deploys, preflights before mutation, creates a safety backup, manages maintenance
mode with failure cleanup, migrates, publishes, caches, restarts queues, and
verifies health.

```bash
DEPLOY_BRANCH=developer HEALTHCHECK_URL=https://example.com/up bash deploy/hostinger/post-deploy.sh
```

Example script: `deploy/hostinger/post-deploy.sh` (edit paths, then `chmod +x`).

---

## How `index.php` finds Laravel

`public/index.php` (copied into `public_html`) loads `resolve-laravel-root.php`, which:

1. Uses `laravel-root.php` override if present.
2. Uses `LARAVEL_ROOT` env var if set.
3. Uses **parent directory** when `public/` is the document root (local / VPS).
4. Walks up the filesystem and checks **sibling** folders (`agricart`, `Agricart`, …) for `vendor/autoload.php`.

No Hostinger-specific code in Laravel core; VPS deployments with document root = `public/` keep working unchanged.

---

## Storage & media on Hostinger

- Set `FILESYSTEM_PUBLIC_SERVE=auto` so `/storage/*` is served by Laravel when `php artisan storage:link` cannot run.
- Do **not** copy `public/storage` during sync (skipped by `hostinger:sync-public`); uploads live in `agricart/storage/app/public`.
- Prefer a verified `public_html/storage` symlink. If a mirror is unavoidable,
  preview with `hostinger:sync-storage --dry-run --delete-stale`, then run with
  `--delete-stale`. The mirror excludes configured private/sensitive prefixes
  and reports stale files instead of silently retaining them.

See also [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md).

---

## Commands

| Command | Purpose |
|---------|---------|
| `php artisan hostinger:sync-public --clean` | Publish `public/` and remove stale/duplicate build directories safely |
| `php artisan hostinger:sync-storage --dry-run --delete-stale` | Preview approved copies and stale deletions |
| `php artisan hostinger:sync-storage --delete-stale` | Guarded mirror fallback; excludes private prefixes |
| `php artisan hostinger:sync-public --dry-run` | Preview files |
| `php artisan hostinger:verify` | Test Laravel root resolution |
| `php artisan deploy:preflight` | Includes `hostinger:verify` when `HOSTINGER_PUBLIC_HTML` is set |

---

## Moving to VPS later

1. Point document root to `agricart/public`.
2. Remove or ignore `HOSTINGER_PUBLIC_HTML`.
3. Run `php artisan storage:link`.
4. Optional: set `FILESYSTEM_PUBLIC_SERVE=false` or leave `auto`.

No codebase fork required.

---

## Troubleshooting

| Issue | Action |
|-------|--------|
| Plain text “application root not found” | Fix `laravel-root.php` or repo folder name; run `hostinger:verify` |
| 500 after deploy | Check `storage/` permissions; `APP_KEY`; Laravel log in `agricart/storage/logs` |
| CSS/JS missing | `git pull` + `hostinger:sync-public`; or run `npm run build` locally, commit `public/build`, push |
| Images 404 | `FILESYSTEM_PUBLIC_SERVE=auto`; files in `storage/app/public` |
| Admin 419 | Session driver, HTTPS, `TRUSTED_PROXIES=*` |

---

## hPanel notes

- Use **Git** or **SSH** to pull into `agricart/`, not into `public_html`.
- Run **PHP 8.3+** and point CLI to the same version as the site.
- Cron (if available): `* * * * * cd /home/USERNAME/agricart && php artisan schedule:run >> /dev/null 2>&1`
- That cron is required for the daily `01:30` database backup, the weekly full backup, and other scheduled jobs.
- Install that scheduler line exactly once and set `SCHEDULER_CONFIGURED=true`.
- If persistent workers are unavailable, add a one-per-minute
  `queue:work --stop-when-empty --tries=3` cron and set
  `QUEUE_WORKER_CONFIGURED=true`.
- Leave `SCHEDULER_SHARED_LOCKS=false` for one scheduler. Enable it only for
  multiple nodes sharing a database/Redis lock cache.
