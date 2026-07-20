<?php

namespace Tests\Feature;

use App\Jobs\SendOnlineOrderPlacedNotification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\ReorderQueueItem;
use App\Models\Sale;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use App\Enums\ShippingChargeMode;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Purchasing\ReorderService;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Staff\StaffAttendanceService;
use App\Services\Store\StoreCartService;
use App\Services\Store\OnlineStoreOrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseThreeOrderInventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('agricart.inventory.allow_overselling', false);
    }

    public function test_invalid_cart_change_preserves_session_state(): void
    {
        $product = $this->product('CART', stock: 3);
        $cart = app(StoreCartService::class);
        $cart->add($product->id, 2);

        try {
            $cart->add($product->id, 2);
            $this->fail('An over-stock cart quantity was accepted.');
        } catch (ValidationException) {
            $this->assertSame(2, $cart->totalQuantity());
            $this->assertSame('2', $cart->checkoutRows()[0]['quantity']);
        }
    }

    public function test_checkout_token_is_idempotent_and_does_not_deduct_stock(): void
    {
        Queue::fake();
        $customer = $this->customer();
        $product = $this->product('CHECKOUT', stock: 10);
        $service = app(OnlineSaleOrderService::class);
        $payload = $this->checkoutPayload($customer, $product, 'same-token');

        $first = $service->createFromCheckout($payload);
        $second = $service->createFromCheckout($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OnlineSaleOrder::query()->count());
        $this->assertSame(10.0, (float) $product->fresh()->stock_qty);
        Queue::assertPushed(SendOnlineOrderPlacedNotification::class, 1);
    }

    public function test_stale_checkout_token_with_changed_cart_is_rejected(): void
    {
        Queue::fake();
        $customer = $this->customer();
        $productA = $this->product('TOKEN-A', stock: 10);
        $productB = $this->product('TOKEN-B', stock: 10);
        $service = app(OnlineSaleOrderService::class);
        $token = 'stale-cart-token';

        $service->createFromCheckout($this->checkoutPayload($customer, $productA, $token));

        $this->expectException(ValidationException::class);
        $service->createFromCheckout($this->checkoutPayload($customer, $productB, $token));
    }

    public function test_duplicate_conversion_creates_one_sale_and_deducts_stock_once(): void
    {
        Queue::fake();
        $customer = $this->customer();
        $product = $this->product('CONVERT', stock: 10);
        $actor = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(OnlineSaleOrderService::class);
        $order = $service->createFromCheckout($this->checkoutPayload($customer, $product, 'conversion-token'));

        $first = $service->convertToSale($order, $actor);
        $second = $service->convertToSale($order->fresh(), $actor);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Sale::query()->count());
        $this->assertSame(8.0, (float) $product->fresh()->stock_qty);
        $this->assertNotNull($order->fresh()->converted_at);
        $this->assertSame($actor->id, $order->fresh()->converted_by_id);
    }

    public function test_document_counter_allocates_unique_monotonic_values(): void
    {
        $counter = app(DocumentCounterService::class);

        $this->assertSame('DOC-001', $counter->next('test.documents', 'DOC-', 3));
        $this->assertSame('DOC-002', $counter->next('test.documents', 'DOC-', 3));
        $this->assertDatabaseHas('document_counters', ['key' => 'test.documents', 'value' => 2]);
    }

    public function test_notification_job_is_after_commit_and_idempotency_guarded(): void
    {
        $job = new SendOnlineOrderPlacedNotification(123);

        $this->assertTrue($job->afterCommit);
        $this->assertSame('online-order-placed:123', $job->uniqueId());
    }

    public function test_notification_timestamp_is_only_set_after_successful_delivery(): void
    {
        $customer = $this->customer();
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-NOTIFY-1',
            'customer_id' => $customer->id,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'subtotal' => 10,
            'total_amount' => 10,
            'amount_paid' => 0,
        ]);
        $failing = $this->mock(OnlineStoreOrderNotifier::class);
        $failing->shouldReceive('orderPlaced')->once()->andThrow(new \RuntimeException('delivery failed'));

        try {
            (new SendOnlineOrderPlacedNotification($order->id))->handle($failing);
            $this->fail('A failed notification delivery did not throw.');
        } catch (\RuntimeException) {
            $this->assertNull($order->fresh()->notification_dispatched_at);
        }

        $successful = $this->mock(OnlineStoreOrderNotifier::class);
        $successful->shouldReceive('orderPlaced')->once();
        (new SendOnlineOrderPlacedNotification($order->id))->handle($successful);

        $this->assertNotNull($order->fresh()->notification_dispatched_at);
    }

    public function test_attendance_defaulting_and_reorder_queue_are_idempotent(): void
    {
        User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $attendance = app(StaffAttendanceService::class);

        $firstCreated = $attendance->ensureDefaultAbsencesForDate(today());
        $secondCreated = $attendance->ensureDefaultAbsencesForDate(today());

        $this->assertSame(1, $firstCreated);
        $this->assertSame(0, $secondCreated);
        $this->assertSame(1, StaffAttendanceRecord::query()->count());

        $product = $this->product('REORDER', stock: 0, required: '5');
        $reorders = app(ReorderService::class);

        $this->assertNotNull($reorders->sendToQueue([$product->id]));
        $this->assertNull($reorders->sendToQueue([$product->id]));
        $this->assertSame(1, ReorderQueueItem::query()->where('status', ReorderQueueItem::STATUS_PENDING)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Customer $customer, Product $product, string $token): array
    {
        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Phase Courier '.$token,
            'name_ur' => 'Phase Courier '.$token,
            'sort_order' => 1,
            'status' => 'active',
            'allowed_payment_methods' => ['cash', 'bank_transfer'],
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);

        $product->deliveryMethods()->sync([$method->id]);

        return [
            'customer_id' => $customer->id,
            'checkout_token' => $token,
            'locale' => 'en',
            'delivery_method_id' => $method->id,
            'customer_payment_method' => 'cash',
            'rows' => [[
                'product_id' => $product->id,
                'quantity' => '2',
                'unit_price' => '100',
                'price_tier_key' => 'retail',
            ]],
        ];
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.fake()->unique()->numerify('#####'),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Three',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '03001234567',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }

    private function product(string $suffix, float $stock, ?string $required = null): Product
    {
        $category = Category::query()->create([
            'slug' => 'phase-three-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Phase Three',
            'name_ur' => 'Phase Three',
        ]);

        return Product::query()->create([
            'code' => 'P3-'.$suffix,
            'slug' => 'p3-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Phase Three Product',
            'name_ur' => 'Phase Three Product',
            'category_id' => $category->id,
            'sku' => 'P3-'.$suffix,
            'stock_qty' => $stock,
            'required_qty' => $required,
            'retail_price' => 100,
        ]);
    }
}
