<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\Catalog\ProductImageWatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageWatermarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_upload_stores_private_original_and_public_watermarked_copy(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $this->seedWatermarkLogo();

        $upload = $this->makePngUpload('product.png', 240, 180);
        $pair = app(ProductImageWatermarkService::class)->processUpload($upload, 'main');

        Storage::disk('local')->assertExists($pair['original']);
        Storage::disk('public')->assertExists($pair['public']);
        $this->assertStringStartsWith('products/originals/', $pair['original']);
        $this->assertStringStartsWith('products/', $pair['public']);
        $this->assertFalse(str_contains($pair['original'], 'gallery'));
    }

    public function test_storefront_product_page_exposes_public_path_not_private_original(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedWatermarkLogo();

        $category = Category::query()->create([
            'code' => 'CAT-WM',
            'slug' => 'wm-cat',
            'status' => 'active',
            'name_en' => 'Watermark Category',
            'name_ur' => 'واٹرمارک زمرہ',
        ]);
        $brand = Brand::query()->create([
            'code' => 'BR-WM',
            'slug' => 'wm-brand',
            'status' => 'active',
            'name_en' => 'Watermark Brand',
            'name_ur' => 'واٹرمارک برانڈ',
            'image_path' => 'brands/wm.webp',
        ]);

        $pair = app(ProductImageWatermarkService::class)->processUpload(
            $this->makePngUpload('seed.png', 200, 200),
            'main',
        );

        $product = Product::query()->create([
            'code' => 'PRD-WM-1',
            'slug' => 'wm-product',
            'status' => 'active',
            'name_en' => 'Watermark Product',
            'name_ur' => 'واٹرمارک پروڈکٹ',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'image_path' => $pair['public'],
            'image_original_path' => $pair['original'],
            'images_watermark_version' => 1,
            'unit_qty' => 1,
        ]);

        $publicUrl = Storage::disk('public')->url($pair['public']);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee($publicUrl, false)
            ->assertDontSee($pair['original'], false);
    }

    public function test_apply_command_migrates_legacy_public_only_product_image(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedWatermarkLogo();

        $category = Category::query()->create([
            'code' => 'CAT-WM2',
            'slug' => 'wm-cat-2',
            'status' => 'active',
            'name_en' => 'Legacy Category',
            'name_ur' => 'پرانا زمرہ',
        ]);
        $brand = Brand::query()->create([
            'code' => 'BR-WM2',
            'slug' => 'wm-brand-2',
            'status' => 'active',
            'name_en' => 'Legacy Brand',
            'name_ur' => 'پرانا برانڈ',
            'image_path' => 'brands/legacy.webp',
        ]);

        $legacyPath = 'products/legacy-main.png';
        Storage::disk('public')->put($legacyPath, $this->pngBinary(160, 120));

        $product = Product::query()->create([
            'code' => 'PRD-LEGACY',
            'slug' => 'legacy-product',
            'status' => 'active',
            'name_en' => 'Legacy Product',
            'name_ur' => 'پرانا پروڈکٹ',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'image_path' => $legacyPath,
            'unit_qty' => 1,
        ]);

        $this->artisan('products:apply-watermarks')->assertSuccessful();

        $product->refresh();
        $this->assertNotNull($product->image_original_path);
        Storage::disk('local')->assertExists($product->image_original_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertNotSame($legacyPath, $product->image_path);
        $this->assertSame(1, (int) $product->images_watermark_version);
    }

    public function test_resolve_for_platform_respects_marketplace_preferences(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seedWatermarkLogo();

        StoreSetting::current()->update([
            'marketplace_image_preferences' => [
                'daraz' => 'original',
                'facebook' => 'watermarked',
            ],
        ]);

        $pair = app(ProductImageWatermarkService::class)->processUpload(
            $this->makePngUpload('platform.png', 180, 180),
            'main',
        );

        $product = new Product([
            'image_path' => $pair['public'],
            'image_original_path' => $pair['original'],
        ]);

        $service = app(ProductImageWatermarkService::class);
        $daraz = $service->resolveForPlatform($product, 'daraz');
        $facebook = $service->resolveForPlatform($product, 'facebook');

        $this->assertSame('original', $daraz['source']);
        $this->assertSame('local', $daraz['disk']);
        $this->assertSame($pair['original'], $daraz['path']);
        $this->assertNull($daraz['url']);

        $this->assertSame('watermarked', $facebook['source']);
        $this->assertSame('public', $facebook['disk']);
        $this->assertSame($pair['public'], $facebook['path']);
        $this->assertNotNull($facebook['url']);
    }

    private function seedWatermarkLogo(): void
    {
        $path = 'store/watermarks/logo.png';
        Storage::disk('public')->put($path, $this->pngBinary(64, 64, [131, 183, 53]));

        StoreSetting::current()->update([
            'product_watermark_enabled' => true,
            'product_watermark_logo_path' => $path,
            'product_watermark_position' => 'bottom-right',
            'product_watermark_size_percent' => 20,
            'product_watermark_opacity' => 70,
            'product_watermark_version' => 1,
        ]);
    }

    private function makePngUpload(string $name, int $width, int $height): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'wm');
        file_put_contents($tmp, $this->pngBinary($width, $height));

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}|null  $rgb
     */
    private function pngBinary(int $width, int $height, ?array $rgb = null): string
    {
        $rgb ??= [30, 64, 120];
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
