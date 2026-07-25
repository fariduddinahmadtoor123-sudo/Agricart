<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreCheckoutGuestRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_login_to_checkout_link_uses_path_redirect(): void
    {
        $product = $this->product();
        app(StoreCartService::class)->add($product->id, 1);

        $loginUrl = route('store.customer.login', ['redirect' => '/checkout']);

        $this->get(route('store.cart'))
            ->assertOk()
            ->assertSee('href="'.$loginUrl.'"', false);
    }

    public function test_login_after_checkout_redirect_query_returns_to_checkout_path(): void
    {
        $customer = $this->customer();

        $this->get(route('store.customer.login', ['redirect' => '/checkout']))
            ->assertOk();

        $this->post(route('store.customer.login.submit'), [
            'login' => $customer->email,
            'login_password' => 'password',
            'redirect' => '/checkout',
        ])->assertRedirect('/checkout');
    }

    public function test_login_accepts_same_host_absolute_checkout_url_as_path(): void
    {
        config(['app.url' => 'https://agricart.test']);
        $customer = $this->customer();

        $this->get(route('store.customer.login', ['redirect' => 'https://agricart.test/checkout']))
            ->assertOk();

        $this->post(route('store.customer.login.submit'), [
            'login' => $customer->email,
            'login_password' => 'password',
            'redirect' => 'https://agricart.test/checkout',
        ])->assertRedirect('/checkout');
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Redirect',
            'last_name' => 'Tester',
            'email' => 'redirect@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        $category = Category::query()->create([
            'slug' => 'redirect-cat',
            'code' => 'CAT-RED',
            'status' => 'active',
            'name_en' => 'Redirect Cat',
            'name_ur' => 'Redirect Cat',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'redirect-product',
            'code' => 'RED-1',
            'sku' => 'RED-1',
            'status' => 'active',
            'name_en' => 'Redirect Product',
            'name_ur' => 'Redirect Product',
            'stock_qty' => 10,
            'retail_price' => 100,
        ]);
    }
}
