<?php

namespace Tests\Feature\Store;

use App\Services\Analytics\GeoLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GeoIpAndSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_geo_location_returns_extended_null_shape_without_database(): void
    {
        config(['analytics.geoip_database' => storage_path('app/private/geoip/missing.mmdb')]);

        $geo = app(GeoLocationService::class)->locate('8.8.8.8');

        $this->assertSame([
            'country_code' => null,
            'country_name' => null,
            'region_name' => null,
            'region_code' => null,
            'city' => null,
            'timezone' => null,
            'latitude' => null,
            'longitude' => null,
        ], $geo);
    }

    public function test_schedule_run_endpoint_requires_token(): void
    {
        config(['deployment.scheduler_token' => 'secret-token-value']);

        $this->get(route('internal.schedule-run'))->assertNotFound();
        $this->get(route('internal.schedule-run', ['token' => 'wrong']))->assertNotFound();
        $this->get(route('internal.schedule-run', ['token' => 'secret-token-value']))
            ->assertOk();
    }

    public function test_scheduler_web_tick_runs_at_most_once_under_lock(): void
    {
        config(['deployment.scheduler_web_tick' => true]);
        Cache::flush();

        $calls = 0;
        Artisan::shouldReceive('call')
            ->with('schedule:run')
            ->andReturnUsing(function () use (&$calls): int {
                $calls++;

                return 0;
            });

        $service = app(\App\Services\Deployment\SchedulerTickService::class);
        $this->assertTrue($service->tick());
        $this->assertFalse($service->tick());
        $this->assertSame(1, $calls);
    }
}
