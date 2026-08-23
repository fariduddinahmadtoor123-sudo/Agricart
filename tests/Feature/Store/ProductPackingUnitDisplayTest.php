<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Store\StorefrontProductPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPackingUnitDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_facts_show_packing_unit_without_pack_qty(): void
    {
        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEED',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $unit = Unit::query()->create([
            'code' => 'KG',
            'slug' => 'kg',
            'status' => 'active',
            'name_en' => 'Kilogram',
            'name_ur' => 'کلو',
            'abbreviation_en' => 'kg',
            'abbreviation_ur' => 'کلو',
        ]);

        $packUnit = PackingUnit::query()->create([
            'code' => 'CTN',
            'slug' => 'carton',
            'status' => 'active',
            'name_en' => 'Carton',
            'name_ur' => 'کارٹن',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'pack_unit_id' => $packUnit->id,
            'unit_qty' => '1',
            'pack_qty' => null,
            'status' => 'active',
            'code' => 'PRD-PACK-1',
            'sku' => 'PACK-1',
            'name_en' => 'Seed Carton',
            'name_ur' => 'بیج کارٹن',
            'slug' => 'seed-carton',
        ]);

        $product->load(['unit', 'packUnit']);

        $facts = collect(app(StorefrontProductPresenter::class)->identityFacts($product))->keyBy('key');

        $this->assertSame('1 Kilogram', $facts['unit']['value'] ?? null);
        $this->assertSame('Carton', $facts['packing_unit']['value'] ?? null);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee(__('agricart.storefront.product.facts.packing_unit'), false)
            ->assertSee('Carton', false);
    }
}
