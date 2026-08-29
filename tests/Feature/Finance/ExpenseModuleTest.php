<?php

namespace Tests\Feature\Finance;

use App\Livewire\Finance\ExpenseCategoryForm;
use App\Livewire\Finance\ExpenseForm;
use App\Livewire\Finance\ListExpenseCategories;
use App\Models\AiConfiguration;
use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\OpenRouterClient;
use App\Services\Finance\CashBookService;
use App\Services\Finance\ExpenseCategoryService;
use App\Services\Finance\ExpenseEnrichmentService;
use App\Services\Finance\ExpensePaymentService;
use App\Services\Finance\ExpenseScheduleService;
use App\Services\Finance\ExpenseService;
use App\Services\Notifications\TopbarNotificationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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

    public function test_expense_category_form_open_prefills_next_code_and_save_close_persists(): void
    {
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(ExpenseCategoryForm::class)
            ->call('openForm', null, 'create')
            ->assertSet('code', 'EXC-00001')
            ->set('name_en', 'Office rent')
            ->set('name_ur', 'آفس کرایہ')
            ->call('handlePanelAction', 'save_close')
            ->assertHasNoErrors()
            ->assertDispatched('close-form-panel');

        $this->assertDatabaseHas('expense_categories', [
            'code' => 'EXC-00001',
            'name_en' => 'Office rent',
            'name_ur' => 'آفس کرایہ',
            'kind' => ExpenseCategory::KIND_OPERATING,
            'status' => ExpenseCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_expense_category_list_shows_view_edit_delete_and_can_delete_unused(): void
    {
        $admin = $this->superAdmin();
        $category = app(ExpenseCategoryService::class)->create([
            'name_en' => 'Utilities',
            'kind' => ExpenseCategory::KIND_OPERATING,
            'status' => ExpenseCategory::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($admin)
            ->test(ListExpenseCategories::class)
            ->assertSee(__('agricart.finance.expense_categories.actions.view_short'))
            ->assertSee(__('agricart.finance.expense_categories.actions.edit_short'))
            ->assertSee(__('agricart.finance.expense_categories.actions.delete_short'))
            ->assertDontSee('agricart.finance.expense_categories.actions.edit_short', false)
            ->call('openView', $category->id)
            ->assertDispatched('open-form-panel')
            ->call('openEdit', $category->id)
            ->assertDispatched('open-form-panel')
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
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

    public function test_expense_form_saves_monthly_schedule_and_starts_reminder(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        try {
            $admin = $this->superAdmin();
            Filament::setCurrentPanel(Filament::getPanel('admin'));
            $this->actingAs($admin);

            $category = ExpenseCategory::query()->create([
                'code' => 'EXC-00002',
                'name_en' => 'Utilities',
                'status' => ExpenseCategory::STATUS_ACTIVE,
                'kind' => ExpenseCategory::KIND_OPERATING,
            ]);

            Livewire::actingAs($admin)
                ->test(ExpenseForm::class)
                ->call('openForm', null, 'create')
                ->assertSet('expense_number', 'EXP-00001')
                ->set('expense_category_id', $category->id)
                ->set('name_en', 'Office electricity')
                ->set('issue_day', 10)
                ->set('due_day', 20)
                ->call('handlePanelAction', 'save_close')
                ->assertHasNoErrors()
                ->assertDispatched('close-form-panel')
                ->assertSee(__('agricart.finance.expenses.due.issued_with_due', ['date' => '2026-08-20']));

            $expense = Expense::query()->sole();
            $this->assertSame(10, $expense->issue_day);
            $this->assertSame(20, $expense->due_day);
            $this->assertSame(Expense::DUE_ISSUED, $expense->dueStatus());
            $this->assertSame(1, Expense::query()->awaitingPayment()->count());

            $summary = app(TopbarNotificationService::class)->summary();
            $this->assertSame(0, $summary['total']);
            $this->assertNull(collect($summary['items'])->firstWhere('key', 'expense_bills'));

            $modules = app(\App\Services\Notifications\ModuleNotificationService::class);
            $this->assertSame(1, $modules->count('expense_bills'));
            $this->assertSame(1, $modules->countsByModule()['finance'] ?? 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expense_form_save_close_accepts_empty_schedule_days(): void
    {
        $admin = $this->superAdmin();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00009',
            'name_en' => 'Office',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        Livewire::actingAs($admin)
            ->test(ExpenseForm::class)
            ->call('openForm', null, 'create')
            ->set('expense_category_id', (string) $category->id)
            ->set('name_en', 'Stationery')
            ->set('issue_day', '')
            ->set('due_day', '')
            ->call('handlePanelAction', 'save_close')
            ->assertHasNoErrors()
            ->assertDispatched('close-form-panel');

        $this->assertDatabaseHas('expenses', [
            'name_en' => 'Stationery',
            'issue_day' => null,
            'due_day' => null,
        ]);
    }

    public function test_expense_form_saves_generic_detail_fields(): void
    {
        $admin = $this->superAdmin();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00010',
            'name_en' => 'Refreshment',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        Livewire::actingAs($admin)
            ->test(ExpenseForm::class)
            ->call('openForm', null, 'create')
            ->set('expense_category_id', $category->id)
            ->set('name_en', 'Office pantry')
            ->set('detail_1', 'Tea')
            ->set('detail_2', 'Biscuits')
            ->set('detail_3', 'Tissue')
            ->set('detail_4', 'Sugar')
            ->call('handlePanelAction', 'save_close')
            ->assertHasNoErrors()
            ->assertDispatched('close-form-panel');

        $this->assertDatabaseHas('expenses', [
            'name_en' => 'Office pantry',
            'detail_1' => 'Tea',
            'detail_2' => 'Biscuits',
            'detail_3' => 'Tissue',
            'detail_4' => 'Sugar',
        ]);
    }

    public function test_expense_form_saves_and_updates_estimated_cost(): void
    {
        $admin = $this->superAdmin();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00011',
            'name_en' => 'Utilities',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        Livewire::actingAs($admin)
            ->test(ExpenseForm::class)
            ->call('openForm', null, 'create')
            ->set('expense_category_id', $category->id)
            ->set('name_en', 'Office electricity')
            ->set('issue_day', 10)
            ->set('due_day', 20)
            ->set('estimated_cost', '15000.50')
            ->call('handlePanelAction', 'save_close')
            ->assertHasNoErrors()
            ->assertDispatched('close-form-panel');

        $expense = Expense::query()->sole();
        $this->assertSame('15000.50', (string) $expense->estimated_cost);

        Livewire::actingAs($admin)
            ->test(ExpenseForm::class)
            ->call('openForm', $expense->id, 'edit')
            ->assertSet('estimated_cost', '15000.50')
            ->set('estimated_cost', '16500')
            ->call('handlePanelAction', 'save_close')
            ->assertHasNoErrors();

        $this->assertSame('16500.00', (string) $expense->fresh()->estimated_cost);
        $this->assertDatabaseCount('cash_book_transactions', 0);
    }

    public function test_estimated_cost_period_multiplies_monthly_bills(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        try {
            $category = ExpenseCategory::query()->create([
                'code' => 'EXC-00012',
                'name_en' => 'Utilities',
                'status' => ExpenseCategory::STATUS_ACTIVE,
                'kind' => ExpenseCategory::KIND_OPERATING,
            ]);

            $electricity = app(ExpenseService::class)->create([
                'expense_category_id' => $category->id,
                'status' => Expense::STATUS_ACTIVE,
                'name_en' => 'Electricity',
                'issue_day' => 10,
                'estimated_cost' => 10000,
            ]);

            $snacks = app(ExpenseService::class)->create([
                'expense_category_id' => $category->id,
                'status' => Expense::STATUS_ACTIVE,
                'name_en' => 'Refreshment',
                'estimated_cost' => 2000,
            ]);

            $schedule = app(ExpenseScheduleService::class);

            $this->assertSame(3, $schedule->estimatedOccurrences($electricity, '2026-01-01', '2026-03-31'));
            $this->assertSame(3, $schedule->estimatedOccurrences($snacks, '2026-01-01', '2026-03-31'));
            $this->assertSame(36000.0, $schedule->estimatedTotalForPeriod(
                collect([$electricity, $snacks]),
                Carbon::parse('2026-01-01'),
                Carbon::parse('2026-03-31'),
            ));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expense_due_day_requires_issue_day(): void
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'EXC-00003',
            'name_en' => 'Rent',
            'status' => ExpenseCategory::STATUS_ACTIVE,
            'kind' => ExpenseCategory::KIND_OPERATING,
        ]);

        $this->expectException(ValidationException::class);

        app(ExpenseService::class)->create([
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_ACTIVE,
            'name_en' => 'Shop rent',
            'due_day' => 20,
        ]);
    }

    public function test_payment_or_mark_done_clears_monthly_reminder_until_next_issue_day(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        try {
            $admin = $this->superAdmin();
            app(CashBookService::class)->recordIn(
                amount: 10_000,
                transactionDate: '2026-08-12',
                description: 'Opening cash for bill reminder test',
            );

            $category = ExpenseCategory::query()->create([
                'code' => 'EXC-00004',
                'name_en' => 'Electricity',
                'status' => ExpenseCategory::STATUS_ACTIVE,
                'kind' => ExpenseCategory::KIND_OPERATING,
            ]);

            $expense = app(ExpenseService::class)->create([
                'expense_category_id' => $category->id,
                'status' => Expense::STATUS_ACTIVE,
                'name_en' => 'Office electricity',
                'issue_day' => 10,
                'due_day' => 20,
            ]);

            $this->assertSame(1, app(ExpenseScheduleService::class)->openCycleCount());

            app(ExpensePaymentService::class)->create([
                'expense_id' => $expense->id,
                'amount' => 500,
                'payment_date' => '2026-08-12',
                'source_wallet' => ExpensePayment::WALLET_CASH,
            ]);

            $this->assertSame(0, app(ExpenseScheduleService::class)->openCycleCount());
            $this->assertSame(Expense::DUE_PAID, $expense->fresh(['payments', 'billCycles'])->dueStatus());

            $internet = app(ExpenseService::class)->create([
                'expense_category_id' => $category->id,
                'status' => Expense::STATUS_ACTIVE,
                'name_en' => 'Office internet',
                'issue_day' => 15,
                'due_day' => 20,
            ]);

            Carbon::setTestNow('2026-08-16 10:00:00');
            $this->assertSame(1, app(ExpenseScheduleService::class)->openCycleCount());

            app(ExpenseScheduleService::class)->markOldestOpenDone(
                $internet->fresh(['payments', 'billCycles']),
                $admin,
            );

            $this->assertSame(0, app(ExpenseScheduleService::class)->openCycleCount());

            Carbon::setTestNow('2026-09-15 10:00:00');
            $this->assertSame(2, app(ExpenseScheduleService::class)->openCycleCount());
            $this->assertSame(Expense::DUE_ISSUED, $internet->fresh(['payments', 'billCycles'])->dueStatus());
            $this->assertSame(Expense::DUE_ISSUED, $expense->fresh(['payments', 'billCycles'])->dueStatus());
        } finally {
            Carbon::setTestNow();
        }
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
}
