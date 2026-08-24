<?php

namespace Tests\Feature\Finance;

use App\Models\CashBookTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnlineOrderShippingCashBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_convert_posts_merchandise_and_shipping_cash_book_once(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->makeConvertibleCashOrder(subtotal: 250, shipping: 75);

        $sale = app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), $admin);

        $this->assertSame(250.0, (float) $sale->amount_paid);
        $this->assertSame(1, CashBookTransaction::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, CashBookTransaction::query()
            ->where('source_key', 'online-sale-order-shipping:'.$order->id)
            ->count());
        $this->assertSame(75.0, (float) CashBookTransaction::query()
            ->where('source_key', 'online-sale-order-shipping:'.$order->id)
            ->value('amount'));
        $this->assertSame(325.0, app(\App\Services\Finance\CashBookService::class)->balance());

        app(\App\Services\Finance\CashBookService::class)
            ->recordOnlineOrderShippingIfApplicable($order->fresh(), 250.0);

        $this->assertSame(1, CashBookTransaction::query()
            ->where('source_key', 'online-sale-order-shipping:'.$order->id)
            ->count());
    }

    public function test_bank_order_shipping_helper_skips_cash_book(): void
    {
        [$order] = $this->makeConvertibleCashOrder(subtotal: 250, shipping: 75);
        $order->update(['payment_method' => 'bank_transfer']);

        app(\App\Services\Finance\CashBookService::class)
            ->recordOnlineOrderShippingIfApplicable($order->fresh(), 250.0);

        $this->assertSame(0, CashBookTransaction::query()->count());
    }

    /**
     * @return array{0: OnlineSaleOrder}
     */
    private function makeConvertibleCashOrder(float $subtotal, float $shipping): array
    {
        $category = Category::query()->create([
            'slug' => 'ship-cat',
            'status' => 'active',
            'name_en' => 'Ship Cat',
            'name_ur' => 'Ship Cat',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'ship-product',
            'code' => 'SHP-1',
            'status' => 'active',
            'name_en' => 'Ship Product',
            'name_ur' => 'Ship Product',
            'stock_qty' => 20,
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-SHIP1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Ship',
            'last_name' => 'Buyer',
            'email' => 'ship-buyer@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $total = round($subtotal + $shipping, 2);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-SHIP-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PAYMENT_RECEIVED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => $subtotal,
            'shipping_amount' => $shipping,
            'total_amount' => $total,
            'amount_paid' => $total,
            'ship_contact_name' => 'Ship Buyer',
            'ship_phone' => '03001112233',
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Ship Product',
            'quantity' => 1,
            'unit_price' => $subtotal,
            'line_total' => $subtotal,
        ]);

        return [$order];
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
