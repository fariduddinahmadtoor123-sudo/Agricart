<?php

use App\Services\Backup\BackupScheduleRegistrar;
use App\Services\SystemErrors\SystemErrorCaptureService;
use App\Http\Middleware\RunSchedulerWebTick;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAgricartLocale;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$trustedProxySetting = trim((string) env('TRUSTED_PROXIES', ''));
$trustedProxies = $trustedProxySetting === ''
    ? null
    : ($trustedProxySetting === '*'
        ? '*'
        : array_values(array_filter(array_map('trim', explode(',', $trustedProxySetting)))));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $protect = static function (Event $event): Event {
            $event->withoutOverlapping();

            if (config('deployment.scheduler_shared_locks', false)) {
                $event->onOneServer();
            }

            return $event;
        };

        // Skip DB-bound tasks when MySQL is unreachable so a database outage
        // does not flood the log with connection errors every minute.
        $databaseReachable = static function (): bool {
            try {
                DB::connection()->getPdo();

                return true;
            } catch (Throwable) {
                return false;
            }
        };

        $protect($schedule->command('staff:attendance-default-day')
            ->dailyAt('00:05')
            ->timezone(config('app.timezone')));

        app(BackupScheduleRegistrar::class)->register($schedule, $protect);

        $protect($schedule->command('media:purge-temp --hours=24')
            ->dailyAt('03:15')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('media:prune-trash')
            ->dailyAt('03:20')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('media:backfill-library')
            ->weeklyOn(0, '03:25')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('system-errors:prune')
            ->dailyAt('03:30')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('ai-logs:prune')
            ->dailyAt('03:35')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('activity-logs:prune')
            ->dailyAt('03:40')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('system-errors:reconcile')
            ->everyFifteenMinutes()
            ->timezone(config('app.timezone')));

        $protect($schedule->command('analytics:rollup')
            ->dailyAt('02:15')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('analytics:rollup --monthly')
            ->monthlyOn(1, '02:45')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('analytics:prune')
            ->dailyAt('03:45')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('store-inquiries:prune')
            ->dailyAt('03:50')
            ->timezone(config('app.timezone')));

        $protect($schedule->command('analytics:geoip-update')
            ->monthlyOn(2, '04:15')
            ->timezone(config('app.timezone')));

        // Shared hosting often has no long-running worker or shell crontab.
        // Drain the database queue every minute via schedule:run / web tick.
        $protect($schedule->command('queue:work --stop-when-empty --tries=3 --max-time=55')
            ->everyMinute()
            ->when($databaseReachable)
            ->timezone(config('app.timezone')));
    })
    ->withMiddleware(function (Middleware $middleware) use ($trustedProxies): void {
        $middleware->trustProxies(at: $trustedProxies);
        // Auto scope (admin vs store) via path/referer so Livewire updates keep the right locale.
        $middleware->web(append: [
            SetAgricartLocale::class,
        ]);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(RunSchedulerWebTick::class);
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin/*')
                ? url('/admin/login')
                : route('store.customer.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->report(function (Throwable $exception): void {
            app(SystemErrorCaptureService::class)->capture($exception);
        });
    })->create();
