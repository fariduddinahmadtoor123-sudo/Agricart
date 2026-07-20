<?php

namespace Tests\Feature\Security;

use App\Services\Backup\BackupArchive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Tests\TestCase;
use ZipArchive;

class PhaseSevenHardeningTest extends TestCase
{
    public function test_backup_archive_rejects_traversal_before_extraction(): void
    {
        $path = $this->archive([
            'manifest.json' => json_encode(['type' => 'database']),
            'database.sql' => '-- safe',
            '../outside.php' => '<?php',
        ]);

        try {
            $this->expectException(ValidationException::class);
            app(BackupArchive::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_backup_archive_rejects_symlink_entries(): void
    {
        $path = $this->archive([
            'manifest.json' => json_encode(['type' => 'database']),
            'database.sql' => '-- safe',
            'files/link' => 'target',
        ], symlink: 'files/link');

        try {
            $this->expectException(ValidationException::class);
            app(BackupArchive::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_backup_archive_rejects_multiple_critical_database_files(): void
    {
        $path = $this->archive([
            'manifest.json' => json_encode(['type' => 'database']),
            'database.sql' => '-- mysql',
            'database.sqlite' => 'sqlite bytes',
        ]);

        try {
            $this->expectException(ValidationException::class);
            app(BackupArchive::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_backup_archive_enforces_uncompressed_and_ratio_limits(): void
    {
        config([
            'backup.archive.max_entry_bytes' => 64,
            'backup.archive.max_compression_ratio' => 2,
        ]);
        $path = $this->archive([
            'manifest.json' => json_encode(['type' => 'database']),
            'database.sql' => str_repeat('A', 1024),
        ]);

        try {
            $this->expectException(ValidationException::class);
            app(BackupArchive::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_backup_archive_bounds_manifest_before_reading_it(): void
    {
        config(['backup.archive.max_manifest_bytes' => 16]);
        $path = $this->archive([
            'manifest.json' => json_encode(['type' => 'database', 'padding' => str_repeat('x', 32)]),
            'database.sql' => '-- safe',
        ]);

        try {
            $this->expectException(ValidationException::class);
            app(BackupArchive::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_security_headers_are_added_and_csp_starts_report_only(): void
    {
        $this->get('/up')
            ->assertSuccessful()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_csp_enforcement_requires_explicit_mode_and_switches_header(): void
    {
        config(['security.headers.csp_mode' => 'enforce']);

        $this->get('/up')
            ->assertSuccessful()
            ->assertHeader('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_hsts_is_only_added_for_production_https_requests(): void
    {
        $this->get('/up')->assertHeaderMissing('Strict-Transport-Security');

        $this->app->detectEnvironment(fn (): string => 'production');
        $response = app(SecurityHeaders::class)->handle(
            Request::create('https://example.test/up'),
            fn () => response('ok'),
        );

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_production_preflight_fails_for_non_production_environment(): void
    {
        $this->artisan('deploy:preflight', ['--environment-only' => true])
            ->expectsOutputToContain('APP_ENV must be production')
            ->assertFailed();
    }

    public function test_scheduled_retention_commands_use_overlap_locks(): void
    {
        $schedule = File::get(base_path('bootstrap/app.php'));

        foreach (['analytics:prune', 'store-inquiries:prune', 'ai-logs:prune', 'activity-logs:prune', 'system-errors:prune', 'media:prune-trash'] as $needle) {
            $this->assertStringContainsString("\$protect(\$schedule->command('{$needle}')", $schedule);
        }

        $this->assertStringContainsString('->withoutOverlapping()', $schedule);
        $this->assertStringContainsString("config('deployment.scheduler_shared_locks', false)", $schedule);
        $this->assertStringContainsString('->onOneServer()', $schedule);
    }

    public function test_deployment_script_orders_preflight_before_mutations(): void
    {
        $script = File::get(base_path('deploy/hostinger/post-deploy.sh'));
        $preflight = strpos($script, 'deploy:preflight --environment-only');

        $this->assertIsInt($preflight);
        $this->assertLessThan(strpos($script, 'php artisan migrate --force'), $preflight);
        $this->assertLessThan(strpos($script, 'hostinger:sync-public --clean'), $preflight);
        $this->assertStringContainsString('backup:create --type=database', $script);
        $this->assertStringContainsString('queue:restart', $script);
        $this->assertStringContainsString('HEALTHCHECK_URL', $script);
    }

    /**
     * @param  array<string, string|false>  $entries
     */
    private function archive(array $entries, ?string $symlink = null): string
    {
        $path = storage_path('framework/testing/phase-seven-'.bin2hex(random_bytes(6)).'.zip');
        File::ensureDirectoryExists(dirname($path));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, (string) $contents);
        }

        if ($symlink !== null) {
            $index = $zip->locateName($symlink);
            $zip->setExternalAttributesIndex($index, ZipArchive::OPSYS_UNIX, 0120777 << 16);
        }

        $zip->close();

        return $path;
    }
}
