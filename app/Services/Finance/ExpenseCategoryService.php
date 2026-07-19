<?php

namespace App\Services\Finance;

use App\Models\ExpenseCategory;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExpenseCategory
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): ExpenseCategory {
            if (! filled($validated['code'] ?? null)) {
                $validated['code'] = $this->nextCode();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            return ExpenseCategory::query()->create($validated);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $validated = $this->validate($data, $category->id);

        $category->update($validated);

        return $category->fresh();
    }

    public function delete(ExpenseCategory $category): void
    {
        if ($category->expenses()->exists()) {
            throw ValidationException::withMessages([
                'category' => [__('agricart.finance.expense_categories.messages.has_expenses')],
            ]);
        }

        $category->delete();
    }

    public function nextCode(): string
    {
        return app(DocumentCounterService::class)->next(
            'expense_categories.code', 'EXC-', 5,
            fn (): int => DocumentCounterService::maximumSuffix('expense_categories', 'code', 'EXC-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        $uniqueCode = Rule::unique('expense_categories', 'code');

        if ($ignoreId !== null) {
            $uniqueCode->ignore($ignoreId);
        }

        return validator($data, [
            'code' => ['nullable', 'string', 'max:32', $uniqueCode],
            'status' => ['required', Rule::in(ExpenseCategory::STATUSES)],
            'kind' => ['required', Rule::in(ExpenseCategory::KINDS)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();
    }
}
