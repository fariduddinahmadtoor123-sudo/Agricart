<?php

namespace Tests\Feature\Deployment;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HostingerPublicMappingTest extends TestCase
{
    public function test_direct_checkout_uses_public_directory_without_copying_a_duplicate_build(): void
    {
        $this->artisan('hostinger:sync-public', [
            '--target' => base_path(),
            '--clean' => true,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Git checkout is inside public_html')
            ->expectsOutputToContain('/build is served from public/build')
            ->assertSuccessful();

        $mapper = File::get(base_path('.htaccess'));

        $this->assertStringContainsString('RewriteRule ^$ public/index.php [L]', $mapper);
        $this->assertStringContainsString('RewriteRule ^(.*)$ public/$1 [L]', $mapper);
        $this->assertStringContainsString('composer\.(json|lock)', $mapper);
    }

    public function test_split_layout_clean_removes_nested_build_and_publishes_current_manifest(): void
    {
        $target = storage_path('framework/testing/hostinger-public-'.str()->random(8));

        try {
            File::ensureDirectoryExists($target.'/build');
            File::ensureDirectoryExists($target.'/public/build');
            File::put($target.'/build/stale.css', 'stale');
            File::put($target.'/public/build/stale.css', 'stale');

            $this->artisan('hostinger:sync-public', [
                '--target' => $target,
                '--clean' => true,
            ])->assertSuccessful();

            $this->assertFileExists($target.'/build/manifest.json');
            $this->assertFileDoesNotExist($target.'/build/stale.css');
            $this->assertDirectoryDoesNotExist($target.'/public/build');
            $this->assertFileExists($target.'/.htaccess');
        } finally {
            File::deleteDirectory($target);
        }
    }

    public function test_storage_mirror_dry_run_reports_stale_files_without_deleting_them(): void
    {
        $target = storage_path('framework/testing/hostinger-storage-'.str()->random(8));
        $stale = $target.'/storage/stale.txt';

        try {
            File::ensureDirectoryExists(dirname($stale));
            File::put($stale, 'stale');

            $this->artisan('hostinger:sync-storage', [
                '--target' => $target,
                '--dry-run' => true,
                '--delete-stale' => true,
            ])
                ->expectsOutputToContain('stale storage/stale.txt (would delete)')
                ->assertSuccessful();

            $this->assertFileExists($stale);
        } finally {
            File::deleteDirectory($target);
        }
    }

    public function test_storage_mirror_refuses_a_destination_link_to_the_wrong_source(): void
    {
        $target = storage_path('framework/testing/hostinger-storage-link-'.str()->random(8));
        $wrong = storage_path('framework/testing/hostinger-storage-wrong-'.str()->random(8));
        File::ensureDirectoryExists($target);
        File::ensureDirectoryExists($wrong);

        if (! @symlink($wrong, $target.'/storage')) {
            File::deleteDirectory($target);
            File::deleteDirectory($wrong);
            $this->markTestSkipped('Creating symlinks is unavailable on this platform.');
        }

        try {
            $this->artisan('hostinger:sync-storage', ['--target' => $target])
                ->expectsOutputToContain('does not resolve exactly')
                ->assertFailed();
        } finally {
            @unlink($target.'/storage');
            File::deleteDirectory($target);
            File::deleteDirectory($wrong);
        }
    }
}
