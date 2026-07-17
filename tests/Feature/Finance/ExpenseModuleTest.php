<?php

namespace Tests\Feature\Finance;

use App\Models\AiConfiguration;
use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Services\Ai\OpenRouterClient;
use App\Services\Finance\CashBookService;
use App\Services\Finance\ExpenseCategoryService;
use App\Services\Finance\ExpenseEnrichmentService;
use App\Services\Finance\ExpensePaymentService;
use App\Services\Finance\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_category_create_assigns_code(): void
    {
        $category = app(ExpenseCategoryService::class)->create([
            'name_en' => 'Utilities',
            'name_ur' => 'یوٹیلیٹیز',
            'kind' => ExpenseCategory::KIND_OPERATING,
            'status' => ExpenseCategory::STATUS_ACTIVE,
        ]);

        $this->assertSame('EXC-00001', $category->code);
        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name_en' => 'Utilities',
            'name_ur' => 'یوٹیلیٹیز',
            'kind' => 'operating',
        ]);
    }

    public function test_expense_head_create_does_not_post_ledger(): void
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00001',
            'name_en' => 'Electricity',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        $expense = app(ExpenseService::class)->create([
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Office electricity',
            'name_ur' => 'آفس بجلی',
            'information' => 'Meter 123 / Bill ID ABC',
        ]);

        $this->assertSame('EXP-00001', $expense->expense_number);
        $this->assertSame('Meter 123 / Bill ID ABC', $expense->information);
        $this->assertDatabaseCount('cash_book_transactions', 0);
        $this->assertDatabaseCount('expense_payments', 0);
    }

    public function test_expense_payment_posts_cash_ledger(): void
    {
        app(CashBookService::class)->recordIn(
            amount: 10_000,
            transactionDate: '2026-07-17',
            description: 'Opening cash for test',
        );

        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00001',
            'name_en' => 'Rent',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        $expense = Expense::query()->create([
            'expense_number' => 'EXP-00001',
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Shop rent',
            'name_ur' => 'دکان کرایہ',
            'information' => 'Main store',
        ]);

        $payment = app(ExpensePaymentService::class)->create([
            'expense_id' => $expense->id,
            'amount' => 1500,
            'payment_date' => '2026-07-17',
            'payment_time' => '10:30',
            'source_wallet' => ExpensePayment::WALLET_CASH,
        ]);

        $this->assertSame('EXPAY-00001', $payment->payment_number);
        $this->assertDatabaseHas('cash_book_transactions', [
            'expense_id' => $expense->id,
            'expense_payment_id' => $payment->id,
            'direction' => CashBookTransaction::DIRECTION_OUT,
            'amount' => 1500,
        ]);
        $this->assertSame(8500.0, app(CashBookService::class)->balance());
    }

    public function test_expense_payment_posts_bank_ledger(): void
    {
        $bank = BankAccount::query()->create([
            'account_code' => 'BNK-00001',
            'name_en' => 'HBL Main',
            'bank_name_en' => 'HBL',
            'account_title' => 'Agricart',
            'account_number' => '1234567890',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => 5000,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);

        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00001',
            'name_en' => 'Utilities',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        $expense = Expense::query()->create([
            'expense_number' => 'EXP-00001',
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Internet bill',
        ]);

        $payment = app(ExpensePaymentService::class)->create([
            'expense_id' => $expense->id,
            'amount' => 2000,
            'payment_date' => '2026-07-17',
            'source_wallet' => ExpensePayment::WALLET_BANK,
            'bank_account_id' => $bank->id,
        ]);

        $this->assertDatabaseHas('bank_account_transactions', [
            'expense_id' => $expense->id,
            'expense_payment_id' => $payment->id,
            'bank_account_id' => $bank->id,
            'type' => BankAccountTransaction::TYPE_EXPENSE_PAYMENT,
            'direction' => BankAccountTransaction::DIRECTION_DEBIT,
            'amount' => 2000,
        ]);
    }

    public function test_expense_enrichment_returns_head_contract(): void
    {
        AiConfiguration::query()->create([
            'provider' => 'openrouter',
            'api_key' => 'sk-or-v1-test-key',
            'model_id' => 'test/model',
            'model_name' => 'Test',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $client = Mockery::mock(OpenRouterClient::class);
        $client->shouldReceive('sendChat')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'ok',
                'response' => json_encode([
                    'name_en' => 'Office electricity',
                    'name_ur' => 'آفس بجلی',
                    'information' => 'Meter 55 — Shop floor',
                ], JSON_UNESCAPED_UNICODE),
                'http_status' => 200,
            ]);

        $this->app->instance(OpenRouterClient::class, $client);

        $result = app(ExpenseEnrichmentService::class)->enrich('Electricity');

        $this->assertTrue($result['success']);
        $this->assertSame('Office electricity', $result['data']['name_en']);
        $this->assertSame('آفس بجلی', $result['data']['name_ur']);
        $this->assertSame('Meter 55 — Shop floor', $result['data']['information']);
    }
}
