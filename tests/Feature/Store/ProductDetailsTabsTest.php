<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailsTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_content_sections_render_as_individual_accessible_tabs(): void
    {
        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEEDS',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-000001',
            'sku' => 'WHEAT-1',
            'name_en' => 'Wheat Seed Pack',
            'name_ur' => 'گندم بیج پیک',
            'slug' => 'wheat-seed-pack',
            'full_description_en' => 'Detailed product information.',
            'usage_guidelines_en' => 'Use according to the crop plan.',
            'warnings_en' => 'Keep away from children.',
            'customs_notes_en' => 'Verify the destination tariff.',
            'hs_code' => '1209.99',
        ]);

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Description', 'Usage guidelines', 'Warnings', 'Customs Notes', 'HS Code'])
            ->assertSee('data-store-pdp-tab="content-0"', false)
            ->assertSee('aria-controls="store-pdp-panel-content-0"', false)
            ->assertSee('data-store-pdp-panel="content-1"', false)
            ->assertSee('aria-labelledby="store-pdp-tab-content-1"', false);
    }
}
