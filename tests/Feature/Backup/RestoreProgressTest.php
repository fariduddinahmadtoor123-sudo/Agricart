<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\RestoreProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RestoreProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(RestoreProgress::class)->clear();

        parent::tearDown();
    }

    public function test_snapshot_requires_matching_token(): void
    {
        $progress = app(RestoreProgress::class);
        $token = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

        $progress->start('backup-1', $token);
        $progress->step('extracting');

        $this->assertSame('extracting', $progress->snapshotForToken($token)['step'] ?? null);
        $this->assertFalse($progress->snapshotForToken('ffffffff-bbbb-4ccc-8ddd-eeeeeeeeeeee')['active']);
        $this->assertFalse($progress->snapshotForToken('not-a-token')['active']);
    }

    public function test_progress_endpoint_returns_step_with_token(): void
    {
        $progress = app(RestoreProgress::class);
        $token = '11111111-2222-4333-8444-555555555555';

        $progress->start('imported_zip', $token);
        $progress->step('importing_database');

        $this->getJson(route('admin.backups.restore-progress', ['token' => $token]))
            ->assertOk()
            ->assertJson([
                'active' => true,
                'step' => 'importing_database',
                'status' => RestoreProgress::STATUS_RUNNING,
            ]);
    }

    public function test_progress_endpoint_stays_available_during_maintenance(): void
    {
        $progress = app(RestoreProgress::class);
        $token = '99999999-aaaa-4bbb-8ccc-ddddeeeeffff';

        $progress->start('imported_zip', $token);
        $progress->step('restoring_files');

        Artisan::call('down', ['--retry' => 60]);

        try {
            $this->getJson(route('admin.backups.restore-progress', ['token' => $token]))
                ->assertOk()
                ->assertJsonPath('step', 'restoring_files');

            $this->get('/admin/settings/backups')->assertStatus(503);
        } finally {
            Artisan::call('up');
        }
    }
}
