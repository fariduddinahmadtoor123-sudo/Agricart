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
}
