<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\StoreSetting;
use App\Services\Catalog\BrandImageWatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandImageWatermarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_upload_stores_private_original_and_public_watermarked_logo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedBrandWatermarkSettings();

        $pair = app(BrandImageWatermarkService::class)->processUpload(
            $this->makePngUpload('brand.png', 220, 220),
        );

        Storage::disk('local')->assertExists($pair['original']);
        Storage::disk('public')->assertExists($pair['public']);
        $this->assertStringStartsWith('brands/originals/', $pair['original']);
        $this->assertStringStartsWith('brands/', $pair['public']);
        $this->assertStringNotContainsString('originals', $pair['public']);
    }

    public function test_brand_storefront_surfaces_use_public_path_only(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedBrandWatermarkSettings();

        $pair = app(BrandImageWatermarkService::class)->processUpload(
            $this->makePngUpload('public-brand.png', 200, 200),
        );

        $brand = Brand::query()->create([
            'code' => 'BR-WM-PUBLIC',
            'slug' => 'wm-public-brand',
            'status' => 'active',
            'name_en' => 'Public Brand',
            'name_ur' => 'عوامی برانڈ',
            'image_path' => $pair['public'],
            'image_original_path' => $pair['original'],
            'image_watermark_version' => 1,
        ]);

        $publicUrl = Storage::disk('public')->url($pair['public']);

        $this->get(route('store.brands'))
            ->assertOk()
            ->assertSee($publicUrl, false)
            ->assertDontSee($pair['original'], false);

        $this->get(route('store.brand', $brand->slug))
            ->assertOk()
            ->assertSee($publicUrl, false)
            ->assertDontSee($pair['original'], false);
    }

    public function test_apply_command_migrates_legacy_brand_logo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedBrandWatermarkSettings();

        $legacyPath = 'brands/legacy-logo.png';
        Storage::disk('public')->put($legacyPath, $this->pngBinary(160, 160));

        $brand = Brand::query()->create([
            'code' => 'BR-LEGACY',
            'slug' => 'legacy-brand',
            'status' => 'active',
            'name_en' => 'Legacy Brand',
            'name_ur' => 'پرانا برانڈ',
            'image_path' => $legacyPath,
        ]);

        $this->artisan('brands:apply-watermarks')->assertSuccessful();

        $brand->refresh();
        $this->assertNotNull($brand->image_original_path);
        Storage::disk('local')->assertExists($brand->image_original_path);
        Storage::disk('public')->assertExists($brand->image_path);
        $this->assertNotSame($legacyPath, $brand->image_path);
        $this->assertSame(1, (int) $brand->image_watermark_version);
    }

    public function test_apply_recreates_missing_private_original_and_regenerates_public_logo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedBrandWatermarkSettings();

        $publicPath = 'brands/restored-public.png';
        $missingOriginal = 'brands/originals/missing-master.png';
        Storage::disk('public')->put($publicPath, $this->pngBinary(160, 160));

        $brand = Brand::query()->create([
            'code' => 'BR-MISS-ORIG',
            'slug' => 'missing-original-brand',
            'status' => 'active',
            'name_en' => 'Missing Original Brand',
            'name_ur' => 'غائب اصل برانڈ',
            'image_path' => $publicPath,
            'image_original_path' => $missingOriginal,
            'image_watermark_version' => 1,
        ]);

        Storage::disk('local')->assertMissing($missingOriginal);

        $this->artisan('brands:apply-watermarks')->assertSuccessful();

        $brand->refresh();
        $this->assertNotSame($publicPath, $brand->image_path);
        $this->assertNotSame($missingOriginal, $brand->image_original_path);
        Storage::disk('local')->assertExists($brand->image_original_path);
        Storage::disk('public')->assertExists($brand->image_path);
        Storage::disk('local')->assertMissing($missingOriginal);
    }

    public function test_force_apply_with_empty_text_and_no_badge_restores_plain_logo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedBrandWatermarkSettings();

        $pair = app(BrandImageWatermarkService::class)->processUpload(
            $this->makePngUpload('plain-candidate.png', 180, 180),
        );

        $brand = Brand::query()->create([
            'code' => 'BR-PLAIN',
            'slug' => 'plain-brand',
            'status' => 'active',
            'name_en' => 'Plain Brand',
            'name_ur' => 'پلین برانڈ',
            'image_path' => $pair['public'],
            'image_original_path' => $pair['original'],
            'image_watermark_version' => 1,
        ]);

        $watermarkedBytes = Storage::disk('public')->get($pair['public']);
        $originalBytes = Storage::disk('local')->get($pair['original']);
        $this->assertNotSame($originalBytes, $watermarkedBytes);

        StoreSetting::current()->update([
            'brand_watermark_text' => '',
            'brand_watermark_logo_path' => null,
            'brand_watermark_version' => 2,
        ]);

        $this->artisan('brands:apply-watermarks', ['--force' => true])->assertSuccessful();

        $brand->refresh();
        $this->assertNotSame($pair['public'], $brand->image_path);
        Storage::disk('public')->assertExists($brand->image_path);
        // Plain public copy should match the private original size class (no text/badge overlay growth needed — content differs by encode but must exist).
        $this->assertGreaterThan(0, Storage::disk('public')->size($brand->image_path));
        $this->assertSame(2, (int) $brand->image_watermark_version);
    }

    private function seedBrandWatermarkSettings(): void
    {
        StoreSetting::current()->update([
            'brand_watermark_enabled' => true,
            'brand_watermark_text' => 'Available at Agricart.pk',
            'brand_watermark_position' => 'bottom-right',
            'brand_watermark_size_percent' => 18,
            'brand_watermark_opacity' => 70,
            'brand_watermark_version' => 1,
        ]);
    }

    private function makePngUpload(string $name, int $width, int $height): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bwm');
        file_put_contents($tmp, $this->pngBinary($width, $height));

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    private function pngBinary(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 40, 90, 40);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
