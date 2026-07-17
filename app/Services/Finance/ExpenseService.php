<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
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
        $next = (int) Expense::query()->max('id') + 1;

        do {
            $number = 'EXP-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Expense::query()->where('expense_number', $number)->exists());

        return $number;
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
        ])->validate();
    }
}
