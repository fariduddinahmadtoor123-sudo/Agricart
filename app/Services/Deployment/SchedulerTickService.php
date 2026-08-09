<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SchedulerTickService
{
    /**
     * Throttle window (seconds) for reporting web-tick failures so a database
     * outage cannot flood the log with a fresh error on every request.
     */
    private const ERROR_REPORT_THROTTLE_SECONDS = 600;

    public function enabled(): bool
    {
        return (bool) config('deployment.scheduler_web_tick', false);
    }

    /**
     * Run due scheduled tasks at most once per minute (shared-hosting safe).
     */
    public function tick(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $lock = Cache::lock('agricart:scheduler-web-tick', 55);

            if (! $lock->get()) {
                return false;
            }

            Artisan::call('schedule:run');

            return true;
        } catch (Throwable $exception) {
            $this->reportThrottled($exception);

            return false;
        }
    }

    /**
     * Report at most once per throttle window using a filesystem marker so the
     * throttle keeps working even when the (database-backed) cache is down.
     */
    private function reportThrottled(Throwable $exception): void
    {
        $marker = storage_path('framework/scheduler-web-tick-error');
        $now = time();

        if (is_file($marker) && ($now - (int) @filemtime($marker)) < self::ERROR_REPORT_THROTTLE_SECONDS) {
            return;
        }

        @touch($marker);
        report($exception);
    }
}
