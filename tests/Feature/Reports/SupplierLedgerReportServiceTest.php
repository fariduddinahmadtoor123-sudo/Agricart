<?php

namespace Tests\Feature\Reports;

use App\Models\FundTransfer;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Reports\SupplierLedgerReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierLedgerReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_ledger_reconstructs_seed_and_carries_prior_movement(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');

        // Running opening_balance mirrors app posting: seed 25 + (100-40) + (50-20) - 15 = 100
        $supplier = $this->makeSupplier('Ledger Supplier', '100.00');
        $settled = $this->makeSupplier('Settled Supplier', '0.00');

        Purchase::query()->create([
            'purchase_number' => 'PO-OLD',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-16',
            'payment_status' => Purchase::PAYMENT_PARTIAL,
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 40,
            'balance_due' => 60,
        ]);

        Purchase::query()->create([
            'purchase_number' => 'PO-TODAY',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-17',
            'payment_status' => Purchase::PAYMENT_PARTIAL,
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 20,
            'balance_due' => 30,
        ]);

        FundTransfer::query()->create([
            'transfer_number' => 'FT-TODAY',
            'transaction_type' => FundTransfer::TYPE_VENDOR_PAYMENT,
            'source_wallet' => FundTransfer::WALLET_CASH,
            'destination_supplier_id' => $supplier->id,
            'amount' => 15,
            'transaction_date' => '2026-07-17',
            'transaction_time' => '10:00:00',
        ]);

        $service = app(SupplierLedgerReportService::class);
        $today = $service->build(periodFilter: 'today');

        $row = collect($today['rows'])->firstWhere('id', $supplier->id);
        $this->assertNotNull($row);
        // seed 25 + prior net (100-40) = 85 opening for today
        $this->assertSame('85.00', $row['opening']);
        $this->assertSame('50.00', $row['debit']);
        $this->assertSame('35.00', $row['credit']);
        $this->assertSame('100.00', $row['balance']);
        $this->assertSame('payable', $row['balance_type']);

        $this->assertSame('2', (string) $today['kpis']['total_suppliers']);
        $this->assertSame('100.00', $today['kpis']['payable']);

        $withBalance = $service->build(periodFilter: 'today', partyFilter: 'balance');
        $this->assertCount(1, $withBalance['rows']);
        $this->assertSame($supplier->id, $withBalance['rows'][0]['id']);

        $zero = $service->build(periodFilter: 'today', partyFilter: 'zero');
        $this->assertCount(1, $zero['rows']);
        $this->assertSame($settled->id, $zero['rows'][0]['id']);

        $history = $service->history($supplier->id, 'today');
        $this->assertSame($supplier->id, $history['party']['id']);
        $this->assertSame('85.00', $history['summary']['opening']);
        $this->assertSame('100.00', $history['summary']['balance']);

        $types = array_column($history['lines'], 'type');
        $this->assertSame(
            ['opening', 'purchase', 'payment', 'vendor_payment'],
            $types,
        );
        $this->assertSame('50.00', $history['lines'][1]['debit']);
        $this->assertSame('20.00', $history['lines'][2]['credit']);
        $this->assertSame('15.00', $history['lines'][3]['credit']);
        $this->assertSame('100.00', $history['lines'][3]['balance']);

        $csv = $service->csvMatrix($today['rows'], ['supplier', 'phone', 'balance']);
        $this->assertSame(['Supplier', 'Phone', 'Balance'], $csv[0]);
    }

    private function makeSupplier(string $name, string $runningOpening): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => 'SUP-'.strtoupper(substr(md5($name), 0, 8)),
            'status' => 'active',
            'business_name_en' => $name,
            'business_name_ur' => $name,
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => $runningOpening,
            'contacts' => [
                ['mobile_number' => '03001234567', 'contact_name_en' => 'Contact'],
            ],
        ]);
    }
}
