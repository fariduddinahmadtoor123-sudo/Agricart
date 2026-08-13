<?php

namespace App\Livewire\Finance;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Finance\ExpenseCategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ExpenseCategoryForm extends Component
{
    public string $mode = 'create';

    public ?int $categoryId = null;

    public string $code = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $kind = ExpenseCategory::KIND_OPERATING;

    public string $status = ExpenseCategory::STATUS_ACTIVE;

    public string $notes = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-expense-category-form-open')]
    public function openForm(?int $categoryId = null, ?string $mode = null): void
    {
        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $mode ?? ($categoryId ? 'edit' : 'create');
        $this->categoryId = $categoryId;

        if ($this->categoryId !== null) {
            $this->loadCategory();
        } else {
            $this->resetFields();
            $this->code = app(ExpenseCategoryService::class)->nextCode();
        }
    }

    #[On('finance-expense-category-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
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
            $this->categoryId = null;
            $this->resetFields();
            $this->code = app(ExpenseCategoryService::class)->nextCode();
            $this->setStatus(__('agricart.finance.expense_categories.messages.saved_next'), 'success');
        }
    }

    public function isReadonly(): bool
    {
        return ! $this->canEditFields();
    }

    public function render(): View
    {
        return view('livewire.finance.expense-categories.form');
    }

    private function persist(): bool
    {
        if ($this->saving || ! $this->canEditFields()) {
            return false;
        }

        $this->saving = true;
        $this->dispatch('finance-expense-category-form-busy', saving: true);

        try {
            $service = app(ExpenseCategoryService::class);
            $payload = [
                'code' => filled($this->code) ? $this->code : null,
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur !== '' ? $this->name_ur : null,
                'kind' => $this->kind,
                'status' => $this->status,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ];

            if ($this->mode === 'create') {
                $category = $service->create($payload);
                $this->mode = 'edit';
                $this->categoryId = $category->id;
                $this->code = $category->code;
            } elseif ($this->categoryId !== null) {
                $category = ExpenseCategory::query()->findOrFail($this->categoryId);
                $service->update($category, $payload);
            }

            $this->dispatch('finance-expense-categories-saved');
            $this->setStatus(__('agricart.finance.expense_categories.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.finance.expense_categories.messages.save_failed'), 'error');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('finance-expense-category-form-busy', saving: false);
        }
    }

    private function loadCategory(): void
    {
        $category = ExpenseCategory::query()->findOrFail($this->categoryId);
        $this->code = (string) $category->code;
        $this->name_en = (string) $category->name_en;
        $this->name_ur = (string) ($category->name_ur ?? '');
        $this->kind = (string) $category->kind;
        $this->status = (string) $category->status;
        $this->notes = (string) ($category->notes ?? '');
    }

    private function resetFields(): void
    {
        $this->code = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->kind = ExpenseCategory::KIND_OPERATING;
        $this->status = ExpenseCategory::STATUS_ACTIVE;
        $this->notes = '';
    }

    private function canEditFields(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        $permission = $this->mode === 'create' ? 'finance.expense-categories.create' : 'finance.expense-categories.edit';

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::userHasKey($user, $permission);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
