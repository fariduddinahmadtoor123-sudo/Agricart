<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Services\Reports\SalesSummaryReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalesSummaryReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_summary_period_channel_and_group_filters(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');

        $customerA = $this->makeCustomer('sales-a@test.test', 'Ali', 'Farmer');
        $customerB = $this->makeCustomer('sales-b@test.test', 'Bilal', 'Trader');

        $category = Category::query()->create([
            'slug' => 'seeds',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $product = Product::query()->create([
            'code' => 'SEED-1',
            'slug' => 'seed-1',
            'status' => 'active',
            'name_en' => 'Wheat Seed',
            'name_ur' => 'گندم بیج',
            'category_id' => $category->id,
            'sku' => 'SEED-1',
            'stock_qty' => 100,
        ]);

        $posSale = $this->makeSale('SALE-POS', $customerA->id, '2026-07-17', 100);
        $this->makeSaleItem($posSale, $product, 100);

        $onlineSale = $this->makeSale('SALE-WEB', $customerB->id, '2026-07-17', 50);
        $this->makeSaleItem($onlineSale, $product, 50);

        OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-1',
            'customer_id' => $customerB->id,
            'sale_id' => $onlineSale->id,
            'order_date' => '2026-07-17',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'payment_status' => 'paid',
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 50,
        ]);

        $this->makeSale('SALE-OLD', $customerA->id, '2026-07-16', 200);

        SaleReturn::query()->create([
            'return_number' => 'RET-1',
            'sale_id' => $posSale->id,
            'customer_id' => $customerA->id,
            'return_date' => '2026-07-17',
            'refund_subtotal' => 20,
            'refund_total' => 20,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $service = app(SalesSummaryReportService::class);

        $today = $service->build(periodFilter: 'today', groupBy: 'day');
        $this->assertSame(2, $today['kpis']['invoices']);
        $this->assertSame('150.00', $today['kpis']['gross_sales']);
        $this->assertSame('20.00', $today['kpis']['returns']);
        $this->assertSame('130.00', $today['kpis']['net_sales']);
        $this->assertSame('75.00', $today['kpis']['avg_ticket']);
        $this->assertCount(1, $today['rows']);
        $this->assertSame('2026-07-17', $today['rows'][0]['group']);
        $this->assertSame('130.00', $today['rows'][0]['net_sales']);

        $posOnly = $service->build(periodFilter: 'today', channelFilter: 'pos', groupBy: 'day');
        $this->assertSame(1, $posOnly['kpis']['invoices']);
        $this->assertSame('100.00', $posOnly['kpis']['gross_sales']);
        $this->assertSame('20.00', $posOnly['kpis']['returns']);
        $this->assertSame('80.00', $posOnly['kpis']['net_sales']);

        $onlineOnly = $service->build(periodFilter: 'today', channelFilter: 'online', groupBy: 'day');
        $this->assertSame(1, $onlineOnly['kpis']['invoices']);
        $this->assertSame('50.00', $onlineOnly['kpis']['gross_sales']);
        $this->assertSame('0.00', $onlineOnly['kpis']['returns']);

        $byCustomer = $service->build(periodFilter: 'today', groupBy: 'customer');
        $this->assertCount(2, $byCustomer['rows']);
        $this->assertSame('100.00', $byCustomer['rows'][0]['gross_sales']);

        $byCategory = $service->build(periodFilter: 'today', groupBy: 'category');
        $this->assertCount(1, $byCategory['rows']);
        $this->assertSame('Seeds', $byCategory['rows'][0]['group']);
        $this->assertSame('150.00', $byCategory['rows'][0]['gross_sales']);

        $byProduct = $service->build(periodFilter: 'today', groupBy: 'product');
        $this->assertCount(1, $byProduct['rows']);
        $this->assertSame('Wheat Seed', $byProduct['rows'][0]['group']);

        $csv = $service->csvMatrix($today['rows'], ['group', 'invoices', 'net_sales']);
        $this->assertSame(['Group', 'Invoices', 'Net sales'], $csv[0]);
        $this->assertSame('2026-07-17', $csv[1][0]);
    }

    private function makeCustomer(string $email, string $firstName, string $lastName): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.strtoupper(substr(md5($email), 0, 8)),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $firstName.' '.$lastName,
            'name_en' => $firstName.' '.$lastName,
            'name_ur' => $firstName.' '.$lastName,
            'email' => $email,
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function makeSale(string $number, int $customerId, string $date, float $total): Sale
    {
        return Sale::query()->create([
            'sale_number' => $number,
            'customer_id' => $customerId,
            'sale_date' => $date,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => $total,
            'amount_paid' => $total,
            'status' => Sale::STATUS_COMPLETED,
        ]);
    }

    private function makeSaleItem(Sale $sale, Product $product, float $lineTotal): SaleItem
    {
        return SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'product_name_ur' => $product->name_ur,
            'unit_label' => 'bag',
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
        ]);
    }
}
