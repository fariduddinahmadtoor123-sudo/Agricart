<?php

namespace Tests\Feature\Media;

use App\Services\Media\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizedImageStorageTest extends TestCase
{
    public function test_catalog_upload_is_stored_as_resized_webp(): void
    {
        Storage::fake('public');

        $png = imagecreatetruecolor(2000, 1200);
        $white = imagecolorallocate($png, 255, 255, 255);
        imagefilledrectangle($png, 0, 0, 1999, 1199, $white);
        ob_start();
        imagepng($png);
        imagedestroy($png);
        $binary = ob_get_clean();

        $this->assertIsString($binary);
        $this->assertNotSame('', $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'agricart-img-');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $binary);

        $upload = new UploadedFile($tmp, 'sample.png', 'image/png', null, true);
        $path = app(OptimizedImageStorage::class)->store($upload, 'categories');

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $saved = Storage::disk('public')->get($path);
        $image = imagecreatefromstring($saved);
        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(1600, imagesx($image));
        $this->assertLessThanOrEqual(1600, imagesy($image));
        imagedestroy($image);

        @unlink($tmp);
    }

    public function test_document_uploads_keep_original_extension(): void
    {
        Storage::fake('public');

        $upload = UploadedFile::fake()->image('cnic.jpg', 800, 600);
        $path = app(OptimizedImageStorage::class)->store($upload, 'users/cnic', 'document');

        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_about_uploads_keep_original_format_instead_of_webp(): void
    {
        Storage::fake('public');

        $upload = UploadedFile::fake()->image('office.jpg', 2000, 1200);
        $path = app(OptimizedImageStorage::class)->store($upload, 'store/about');

        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_hero_slider_uploads_use_hero_preset_not_logo_cap(): void
    {
        Storage::fake('public');

        $storage = app(OptimizedImageStorage::class);
        $this->assertSame('hero', $storage->presetForDirectory('store/hero'));

        $png = imagecreatetruecolor(1920, 640);
        $white = imagecolorallocate($png, 255, 255, 255);
        imagefilledrectangle($png, 0, 0, 1919, 639, $white);
        ob_start();
        imagepng($png);
        imagedestroy($png);
        $binary = ob_get_clean();

        $this->assertIsString($binary);
        $tmp = tempnam(sys_get_temp_dir(), 'agricart-hero-');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $binary);

        $upload = new UploadedFile($tmp, 'banner.png', 'image/png', null, true);
        $path = $storage->store($upload, 'store/hero');

        $this->assertStringEndsWith('.webp', $path);
        $saved = Storage::disk('public')->get($path);
        $image = imagecreatefromstring($saved);
        $this->assertNotFalse($image);
        $this->assertSame(1920, imagesx($image));
        $this->assertSame(640, imagesy($image));
        imagedestroy($image);

        @unlink($tmp);
    }
}
