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

    public function test_storefront_shows_bulk_distributor_not_retail_or_wholesale(): void
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
        $this->assertNotContains('Wholesale', $labels);
        $this->assertNotContains('ہول سیل', $labels);
        $this->assertContains('Bulk', $labels);
        $this->assertContains('Distributor', $labels);
        $this->assertCount(2, $table);

        $resolved = app(QuantityPricingEngine::class)->resolveForQuantity($product, 1, forPos: false);
        $this->assertSame('wholesale_price', $resolved['field'] ?? null);

        $pos = app(QuantityPricingEngine::class)->resolveForQuantity($product, 1, forPos: true);
        $this->assertSame('retail_price', $pos['field'] ?? null);
    }

    public function test_product_page_shows_quantity_rates_next_to_price(): void
    {
        app(QuantityControlService::class)->ensureDefaultProfile();
        app(QuantityPricingEngine::class)->clearCache();

        $category = Category::query()->create([
            'slug' => 'qty-front',
            'code' => 'CAT-QTYF',
            'status' => 'active',
            'name_en' => 'Qty Front',
            'name_ur' => 'کوانٹٹی فرنٹ',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-QTYF-1',
            'sku' => 'QTYF-1',
            'name_en' => 'Qty Front Seed',
            'name_ur' => 'کوانٹٹی فرنٹ بیج',
            'slug' => 'qty-front-seed',
            'retail_price' => 100,
            'wholesale_price' => 90,
            'super_wholesale_price' => 80,
            'distributor_price' => 70,
            'stock_qty' => 100,
        ]);

        $format = app(\App\Services\PricingControls\ChannelPricingEngine::class);
        $rs = static fn (float $amount): string => __('agricart.storefront.products.price', [
            'amount' => $format->formatPrice($amount),
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertDontSee('ag-store-pdp__price-tier', false)
            ->assertSee('ag-store-qty-rates', false)
            ->assertDontSee(__('agricart.storefront.product.price_table.qty_rate', [
                'qty' => 5,
                'price' => $rs(90),
            ]), false)
            ->assertSee(__('agricart.storefront.product.price_table.qty_rate', [
                'qty' => 20,
                'price' => $rs(80),
            ]), false)
            ->assertSee(__('agricart.storefront.product.price_table.qty_rate', [
                'qty' => 500,
                'price' => $rs(70),
            ]), false)
            ->assertSee(__('agricart.storefront.product.price_table.bulk_hint'), false)
            ->assertSee(__('agricart.storefront.product.price_table.bulk_hint_contact'), false);
    }

    public function test_compact_quantity_rates_hide_when_only_one_plus_price_exists(): void
    {
        $presenter = app(StorefrontProductPresenter::class);

        $this->assertSame([], $presenter->compactRows([
            [
                'min_qty' => 1,
                'tier_label' => 'Wholesale',
                'price' => '90.00',
                'formatted' => '90',
            ],
        ]));

        $this->assertCount(2, $presenter->compactRows([
            [
                'min_qty' => 5,
                'tier_label' => 'Wholesale',
                'price' => '90.00',
                'formatted' => '90',
            ],
            [
                'min_qty' => 20,
                'tier_label' => 'Bulk',
                'price' => '80.00',
                'formatted' => '80',
            ],
        ]));
    }
}
