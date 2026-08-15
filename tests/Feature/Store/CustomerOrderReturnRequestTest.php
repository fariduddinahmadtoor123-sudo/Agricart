<?php

namespace Tests\Feature\Store;

use App\Livewire\SaleReturns\ListSaleReturns;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineOrderReturnRequest;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerOrderReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_order_shows_return_button_and_form(): void
    {
        [$customer, $order] = $this->deliveredOrderWithInvoice();

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee(__('agricart.customer.returns.request_return'), false);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.return', $order->order_number))
            ->assertOk()
            ->assertSee(__('agricart.customer.returns.submit'), false);
    }

    public function test_undelivered_order_cannot_submit_a_return(): void
    {
        [$customer, $order] = $this->deliveredOrderWithInvoice();
        $order->forceFill(['fulfillment_status' => OnlineSaleOrder::FULFILLMENT_TRANSIT])->save();

        $item = $order->items->first();

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.orders.return.store', $order->order_number), [
                'items' => [
                    $item->id => [
                        'quantity' => '1',
                        'reason' => 'damaged',
                    ],
                ],
            ])
            ->assertSessionHasErrors('order');

        $this->assertDatabaseCount('online_order_return_requests', 0);
    }

    public function test_customer_can_submit_and_staff_can_approve_creating_a_sale_return(): void
    {
        [$customer, $order, $product] = $this->deliveredOrderWithInvoice(stock: 4);
        $item = $order->items->first();

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.orders.return.store', $order->order_number), [
                'customer_note' => 'Chain links were broken on arrival.',
                'items' => [
                    $item->id => [
                        'quantity' => '1',
                        'reason' => 'damaged',
                    ],
                ],
            ])
            ->assertRedirect(route('store.customer.orders.return', $order->order_number));

        $request = OnlineOrderReturnRequest::query()->firstOrFail();
        $this->assertSame(OnlineOrderReturnRequest::STATUS_PENDING, $request->status);
        $this->assertSame('Chain links were broken on arrival.', $request->customer_note);

        $admin = $this->superAdmin();
        $this->actingAs($admin);

        Livewire::test(ListSaleReturns::class)
            ->call('approveRequest', $request->id)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(OnlineOrderReturnRequest::STATUS_APPROVED, $request->status);
        $this->assertNotNull($request->sale_return_id);
        $this->assertSame(1, SaleReturn::query()->count());
        $this->assertSame(5.0, (float) $product->fresh()->stock_qty);
    }

    /**
     * @return array{0: Customer, 1: OnlineSaleOrder, 2: Product}
     */
    private function deliveredOrderWithInvoice(float $stock = 4): array
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-RET-00001',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Return',
            'last_name' => 'Buyer',
            'email' => 'return-buyer@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'return-cat',
            'code' => 'CAT-RET',
            'status' => 'active',
            'name_en' => 'Return category',
            'name_ur' => 'واپسی زمرہ',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-RET',
            'sku' => 'SKU-RET',
            'name_en' => 'Drive chain',
            'name_ur' => 'ڈرائیو چین',
            'slug' => 'drive-chain-return',
            'full_description_en' => 'Test chain',
            'stock_qty' => $stock,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'SALE-RET-1',
            'customer_id' => $customer->id,
            'sale_date' => today(),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 100,
            'amount_paid' => 100,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-RET-00001',
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_DELIVERED,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'ship_contact_name' => $customer->displayName(),
            'ship_phone' => $customer->phone,
            'converted_at' => now(),
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return [$customer, $order->fresh('items'), $product];
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
            'name' => 'Returns Admin',
            'email' => 'returns-admin@test.test',
            'phone_numbers' => [[
                'contact_person' => 'Admin',
                'contact_person_ur' => 'Admin',
                'phone_number' => '03005556677',
            ]],
        ]);
    }
}
