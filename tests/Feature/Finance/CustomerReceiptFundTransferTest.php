<?php

namespace Tests\Feature\Finance;

use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Customer;
use App\Models\FundTransfer;
use App\Services\Finance\CashBookService;
use App\Services\Finance\FundTransferService;
use App\Services\Reports\CustomerLedgerReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerReceiptFundTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_receipt_posts_cash_in_and_credits_ledger_without_mutating_opening_balance(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-00091',
            'customer_channel' => Customer::CHANNEL_OFFLINE,
            'first_name' => 'Ali',
            'last_name' => 'Farmer',
            'display_name' => 'Ali Farmer',
            'name_en' => 'Ali Farmer',
            'email' => 'ali.receipt@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => 'Shop',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 1000,
            'is_active' => true,
        ]);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_CUSTOMER_RECEIPT,
            'source_wallet' => FundTransfer::WALLET_CASH,
            'destination_customer_id' => $customer->id,
            'amount' => 400,
            'transaction_date' => today()->toDateString(),
            'transaction_time' => '10:00',
            'notes' => 'Partial settlement',
        ]);

        $this->assertDatabaseHas('cash_book_transactions', [
            'fund_transfer_id' => $transfer->id,
            'direction' => CashBookTransaction::DIRECTION_IN,
            'amount' => 400,
        ]);
        $this->assertSame(400.0, app(CashBookService::class)->balance());

        $customer->refresh();
        $this->assertSame(1000.0, (float) $customer->opening_balance);

        $ledger = app(CustomerLedgerReportService::class)->build();
        $row = collect($ledger['rows'])->firstWhere('id', $customer->id);
        $this->assertNotNull($row);
        $this->assertSame('400.00', $row['credit']);
        $this->assertSame('600.00', $row['balance']);
    }

    public function test_customer_receipt_posts_bank_credit(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-00092',
            'customer_channel' => Customer::CHANNEL_OFFLINE,
            'first_name' => 'Bilal',
            'last_name' => 'Trader',
            'display_name' => 'Bilal Trader',
            'name_en' => 'Bilal Trader',
            'email' => 'bilal.receipt@test.test',
            'phone' => '03004445566',
            'password' => Hash::make('password'),
            'address_line1' => 'Shop',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 200,
            'is_active' => true,
        ]);

        $bank = BankAccount::query()->create([
            'account_code' => 'BNK-00091',
            'name_en' => 'Receipt Bank',
            'bank_name_en' => 'HBL',
            'account_title' => 'Agricart',
            'account_number' => '9988776655',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_CUSTOMER_RECEIPT,
            'source_wallet' => FundTransfer::WALLET_BANK,
            'source_bank_account_id' => $bank->id,
            'destination_customer_id' => $customer->id,
            'amount' => 150,
            'transaction_date' => today()->toDateString(),
            'transaction_time' => '11:30',
        ]);

        $this->assertDatabaseHas('bank_account_transactions', [
            'fund_transfer_id' => $transfer->id,
            'customer_id' => $customer->id,
            'type' => BankAccountTransaction::TYPE_CUSTOMER_RECEIPT,
            'direction' => BankAccountTransaction::DIRECTION_CREDIT,
            'amount' => 150,
        ]);
    }
}
