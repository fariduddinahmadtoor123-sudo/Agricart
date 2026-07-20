<?php

namespace Tests\Feature\Logistics;

use App\Enums\ShippingChargeMode;
use App\Livewire\DeliveryMethods\Form;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_admin_can_create_delivery_method_with_rules(): void
    {
        Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Courier Delivery')
            ->set('name_ur', 'کورئیر ڈیلیوری')
            ->set('description_en', 'Doorstep courier')
            ->set('allowed_payment_methods', ['cash', 'bank_transfer'])
            ->set('cod_allowed', true)
            ->set('cod_min_advance_percent', '10')
            ->set('full_advance_required', false)
            ->set('shipping_charge_mode', ShippingChargeMode::CalculateLater->value)
            ->set('customer_notice_en', 'Courier will call first')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $method = DeliveryMethod::query()->where('slug', 'courier-delivery')->firstOrFail();
        $this->assertSame(DeliveryMethod::STATUS_ACTIVE, $method->status);
        $this->assertNotNull($method->rule);
        $this->assertSame(['cash', 'bank_transfer'], $method->rule->allowedPaymentMethods());
        $this->assertTrue($method->rule->cod_allowed);
        $this->assertSame(10.0, (float) $method->rule->cod_min_advance_percent);
        $this->assertSame(ShippingChargeMode::CalculateLater, $method->rule->shipping_charge_mode);
    }

    public function test_inactive_methods_are_hidden_from_checkout_list(): void
    {
        $service = app(DeliveryMethodService::class);

        $active = $service->create($this->methodPayload([
            'name_en' => 'Active Courier',
            'name_ur' => 'فعال کورئیر',
            'status' => DeliveryMethod::STATUS_ACTIVE,
            'sort_order' => 1,
        ]));
        $service->create($this->methodPayload([
            'name_en' => 'Inactive Cargo',
            'name_ur' => 'غیر فعال کارگو',
            'status' => DeliveryMethod::STATUS_INACTIVE,
            'sort_order' => 2,
        ]));

        $activeList = $service->activeForCheckout();

        $this->assertCount(1, $activeList);
        $this->assertSame($active->id, $activeList->first()->id);
        $this->assertNull($service->findActive(
            DeliveryMethod::query()->where('slug', 'inactive-cargo')->value('id')
        ));
    }

    public function test_payment_methods_respect_allowed_list_and_cod_flag(): void
    {
        $service = app(DeliveryMethodService::class);

        $bankOnly = $service->create($this->methodPayload([
            'name_en' => 'Transport Booking',
            'name_ur' => 'ٹرانسپورٹ',
            'allowed_payment_methods' => ['bank_transfer', 'cash'],
            'cod_allowed' => false,
        ]));

        $this->assertSame(['bank_transfer'], $service->paymentMethodsFor($bankOnly));

        $this->expectException(ValidationException::class);
        $service->assertCheckoutSelection($bankOnly, 'cash');
    }

    public function test_checkout_intersects_delivery_methods_across_products(): void
    {
        $service = app(DeliveryMethodService::class);

        $shared = $service->create($this->methodPayload([
            'name_en' => 'Shared Courier',
            'name_ur' => 'مشترکہ کورئیر',
            'sort_order' => 1,
        ]));
        $onlyA = $service->create($this->methodPayload([
            'name_en' => 'Only Product A',
            'name_ur' => 'صرف اے',
            'sort_order' => 2,
        ]));
        $onlyB = $service->create($this->methodPayload([
            'name_en' => 'Only Product B',
            'name_ur' => 'صرف بی',
            'sort_order' => 3,
        ]));

        $category = Category::query()->create([
            'slug' => 'intersect-cat',
            'status' => 'active',
            'name_en' => 'Intersect',
            'name_ur' => 'Intersect',
        ]);

        $productA = Product::query()->create([
            'code' => 'INT-A',
            'slug' => 'int-a',
            'status' => 'active',
            'name_en' => 'Product A',
            'name_ur' => 'Product A',
            'category_id' => $category->id,
            'sku' => 'INT-A',
            'stock_qty' => 5,
            'retail_price' => 10,
        ]);
        $productB = Product::query()->create([
            'code' => 'INT-B',
            'slug' => 'int-b',
            'status' => 'active',
            'name_en' => 'Product B',
            'name_ur' => 'Product B',
            'category_id' => $category->id,
            'sku' => 'INT-B',
            'stock_qty' => 5,
            'retail_price' => 10,
        ]);

        $productA->deliveryMethods()->sync([$shared->id, $onlyA->id]);
        $productB->deliveryMethods()->sync([$shared->id, $onlyB->id]);

        $available = $service->activeForCheckout([$productA->id, $productB->id]);

        $this->assertCount(1, $available);
        $this->assertSame($shared->id, $available->first()->id);

        $service->assertAvailableForProducts($shared, [$productA->id, $productB->id]);

        $this->expectException(ValidationException::class);
        $service->assertAvailableForProducts($onlyA, [$productA->id, $productB->id]);
    }

    public function test_checkout_persists_delivery_method_snapshot(): void
    {
        Queue::fake();

        $service = app(DeliveryMethodService::class);
        $method = $service->create($this->methodPayload([
            'name_en' => 'Freight Delivery',
            'name_ur' => 'فریٹ ڈیلیوری',
            'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
            'cod_allowed' => true,
            'cod_min_advance_percent' => 25,
            'full_advance_required' => false,
            'shipping_charge_mode' => ShippingChargeMode::CustomerPaysCarrier->value,
            'customer_notice_en' => 'Pay freight at the terminal',
            'customer_notice_ur' => 'ٹرمینل پر فریٹ ادا کریں',
        ]));

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-DEL-0001',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Delivery',
            'last_name' => 'Tester',
            'email' => 'delivery-tester@example.com',
            'phone' => '03001112233',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'delivery-test',
            'status' => 'active',
            'name_en' => 'Delivery Test',
            'name_ur' => 'Delivery Test',
        ]);

        $product = Product::query()->create([
            'code' => 'DEL-1',
            'slug' => 'del-1',
            'status' => 'active',
            'name_en' => 'Delivery Product',
            'name_ur' => 'Delivery Product',
            'category_id' => $category->id,
            'sku' => 'DEL-1',
            'stock_qty' => 20,
            'retail_price' => 100,
        ]);
        $product->deliveryMethods()->sync([$method->id]);

        $order = app(OnlineSaleOrderService::class)->createFromCheckout([
            'customer_id' => $customer->id,
            'checkout_token' => 'delivery-snapshot-token',
            'locale' => 'en',
            'delivery_method_id' => $method->id,
            'customer_payment_method' => 'cash',
            'ship_contact_name' => 'Delivery Tester',
            'ship_phone' => '03001112233',
            'ship_address' => '1 Test Street',
            'ship_city' => 'Lahore',
            'rows' => [[
                'product_id' => $product->id,
                'quantity' => '1',
                'unit_price' => '100',
                'price_tier_key' => 'retail',
            ]],
        ]);

        $this->assertSame($method->id, $order->delivery_method_id);
        $this->assertSame('Freight Delivery', $order->delivery_method_name_en);
        $this->assertSame('فریٹ ڈیلیوری', $order->delivery_method_name_ur);
        $this->assertSame(ShippingChargeMode::CustomerPaysCarrier, $order->shipping_charge_mode);
        $this->assertSame(25.0, (float) $order->cod_min_advance_percent);
        $this->assertFalse((bool) $order->full_advance_required);
        $this->assertSame('Pay freight at the terminal', $order->delivery_customer_notice_en);
        $this->assertSame('ٹرمینل پر فریٹ ادا کریں', $order->delivery_customer_notice_ur);

        $method->update(['name_en' => 'Renamed Freight']);
        $this->assertSame('Freight Delivery', $order->fresh()->delivery_method_name_en);
    }

    public function test_delivery_method_cannot_leave_zero_effective_payments(): void
    {
        $service = app(DeliveryMethodService::class);

        $this->expectException(ValidationException::class);

        $service->create($this->methodPayload([
            'allowed_payment_methods' => ['cash'],
            'cod_allowed' => false,
        ]));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function methodPayload(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Courier Delivery',
            'name_ur' => 'کورئیر ڈیلیوری',
            'description_en' => null,
            'description_ur' => null,
            'sort_order' => 10,
            'status' => DeliveryMethod::STATUS_ACTIVE,
            'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'customer_notice_en' => null,
            'customer_notice_ur' => null,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ], $overrides);
    }
}
