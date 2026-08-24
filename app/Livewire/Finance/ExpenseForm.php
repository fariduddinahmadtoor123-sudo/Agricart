<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Finance\ExpenseEnrichmentService;
use App\Services\Finance\ExpenseScheduleService;
use App\Services\Finance\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ExpenseForm extends Component
{
    use AuthorizesSensitiveActions;
    use WithFileUploads;

    public string $mode = 'create';

    public ?int $expenseId = null;

    public string $expense_number = '';

    public string $name_en = '';

    public string $name_ur = '';

    /** @var int|string|null */
    public $expense_category_id = null;

    public string $information = '';

    /** @var int|string|null */
    public $issue_day = null;

    /** @var int|string|null */
    public $due_day = null;

    public string $detail_1 = '';

    public string $detail_2 = '';

    public string $detail_3 = '';

    public string $detail_4 = '';

    public string $estimated_cost = '';

    public string $status = Expense::STATUS_ACTIVE;

    public ?TemporaryUploadedFile $billImageUpload = null;

    public ?string $storedBillImagePath = null;

    public ?string $billImagePreviewUrl = null;

    public bool $remove_bill_image = false;

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('finance-expense-form-open')]
    public function openForm(?int $expenseId = null, ?string $mode = null): void
    {
        $resolvedMode = $mode ?? ($expenseId ? 'edit' : 'create');
        $this->authorizeAny(
            $resolvedMode === 'view'
                ? ['finance.expenses.view', 'finance.expenses.edit']
                : ($expenseId ? 'finance.expenses.edit' : 'finance.expenses.create')
        );

        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $resolvedMode;
        $this->expenseId = $expenseId;

        if ($this->expenseId !== null) {
            $this->loadExpense();
        } else {
            $this->resetFields();
            $this->expense_number = app(ExpenseService::class)->nextExpenseNumber();
        }
    }

    #[On('finance-expense-form-action')]
    public function handlePanelAction(mixed $action = null): void
    {
        $this->authorizeAny($this->requiredActionPermission());

        match ($this->panelActionKey($action)) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedBillImageUpload(): void
    {
        $this->validateOnly('billImageUpload', [
            'billImageUpload' => ['nullable', 'image', 'max:12288'],
        ]);

        $this->billImagePreviewUrl = $this->billImageUpload?->temporaryUrl();
        $this->remove_bill_image = false;
    }

    public function removeBillImage(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->billImageUpload = null;
        $this->billImagePreviewUrl = null;
        $this->storedBillImagePath = null;
        $this->remove_bill_image = true;
    }

    public function enrichWithAi(): void
    {
        $this->authorizeAny($this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create');

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
            $result = app(ExpenseEnrichmentService::class)->enrich($this->name_en, null);

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
        $this->authorizeAny($this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create');

        if ($this->persist()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        $this->authorizeAny($this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create');

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
        return $this->mode === 'view' || ! $this->canEditFields();
    }

    public function markCycleDone(): void
    {
        $this->authorizeAny('finance.expenses.edit');

        if ($this->expenseId === null || $this->mode === 'view' || ! $this->canEditFields()) {
            return;
        }

        try {
            $expense = Expense::query()->with(['payments', 'billCycles'])->findOrFail($this->expenseId);
            app(ExpenseScheduleService::class)->markOldestOpenDone($expense, auth()->user() instanceof User ? auth()->user() : null);
            $this->dispatch('finance-expenses-saved');
            $this->setStatus(__('agricart.finance.expenses.messages.cycle_done'), 'success');
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.finance.expenses.messages.save_failed'), 'error');
        }
    }

    /**
     * @return array{type: string, message: string}|null
     */
    public function dueNotice(): ?array
    {
        if ($this->nullableInt($this->issue_day) === null) {
            return null;
        }

        try {
            return $this->dueNoticeForSchedule();
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'type' => 'info',
                'message' => __('agricart.finance.expenses.due.schedule_set'),
            ];
        }
    }

    /**
     * @return array{type: string, message: string}|null
     */
    private function dueNoticeForSchedule(): ?array
    {
        $schedule = app(ExpenseScheduleService::class);
        $stub = $this->schedulePreviewExpense();

        if ($stub === null) {
            return [
                'type' => 'info',
                'message' => __('agricart.finance.expenses.due.schedule_set'),
            ];
        }

        $open = $schedule->oldestOpenCycle($stub);
        $status = $schedule->dueStatus($stub);

        if ($status === Expense::DUE_UPCOMING && $open === null) {
            $cycle = $schedule->cycleForMonth($stub, (int) now()->year, (int) now()->month);

            if ($cycle === null) {
                return [
                    'type' => 'info',
                    'message' => __('agricart.finance.expenses.due.schedule_set'),
                ];
            }

            return [
                'type' => 'info',
                'message' => __('agricart.finance.expenses.due.upcoming', [
                    'date' => $cycle['issue_on']->toDateString(),
                ]),
            ];
        }

        if ($status === Expense::DUE_PAID) {
            return [
                'type' => 'success',
                'message' => __('agricart.finance.expenses.due.paid'),
            ];
        }

        $due = $open['due_on'] ?? null;
        $issue = $open['issue_on'] ?? null;

        if ($status === Expense::DUE_OVERDUE) {
            return [
                'type' => 'error',
                'message' => __('agricart.finance.expenses.due.overdue', [
                    'date' => $due?->toDateString() ?? '',
                ]),
            ];
        }

        if ($status === Expense::DUE_SOON) {
            return [
                'type' => 'warning',
                'message' => __('agricart.finance.expenses.due.due_soon', [
                    'date' => $due?->toDateString() ?? '',
                ]),
            ];
        }

        return [
            'type' => 'warning',
            'message' => $due !== null
                ? __('agricart.finance.expenses.due.issued_with_due', ['date' => $due->toDateString()])
                : __('agricart.finance.expenses.due.issued', ['date' => $issue?->toDateString() ?? '']),
        ];
    }

    public function hasOpenCycle(): bool
    {
        if ($this->expenseId === null || $this->nullableInt($this->issue_day) === null) {
            return false;
        }

        try {
            $expense = Expense::query()->with(['payments', 'billCycles'])->find($this->expenseId);

            return $expense instanceof Expense
                && app(ExpenseScheduleService::class)->oldestOpenCycle($expense) !== null;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
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
        $this->authorizeAny($this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create');

        if ($this->saving) {
            return false;
        }

        if ($this->mode === 'view' || ! $this->canEditFields()) {
            $this->setStatus(__('agricart.errors.denied'), 'error');

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            if ($this->billImageUpload !== null) {
                $this->validate([
                    'billImageUpload' => ['nullable', 'image', 'max:12288'],
                ]);
            }

            $service = app(ExpenseService::class);

            if ($this->mode === 'create') {
                $expense = $service->create($this->payload(), null, $this->billImageUpload);
                $this->mode = 'edit';
                $this->expenseId = $expense->id;
                $this->expense_number = $expense->expense_number;
            } elseif ($this->expenseId !== null) {
                $expense = Expense::query()->findOrFail($this->expenseId);
                $expense = $service->update(
                    $expense,
                    $this->payload(),
                    $this->billImageUpload,
                    $this->remove_bill_image,
                );
            } else {
                return false;
            }

            $this->billImageUpload = null;
            $this->remove_bill_image = false;
            $this->storedBillImagePath = $expense->bill_image_path;
            $this->billImagePreviewUrl = $expense->billImageUrl();

            $this->dispatch('finance-expenses-saved');
            $this->setStatus(__('agricart.finance.expenses.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.finance.expenses.messages.save_failed'), 'error');

            return false;
        } catch (\Throwable $exception) {
            report($exception);
            $this->setStatus(__('agricart.finance.expenses.messages.save_failed'), 'error');

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
            'expense_category_id' => $this->nullableInt($this->expense_category_id),
            'status' => $this->status,
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur !== '' ? $this->name_ur : null,
            'information' => $this->information !== '' ? $this->information : null,
            'issue_day' => $this->nullableDay($this->issue_day),
            'due_day' => $this->nullableDay($this->due_day),
            'detail_1' => $this->nullableString($this->detail_1),
            'detail_2' => $this->nullableString($this->detail_2),
            'detail_3' => $this->nullableString($this->detail_3),
            'detail_4' => $this->nullableString($this->detail_4),
            'estimated_cost' => $this->nullableAmount($this->estimated_cost),
        ];
    }

    private function loadExpense(): void
    {
        $this->authorizeAny($this->mode === 'view' ? ['finance.expenses.view', 'finance.expenses.edit'] : 'finance.expenses.edit');
        $expense = Expense::query()->findOrFail($this->expenseId);
        $this->expense_number = (string) $expense->expense_number;
        $this->name_en = (string) $expense->name_en;
        $this->name_ur = (string) ($expense->name_ur ?? '');
        $this->expense_category_id = $expense->expense_category_id;
        $this->information = (string) ($expense->information ?? '');
        $this->issue_day = $expense->issue_day;
        $this->due_day = $expense->due_day;
        $this->detail_1 = (string) ($expense->detail_1 ?? '');
        $this->detail_2 = (string) ($expense->detail_2 ?? '');
        $this->detail_3 = (string) ($expense->detail_3 ?? '');
        $this->detail_4 = (string) ($expense->detail_4 ?? '');
        $this->estimated_cost = $expense->estimated_cost !== null
            ? number_format((float) $expense->estimated_cost, 2, '.', '')
            : '';
        $this->status = (string) $expense->status;
        $this->billImageUpload = null;
        $this->remove_bill_image = false;
        $this->storedBillImagePath = $expense->bill_image_path;
        $this->billImagePreviewUrl = $expense->billImageUrl();
    }

    private function resetFields(): void
    {
        $this->expense_number = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->expense_category_id = null;
        $this->information = '';
        $this->issue_day = null;
        $this->due_day = null;
        $this->detail_1 = '';
        $this->detail_2 = '';
        $this->detail_3 = '';
        $this->detail_4 = '';
        $this->estimated_cost = '';
        $this->status = Expense::STATUS_ACTIVE;
        $this->billImageUpload = null;
        $this->storedBillImagePath = null;
        $this->billImagePreviewUrl = null;
        $this->remove_bill_image = false;
    }

    private function canEditFields(): bool
    {
        return $this->canAccessAny(
            $this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create'
        );
    }

    private function requiredActionPermission(): array|string
    {
        return $this->isReadonly()
            ? ['finance.expenses.view', 'finance.expenses.edit']
            : ($this->expenseId ? 'finance.expenses.edit' : 'finance.expenses.create');
    }

    private function schedulePreviewExpense(): ?Expense
    {
        if ($this->expenseId !== null) {
            $expense = Expense::query()->with(['payments', 'billCycles'])->find($this->expenseId);

            if ($expense instanceof Expense) {
                $expense->issue_day = $this->nullableDay($this->issue_day);
                $expense->due_day = $this->nullableDay($this->due_day);

                return $expense;
            }
        }

        $expense = new Expense([
            'issue_day' => $this->nullableDay($this->issue_day),
            'due_day' => $this->nullableDay($this->due_day),
            'status' => Expense::STATUS_ACTIVE,
        ]);
        $expense->created_at = now();
        $expense->setRelation('payments', collect());
        $expense->setRelation('billCycles', collect());

        return $expense;
    }

    private function panelActionKey(mixed $action): string
    {
        if (is_array($action)) {
            $action = $action['action'] ?? $action[0] ?? '';
        }

        return is_string($action) ? $action : '';
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableDay(mixed $value): ?int
    {
        $day = $this->nullableInt($value);

        return ($day !== null && $day >= 1) ? $day : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function nullableAmount(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
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
