<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Finance\ExpenseEnrichmentService;
use App\Services\Finance\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ExpenseForm extends Component
{
    public string $mode = 'create';

    public ?int $expenseId = null;

    public string $expense_number = '';

    public string $name_en = '';

    public string $name_ur = '';

    public ?int $expense_category_id = null;

    public string $information = '';

    public string $status = Expense::STATUS_ACTIVE;

    public string $enrichmentPrompt = '';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-expense-form-open')]
    public function openForm(?int $expenseId = null, ?string $mode = null): void
    {
        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $mode ?? ($expenseId ? 'edit' : 'create');
        $this->expenseId = $expenseId;

        if ($this->expenseId !== null) {
            $this->loadExpense();
        } else {
            $this->resetFields();
            $this->expense_number = app(ExpenseService::class)->nextExpenseNumber();
        }
    }

    #[On('finance-expense-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function enrichWithAi(): void
    {
        if ($this->enriching || $this->saving || ! $this->canEditFields()) {
            return;
        }

        if (! filled(trim($this->name_en))) {
            $this->setStatus(__('agricart.finance.expenses.ai.name_required'), 'error');

            return;
        }

        $this->enriching = true;
        $this->dispatchBusyState();

        try {
            $result = app(ExpenseEnrichmentService::class)->enrich(
                $this->name_en,
                filled($this->enrichmentPrompt) ? $this->enrichmentPrompt : null,
            );

            if (! $result['success']) {
                $this->setStatus($result['message'], 'error');

                return;
            }

            $data = $result['data'] ?? [];

            if (filled($data['name_en'] ?? null)) {
                $this->name_en = (string) $data['name_en'];
            }

            if (array_key_exists('name_ur', $data)) {
                $this->name_ur = (string) ($data['name_ur'] ?? '');
            }

            if (array_key_exists('information', $data)) {
                $this->information = (string) ($data['information'] ?? '');
            }

            $this->setStatus($result['message'], 'success');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->mode = 'create';
            $this->expenseId = null;
            $this->resetFields();
            $this->expense_number = app(ExpenseService::class)->nextExpenseNumber();
            $this->setStatus(__('agricart.finance.expenses.messages.saved_next'), 'success');
        }
    }

    public function isReadonly(): bool
    {
        return ! $this->canEditFields();
    }

    public function render(): View
    {
        /** @var Collection<int, ExpenseCategory> $categories */
        $categories = ExpenseCategory::query()
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->orderBy('name_en')
            ->get();

        return view('livewire.finance.expenses.form', [
            'categories' => $categories,
        ]);
    }

    private function persist(): bool
    {
        if ($this->saving || ! $this->canEditFields()) {
            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(ExpenseService::class);

            if ($this->mode === 'create') {
                $expense = $service->create($this->payload());
                $this->mode = 'edit';
                $this->expenseId = $expense->id;
                $this->expense_number = $expense->expense_number;
            } elseif ($this->expenseId !== null) {
                $expense = Expense::query()->findOrFail($this->expenseId);
                $service->update($expense, $this->payload());
            }

            $this->dispatch('finance-expenses-saved');
            $this->setStatus(__('agricart.finance.expenses.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.finance.expenses.messages.save_failed'), 'error');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'expense_number' => filled($this->expense_number) ? $this->expense_number : null,
            'expense_category_id' => $this->expense_category_id,
            'status' => $this->status,
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur !== '' ? $this->name_ur : null,
            'information' => $this->information !== '' ? $this->information : null,
        ];
    }

    private function loadExpense(): void
    {
        $expense = Expense::query()->findOrFail($this->expenseId);
        $this->expense_number = (string) $expense->expense_number;
        $this->name_en = (string) $expense->name_en;
        $this->name_ur = (string) ($expense->name_ur ?? '');
        $this->expense_category_id = $expense->expense_category_id;
        $this->information = (string) ($expense->information ?? '');
        $this->status = (string) $expense->status;
    }

    private function resetFields(): void
    {
        $this->expense_number = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->expense_category_id = null;
        $this->information = '';
        $this->status = Expense::STATUS_ACTIVE;
        $this->enrichmentPrompt = '';
    }

    private function canEditFields(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        $permission = $this->mode === 'create' ? 'finance.expenses.create' : 'finance.expenses.edit';

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::userHasKey($user, $permission);
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('finance-expense-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
