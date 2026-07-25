<?php

namespace Tests\Feature\Media;

use App\Services\Media\PublicMediaMirror;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaMirrorTest extends TestCase
{
    public function test_publish_and_forget_sync_single_file_to_hostinger_mirror(): void
    {
        $publicHtml = storage_path('framework/testing/hostinger-mirror-'.str()->random(8));
        $mirrorStorage = $publicHtml.DIRECTORY_SEPARATOR.'storage';

        config([
            'hostinger.public_html' => $publicHtml,
            'hostinger.mirror_public_media_on_write' => true,
        ]);

        Storage::fake('public');

        try {
            File::ensureDirectoryExists($mirrorStorage);

            // Use real public disk path for File::copy — fake disk still has a local root.
            $disk = Storage::disk('public');
            $relative = 'categories/test-thumb.webp';
            $disk->put($relative, 'fake-webp-bytes');

            $mirror = app(PublicMediaMirror::class);

            $this->assertNotNull($mirror->mirrorRoot());
            $this->assertTrue($mirror->publish($relative));
            $this->assertFileExists($mirrorStorage.DIRECTORY_SEPARATOR.'categories'.DIRECTORY_SEPARATOR.'test-thumb.webp');

            $this->assertTrue($mirror->forget($relative));
            $this->assertFileDoesNotExist($mirrorStorage.DIRECTORY_SEPARATOR.'categories'.DIRECTORY_SEPARATOR.'test-thumb.webp');
        } finally {
            File::deleteDirectory($publicHtml);
        }
    }

    public function test_inactive_when_hostinger_public_html_unset(): void
    {
        config([
            'hostinger.public_html' => null,
            'hostinger.mirror_public_media_on_write' => true,
        ]);

        $mirror = app(PublicMediaMirror::class);

        $this->assertNull($mirror->mirrorRoot());
        $this->assertFalse($mirror->publish('categories/x.webp'));
    }
}
