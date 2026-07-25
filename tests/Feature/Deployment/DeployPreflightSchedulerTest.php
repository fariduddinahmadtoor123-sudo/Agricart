<?php

namespace Tests\Feature\Deployment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployPreflightSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_preflight_fails_when_cron_and_web_tick_enabled_without_acknowledgement(): void
    {
        $this->applyProductionPreflightBaseline();
        config([
            'deployment.scheduler_configured' => true,
            'deployment.scheduler_web_tick' => true,
            'deployment.scheduler_dual_mode_acknowledged' => false,
        ]);

        $this->artisan('deploy:preflight', ['--environment-only' => true])
            ->assertFailed();
    }

    public function test_preflight_allows_dual_mode_when_acknowledged(): void
    {
        $this->applyProductionPreflightBaseline();
        config([
            'deployment.scheduler_configured' => true,
            'deployment.scheduler_web_tick' => true,
            'deployment.scheduler_dual_mode_acknowledged' => true,
        ]);

        $this->artisan('deploy:preflight', ['--environment-only' => true])
            ->assertSuccessful();
    }

    private function applyProductionPreflightBaseline(): void
    {
        $this->app['env'] = 'production';

        // deploy:preflight reads env('LOG_LEVEL') directly — override cached .env for this test.
        putenv('LOG_LEVEL=error');
        $_ENV['LOG_LEVEL'] = 'error';
        $_SERVER['LOG_LEVEL'] = 'error';

        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://example.com',
            'session.secure' => true,
            'session.http_only' => true,
            'logging.default' => 'daily',
            'logging.channels.daily.days' => 14,
            'logging.channels.daily.level' => 'error',
            'security.headers.csp_mode_explicit' => true,
            'security.headers.csp_mode' => 'report-only',
            'deployment.scheduler_configured' => true,
            'deployment.queue_worker_configured' => true,
            'queue.default' => 'database',
        ]);
    }

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';
        putenv('LOG_LEVEL');
        unset($_ENV['LOG_LEVEL'], $_SERVER['LOG_LEVEL']);

        parent::tearDown();
    }
}
