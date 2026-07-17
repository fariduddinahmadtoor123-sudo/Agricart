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
}
