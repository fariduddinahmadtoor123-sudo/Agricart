<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class StoreCartActiveLinesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('agricart.inventory.allow_overselling', false);
    }

    public function test_total_quantity_ignores_inactive_products_and_prunes_session(): void
    {
        $active = $this->product('active-cart-line', 'active');
        $inactive = $this->product('inactive-cart-line', 'inactive');

        Session::put(StoreCartService::SESSION_KEY, [
            ['product_id' => $active->id, 'quantity' => '2'],
            ['product_id' => $inactive->id, 'quantity' => '5'],
        ]);

        $cart = app(StoreCartService::class);

        $this->assertSame(2, $cart->totalQuantity());
        $this->assertCount(1, $cart->lines());

        $sessionItems = Session::get(StoreCartService::SESSION_KEY, []);
        $this->assertCount(1, $sessionItems);
        $this->assertSame($active->id, (int) $sessionItems[0]['product_id']);
    }

    public function test_merge_after_login_clamps_to_stock_and_flashes_when_adjusted(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-MERGE-1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Merge',
            'last_name' => 'Test',
            'email' => 'merge-cart@test.test',
            'phone' => '03001110001',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $product = $this->product('merge-stock', 'active', stock: 3);
        $cart = app(StoreCartService::class);

        $cart->mergeAfterCustomerLogin($customer->id, [
            ['product_id' => $product->id, 'quantity' => '5'],
        ]);

        $this->assertSame(3, $cart->totalQuantity());
        $this->assertSame('3', Session::get(StoreCartService::SESSION_KEY)[0]['quantity']);
        $this->assertSame(__('agricart.storefront.cart.merge_adjusted'), session('store_status'));
        $this->assertSame('warning', session('store_status_type'));
    }

    public function test_merge_after_login_drops_inactive_products(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-MERGE-2',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Merge',
            'last_name' => 'Inactive',
            'email' => 'merge-inactive@test.test',
            'phone' => '03001110002',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $inactive = $this->product('merge-inactive', 'inactive', stock: 10);
        $cart = app(StoreCartService::class);

        $cart->mergeAfterCustomerLogin($customer->id, [
            ['product_id' => $inactive->id, 'quantity' => '2'],
        ]);

        $this->assertSame(0, $cart->totalQuantity());
        $this->assertSame([], Session::get(StoreCartService::SESSION_KEY, []));
    }

    private function product(string $slug, string $status, float $stock = 10): Product
    {
        $category = Category::query()->create([
            'slug' => $slug.'-cat',
            'code' => strtoupper(substr($slug, 0, 8)),
            'status' => 'active',
            'name_en' => 'Cat '.$slug,
            'name_ur' => 'Cat '.$slug,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'slug' => $slug,
            'code' => strtoupper($slug),
            'sku' => strtoupper($slug),
            'status' => $status,
            'name_en' => 'Product '.$slug,
            'name_ur' => 'Product '.$slug,
            'stock_qty' => $stock,
            'retail_price' => 100,
        ]);
    }
}
