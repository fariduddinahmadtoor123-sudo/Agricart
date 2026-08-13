<?php

namespace Tests\Feature\Sales;

use App\Enums\ActivityEvent;
use App\Livewire\OnlineSaleOrders\Form;
use App\Livewire\OnlineSaleOrders\ListOnlineSaleOrders;
use App\Livewire\OnlineSaleOrders\ListOnlineSaleQuotations;
use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Livewire\Sales\ListSales;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Notifications\TopbarNotificationService;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineQuotationInvoiceSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_list_shows_non_converted_and_invoice_list_shows_converted(): void
    {
        $admin = $this->superAdmin();
        $quotation = $this->makeOrder(OnlineSaleOrder::STATUS_PENDING, 'ONL-Q-1');
        $invoice = $this->makeOrder(OnlineSaleOrder::STATUS_CONVERTED, 'ONL-I-1');

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleQuotations::class)
            ->assertSee('ONL-Q-1', false)
            ->assertDontSee('ONL-I-1', false);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleOrders::class)
            ->assertSee('ONL-I-1', false)
            ->assertDontSee('ONL-Q-1', false);

        $this->assertSame(OnlineSaleOrder::STATUS_PENDING, $quotation->order_status);
        $this->assertSame(OnlineSaleOrder::STATUS_CONVERTED, $invoice->order_status);
    }

    public function test_convert_moves_order_from_quotations_to_invoices(): void
    {
        $admin = $this->superAdmin();
        [$order, $product] = $this->makeConvertibleOrder();

        $product->update(['stock_qty' => 10]);

        app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), $admin);

        $order->refresh();
        $this->assertSame(OnlineSaleOrder::STATUS_CONVERTED, $order->order_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(250.0, (float) $order->amount_paid);
        $this->assertNotNull($order->queued_at);
        $this->assertSame(OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE, $order->queue_document);
        $this->assertNotNull($order->sale_id);

        $sale = Sale::query()->find($order->sale_id);
        $this->assertNotNull($sale);

        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::Converted->value,
            'user_id' => $admin->id,
            'subject_type' => OnlineSaleOrder::class,
            'subject_id' => $order->id,
            'subject_label' => $order->order_number,
        ]);

        $activity = ActivityLog::query()
            ->where('event', ActivityEvent::Converted->value)
            ->where('subject_id', $order->id)
            ->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString($order->order_number, (string) $activity->description);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleQuotations::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleOrders::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertSee($order->order_number, false)
            ->assertSee(__('online_sale_orders.actions.forward_short'), false);

        Livewire::actingAs($admin)
            ->test(ListSales::class)
            ->assertSee($sale->sale_number, false)
            ->assertSee($order->order_number, false)
            ->assertSee(__('pos.channels.online'), false);
    }

    public function test_convert_with_shipping_does_not_fail_paid_amount_check(): void
    {
        $admin = $this->superAdmin();
        [$order, $product] = $this->makeConvertibleOrder();
        $product->update(['stock_qty' => 10]);

        $order->update([
            'order_number' => 'ONL-CONV-SHIP',
            'subtotal' => 250,
            'shipping_amount' => 75,
            'total_amount' => 325,
            'amount_paid' => 325,
        ]);

        $sale = app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), $admin);

        $order->refresh();
        $this->assertSame(OnlineSaleOrder::STATUS_CONVERTED, $order->order_status);
        $this->assertSame(325.0, (float) $order->amount_paid);
        $this->assertSame(250.0, (float) $sale->total_amount);
        $this->assertSame(250.0, (float) $sale->amount_paid);
        $this->assertStringContainsString('shipping', (string) $sale->notes);
    }

    public function test_convert_keeps_online_unit_price_when_pos_retail_differs(): void
    {
        $admin = $this->superAdmin();
        [$order, $product] = $this->makeConvertibleOrder();

        // Storefront charged 203; POS retail card is lower — convert must keep 203.
        $product->update([
            'stock_qty' => 10,
            'retail_price' => 193,
        ]);
        $order->items()->first()?->update([
            'unit_price' => 203,
            'line_total' => 609,
            'quantity' => 3,
        ]);
        $order->update([
            'order_number' => 'ONL-CONV-PRICE',
            'subtotal' => 609,
            'shipping_amount' => 0,
            'total_amount' => 609,
            'amount_paid' => 609,
        ]);

        $sale = app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), $admin);

        $order->refresh();
        $this->assertSame(OnlineSaleOrder::STATUS_CONVERTED, $order->order_status);
        $this->assertSame(609.0, (float) $order->amount_paid);
        $this->assertSame(609.0, (float) $sale->total_amount);
        $this->assertSame(609.0, (float) $sale->amount_paid);
        $this->assertSame(203.0, (float) $sale->items()->first()->unit_price);
    }

    public function test_quotation_and_unqueued_invoice_forms_hide_fulfillment(): void
    {
        $admin = $this->superAdmin();
        $quotation = $this->makeOrder(OnlineSaleOrder::STATUS_PENDING, 'ONL-Q-FORM');
        $invoice = $this->makeOrder(OnlineSaleOrder::STATUS_CONVERTED, 'ONL-I-FORM');

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openForm', $quotation->id, 'view')
            ->assertSet('order_status', OnlineSaleOrder::STATUS_PENDING)
            ->assertDontSee('Assigned staff', false)
            ->assertSee(__('online_sale_orders.sections.customer_payment'), false);

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openForm', $invoice->id, 'view')
            ->assertSet('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->assertDontSee('Assigned staff', false)
            ->assertDontSee('Suppliers (by product category)', false);
    }

    public function test_queued_invoice_form_hides_fulfillment_and_suppliers(): void
    {
        $admin = $this->superAdmin();
        $invoice = $this->makeOrder(OnlineSaleOrder::STATUS_CONVERTED, 'ONL-I-QUEUED');
        $invoice->update(['queued_at' => now()]);

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openForm', $invoice->id, 'view')
            ->assertSet('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->assertDontSee('Assigned staff', false)
            ->assertDontSee('Suppliers (by product category)', false)
            ->assertDontSee(__('order_dispatch.sections.fulfillment'), false);
    }

    public function test_quotations_list_highlights_payment_notified_and_sorts_them_first(): void
    {
        $admin = $this->superAdmin();
        $waiting = $this->makeOrder(OnlineSaleOrder::STATUS_PENDING, 'ONL-WAIT-1');
        $notified = $this->makeOrder(OnlineSaleOrder::STATUS_PENDING, 'ONL-NOTE-1');
        $notified->update([
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
            'created_at' => now()->subHour(),
        ]);
        $waiting->update(['created_at' => now()]);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleQuotations::class)
            ->assertSee(__('online_sale_orders.list.receipt_notified'), false)
            ->assertSee(__('online_sale_orders.list.receipt_waiting'), false)
            ->assertSeeInOrder(['ONL-NOTE-1', 'ONL-WAIT-1']);
    }

    public function test_bell_points_to_quotations_page(): void
    {
        $this->actingAs($this->superAdmin());

        OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-BELL-1',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        $summary = app(TopbarNotificationService::class)->summary();
        $item = collect($summary['items'])->firstWhere('key', 'online_orders');

        $this->assertNotNull($item);
        $this->assertStringContainsString('online-quotations', $item['url']);
    }

    private function makeOrder(string $status, string $number): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-' . substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Quote',
            'last_name' => 'Buyer',
            'email' => strtolower($number) . '@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => $status,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => $status === OnlineSaleOrder::STATUS_CONVERTED ? 'paid' : 'unpaid',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => $status === OnlineSaleOrder::STATUS_CONVERTED ? 100 : 0,
            'ship_contact_name' => 'Quote Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => $status === OnlineSaleOrder::STATUS_CONVERTED ? now() : null,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Sample',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return $order;
    }

    /**
     * @return array{0: OnlineSaleOrder, 1: Product}
     */
    private function makeConvertibleOrder(): array
    {
        $category = Category::query()->create([
            'slug' => 'quote-cat',
            'status' => 'active',
            'name_en' => 'Quote Cat',
            'name_ur' => 'Quote Cat',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'quote-product',
            'code' => 'QP-1',
            'status' => 'active',
            'name_en' => 'Quote Product',
            'name_ur' => 'Quote Product',
            'stock_qty' => 5,
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-CONV1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Convert',
            'last_name' => 'Buyer',
            'email' => 'convert-buyer@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-CONV-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PAYMENT_RECEIVED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 250,
            'total_amount' => 250,
            'amount_paid' => 250,
            'ship_contact_name' => 'Convert Buyer',
            'ship_phone' => '03001112233',
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Quote Product',
            'quantity' => 1,
            'unit_price' => 250,
            'line_total' => 250,
        ]);

        return [$order, $product];
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
            'name' => 'Admin Quotes',
            'email' => 'admin-quotes@test.test',
        ]);
    }
}
