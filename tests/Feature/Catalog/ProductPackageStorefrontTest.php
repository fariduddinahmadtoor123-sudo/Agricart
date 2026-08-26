<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\ProductPackageService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StorePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPackageStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_appears_on_category_and_adds_discounted_lines_to_cart(): void
    {
        $category = Category::query()->create([
            'slug' => 'water-pumps',
            'status' => 'active',
            'name_en' => 'Water Pumps',
            'name_ur' => 'Water Pumps',
        ]);

        $bearing = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'pump-bearing',
            'code' => 'BRG-1',
            'status' => 'active',
            'name_en' => 'Pump Bearing',
            'name_ur' => 'Pump Bearing',
            'stock_qty' => 100,
            'wholesale_price' => 100,
            'retail_price' => 120,
        ]);

        $seal = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'pump-seal',
            'code' => 'SEAL-1',
            'status' => 'active',
            'name_en' => 'Pump Seal',
            'name_ur' => 'Pump Seal',
            'stock_qty' => 100,
            'wholesale_price' => 50,
            'retail_price' => 60,
        ]);

        $admin = $this->superAdmin();

        $package = app(ProductPackageService::class)->create(
            [
                'name_en' => 'Water Pump Kit',
                'name_ur' => 'Water Pump Kit',
                'status' => ProductPackage::STATUS_ACTIVE,
                'discount_percent' => 10,
                'sort_order' => 1,
            ],
            [
                ['product_id' => $bearing->id, 'quantity' => 2],
                ['product_id' => $seal->id, 'quantity' => 1],
            ],
            [$category->id],
            null,
            $admin,
        );

        $this->get(route('store.category', $category->slug))
            ->assertOk()
            ->assertSee('Water Pump Kit', false)
            ->assertSee(route('store.package', $package->slug), false);

        $this->get(route('store.package', $package->slug))
            ->assertOk()
            ->assertSee('Pump Bearing', false)
            ->assertSee('Pump Seal', false);

        $this->post(route('store.package.add', $package->slug), [
            'package_qty' => 1,
        ])->assertRedirect(route('store.cart'));

        $lines = app(StoreCartService::class)->lines();
        $this->assertCount(2, $lines);

        $byId = collect($lines)->keyBy(fn (array $line) => $line['product']->id);
        $this->assertSame(2.0, $byId[$bearing->id]['quantity_numeric']);
        $this->assertSame(1.0, $byId[$seal->id]['quantity_numeric']);
        // 10% package discount baked into locked unit prices
        $this->assertEqualsWithDelta(90.0, (float) $byId[$bearing->id]['unit_price'], 0.01);
        $this->assertEqualsWithDelta(45.0, (float) $byId[$seal->id]['unit_price'], 0.01);
    }

    public function test_store_package_service_builds_bill_with_discount(): void
    {
        $category = Category::query()->create([
            'slug' => 'kits',
            'status' => 'active',
            'name_en' => 'Kits',
            'name_ur' => 'Kits',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'kit-item',
            'code' => 'KIT-1',
            'status' => 'active',
            'name_en' => 'Kit Item',
            'name_ur' => 'Kit Item',
            'stock_qty' => 20,
            'wholesale_price' => 200,
            'retail_price' => 220,
        ]);

        $package = app(ProductPackageService::class)->create(
            [
                'name_en' => 'Simple Kit',
                'status' => ProductPackage::STATUS_ACTIVE,
                'discount_percent' => 5,
            ],
            [['product_id' => $product->id, 'quantity' => 3]],
            [$category->id],
            null,
            $this->superAdmin(),
        );

        $bill = app(StorePackageService::class)->buildBill($package->fresh(['items.product']), 1);
        $this->assertCount(1, $bill['lines']);
        $this->assertEqualsWithDelta(600.0, $bill['subtotal'], 0.01);
        $this->assertEqualsWithDelta(30.0, $bill['discount_amount'], 0.01);
        $this->assertEqualsWithDelta(570.0, $bill['total'], 0.01);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
