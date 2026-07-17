<?php

namespace Tests\Feature\Reports;

use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Reports\SeasonalPurchasesReportService;
use App\Services\Reports\SeasonalSalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonalReportServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seasonal_sales_groups_products_by_calendar_quarter_and_channel(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');
        $product = $this->makeProduct();
        $pos = $this->makeSale('SALE-POS', '2026-07-10', 200);
        $online = $this->makeSale('SALE-WEB', '2026-07-11', 100);
        $this->makeSaleItem($pos, $product, 2, 200);
        $this->makeSaleItem($online, $product, 1, 100);

        OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-1',
            'sale_id' => $online->id,
            'order_date' => '2026-07-11',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
        ]);

        $service = app(SeasonalSalesReportService::class);
        $all = $service->build(periodFilter: 'quarter');
        $onlineOnly = $service->build(periodFilter: 'quarter', channelFilter: 'online');

        $this->assertCount(1, $all['rows']);
        $this->assertSame('2026 Q3 (Jul–Sep)', $all['rows'][0]['season']);
        $this->assertSame('3.000', $all['rows'][0]['qty_sold']);
        $this->assertSame('300.00 PKR', $all['rows'][0]['net_sales']);
        $this->assertSame('1.000', $onlineOnly['rows'][0]['qty_sold']);
        $this->assertSame(['Season', 'Product', 'Qty sold'], $service->csvMatrix($all['rows'], ['season', 'product', 'qty_sold'])[0]);
    }

    public function test_seasonal_purchases_groups_products_by_quarter_and_supplier(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $supplier = $this->makeSupplier();
        $purchase = Purchase::query()->create([
            'purchase_number' => 'PUR-1',
            'supplier_id' => $supplier->id,
            'purchaser_id' => $user->id,
            'created_by' => $user->id,
            'purchase_date' => '2026-07-12',
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'payment_status' => Purchase::PAYMENT_PAID,
            'subtotal' => 500,
            'amount_paid' => 500,
            'total_amount' => 500,
            'balance_due' => 0,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'product_name_ur' => $product->name_ur,
            'purchase_qty' => 5,
            'current_rate' => 100,
            'line_total' => 500,
        ]);

        $service = app(SeasonalPurchasesReportService::class);
        $report = $service->build(periodFilter: 'quarter', supplierFilter: 'active');

        $this->assertCount(1, $report['rows']);
        $this->assertSame('5.000', $report['rows'][0]['qty_purchased']);
        $this->assertSame('500.00 PKR', $report['rows'][0]['purchase_value']);
        $this->assertSame('Active Supplier', $report['rows'][0]['supplier']);
    }

    private function makeProduct(): Product
    {
        return Product::query()->create([
            'code' => 'SEED-1',
            'slug' => 'seed-1',
            'status' => 'active',
            'name_en' => 'Wheat Seed',
            'name_ur' => 'گندم بیج',
            'sku' => 'SEED-1',
            'stock_qty' => 100,
        ]);
    }

    private function makeSale(string $number, string $date, float $total): Sale
    {
        return Sale::query()->create([
            'sale_number' => $number,
            'sale_date' => $date,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => $total,
            'amount_paid' => $total,
            'status' => Sale::STATUS_COMPLETED,
        ]);
    }

    private function makeSaleItem(Sale $sale, Product $product, float $quantity, float $total): SaleItem
    {
        return SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'product_name_ur' => $product->name_ur,
            'unit_label' => 'bag',
            'quantity' => $quantity,
            'unit_price' => $total / $quantity,
            'line_total' => $total,
        ]);
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => 'SUP-00001',
            'status' => 'active',
            'business_name_en' => 'Active Supplier',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'city_en' => 'Lahore',
            'city_ur' => 'لاہور',
            'address_en' => 'Address',
            'contacts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'ai_status' => 'none',
        ]);
    }
}
