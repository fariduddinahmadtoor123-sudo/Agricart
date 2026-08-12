<?php

namespace Tests\Feature\Purchasing;

use App\Livewire\PriceUpdates\ListPriceUpdates;
use App\Livewire\PriceUpdates\Sheet;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use App\Services\PricingControls\PriceControlService;
use App\Services\PricingControls\ProductPriceUpdateService;
use Database\Seeders\QuantityControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PriceUpdateSheetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin);

        \App\Models\CompanyProfile::current()->update([
            'price_use_decimals' => true,
            'price_decimal_places' => 2,
        ]);

        app(PriceControlService::class)->ensureDefaultProfile();
        $this->seed(QuantityControlSeeder::class);

        $profile = app(PriceControlService::class)->ensureDefaultProfile();
        $retail = $profile->tiers()->where('tier_key', 'retail')->firstOrFail();
        app(PriceControlService::class)->updateTier($retail, [
            'markup_percent' => 20,
            'status' => 'active',
        ]);
    }

    public function test_buy_price_fills_all_channel_prices_from_markups(): void
    {
        $product = $this->makeProduct(['stock_qty' => 42]);
        $purchasesBefore = Purchase::query()->count();

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            ->set('rows.0.last_buy_price', '100')
            ->call('recalculateRow', 0, true)
            ->assertSet('rows.0.retail_price', '120')
            ->assertSet('rows.0.wholesale_price', '110')
            ->assertSet('rows.0.super_wholesale_price', '105')
            ->assertSet('rows.0.distributor_price', '102')
            ->call('saveAll')
            ->assertSet('statusType', 'success');

        $product->refresh();

        $this->assertEquals(100.0, (float) $product->last_buy_price);
        $this->assertEquals(120.0, (float) $product->retail_price);
        $this->assertEquals(110.0, (float) $product->wholesale_price);
        $this->assertEquals(105.0, (float) $product->super_wholesale_price);
        $this->assertEquals(102.0, (float) $product->distributor_price);
        $this->assertEquals(42.0, (float) $product->stock_qty);
        $this->assertSame($purchasesBefore, Purchase::query()->count());
    }

    public function test_changing_buy_price_recalculates_all_sell_prices(): void
    {
        $product = $this->makeProduct([
            'retail_price' => '150',
            'wholesale_price' => '140',
            'super_wholesale_price' => '130',
            'distributor_price' => '120',
        ]);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            // First buy price recalculates every tier from markups (overwrites loaded prices).
            ->set('rows.0.last_buy_price', '100')
            ->assertSet('rows.0.retail_price', '120')
            ->assertSet('rows.0.wholesale_price', '110')
            ->assertSet('rows.0.super_wholesale_price', '105')
            ->assertSet('rows.0.distributor_price', '102')
            // Changing the buy price again re-applies markups to all sell prices automatically.
            ->set('rows.0.last_buy_price', '200')
            ->assertSet('rows.0.retail_price', '240')
            ->assertSet('rows.0.wholesale_price', '220')
            ->assertSet('rows.0.super_wholesale_price', '210')
            ->assertSet('rows.0.distributor_price', '204')
            ->call('saveAll')
            ->assertSet('statusType', 'success');

        $product->refresh();
        $this->assertEquals(200.0, (float) $product->last_buy_price);
        $this->assertEquals(240.0, (float) $product->retail_price);
        $this->assertEquals(220.0, (float) $product->wholesale_price);
        $this->assertEquals(210.0, (float) $product->super_wholesale_price);
        $this->assertEquals(204.0, (float) $product->distributor_price);
    }

    public function test_prices_fill_when_tier_keys_match_product_fields(): void
    {
        $profile = app(PriceControlService::class)->ensureDefaultProfile();

        foreach ($profile->tiers as $tier) {
            if ($tier->tier_key === 'retail') {
                continue;
            }

            // Simulate hosting data where tier_key was saved as the product field name.
            $tier->update(['tier_key' => $tier->product_field]);
        }

        $prices = app(ProductPriceUpdateService::class)->pricesFromBuyPrice(50);

        // setUp configures retail markup at 20%.
        $this->assertEquals(60.0, (float) $prices['retail_price']);
        $this->assertNotNull($prices['wholesale_price']);
        $this->assertNotNull($prices['super_wholesale_price']);
        $this->assertNotNull($prices['distributor_price']);
        $this->assertGreaterThan(0, (float) $prices['wholesale_price']);
        $this->assertGreaterThan(0, (float) $prices['super_wholesale_price']);
        $this->assertGreaterThan(0, (float) $prices['distributor_price']);
    }

    public function test_tier_quantities_save_without_touching_stock(): void
    {
        app(\App\Services\PricingControls\QuantityControlService::class)->ensureDefaultProfile();

        $product = $this->makeProduct([
            'stock_qty' => 15,
            // Product-specific breaks must load (not forced back to Quantity Channel).
            'price_tier_quantities' => [
                'retail_price' => 1,
                'wholesale_price' => 5,
                'super_wholesale_price' => 100,
                'distributor_price' => 20,
            ],
        ]);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            ->assertSet('rows.0.price_tier_quantities.super_wholesale_price', 100)
            ->set('rows.0.last_buy_price', '50')
            ->set('rows.0.price_tier_quantities.wholesale_price', 100)
            ->set('rows.0.price_tier_quantities.super_wholesale_price', 1000)
            ->call('saveAll')
            ->assertSet('statusType', 'success')
            ->assertSet('rows.0.price_tier_quantities.super_wholesale_price', 1000);

        $product->refresh();

        $this->assertSame(100, (int) ($product->price_tier_quantities['wholesale_price'] ?? 0));
        $this->assertSame(1000, (int) ($product->price_tier_quantities['super_wholesale_price'] ?? 0));
        $this->assertEquals(15.0, (float) $product->stock_qty);
    }

    public function test_list_shows_products_after_price_update(): void
    {
        $product = $this->makeProduct(['last_buy_price' => 80, 'retail_price' => 96]);

        Livewire::test(ListPriceUpdates::class)
            ->assertSee($product->name_en)
            ->assertSee('80');
    }

    public function test_service_updates_selling_prices_without_buy_price(): void
    {
        $product = $this->makeProduct();

        app(ProductPriceUpdateService::class)->updateProductPricing($product, [
            'last_buy_price' => '',
            'retail_price' => '10',
            'wholesale_price' => '9',
        ]);

        $product->refresh();

        // Blank buy leaves the purchase cost untouched but still saves selling prices.
        $this->assertNull($product->last_buy_price);
        $this->assertEquals(10.0, (float) $product->retail_price);
        $this->assertEquals(9.0, (float) $product->wholesale_price);
    }

    public function test_service_rejects_non_numeric_buy_price(): void
    {
        $product = $this->makeProduct();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ProductPriceUpdateService::class)->updateProductPricing($product, [
            'last_buy_price' => 'abc',
            'retail_price' => '10',
        ]);
    }

    public function test_add_product_prepends_newest_row_to_top(): void
    {
        $first = $this->makeProduct(['name_en' => 'First Product']);
        $second = $this->makeProduct(['name_en' => 'Second Product']);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $first->id)
            ->assertSet('rows.0.product_id', $first->id)
            ->call('addProduct', $second->id)
            ->assertSet('rows.0.product_id', $second->id)
            ->assertSet('rows.1.product_id', $first->id);
    }

    public function test_save_all_updates_every_priced_row_and_skips_blank_rows(): void
    {
        $pricedA = $this->makeProduct(['name_en' => 'Priced A']);
        $pricedB = $this->makeProduct(['name_en' => 'Priced B']);
        $pricedC = $this->makeProduct(['name_en' => 'Priced C']);
        $blank = $this->makeProduct(['name_en' => 'Blank One']);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $pricedA->id)
            ->call('addProduct', $pricedB->id)
            ->call('addProduct', $pricedC->id)
            ->call('addProduct', $blank->id)
            // Newest product stays at the top (blank); older rows shift down.
            ->set('rows.3.retail_price', '100')
            ->set('rows.2.wholesale_price', '90')
            ->set('rows.1.retail_price', '75')
            ->call('saveAll')
            ->assertSet('statusType', 'success');

        $this->assertEquals(100.0, (float) $pricedA->refresh()->retail_price);
        $this->assertEquals(90.0, (float) $pricedB->refresh()->wholesale_price);
        $this->assertEquals(75.0, (float) $pricedC->refresh()->retail_price);
        // Untouched row stays untouched — no accidental blanking or stamping.
        $this->assertNull($blank->refresh()->retail_price);
    }

    public function test_save_all_warns_when_no_prices_entered(): void
    {
        $product = $this->makeProduct(['name_en' => 'Empty Row']);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            ->call('saveAll')
            ->assertSet('statusType', 'warning');
    }

    public function test_user_without_edit_permission_cannot_save(): void
    {
        $viewOnly = Role::query()->create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'is_system' => false,
        ]);

        $permission = Permission::query()->create([
            'key' => 'purchasing.view',
            'group_key' => 'purchasing',
            'label_en' => 'View purchasing',
            'label_ur' => 'خریداری دیکھیں',
            'sort_order' => 1,
        ]);
        $viewOnly->permissions()->attach($permission->id);

        $user = User::factory()->create([
            'role_id' => $viewOnly->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $product = $this->makeProduct();

        Livewire::actingAs($user)
            ->test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            ->assertCount('rows', 0);

        Livewire::actingAs($user)
            ->test(Sheet::class)
            ->set('rows', [[
                'row_id' => 'x',
                'product_id' => $product->id,
                'name' => $product->name_en,
                'name_ur' => '',
                'code' => $product->code,
                'sku' => '',
                'thumb_url' => null,
                'last_buy_price' => '100',
                'retail_price' => '120',
                'wholesale_price' => '110',
                'super_wholesale_price' => '105',
                'distributor_price' => '102',
                'channel_prices' => [],
                'price_tier_quantities' => [
                    'retail_price' => 1,
                    'wholesale_price' => 5,
                    'super_wholesale_price' => 10,
                    'distributor_price' => 20,
                ],
            ]])
            ->call('saveAll')
            ->assertSet('statusType', 'error');

        $product->refresh();
        $this->assertNull($product->last_buy_price);
    }

    public function test_load_category_adds_products_from_nested_subcategories(): void
    {
        $root = Category::query()->create([
            'slug' => 'agriculture',
            'code' => 'CAT-AGR',
            'status' => 'active',
            'name_en' => 'Agriculture',
            'name_ur' => 'زراعت',
        ]);

        $boom = Category::query()->create([
            'parent_id' => $root->id,
            'slug' => 'boom',
            'code' => 'CAT-BOOM',
            'status' => 'active',
            'name_en' => 'Boom',
            'name_ur' => 'بوم',
        ]);

        $meshes = Category::query()->create([
            'parent_id' => $boom->id,
            'slug' => 'meshes',
            'code' => 'CAT-MESH',
            'status' => 'active',
            'name_en' => 'Meshes',
            'name_ur' => 'میشز',
        ]);

        $other = Category::query()->create([
            'slug' => 'other',
            'code' => 'CAT-OTH',
            'status' => 'active',
            'name_en' => 'Other',
            'name_ur' => 'دیگر',
        ]);

        $inRoot = $this->makeProduct(['category_id' => $root->id, 'name_en' => 'Root Product']);
        $inBoom = $this->makeProduct(['category_id' => $boom->id, 'name_en' => 'Boom Product']);
        $inMeshes = $this->makeProduct(['category_id' => $meshes->id, 'name_en' => 'Mesh Product']);
        $elsewhere = $this->makeProduct(['category_id' => $other->id, 'name_en' => 'Other Product']);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProductsFromCategory', $root->id)
            ->assertCount('rows', 3)
            ->assertSee('Root Product')
            ->assertSee('Boom Product')
            ->assertSee('Mesh Product')
            ->assertDontSee('Other Product');

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProductsFromCategory', $meshes->id)
            ->assertCount('rows', 1)
            ->assertSet('rows.0.product_id', $inMeshes->id);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $inBoom->id)
            ->call('addProductsFromCategory', $boom->id)
            ->assertCount('rows', 2)
            ->assertSet('statusType', 'success');

        $this->assertNotNull($inRoot->id);
        $this->assertNotNull($elsewhere->id);
    }

    public function test_load_brand_adds_all_products_of_that_brand(): void
    {
        $acme = Brand::query()->create([
            'slug' => 'acme',
            'code' => 'BR-ACME',
            'status' => 'active',
            'name_en' => 'Acme',
            'name_ur' => 'ایکمی',
            'image_path' => 'brands/acme.webp',
        ]);

        $other = Brand::query()->create([
            'slug' => 'other-brand',
            'code' => 'BR-OTHER',
            'status' => 'active',
            'name_en' => 'Other Brand',
            'name_ur' => 'دیگر برانڈ',
            'image_path' => 'brands/other.webp',
        ]);

        $acmeOne = $this->makeProduct(['brand_id' => $acme->id, 'name_en' => 'Acme Spade']);
        $acmeTwo = $this->makeProduct(['brand_id' => $acme->id, 'name_en' => 'Acme Hoe']);
        $elsewhere = $this->makeProduct(['brand_id' => $other->id, 'name_en' => 'Other Rake']);

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProductsFromBrand', $acme->id)
            ->assertCount('rows', 2)
            ->assertSee('Acme Spade')
            ->assertSee('Acme Hoe')
            ->assertDontSee('Other Rake')
            ->assertSet('statusType', 'success');

        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $acmeOne->id)
            ->call('addProductsFromBrand', $acme->id)
            ->assertCount('rows', 2);

        $this->assertNotNull($acmeTwo->id);
        $this->assertNotNull($elsewhere->id);
    }

    public function test_print_blank_buy_sheet_opens_with_four_columns_and_blank_buy(): void
    {
        $product = $this->makeProduct([
            'name_en' => 'Spray Pump Body',
            'name_ur' => 'سپری پمپ باڈی',
            'last_buy_price' => 250,
        ]);

        $component = Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('addProduct', $product->id)
            ->set('printLocale', 'both')
            ->call('printBlankBuySheet')
            ->assertSet('statusType', 'success')
            ->assertDispatched('price-update-open-print');

        $url = null;
        foreach ($component->effects['dispatches'] ?? [] as $event) {
            if (($event['name'] ?? '') !== 'price-update-open-print') {
                continue;
            }

            $url = $event['params']['url']
                ?? (is_array($event['params'][0] ?? null) ? ($event['params'][0]['url'] ?? null) : null)
                ?? null;
        }

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/price-updates/buy-sheet/print', (string) $url);

        $response = $this->get($url.(str_contains((string) $url, '?') ? '&' : '?').'autoprint=0');
        $response->assertOk();
        $response->assertSee('Spray Pump Body', false);
        $response->assertSee('سپری پمپ باڈی', false);
        $response->assertSee(__('price_updates.print.headers.sr'), false);
        $response->assertSee(__('price_updates.print.headers.pic'), false);
        $response->assertSee(__('price_updates.print.headers.name_en'), false);
        $response->assertSee(__('price_updates.print.headers.name_ur'), false);
        $response->assertSee(__('price_updates.print.headers.buy'), false);
        $response->assertSee('buy-table--both', false);
        $response->assertDontSee('>250<', false);
        $response->assertDontSee(__('price_updates.headers.retail'), false);
    }

    public function test_print_blank_buy_sheet_requires_rows(): void
    {
        Livewire::test(Sheet::class)
            ->call('openForm')
            ->call('printBlankBuySheet')
            ->assertSet('statusType', 'warning')
            ->assertNotDispatched('price-update-open-print');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeProduct(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'slug' => 'price-update-'.uniqid(),
            'code' => 'PRD-PU-'.uniqid(),
            'status' => 'active',
            'name_en' => 'Price Update Product',
            'name_ur' => 'پرائس اپڈیٹ',
            'stock_qty' => 0,
        ], $overrides));
    }
}
