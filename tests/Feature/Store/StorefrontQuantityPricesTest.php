<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\PricingControls\QuantityControlService;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Store\StorefrontProductPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontQuantityPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_shows_wholesale_bulk_distributor_not_retail(): void
    {
        app(QuantityControlService::class)->ensureDefaultProfile();
        app(QuantityPricingEngine::class)->clearCache();

        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEED',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-PRICE-1',
            'sku' => 'SEED-1',
            'name_en' => 'Hybrid Seed',
            'name_ur' => 'ہائبرڈ بیج',
            'slug' => 'hybrid-seed',
            'retail_price' => 100,
            'wholesale_price' => 90,
            'super_wholesale_price' => 80,
            'distributor_price' => 70,
            'stock_qty' => 100,
        ]);

        $table = app(StorefrontProductPresenter::class)->quantityPriceTable($product);
        $labels = array_column($table, 'tier_label');

        $this->assertNotContains('Retail', $labels);
        $this->assertNotContains('ریٹیل', $labels);
        $this->assertContains('Wholesale', $labels);
        $this->assertContains('Bulk', $labels);
        $this->assertContains('Distributor', $labels);
        $this->assertCount(3, $table);

        $resolved = app(QuantityPricingEngine::class)->resolveForQuantity($product, 1, forPos: false);
        $this->assertSame('wholesale_price', $resolved['field'] ?? null);

        $pos = app(QuantityPricingEngine::class)->resolveForQuantity($product, 1, forPos: true);
        $this->assertSame('retail_price', $pos['field'] ?? null);
    }
}
