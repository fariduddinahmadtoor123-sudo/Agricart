<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StaffAdvanceTransaction;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffSalaryAssignment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\CustomerService;
use App\Services\Sales\HistoricalCostingService;
use App\Services\Reports\ProfitLossReportService;
use App\Services\Reports\StockReportService;
use App\Services\Purchasing\ReorderService;
use App\Services\Staff\StaffPayrollService;
use App\Services\Staff\StaffSalaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFourHistoricalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_uses_period_end_salary_and_advance_cutoffs(): void
    {
        $user = $this->user('staff@example.test');
        StaffSalaryAssignment::query()->create([
            'user_id' => $user->id, 'monthly_gross' => 3100, 'currency' => 'PKR',
            'effective_from' => '2026-01-01', 'effective_to' => '2026-01-31', 'created_by' => $user->id,
        ]);
        StaffSalaryAssignment::query()->create([
            'user_id' => $user->id, 'monthly_gross' => 6200, 'currency' => 'PKR',
            'effective_from' => '2026-02-01', 'effective_to' => null, 'created_by' => $user->id,
        ]);
        StaffAttendanceRecord::query()->create([
            'user_id' => $user->id, 'attendance_date' => '2026-01-10',
            'status' => StaffAttendanceRecord::STATUS_PRESENT, 'is_paid_day' => true,
        ]);
        StaffAdvanceTransaction::query()->create([
            'user_id' => $user->id, 'type' => StaffAdvanceTransaction::TYPE_ADVANCE,
            'amount' => 100, 'transaction_date' => '2026-01-20', 'created_by' => $user->id,
        ]);
        StaffAdvanceTransaction::query()->create([
            'user_id' => $user->id, 'type' => StaffAdvanceTransaction::TYPE_REPAYMENT,
            'amount' => 100, 'transaction_date' => '2026-02-01', 'created_by' => $user->id,
        ]);

        $summary = app(StaffPayrollService::class)->summaryForUser($user, Carbon::parse('2026-01-01'));

        $this->assertSame(3100.0, $summary['monthly_gross']);
        $this->assertSame(100.0, $summary['advance_balance']);
        $this->assertSame('2026-01-31', $summary['period_to']);
    }

    public function test_salary_update_allows_adjacency_and_rejects_overlap(): void
    {
        $user = $this->user('timeline@example.test');
        $first = StaffSalaryAssignment::query()->create([
            'user_id' => $user->id, 'monthly_gross' => 1000, 'currency' => 'PKR',
            'effective_from' => '2026-01-01', 'effective_to' => '2026-01-31', 'created_by' => $user->id,
        ]);
        $second = StaffSalaryAssignment::query()->create([
            'user_id' => $user->id, 'monthly_gross' => 2000, 'currency' => 'PKR',
            'effective_from' => '2026-02-01', 'effective_to' => null, 'created_by' => $user->id,
        ]);
        $service = app(StaffSalaryService::class);

        $service->updateAssignment($second, ['monthly_gross' => 2100, 'effective_from' => '2026-02-01'], $user);
        $this->expectException(ValidationException::class);
        $service->updateAssignment($second->fresh(), ['monthly_gross' => 2100, 'effective_from' => '2026-01-31'], $user);
    }

    public function test_cost_and_return_snapshots_are_immutable_after_purchase_edit(): void
    {
        [$product, $purchaseItem] = $this->productWithPurchase();
        $sale = Sale::query()->create([
            'sale_number' => 'SAL-P4', 'sale_date' => '2026-07-10', 'payment_status' => 'paid',
            'payment_method' => 'cash', 'total_amount' => 100, 'amount_paid' => 100, 'status' => Sale::STATUS_COMPLETED,
        ]);
        $item = SaleItem::query()->create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'product_name_en' => 'P4 Product',
            'quantity' => 10, 'unit_price' => 10, 'line_total' => 100,
        ]);
        $return = SaleReturn::query()->create([
            'return_number' => 'RET-P4', 'sale_id' => $sale->id, 'return_date' => '2026-07-11',
            'refund_subtotal' => 20, 'refund_total' => 20, 'payment_status' => 'unpaid', 'payment_method' => 'cash',
        ]);
        $returnItem = SaleReturnItem::query()->create([
            'sale_return_id' => $return->id, 'sale_item_id' => $item->id, 'product_id' => $product->id,
            'product_name_en' => 'P4 Product', 'sold_quantity' => 10, 'unit_price' => 10,
            'return_quantity' => 2, 'return_amount' => 20,
        ]);

        $this->assertSame('5.0000', (string) $item->unit_cost_snapshot);
        $this->assertSame('10.0000', (string) $returnItem->cogs_reversal_snapshot);
        $before = app(ProfitLossReportService::class)->build(search: 'SAL-P4')['rows'][0]['profit'];
        $purchaseItem->update(['cost_price' => 99]);
        $after = app(ProfitLossReportService::class)->build(search: 'SAL-P4')['rows'][0]['profit'];
        $this->assertSame($before, $after);
        $this->assertSame('40.00', $after);
    }

    public function test_historical_costing_uses_latest_purchase_and_item_ids_for_equal_dates(): void
    {
        [$product, $firstItem] = $this->productWithPurchase();
        $firstItem->update(['cost_price' => 5]);
        $firstPurchase = Purchase::query()->findOrFail($firstItem->purchase_id);

        $laterPurchase = Purchase::query()->create([
            'purchase_number' => 'PO-LATEST-SAME-DATE',
            'supplier_id' => $firstPurchase->supplier_id,
            'purchase_date' => $firstPurchase->purchase_date,
            'payment_status' => Purchase::PAYMENT_PAID,
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'subtotal' => 18,
            'total_amount' => 18,
            'amount_paid' => 18,
            'balance_due' => 0,
        ]);
        PurchaseItem::query()->create([
            'purchase_id' => $laterPurchase->id,
            'product_id' => $product->id,
            'product_name_en' => 'P4 Product',
            'purchase_qty' => 1,
            'current_rate' => 9,
            'cost_price' => 9,
            'line_total' => 9,
        ]);
        $latestItem = PurchaseItem::query()->create([
            'purchase_id' => $laterPurchase->id,
            'product_id' => $product->id,
            'product_name_en' => 'P4 Product',
            'purchase_qty' => 1,
            'current_rate' => 11,
            'cost_price' => 11,
            'line_total' => 11,
        ]);

        $resolved = app(HistoricalCostingService::class)->resolve($product->id, '2026-07-10', true);

        $this->assertSame(11.0, $resolved['unit_cost']);
        $this->assertFalse($resolved['ambiguous']);
        $this->assertGreaterThan($firstItem->id, $latestItem->id);
    }

    public function test_backfill_is_dry_run_by_default_and_idempotent(): void
    {
        [$product] = $this->productWithPurchase();
        $saleId = DB::table('sales')->insertGetId([
            'sale_number' => 'SAL-OLD-P4', 'sale_date' => '2026-07-10', 'payment_status' => 'paid',
            'payment_method' => 'cash', 'price_display' => 'plain', 'subtotal' => 20, 'discount_amount' => 0,
            'total_amount' => 20, 'amount_paid' => 20, 'print_controls' => 0, 'status' => Sale::STATUS_COMPLETED,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('sale_items')->insertGetId([
            'sale_id' => $saleId, 'product_id' => $product->id, 'sort_order' => 0,
            'product_name_en' => 'Old', 'quantity' => '2', 'unit_price' => '10', 'line_total' => '20',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('sales:backfill-cost-snapshots')->assertSuccessful();
        $this->assertNull(DB::table('sale_items')->where('id', $itemId)->value('unit_cost_snapshot'));
        $this->artisan('sales:backfill-cost-snapshots', ['--commit' => true])->assertSuccessful();
        $this->assertSame(5.0, (float) DB::table('sale_items')->where('id', $itemId)->value('unit_cost_snapshot'));
        $this->artisan('sales:backfill-cost-snapshots', ['--commit' => true])->assertSuccessful();
        $this->assertSame(1, DB::table('sale_items')->whereNotNull('unit_cost_snapshot')->count());
    }

    public function test_normalized_phone_table_enforces_canonical_uniqueness(): void
    {
        $first = $this->customer('one@example.test', '0300-1234567');
        app(CustomerService::class)->syncNormalizedPhones($first, [['mobile_number' => '0300-1234567']]);
        $this->assertSame('3001234567', CustomerPhone::query()->firstOrFail()->canonical_phone);

        $second = $this->customer('two@example.test', '03001234567');
        $this->expectException(\Illuminate\Database\QueryException::class);
        app(CustomerService::class)->syncNormalizedPhones($second, [['mobile_number' => '+92 300 1234567']]);
    }

    public function test_stock_and_reorder_interactive_queries_are_bounded(): void
    {
        $now = now();
        $rows = [];
        for ($i = 1; $i <= 510; $i++) {
            $rows[] = [
                'code' => 'BOUND-'.$i, 'slug' => 'bound-'.$i, 'status' => 'active',
                'name_en' => 'Bound '.$i, 'name_ur' => 'Bound '.$i, 'stock_qty' => 0,
                'required_qty' => '5', 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $stock = app(StockReportService::class)->build();
        $stockQueries = count(DB::getQueryLog());
        DB::flushQueryLog();
        $reorder = app(ReorderService::class)->reorderCandidates(filter: 'all');
        $reorderQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(StockReportService::INTERACTIVE_ROW_LIMIT, $stock['rows']);
        $this->assertSame(510, $stock['kpis']['total_skus']);
        $this->assertCount(ReorderService::CANDIDATE_LIMIT, $reorder);
        $this->assertLessThanOrEqual(4, $stockQueries);
        $this->assertLessThanOrEqual(3, $reorderQueries);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase Four', 'email' => $email, 'password' => Hash::make('password'),
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function customer(string $email, string $phone): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'C'.substr(md5($email), 0, 8), 'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase', 'last_name' => 'Four', 'email' => $email, 'phone' => $phone,
            'password' => Hash::make('password'), 'address_line1' => 'Test', 'city' => 'Lahore',
            'state' => 'Punjab', 'country' => 'Pakistan',
        ]);
    }

    /**
     * @return array{Product, PurchaseItem}
     */
    private function productWithPurchase(): array
    {
        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP'.random_int(1000, 9999), 'status' => 'active',
            'business_name_en' => 'P4 Supplier', 'business_name_ur' => 'P4 Supplier',
            'address_en' => 'Test', 'city_en' => 'Lahore', 'opening_balance' => 0,
        ]);
        $product = Product::query()->create([
            'code' => 'P4'.random_int(1000, 9999), 'slug' => 'p4-'.random_int(1000, 9999),
            'status' => 'active', 'name_en' => 'P4 Product', 'name_ur' => 'P4 Product', 'stock_qty' => 100,
        ]);
        $purchase = Purchase::query()->create([
            'purchase_number' => 'PO'.random_int(1000, 9999), 'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-01', 'payment_status' => Purchase::PAYMENT_PAID,
            'delivery_status' => Purchase::DELIVERY_RECEIVED, 'subtotal' => 50,
            'total_amount' => 50, 'amount_paid' => 50, 'balance_due' => 0,
        ]);
        $item = PurchaseItem::query()->create([
            'purchase_id' => $purchase->id, 'product_id' => $product->id,
            'product_name_en' => 'P4 Product', 'purchase_qty' => 10,
            'current_rate' => 5, 'cost_price' => 5, 'line_total' => 50,
        ]);

        return [$product, $item];
    }
}
