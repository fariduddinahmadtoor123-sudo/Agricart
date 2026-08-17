<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Expense
    {
        $validated = $this->validate($data);
        $actor ??= auth()->user();

        return DB::transaction(function () use ($validated, $actor): Expense {
            if (! filled($validated['expense_number'] ?? null)) {
                $validated['expense_number'] = $this->nextExpenseNumber();
            }

            $validated['created_by'] = $actor?->id;

            return Expense::query()->create($validated)->fresh(['category']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data): Expense
    {
        $validated = $this->validate($data, $expense->id);

        $expense->update($validated);

        return $expense->fresh(['category']);
    }

    public function delete(Expense $expense): void
    {
        if ($expense->payments()->exists()) {
            throw ValidationException::withMessages([
                'expense' => [__('agricart.finance.expenses.messages.has_payments')],
            ]);
        }

        $expense->delete();
    }

    public function nextExpenseNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'expenses.expense_number',
            'EXP-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('expenses', 'expense_number', 'EXP-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        $uniqueNumber = Rule::unique('expenses', 'expense_number');

        if ($ignoreId !== null) {
            $uniqueNumber->ignore($ignoreId);
        }

        return validator($data, [
            'expense_number' => ['nullable', 'string', 'max:32', $uniqueNumber],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'status' => ['required', Rule::in(Expense::STATUSES)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'information' => ['nullable', 'string', 'max:5000'],
            'issue_day' => ['nullable', 'integer', 'min:1', 'max:31', 'required_with:due_day'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'detail_1' => ['nullable', 'string', 'max:120'],
            'detail_2' => ['nullable', 'string', 'max:120'],
            'detail_3' => ['nullable', 'string', 'max:120'],
            'detail_4' => ['nullable', 'string', 'max:120'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ])->validate();
    }
}
