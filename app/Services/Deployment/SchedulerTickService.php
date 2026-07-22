<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SchedulerTickService
{
    public function enabled(): bool
    {
        return (bool) config('deployment.scheduler_web_tick', true);
    }

    /**
     * Run due scheduled tasks at most once per minute (shared-hosting safe).
     */
    public function tick(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $lock = Cache::lock('agricart:scheduler-web-tick', 55);

        if (! $lock->get()) {
            return false;
        }

        try {
            Artisan::call('schedule:run');

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        } finally {
            // Keep the lock until TTL so concurrent requests do not pile up.
        }
    }
}
