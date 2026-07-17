<?php

namespace Tests\Feature\Reports;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\Reports\CustomerLedgerReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerLedgerReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_ledger_carries_prior_movement_into_period_opening(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 09:00:00');

        $customer = $this->makeCustomer('ledger@test.test', 'Ledger', 'Customer', '10.00');
        $settled = $this->makeCustomer('settled@test.test', 'Settled', 'Customer', '0.00');

        Sale::query()->create([
            'sale_number' => 'SALE-OLD',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-16',
            'payment_status' => 'partial',
            'payment_method' => 'cash',
            'total_amount' => 100,
            'amount_paid' => 40,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        Sale::query()->create([
            'sale_number' => 'SALE-TODAY',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-17',
            'payment_status' => 'partial',
            'payment_method' => 'cash',
            'total_amount' => 50,
            'amount_paid' => 20,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-TODAY',
            'customer_id' => $customer->id,
            'order_date' => '2026-07-17',
            'order_status' => OnlineSaleOrder::STATUS_PROCESSING,
            'payment_status' => 'partial',
            'subtotal' => 30,
            'total_amount' => 30,
            'amount_paid' => 5,
        ]);

        SaleReturn::query()->create([
            'return_number' => 'RET-TODAY',
            'sale_id' => Sale::query()->where('sale_number', 'SALE-TODAY')->value('id'),
            'customer_id' => $customer->id,
            'return_date' => '2026-07-17',
            'refund_subtotal' => 10,
            'refund_total' => 10,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $service = app(CustomerLedgerReportService::class);
        $today = $service->build(periodFilter: 'today');

        $row = collect($today['rows'])->firstWhere('id', $customer->id);
        $this->assertNotNull($row);
        $this->assertSame('70.00', $row['opening']);
        $this->assertSame('80.00', $row['debit']);
        $this->assertSame('35.00', $row['credit']);
        $this->assertSame('115.00', $row['balance']);
        $this->assertSame('receivable', $row['balance_type']);

        $this->assertSame('2', (string) $today['kpis']['total_customers']);
        $this->assertSame('115.00', $today['kpis']['receivable']);

        $withBalance = $service->build(periodFilter: 'today', partyFilter: 'balance');
        $this->assertCount(1, $withBalance['rows']);
        $this->assertSame($customer->id, $withBalance['rows'][0]['id']);

        $zero = $service->build(periodFilter: 'today', partyFilter: 'zero');
        $this->assertCount(1, $zero['rows']);
        $this->assertSame($settled->id, $zero['rows'][0]['id']);

        $search = $service->build(search: 'ledger@test.test');
        $this->assertCount(1, $search['rows']);

        $csv = $service->csvMatrix($today['rows'], ['customer', 'phone', 'balance']);
        $this->assertSame(['Customer', 'Phone', 'Balance'], $csv[0]);

        $history = $service->history($customer->id, 'today');
        $this->assertSame($customer->id, $history['customer']['id']);
        $this->assertSame('70.00', $history['summary']['opening']);
        $this->assertSame('80.00', $history['summary']['debit']);
        $this->assertSame('35.00', $history['summary']['credit']);
        $this->assertSame('115.00', $history['summary']['balance']);

        $lines = $history['lines'];
        $this->assertSame('opening', $lines[0]['type']);
        $this->assertSame('70.00', $lines[0]['balance']);

        $types = array_column($lines, 'type');
        $this->assertSame(
            ['opening', 'sale', 'payment', 'online_order', 'payment', 'return'],
            $types,
        );

        $this->assertSame('50.00', $lines[1]['debit']);
        $this->assertSame('20.00', $lines[2]['credit']);
        $this->assertSame('30.00', $lines[3]['debit']);
        $this->assertSame('5.00', $lines[4]['credit']);
        $this->assertSame('10.00', $lines[5]['credit']);
        $this->assertSame('115.00', $lines[5]['balance']);
        $this->assertStringContainsString('SALE-TODAY', (string) $lines[1]['reference']);
        $this->assertStringContainsString('Cash', (string) $lines[2]['method']);
    }

    private function makeCustomer(string $email, string $firstName, string $lastName, string $opening): Customer
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
            'opening_balance' => $opening,
            'is_active' => true,
        ]);
    }
}
