<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Supplier;
use App\Services\Reports\ProfitLossReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfitLossReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_loss_lists_invoices_with_profit_and_loss(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');

        $customer = $this->makeCustomer();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-PL01',
            'status' => 'active',
            'business_name_en' => 'PL Supplier',
            'business_name_ur' => 'PL Supplier',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Contact'],
            ],
        ]);

        $category = Category::query()->create([
            'slug' => 'seeds',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $product = Product::query()->create([
            'code' => 'SEED-PL',
            'slug' => 'seed-pl',
            'status' => 'active',
            'name_en' => 'Wheat Seed',
            'name_ur' => 'گندم',
            'category_id' => $category->id,
            'sku' => 'SEED-PL',
            'stock_qty' => 100,
        ]);

        $purchase = Purchase::query()->create([
            'purchase_number' => 'PO-PL',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-10',
            'payment_status' => Purchase::PAYMENT_PAID,
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'subtotal' => 60,
            'total_amount' => 60,
            'amount_paid' => 60,
            'balance_due' => 0,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Wheat Seed',
            'purchase_qty' => '10',
            'current_rate' => '5',
            'landing_cost' => '1',
            'cost_price' => '6',
            'line_total' => '50',
        ]);

        $profitSale = Sale::query()->create([
            'sale_number' => 'SALE-PROFIT',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-17',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 100,
            'amount_paid' => 100,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $profitItem = SaleItem::query()->create([
            'sale_id' => $profitSale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Wheat Seed',
            'quantity' => 10,
            'unit_price' => 10,
            'line_total' => 100,
        ]);

        $return = SaleReturn::query()->create([
            'return_number' => 'RET-PL',
            'sale_id' => $profitSale->id,
            'customer_id' => $customer->id,
            'return_date' => '2026-07-17',
            'refund_subtotal' => 20,
            'refund_total' => 20,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        SaleReturnItem::query()->create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $profitItem->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Wheat Seed',
            'sold_quantity' => 10,
            'unit_price' => 10,
            'return_quantity' => 2,
            'return_amount' => 20,
        ]);

        $lossSale = Sale::query()->create([
            'sale_number' => 'SALE-LOSS',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-17',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 40,
            'amount_paid' => 40,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::query()->create([
            'sale_id' => $lossSale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Wheat Seed',
            'quantity' => 10,
            'unit_price' => 4,
            'line_total' => 40,
        ]);

        Sale::query()->create([
            'sale_number' => 'SALE-OLD',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-16',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 500,
            'amount_paid' => 500,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $service = app(ProfitLossReportService::class);
        $today = $service->build(periodFilter: 'today');

        // SALE-PROFIT: net 80, cogs 48, profit 32
        // SALE-LOSS: net 40, cogs 60, profit -20
        $this->assertSame(2, $today['kpis']['invoices']);
        $this->assertSame('120.00', $today['kpis']['net_sales']);
        $this->assertSame('108.00', $today['kpis']['cogs']);
        $this->assertSame('12.00', $today['kpis']['gross_profit']);
        $this->assertSame(1, $today['kpis']['profit_count']);
        $this->assertSame(1, $today['kpis']['loss_count']);

        $byInvoice = collect($today['rows'])->keyBy('invoice');
        $this->assertSame('profit', $byInvoice['SALE-PROFIT']['result']);
        $this->assertSame('32.00', $byInvoice['SALE-PROFIT']['profit']);
        $this->assertSame('loss', $byInvoice['SALE-LOSS']['result']);
        $this->assertSame('-20.00', $byInvoice['SALE-LOSS']['profit']);

        $lossesOnly = $service->build(periodFilter: 'today', resultFilter: 'loss');
        $this->assertCount(1, $lossesOnly['rows']);
        $this->assertSame('SALE-LOSS', $lossesOnly['rows'][0]['invoice']);

        $search = $service->build(search: 'SALE-PROFIT', periodFilter: 'today');
        $this->assertCount(1, $search['rows']);
        $this->assertSame('SALE-PROFIT', $search['rows'][0]['invoice']);

        $csv = $service->csvMatrix($today['rows'], ['invoice', 'profit', 'result']);
        $this->assertSame(['Invoice', 'Profit / Loss', 'Result'], $csv[0]);
    }

    public function test_profit_loss_operating_expenses_include_expense_payments(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');

        $customer = $this->makeCustomer();

        Sale::query()->create([
            'sale_number' => 'SALE-EXP-KPI',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-17',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 100,
            'amount_paid' => 100,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $category = \App\Models\ExpenseCategory::query()->create([
            'code' => 'EXC-PL01',
            'name_en' => 'Utilities',
            'status' => \App\Models\ExpenseCategory::STATUS_ACTIVE,
            'kind' => \App\Models\ExpenseCategory::KIND_OPERATING,
        ]);

        $head = \App\Models\Expense::query()->create([
            'expense_number' => 'EXP-PL01',
            'expense_category_id' => $category->id,
            'status' => \App\Models\Expense::STATUS_ACTIVE,
            'name_en' => 'Office electricity',
        ]);

        \App\Models\ExpensePayment::query()->create([
            'payment_number' => 'EXPAY-PL01',
            'expense_id' => $head->id,
            'amount' => 25,
            'payment_date' => '2026-07-17',
            'source_wallet' => \App\Models\ExpensePayment::WALLET_CASH,
        ]);

        \App\Models\FundTransfer::query()->create([
            'transfer_number' => 'FT-PL01',
            'transaction_type' => \App\Models\FundTransfer::TYPE_EXPENSE_PAYMENT,
            'source_wallet' => \App\Models\FundTransfer::WALLET_CASH,
            'amount' => 10,
            'transaction_date' => '2026-07-17',
            'transaction_time' => '09:00:00',
        ]);

        $report = app(ProfitLossReportService::class)->build(periodFilter: 'today');

        // Gross profit on a sale with no items is 100; expenses 25 + 10 = 35; net 65.
        $this->assertSame('100.00', $report['kpis']['gross_profit']);
        $this->assertSame('35.00', $report['kpis']['expenses']);
        $this->assertSame('65.00', $report['kpis']['net_profit']);
    }

    private function makeCustomer(): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-PL01',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Profit',
            'last_name' => 'Test',
            'display_name' => 'Profit Test',
            'name_en' => 'Profit Test',
            'name_ur' => 'Profit Test',
            'email' => 'profit@test.test',
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
}
