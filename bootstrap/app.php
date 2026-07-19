<?php

use App\Services\SystemErrors\SystemErrorCaptureService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('staff:attendance-default-day')
            ->dailyAt('00:05')
            ->timezone(config('app.timezone'));

        $schedule->command('media:purge-temp --hours=24')
            ->dailyAt('03:15')
            ->timezone(config('app.timezone'));

        $schedule->command('system-errors:prune --days=90')
            ->dailyAt('03:30')
            ->timezone(config('app.timezone'));

        $schedule->command('analytics:rollup')
            ->dailyAt('02:15')
            ->timezone(config('app.timezone'));

        $schedule->command('analytics:rollup --monthly')
            ->monthlyOn(1, '02:45')
            ->timezone(config('app.timezone'));

        $schedule->command('analytics:prune')
            ->dailyAt('03:45')
            ->timezone(config('app.timezone'));

        $schedule->command('analytics:geoip-update')
            ->monthlyOn(2, '04:15')
            ->timezone(config('app.timezone'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
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
