<?php

use App\Services\Backup\BackupScheduleRegistrar;
use App\Services\SystemErrors\SystemErrorCaptureService;
use App\Http\Middleware\RunSchedulerWebTick;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
    })
    ->withMiddleware(function (Middleware $middleware) use ($trustedProxies): void {
        $middleware->trustProxies(at: $trustedProxies);
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
