<?php

namespace Tests\Feature\Sales;

use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaidAmountAgainstTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_paid_accepts_exact_total(): void
    {
        $service = app(SaleService::class);

        $this->assertSame(1749.6, $service->normalizePaidAgainstTotal(1749.6, 1749.6));
    }

    public function test_normalize_paid_accepts_display_rounded_match_when_decimals_off(): void
    {
        CompanyProfile::current()->forceFill([
            'price_use_decimals' => false,
            'price_decimal_places' => 2,
        ])->save();

        $service = app(SaleService::class);

        // UI shows both as 1750 when whole-rupee display is on.
        $this->assertSame(1749.6, $service->normalizePaidAgainstTotal(1750.0, 1749.6));
    }

    public function test_normalize_paid_rejects_clear_overpayment(): void
    {
        CompanyProfile::current()->forceFill([
            'price_use_decimals' => false,
            'price_decimal_places' => 2,
        ])->save();

        $this->expectException(ValidationException::class);

        app(SaleService::class)->normalizePaidAgainstTotal(1800.0, 1749.6);
    }

    public function test_online_order_partial_payment_accepts_displayed_total(): void
    {
        CompanyProfile::current()->forceFill([
            'price_use_decimals' => false,
            'price_decimal_places' => 2,
        ])->save();

        $order = $this->makeOrder(1749.60);

        app(OnlineSaleOrderService::class)->updateAdmin($order, [
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'payment_status' => 'partial',
            'payment_method' => 'cash',
            'amount_paid' => '1750',
        ]);

        $order->refresh();
        $this->assertSame(1749.6, (float) $order->amount_paid);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_numeric_strips_currency_noise(): void
    {
        $service = app(SaleService::class);

        $this->assertSame(1750.0, $service->numeric('Rs 1,750'));
        $this->assertSame(1750.5, $service->numeric('PKR 1,750.50'));
    }

    private function makeOrder(float $total): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PAID-1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Paid',
            'last_name' => 'Tester',
            'email' => 'paid-amount@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'paid-amount-cat',
            'code' => 'CAT-PAID',
            'status' => 'active',
            'name_en' => 'Paid Amount',
            'name_ur' => 'Paid Amount',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'paid-amount-product',
            'code' => 'PAID-1',
            'sku' => 'PAID-1',
            'status' => 'active',
            'name_en' => 'Paid Product',
            'name_ur' => 'Paid Product',
            'stock_qty' => 20,
            'retail_price' => $total,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'OSO-PAID-1',
            'checkout_token' => 'paid-amount-token',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'subtotal' => $total,
            'shipping_amount' => 0,
            'total_amount' => $total,
            'amount_paid' => 0,
            'ship_contact_name' => 'Paid Tester',
            'ship_phone' => '03001112233',
            'ship_address' => '1 Test Street',
            'ship_city' => 'Lahore',
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_en' => 'Paid Product',
            'quantity' => 1,
            'unit_price' => $total,
            'line_total' => $total,
            'sort_order' => 0,
        ]);

        return $order;
    }
}
