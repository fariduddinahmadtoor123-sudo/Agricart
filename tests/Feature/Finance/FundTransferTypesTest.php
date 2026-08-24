<?php

namespace Tests\Feature\Finance;

use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FundTransfer;
use App\Models\Sale;
use App\Models\User;
use App\Services\Finance\BankAccountService;
use App\Services\Finance\CashBookService;
use App\Services\Finance\ExpenseScheduleService;
use App\Services\Finance\FundTransferService;
use App\Services\Reports\ProfitLossReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FundTransferTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdrawal_posts_bank_debit_and_cash_in(): void
    {
        $bank = $this->makeBank(opening: 5000);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_WITHDRAWAL,
            'source_bank_account_id' => $bank->id,
            'amount' => 1500,
            'transaction_date' => '2026-08-17',
            'transaction_time' => '10:00',
        ]);

        $this->assertSame(FundTransfer::WALLET_BANK, $transfer->source_wallet);
        $this->assertDatabaseHas('bank_account_transactions', [
            'fund_transfer_id' => $transfer->id,
            'bank_account_id' => $bank->id,
            'type' => BankAccountTransaction::TYPE_CASH_WITHDRAWAL,
            'direction' => BankAccountTransaction::DIRECTION_DEBIT,
            'amount' => 1500,
        ]);
        $this->assertDatabaseHas('cash_book_transactions', [
            'fund_transfer_id' => $transfer->id,
            'direction' => CashBookTransaction::DIRECTION_IN,
            'amount' => 1500,
        ]);
        $this->assertSame(1500.0, app(CashBookService::class)->balance());
        $this->assertSame(3500.0, app(BankAccountService::class)->balance($bank));
    }

    public function test_external_deposit_credits_bank_without_cash_book(): void
    {
        $bank = $this->makeBank(opening: 2000);
        $cashBefore = app(CashBookService::class)->balance();

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_DEPOSIT,
            'destination_bank_account_id' => $bank->id,
            'amount' => 3000,
            'handled_by_name' => 'Ahmed',
            'transaction_date' => '2026-08-23',
            'transaction_time' => '14:30',
        ]);

        $this->assertNull($transfer->source_wallet);
        $this->assertSame('Ahmed', $transfer->handled_by_name);
        $this->assertDatabaseHas('bank_account_transactions', [
            'fund_transfer_id' => $transfer->id,
            'bank_account_id' => $bank->id,
            'type' => BankAccountTransaction::TYPE_OWNER_DEPOSIT,
            'direction' => BankAccountTransaction::DIRECTION_CREDIT,
            'amount' => 3000,
        ]);
        $this->assertDatabaseMissing('cash_book_transactions', [
            'fund_transfer_id' => $transfer->id,
        ]);
        $this->assertSame($cashBefore, app(CashBookService::class)->balance());
        $this->assertSame(5000.0, app(BankAccountService::class)->balance($bank));
    }

    public function test_external_withdrawal_debits_bank_without_cash_book(): void
    {
        $bank = $this->makeBank(opening: 8000);
        $cashBefore = app(CashBookService::class)->balance();

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_WITHDRAWAL,
            'source_bank_account_id' => $bank->id,
            'amount' => 2500,
            'handled_by_name' => 'Bilal',
            'transaction_date' => '2026-08-23',
            'transaction_time' => '15:00',
        ]);

        $this->assertSame(FundTransfer::WALLET_BANK, $transfer->source_wallet);
        $this->assertSame('Bilal', $transfer->handled_by_name);
        $this->assertDatabaseHas('bank_account_transactions', [
            'fund_transfer_id' => $transfer->id,
            'bank_account_id' => $bank->id,
            'type' => BankAccountTransaction::TYPE_OWNER_WITHDRAWAL,
            'direction' => BankAccountTransaction::DIRECTION_DEBIT,
            'amount' => 2500,
        ]);
        $this->assertDatabaseMissing('cash_book_transactions', [
            'fund_transfer_id' => $transfer->id,
        ]);
        $this->assertSame($cashBefore, app(CashBookService::class)->balance());
        $this->assertSame(5500.0, app(BankAccountService::class)->balance($bank));
    }

    public function test_external_deposit_requires_handled_by_name(): void
    {
        $bank = $this->makeBank(opening: 1000);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_DEPOSIT,
            'destination_bank_account_id' => $bank->id,
            'amount' => 500,
            'transaction_date' => '2026-08-23',
            'transaction_time' => '16:00',
        ]);
    }

    public function test_expense_payment_creates_expay_one_ledger_row_and_completes_bill_cycle(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        try {
            app(CashBookService::class)->recordIn(
                amount: 10_000,
                transactionDate: '2026-08-12',
                description: 'Opening cash for fund transfer expense test',
            );

            $expense = $this->makeExpense(issueDay: 10, dueDay: 20);

            $this->assertSame(1, app(ExpenseScheduleService::class)->openCycleCount());

            $transfer = app(FundTransferService::class)->record([
                'transaction_type' => FundTransfer::TYPE_EXPENSE_PAYMENT,
                'source_wallet' => FundTransfer::WALLET_CASH,
                'destination_expense_id' => $expense->id,
                'amount' => 2500,
                'transaction_date' => '2026-08-12',
                'transaction_time' => '11:00',
            ]);

            $this->assertNotNull($transfer->expense_payment_id);
            $this->assertDatabaseCount('expense_payments', 1);
            $this->assertDatabaseHas('expense_payments', [
                'id' => $transfer->expense_payment_id,
                'expense_id' => $expense->id,
                'fund_transfer_id' => $transfer->id,
                'amount' => 2500,
            ]);
            $this->assertDatabaseCount('cash_book_transactions', 2);
            $this->assertDatabaseHas('cash_book_transactions', [
                'fund_transfer_id' => $transfer->id,
                'expense_id' => $expense->id,
                'expense_payment_id' => $transfer->expense_payment_id,
                'direction' => CashBookTransaction::DIRECTION_OUT,
                'amount' => 2500,
            ]);
            $this->assertSame(0, app(ExpenseScheduleService::class)->openCycleCount());
            $this->assertSame(Expense::DUE_PAID, $expense->fresh(['payments', 'billCycles'])->dueStatus());
            $this->assertSame(7500.0, app(CashBookService::class)->balance());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_linked_expense_payment_is_not_double_counted_on_profit_loss(): void
    {
        Carbon::setTestNow('2026-08-17 09:00:00');

        try {
            app(CashBookService::class)->recordIn(
                amount: 10_000,
                transactionDate: '2026-08-17',
                description: 'Opening cash for P&L expense transfer test',
            );

            $customer = Customer::query()->create([
                'customer_number' => 'CUS-FT01',
                'customer_channel' => Customer::CHANNEL_ONLINE,
                'first_name' => 'Fund',
                'last_name' => 'Transfer',
                'display_name' => 'Fund Transfer',
                'name_en' => 'Fund Transfer',
                'name_ur' => 'Fund Transfer',
                'email' => 'ft@test.test',
                'phone' => '03001110000',
                'password' => Hash::make('password'),
                'address_line1' => '1 Test Street',
                'city' => 'Lahore',
                'state' => 'Punjab',
                'country' => 'Pakistan',
                'opening_balance' => 0,
                'is_active' => true,
            ]);

            Sale::query()->create([
                'sale_number' => 'SALE-FT-EXP',
                'customer_id' => $customer->id,
                'sale_date' => '2026-08-17',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'total_amount' => 100,
                'amount_paid' => 100,
                'status' => Sale::STATUS_COMPLETED,
            ]);

            $expense = $this->makeExpense();

            app(FundTransferService::class)->record([
                'transaction_type' => FundTransfer::TYPE_EXPENSE_PAYMENT,
                'source_wallet' => FundTransfer::WALLET_CASH,
                'destination_expense_id' => $expense->id,
                'amount' => 25,
                'transaction_date' => '2026-08-17',
                'transaction_time' => '09:30',
            ]);

            $report = app(ProfitLossReportService::class)->build(periodFilter: 'today');

            $this->assertSame('100.00', $report['kpis']['gross_profit']);
            $this->assertSame('25.00', $report['kpis']['expenses']);
            $this->assertSame('75.00', $report['kpis']['net_profit']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_purchaser_payment_posts_without_expense_payment(): void
    {
        app(CashBookService::class)->recordIn(
            amount: 5_000,
            transactionDate: '2026-08-17',
            description: 'Opening cash for purchaser payment test',
        );

        $payee = User::factory()->create([
            'name' => 'Shop Purchaser',
            'status' => User::STATUS_ACTIVE,
        ]);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_PURCHASER_PAYMENT,
            'source_wallet' => FundTransfer::WALLET_CASH,
            'destination_purchaser_id' => $payee->id,
            'amount' => 800,
            'transaction_date' => '2026-08-17',
            'transaction_time' => '12:00',
        ]);

        $this->assertNull($transfer->expense_payment_id);
        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertDatabaseHas('cash_book_transactions', [
            'fund_transfer_id' => $transfer->id,
            'direction' => CashBookTransaction::DIRECTION_OUT,
            'amount' => 800,
        ]);
        $this->assertSame(4200.0, app(CashBookService::class)->balance());
    }

    private function makeBank(float $opening): BankAccount
    {
        return BankAccount::query()->create([
            'account_code' => 'BNK-FT01',
            'name_en' => 'HBL Main',
            'bank_name_en' => 'HBL',
            'account_title' => 'Agricart',
            'account_number' => '1234567890',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => $opening,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    private function makeExpense(?int $issueDay = null, ?int $dueDay = null): Expense
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-FT01',
            'name_en' => 'Utilities',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        return Expense::query()->create([
            'expense_number' => 'EXP-FT01',
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Office electricity',
            'issue_day' => $issueDay,
            'due_day' => $dueDay,
        ]);
    }
}
