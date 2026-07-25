<?php

namespace Tests\Feature\Store;

use App\Enums\ShippingChargeMode;
use App\Jobs\SendOnlineOrderPlacedNotification;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StoreCheckoutPhaseBBatch3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('agricart.inventory.allow_overselling', false);
    }

    public function test_collect_upfront_shipping_is_included_in_order_total(): void
    {
        Queue::fake();
        [$customer, $product, $method] = $this->checkoutFixtures(
            shippingMode: ShippingChargeMode::CollectUpfront,
            shippingFee: 75,
        );
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $this->placePayload($method->id))
            ->assertRedirect();

        $order = OnlineSaleOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->subtotal);
        $this->assertSame(75.0, (float) $order->shipping_amount);
        $this->assertSame(175.0, (float) $order->total_amount);
    }

    public function test_non_collect_shipping_modes_store_zero_shipping_amount(): void
    {
        Queue::fake();
        [$customer, $product, $method] = $this->checkoutFixtures(
            shippingMode: ShippingChargeMode::CalculateLater,
            shippingFee: 99,
        );
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $this->placePayload($method->id))
            ->assertRedirect();

        $order = OnlineSaleOrder::query()->first();
        $this->assertSame(0.0, (float) $order->shipping_amount);
        $this->assertSame(100.0, (float) $order->total_amount);
    }

    public function test_place_order_dispatches_online_order_placed_notification_job(): void
    {
        Queue::fake();
        [$customer, $product, $method] = $this->checkoutFixtures();
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $this->placePayload($method->id))
            ->assertRedirect();

        Queue::assertPushed(SendOnlineOrderPlacedNotification::class, 1);
    }

    public function test_checkout_place_rejects_cash_when_not_allowed_for_delivery_method(): void
    {
        [$customer, $product] = $this->customerAndProduct();
        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Bank only courier',
            'name_ur' => 'Bank only courier',
            'sort_order' => 1,
            'status' => 'active',
            'allowed_payment_methods' => ['bank_transfer'],
            'cod_allowed' => false,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);
        $product->deliveryMethods()->sync([$method->id]);
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $payload = $this->placePayload($method->id);
        $payload['customer_payment_method'] = 'cash';

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $payload)
            ->assertSessionHasErrors('customer_payment_method');
    }

    public function test_full_advance_requires_bank_transfer_and_payment_proof(): void
    {
        Queue::fake();
        [$customer, $product] = $this->customerAndProduct();
        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Full advance courier',
            'name_ur' => 'Full advance courier',
            'sort_order' => 1,
            'status' => 'active',
            'allowed_payment_methods' => ['cash', 'bank_transfer'],
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => true,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);
        $product->deliveryMethods()->sync([$method->id]);
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $payload = $this->placePayload($method->id);
        $payload['customer_payment_method'] = 'cash';

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $payload)
            ->assertSessionHasErrors('customer_payment_method');

        $payload['customer_payment_method'] = 'bank_transfer';
        unset($payload['customer_payment_reference']);

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $payload)
            ->assertSessionHasErrors('customer_payment_reference');

        $payload['customer_payment_reference'] = 'TXN-FULL-ADV-001';

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $payload)
            ->assertRedirect();

        $this->assertSame('bank_transfer', OnlineSaleOrder::query()->value('customer_payment_method'));
    }

    public function test_full_advance_accepts_bank_receipt_upload_instead_of_reference(): void
    {
        Queue::fake();
        [$customer, $product] = $this->customerAndProduct();
        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Full advance receipt',
            'name_ur' => 'Full advance receipt',
            'sort_order' => 2,
            'status' => 'active',
            'allowed_payment_methods' => ['bank_transfer'],
            'cod_allowed' => false,
            'full_advance_required' => true,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);
        $product->deliveryMethods()->sync([$method->id]);
        $this->activeBankAccount();
        app(StoreCartService::class)->add($product->id, 1);

        $payload = $this->placePayload($method->id);
        $payload['bank_receipt'] = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($customer, 'customer')
            ->post(route('store.checkout.place'), $payload)
            ->assertRedirect();

        $this->assertNotNull(OnlineSaleOrder::query()->value('customer_bank_receipt_path'));
    }

    /**
     * @return array{0: Customer, 1: Product, 2: \App\Models\DeliveryMethod}
     */
    private function checkoutFixtures(
        ShippingChargeMode $shippingMode = ShippingChargeMode::CalculateLater,
        float $shippingFee = 0,
    ): array {
        [$customer, $product] = $this->customerAndProduct();

        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Phase B Courier',
            'name_ur' => 'Phase B Courier',
            'sort_order' => 1,
            'status' => 'active',
            'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'shipping_charge_mode' => $shippingMode->value,
            'shipping_fee' => $shippingFee,
        ]);

        $product->deliveryMethods()->sync([$method->id]);

        return [$customer, $product, $method];
    }

    /**
     * @return array{0: Customer, 1: Product}
     */
    private function customerAndProduct(): array
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'B',
            'email' => 'phase-b-batch3@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'phase-b-batch3-cat',
            'code' => 'CAT-PBB3',
            'status' => 'active',
            'name_en' => 'Phase B Batch 3',
            'name_ur' => 'Phase B Batch 3',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'phase-b-batch3-product',
            'code' => 'PBB3-1',
            'sku' => 'PBB3-1',
            'status' => 'active',
            'name_en' => 'Phase B Product',
            'name_ur' => 'Phase B Product',
            'stock_qty' => 20,
            'retail_price' => 100,
        ]);

        return [$customer, $product];
    }

    /**
     * @return array<string, mixed>
     */
    private function placePayload(int $deliveryMethodId): array
    {
        return [
            'checkout_token' => 'phase-b-batch3-token',
            'delivery_method_id' => $deliveryMethodId,
            'customer_payment_method' => 'bank_transfer',
            'ship_contact_name' => 'Phase B',
            'ship_phone' => '03001112233',
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
        ];
    }

    private function activeBankAccount(): BankAccount
    {
        return BankAccount::query()->create([
            'account_code' => 'BANK-PBB3-TEST',
            'name_en' => 'Phase B Bank',
            'account_title' => 'Agricart',
            'account_number' => '9876543210',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }
}
