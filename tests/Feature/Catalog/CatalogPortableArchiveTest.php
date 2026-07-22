<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Catalog\CatalogPortableArchiveService;
use App\Services\Catalog\FacebookCatalogCsvService;
use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogPortableArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_import_round_trip_restores_catalog_graph_and_media(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('brands/logo.webp', 'brand-bytes');
        Storage::disk('public')->put('categories/cat.webp', 'category-bytes');
        Storage::disk('public')->put('products/main.webp', 'product-bytes');
        Storage::disk('public')->put('products/gallery/g1.webp', 'gallery-bytes');

        Unit::query()->create([
            'code' => 'UNT-KG',
            'slug' => 'kilogram',
            'status' => 'active',
            'name_en' => 'Kilogram',
            'name_ur' => 'کلو',
            'abbreviation_en' => 'kg',
            'abbreviation_ur' => 'کلو',
            'ai_status' => 'none',
        ]);

        CatalogAttribute::query()->create([
            'code' => 'ATR-SIZE',
            'slug' => 'size',
            'status' => 'active',
            'name_en' => 'Size',
            'name_ur' => 'سائز',
            'ai_status' => 'none',
        ]);

        CatalogControl::query()->create([
            'code' => 'CTL-SAFE',
            'slug' => 'safety',
            'status' => 'active',
            'name_en' => 'Safety',
            'name_ur' => 'حفاظت',
            'value_en' => 'Keep dry',
            'value_ur' => 'خشک رکھیں',
            'ai_status' => 'none',
        ]);

        $brand = Brand::query()->create([
            'code' => 'BRD-ACME',
            'slug' => 'acme',
            'status' => 'active',
            'name_en' => 'Acme',
            'name_ur' => 'ایکمی',
            'image_path' => 'brands/logo.webp',
            'ai_status' => 'none',
        ]);

        $category = Category::query()->create([
            'code' => 'CAT-SEED',
            'slug' => 'seeds',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
            'image_path' => 'categories/cat.webp',
            'ai_status' => 'none',
        ]);

        $unit = Unit::query()->where('code', 'UNT-KG')->firstOrFail();
        $attribute = CatalogAttribute::query()->where('code', 'ATR-SIZE')->firstOrFail();
        $control = CatalogControl::query()->where('code', 'CTL-SAFE')->firstOrFail();

        $delivery = DeliveryMethod::query()->create([
            'name_en' => 'Pickup',
            'name_ur' => 'پک اپ',
            'slug' => 'pickup',
            'status' => DeliveryMethod::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'code' => 'PRD-PORTABLE',
            'slug' => 'portable-seed',
            'status' => 'active',
            'name_en' => 'Portable Seed',
            'name_ur' => 'پورٹیبل بیج',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'image_path' => 'products/main.webp',
            'gallery_images' => ['products/gallery/g1.webp'],
            'retail_price' => 250,
            'stock_qty' => 12,
            'label_code' => 'R1-P1',
            'ai_status' => 'none',
        ]);

        $product->attributes()->sync([$attribute->id => ['value' => '1kg']]);
        $product->controls()->sync([$control->id]);
        $product->deliveryMethods()->sync([$delivery->id]);

        $service = app(CatalogPortableArchiveService::class);
        $export = $service->exportAll();

        $this->assertFileExists($export['path']);
        $this->assertSame(1, $export['count']);
        $this->assertStringEndsWith('.ags', $export['filename']);
        $this->assertStringStartsWith(
            PortableArchiveCodec::MAGIC,
            (string) file_get_contents($export['path']),
        );

        Product::query()->where('code', 'PRD-PORTABLE')->delete();
        Brand::query()->where('code', 'BRD-ACME')->delete();
        Category::query()->where('code', 'CAT-SEED')->delete();
        Unit::query()->where('code', 'UNT-KG')->delete();
        CatalogAttribute::query()->where('code', 'ATR-SIZE')->delete();
        CatalogControl::query()->where('code', 'CTL-SAFE')->delete();

        $result = $service->importFromPath($export['path']);

        $this->assertSame(1, $result['products']);
        $this->assertGreaterThanOrEqual(1, $result['created']);
        $this->assertGreaterThanOrEqual(3, $result['media']);

        $restored = Product::query()->where('code', 'PRD-PORTABLE')->first();
        $this->assertNotNull($restored);
        $this->assertSame('Portable Seed', $restored->name_en);
        $this->assertSame('R1-P1', $restored->label_code);
        $this->assertSame(0.0, (float) $restored->stock_qty);
        $this->assertTrue(Storage::disk('public')->exists((string) $restored->image_path));
        $this->assertSame('product-bytes', Storage::disk('public')->get((string) $restored->image_path));
        $this->assertSame('Acme', $restored->brand?->name_en);
        $this->assertSame('Seeds', $restored->category?->name_en);
        $this->assertTrue($restored->attributes->contains(fn ($a) => $a->code === 'ATR-SIZE' && ($a->pivot->value ?? '') === '1kg'));
        $this->assertTrue($restored->deliveryMethods->contains(fn ($d) => $d->slug === 'pickup'));

        @unlink($export['path']);
    }

    public function test_brands_scoped_export_filename_and_rejects_wrong_page_format(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/logo.webp', 'brand-bytes');

        Brand::query()->create([
            'code' => 'BRD-SCOPE',
            'slug' => 'scope-brand',
            'status' => 'active',
            'name_en' => 'Scope Brand',
            'name_ur' => 'اسکوپ',
            'image_path' => 'brands/logo.webp',
            'ai_status' => 'none',
        ]);

        $service = app(CatalogPortableArchiveService::class);
        $export = $service->export(CatalogPortableArchiveService::SCOPE_BRANDS);

        $this->assertSame(1, $export['count']);
        $this->assertSame(CatalogPortableArchiveService::SCOPE_BRANDS, $export['scope']);
        $this->assertStringStartsWith('brands-', $export['filename']);
        $this->assertStringEndsWith('.ags', $export['filename']);

        $this->expectException(ValidationException::class);
        try {
            $service->importFromPath($export['path'], CatalogPortableArchiveService::SCOPE_CATEGORIES);
        } finally {
            @unlink($export['path']);
        }
    }

    public function test_brands_scoped_import_does_not_touch_units(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/logo.webp', 'brand-bytes');

        Brand::query()->create([
            'code' => 'BRD-ONLY',
            'slug' => 'only-brand',
            'status' => 'active',
            'name_en' => 'Only Brand',
            'name_ur' => 'صرف',
            'image_path' => 'brands/logo.webp',
            'ai_status' => 'none',
        ]);

        Unit::query()->create([
            'code' => 'UNT-STAY',
            'slug' => 'stay-unit',
            'status' => 'active',
            'name_en' => 'Stay Unit',
            'name_ur' => 'رہو',
            'abbreviation_en' => 'su',
            'abbreviation_ur' => 'س',
            'ai_status' => 'none',
        ]);

        $service = app(CatalogPortableArchiveService::class);
        $export = $service->export(CatalogPortableArchiveService::SCOPE_BRANDS);

        Brand::query()->where('code', 'BRD-ONLY')->delete();
        Unit::query()->where('code', 'UNT-STAY')->delete();

        $result = $service->importFromPath($export['path'], CatalogPortableArchiveService::SCOPE_BRANDS);

        $this->assertGreaterThanOrEqual(1, $result['created']);
        $this->assertNotNull(Brand::query()->where('code', 'BRD-ONLY')->first());
        $this->assertNull(Unit::query()->where('code', 'UNT-STAY')->first());
        $this->assertSame(0, $result['products']);

        @unlink($export['path']);
    }

    public function test_facebook_csv_contains_required_meta_columns(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/fb.webp', 'img');
        Storage::disk('public')->put('brands/fb.webp', 'brand');

        $brand = Brand::query()->create([
            'code' => 'BRD-FB',
            'slug' => 'fb-brand',
            'status' => 'active',
            'name_en' => 'FB Brand',
            'name_ur' => 'ایف بی',
            'image_path' => 'brands/fb.webp',
            'ai_status' => 'none',
        ]);

        $category = Category::query()->create([
            'code' => 'CAT-FB',
            'slug' => 'fb-cat',
            'status' => 'active',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
            'ai_status' => 'none',
        ]);

        Product::query()->create([
            'code' => 'PRD-FB1',
            'slug' => 'fb-product',
            'status' => 'active',
            'name_en' => 'FB Product',
            'name_ur' => 'پروڈکٹ',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'image_path' => 'products/fb.webp',
            'retail_price' => 99.5,
            'stock_qty' => 5,
            'short_description_en' => 'A test product',
            'ai_status' => 'none',
        ]);

        $export = app(FacebookCatalogCsvService::class)->exportActiveProducts();
        $this->assertFileExists($export['path']);
        $this->assertSame(1, $export['count']);

        $csv = (string) file_get_contents($export['path']);
        $this->assertStringContainsString('id,title,description,availability,condition,price,link,image_link', $csv);
        $this->assertStringContainsString('PRD-FB1', $csv);
        $this->assertStringContainsString('FB Product', $csv);
        $this->assertStringContainsString('99.50 PKR', $csv);
        $this->assertStringContainsString('in stock', $csv);

        @unlink($export['path']);
    }
}
