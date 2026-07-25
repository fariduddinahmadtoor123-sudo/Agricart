<?php

namespace App\Console\Commands;

use App\Services\Access\BootstrapSuperAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DeployPreflightCommand extends Command
{
    protected $signature = 'deploy:preflight
                            {--prepare-testing : Enable online store and clear maintenance (store:preflight)}
                            {--link-storage : Attempt storage:link when missing}
                            {--environment-only : Check blocking environment/runtime requirements before deployment mutations}';

    protected $description = 'Production deployment checks: migrations, storage, storefront, assets';

    public function handle(): int
    {
        $this->components->info('Agricart production preflight');
        $failures = $this->validateProductionEnvironment();

        if ($this->option('environment-only')) {
            return $failures === 0 ? self::SUCCESS : self::FAILURE;
        }

        $migrationAudit = Artisan::call('migrations:audit-identifiers');
        $this->output->write(Artisan::output());

        if ($migrationAudit !== self::SUCCESS) {
            $this->components->error('Migration identifier audit failed.');
            $failures++;
        }

        $args = [];
        if ($this->option('prepare-testing')) {
            $args['--prepare-testing'] = true;
        }
        if ($this->option('link-storage')) {
            $args['--link-storage'] = true;
        }

        $storeExit = Artisan::call('store:preflight', $args);
        $this->output->write(Artisan::output());

        if (filled(config('hostinger.public_html'))) {
            $hostingerExit = Artisan::call('hostinger:verify');
            $this->output->write(Artisan::output());

            if ($hostingerExit !== self::SUCCESS) {
                $failures++;
            }
        }

        $this->components->info('After deploy on hosting, run: php artisan optimize && php artisan media:purge-temp && php artisan media:optimize-images && php artisan media:prune-orphans');

        $bootstrap = app(BootstrapSuperAdminService::class);

        if (! $bootstrap->hasSuperAdminUser()) {
            $this->components->warn('No Super Admin user — run: php artisan agricart:repair-bootstrap-owner your@email.com');
        }

        if ($storeExit !== self::SUCCESS) {
            $failures++;
        }

        if ($failures > 0) {
            $this->components->error("Production preflight failed with {$failures} blocking issue(s).");

            return self::FAILURE;
        }

        $this->components->info('Production preflight passed.');

        return self::SUCCESS;
    }

    private function validateProductionEnvironment(): int
    {
        $failures = 0;
        $fail = function (string $message) use (&$failures): void {
            $this->components->error($message);
            $failures++;
        };

        if (! app()->environment('production')) {
            $fail('APP_ENV must be production.');
        }
        if ((bool) config('app.debug')) {
            $fail('APP_DEBUG must be false.');
        }
        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) !== 'https') {
            $fail('APP_URL must use HTTPS.');
        }
        if (! (bool) config('session.secure')) {
            $fail('SESSION_SECURE_COOKIE must be true.');
        }
        if (! (bool) config('session.http_only')) {
            $fail('SESSION_HTTP_ONLY must be true.');
        }

        $level = strtolower((string) config('logging.channels.'.config('logging.default').'.level', env('LOG_LEVEL', 'debug')));
        if (in_array(strtolower((string) env('LOG_LEVEL', $level)), ['debug'], true)) {
            $fail('LOG_LEVEL must not be debug in production.');
        }
        if (config('logging.default') !== 'daily'
            && ! in_array('daily', (array) config('logging.channels.stack.channels', []), true)) {
            $fail('Production logging must include the bounded daily channel.');
        }
        if ((int) config('logging.channels.daily.days', 0) < 1) {
            $fail('LOG_DAILY_DAYS must be at least 1.');
        }
        if (! config('security.headers.csp_mode_explicit')
            || ! in_array(config('security.headers.csp_mode'), ['report-only', 'enforce'], true)) {
            $fail('SECURITY_CSP_MODE must be explicitly set to report-only or enforce.');
        }

        $trusted = trim((string) config('deployment.trusted_proxies'));
        if ($trusted === '*' && ! config('deployment.allow_wildcard_trusted_proxies', false)) {
            $fail('TRUSTED_PROXIES=* requires ALLOW_WILDCARD_TRUSTED_PROXIES=true or a restricted proxy list.');
        }
        if (! config('deployment.scheduler_configured', false)) {
            $fail('SCHEDULER_CONFIGURED=true is required after installing the one-per-minute cron.');
        }
        if (
            config('deployment.scheduler_configured', false)
            && config('deployment.scheduler_web_tick', false)
            && ! config('deployment.scheduler_dual_mode_acknowledged', false)
        ) {
            $fail(
                'SCHEDULER_CONFIGURED and SCHEDULER_WEB_TICK cannot both be true. Use cron only, or set SCHEDULER_WEB_TICK=true only without cron, or set SCHEDULER_DUAL_MODE_ACKNOWLEDGE=true if both are intentional.',
            );
        }
        if (config('deployment.scheduler_shared_locks', false)) {
            $cacheStore = (string) config('cache.default');
            $cacheDriver = (string) config("cache.stores.{$cacheStore}.driver");

            if (! in_array($cacheDriver, ['database', 'redis', 'memcached', 'dynamodb'], true)) {
                $fail('SCHEDULER_SHARED_LOCKS requires a cache shared by every scheduler node.');
            }
        }

        $queue = (string) config('queue.default');
        if (in_array($queue, ['sync', 'null'], true)) {
            $fail('QUEUE_CONNECTION must use a durable production backend.');
        } elseif (! config('deployment.queue_worker_configured', false)) {
            $fail('QUEUE_WORKER_CONFIGURED=true is required after configuring a worker or Hostinger cron worker.');
        }
        if ($queue === 'database' && (! Schema::hasTable('jobs') || ! Schema::hasTable('failed_jobs'))) {
            $fail('Database queue requires jobs and failed_jobs tables.');
        }

        foreach ([storage_path(), storage_path('framework'), base_path('bootstrap/cache')] as $path) {
            if (! File::isDirectory($path) || ! is_writable($path)) {
                $fail("Required writable directory is missing or not writable: {$path}");
            }
        }

        return $failures;
    }
}
