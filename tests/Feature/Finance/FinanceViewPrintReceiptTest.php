<?php

namespace Tests\Feature\Finance;

use App\Livewire\Finance\ExpensePaymentForm;
use App\Livewire\Finance\FundTransferForm;
use App\Livewire\Finance\ListFundTransfers;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Models\FundTransfer;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\CashBookService;
use App\Services\Finance\ExpensePaymentService;
use App\Services\Finance\FundTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceViewPrintReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_fund_transfer_record_stores_bank_receipt_path(): void
    {
        Storage::fake((string) config('media.disk', 'public'));

        $bank = $this->makeBank(opening: 5000);
        $upload = UploadedFile::fake()->image('receipt.jpg', 800, 600);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_DEPOSIT,
            'destination_bank_account_id' => $bank->id,
            'amount' => 1200,
            'handled_by_name' => 'Ahmed',
            'transaction_date' => '2026-08-25',
            'transaction_time' => '10:00',
            'notes' => 'Owner cash in',
        ], null, $upload);

        $this->assertNotNull($transfer->bank_receipt_path);
        $this->assertStringContainsString('finance/fund-transfers/receipts', (string) $transfer->bank_receipt_path);
        Storage::disk((string) config('media.disk', 'public'))->assertExists($transfer->bank_receipt_path);
        $this->assertNotNull($transfer->bankReceiptUrl());
    }

    public function test_super_admin_can_open_fund_transfer_view(): void
    {
        $admin = $this->superAdmin();
        $bank = $this->makeBank(opening: 3000);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_DEPOSIT,
            'destination_bank_account_id' => $bank->id,
            'amount' => 500,
            'handled_by_name' => 'Sara',
            'transaction_date' => '2026-08-25',
            'transaction_time' => '11:00',
        ]);

        Livewire::actingAs($admin)
            ->test(ListFundTransfers::class)
            ->call('openView', $transfer->id)
            ->assertDispatched('open-form-panel');

        Livewire::actingAs($admin)
            ->test(FundTransferForm::class)
            ->call('openForm', $transfer->id, 'view')
            ->assertSet('mode', 'view')
            ->assertSet('transferId', $transfer->id)
            ->assertSet('transaction_number', $transfer->transfer_number)
            ->assertSet('amount', '500.00');
    }

    public function test_expense_payment_create_update_notes_and_receipt(): void
    {
        Storage::fake((string) config('media.disk', 'public'));

        app(CashBookService::class)->recordIn(
            amount: 5000,
            transactionDate: '2026-08-01',
            description: 'Opening cash for tests',
        );

        $expense = $this->makeExpense();
        $upload = UploadedFile::fake()->image('pay-receipt.png', 640, 480);

        $payment = app(ExpensePaymentService::class)->create([
            'expense_id' => $expense->id,
            'amount' => 250,
            'payment_date' => '2026-08-25',
            'payment_time' => '09:30',
            'source_wallet' => ExpensePayment::WALLET_CASH,
            'notes' => 'First note',
        ], null, $upload);

        $this->assertSame('First note', $payment->notes);
        $this->assertNotNull($payment->bank_receipt_path);
        Storage::disk((string) config('media.disk', 'public'))->assertExists($payment->bank_receipt_path);

        $replacement = UploadedFile::fake()->image('pay-receipt-2.jpg', 500, 400);
        $previousPath = $payment->bank_receipt_path;
        $updated = app(ExpensePaymentService::class)->update(
            $payment,
            ['notes' => 'Updated note'],
            $replacement,
        );

        $this->assertSame('Updated note', $updated->notes);
        $this->assertNotNull($updated->bank_receipt_path);
        Storage::disk((string) config('media.disk', 'public'))->assertExists($updated->bank_receipt_path);

        if ($previousPath !== $updated->bank_receipt_path) {
            Storage::disk((string) config('media.disk', 'public'))->assertMissing($previousPath);
        }
    }

    public function test_fund_transfer_expense_payment_stores_receipt_on_linked_payment(): void
    {
        Storage::fake((string) config('media.disk', 'public'));

        app(CashBookService::class)->recordIn(
            amount: 5000,
            transactionDate: '2026-08-01',
            description: 'Opening cash for fund transfer receipt test',
        );

        $expense = $this->makeExpense();
        $upload = UploadedFile::fake()->image('expense-receipt.jpg', 700, 900);

        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXPENSE_PAYMENT,
            'destination_expense_id' => $expense->id,
            'amount' => 400,
            'transaction_date' => '2026-08-25',
            'transaction_time' => '15:00',
            'source_wallet' => ExpensePayment::WALLET_CASH,
        ], null, $upload);

        $payment = ExpensePayment::query()->findOrFail($transfer->expense_payment_id);

        $this->assertNotNull($payment->bank_receipt_path);
        $this->assertSame($payment->bank_receipt_path, $transfer->fresh()->bank_receipt_path);
        $this->assertTrue($payment->hasReceipt());
        Storage::disk((string) config('media.disk', 'public'))->assertExists($payment->bank_receipt_path);
    }

    public function test_unauthorized_user_forbidden_on_open_view(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'role_id' => Role::query()->firstOrCreate(
                ['slug' => 'viewer'],
                ['name' => 'Viewer', 'is_system' => false],
            )->id,
        ]);

        $bank = $this->makeBank(opening: 1000);
        $transfer = app(FundTransferService::class)->record([
            'transaction_type' => FundTransfer::TYPE_EXTERNAL_DEPOSIT,
            'destination_bank_account_id' => $bank->id,
            'amount' => 100,
            'handled_by_name' => 'Ali',
            'transaction_date' => '2026-08-25',
            'transaction_time' => '12:00',
        ]);

        Livewire::actingAs($user)
            ->test(FundTransferForm::class)
            ->call('openForm', $transfer->id, 'view')
            ->assertForbidden();

        app(CashBookService::class)->recordIn(
            amount: 1000,
            transactionDate: '2026-08-01',
            description: 'Opening cash for unauthorized test',
        );

        $expense = $this->makeExpense();
        $payment = app(ExpensePaymentService::class)->create([
            'expense_id' => $expense->id,
            'amount' => 50,
            'payment_date' => '2026-08-25',
            'source_wallet' => ExpensePayment::WALLET_CASH,
        ]);

        Livewire::actingAs($user)
            ->test(ExpensePaymentForm::class)
            ->call('openForm', $payment->id, 'view')
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function makeBank(float $opening): BankAccount
    {
        return BankAccount::query()->create([
            'account_code' => 'BNK-VR'.random_int(10, 99),
            'name_en' => 'HBL Main',
            'bank_name_en' => 'HBL',
            'account_title' => 'Agricart',
            'account_number' => (string) random_int(1000000000, 1999999999),
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => $opening,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    private function makeExpense(): Expense
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-VR'.random_int(10, 99),
            'name_en' => 'Utilities',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        return Expense::query()->create([
            'expense_number' => 'EXP-VR'.random_int(10, 99),
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Office electricity',
        ]);
    }
}
