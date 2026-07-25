<?php

namespace Tests\Feature\Store;

use App\Enums\ShippingChargeMode;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreCheckoutBankTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('agricart.inventory.allow_overselling', false);
    }

    public function test_checkout_show_blocks_place_order_when_no_active_bank_account(): void
    {
        [$customer, $product, $method] = $this->checkoutFixtures();
        app(StoreCartService::class)->add($product->id, 1);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('store.checkout'));

        $response->assertOk()
            ->assertSee(__('agricart.storefront.checkout.bank_transfer_unavailable'), false);

        $this->assertMatchesRegularExpression(
            '/<button[^>]*ag-store-checkout__submit[^>]*\bdisabled\b/',
            (string) $response->getContent(),
        );
    }

    public function test_checkout_place_rejects_bank_transfer_without_active_bank_account(): void
    {
        [$customer, $product, $method] = $this->checkoutFixtures();
        app(StoreCartService::class)->add($product->id, 1);

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $this->placePayload($method->id))
            ->assertSessionHasErrors('customer_payment_method');
    }

    public function test_checkout_show_allows_submit_when_bank_account_exists(): void
    {
        [$customer, $product, $method] = $this->checkoutFixtures();
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('store.checkout'));

        $response->assertOk()
            ->assertDontSee(__('agricart.storefront.checkout.bank_transfer_unavailable'), false);

        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression('/<button[^>]*ag-store-checkout__submit(?![^>]*\bdisabled\b)[^>]*>/', $html);
    }

    /**
     * @return array{0: Customer, 1: Product, 2: \App\Models\DeliveryMethod}
     */
    private function checkoutFixtures(): array
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Bank',
            'last_name' => 'Tester',
            'email' => 'bank-checkout@test.test',
            'phone' => '03009998877',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'bank-checkout-cat',
            'code' => 'CAT-BANK',
            'status' => 'active',
            'name_en' => 'Bank Checkout',
            'name_ur' => 'Bank Checkout',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'bank-checkout-product',
            'code' => 'BNK-1',
            'sku' => 'BNK-1',
            'status' => 'active',
            'name_en' => 'Bank Checkout Product',
            'name_ur' => 'Bank Checkout Product',
            'stock_qty' => 20,
            'retail_price' => 100,
        ]);

        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Bank Test Courier',
            'name_ur' => 'Bank Test Courier',
            'sort_order' => 1,
            'status' => 'active',
            'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);

        $product->deliveryMethods()->sync([$method->id]);

        return [$customer, $product, $method];
    }

    /**
     * @return array<string, mixed>
     */
    private function placePayload(int $deliveryMethodId): array
    {
        return [
            'checkout_token' => 'bank-transfer-test-token',
            'delivery_method_id' => $deliveryMethodId,
            'customer_payment_method' => 'bank_transfer',
            'ship_contact_name' => 'Bank Tester',
            'ship_phone' => '03009998877',
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
        ];
    }

    private function activeBankAccount(): BankAccount
    {
        return BankAccount::query()->create([
            'account_code' => 'BANK-STORE-TEST',
            'name_en' => 'Store Test Bank',
            'account_title' => 'Agricart',
            'account_number' => '1234567890',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }
}
