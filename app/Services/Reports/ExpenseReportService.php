<?php

namespace App\Services\Reports;

use App\Models\ExpensePayment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ExpenseReportService
{
    public const INTERACTIVE_ROW_LIMIT = 500;

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     kpis: array<string, int|float|string>,
     *     generated_at: string
     * }
     */
    public function build(?string $search = null, ?string $periodFilter = null, string $expenseFilter = 'all'): array
    {
        [$start, $end] = ReportPeriod::range($periodFilter);

        $query = ExpensePayment::query()
            ->with(['expense.category', 'bankAccount'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        $this->applyDateRange($query, 'payment_date', $start, $end);

        if (in_array($expenseFilter, ['operating', 'other'], true)) {
            $query->whereHas('expense.category', fn (Builder $q) => $q->where('kind', $expenseFilter));
        }

        if (filled($search)) {
            $needle = '%'.$search.'%';
            $query->where(function (Builder $inner) use ($needle): void {
                $inner->where('payment_number', 'like', $needle)
                    ->orWhere('notes', 'like', $needle)
                    ->orWhereHas('expense', function (Builder $expense) use ($needle): void {
                        $expense->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('expense_number', 'like', $needle);
                    });
            });
        }

        $payments = $query->limit(self::INTERACTIVE_ROW_LIMIT)->get();
        $total = 0.0;
        $rows = [];

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $total += $amount;
            $expense = $payment->expense;

            $rows[] = [
                'id' => $payment->id,
                'date' => $payment->payment_date?->format('d M Y') ?? '—',
                'category' => $expense?->category?->displayName() ?? '—',
                'reference' => (string) ($payment->payment_number ?: '—'),
                'description' => $expense?->displayName() ?? (string) ($payment->notes ?: '—'),
                'amount' => $this->formatMoney($amount),
                'amount_raw' => $amount,
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'payments' => count($rows),
                'total_amount' => $this->formatMoney($total),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $visibleColumns
     * @return list<list<string>>
     */
    public function csvMatrix(array $rows, array $visibleColumns): array
    {
        $labels = [
            'date' => __('reports.columns.date'),
            'category' => __('reports.columns.category'),
            'reference' => __('reports.columns.reference'),
            'description' => __('reports.columns.description'),
            'amount' => __('reports.columns.amount'),
        ];

        $columns = array_values(array_filter($visibleColumns, fn (string $col): bool => isset($labels[$col])));
        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $col): string => (string) ($row[$col] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * @param  Builder<\App\Models\ExpensePayment>  $query
     */
    private function applyDateRange(Builder $query, string $column, ?CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        if ($start !== null) {
            $query->whereDate($column, '>=', $start->toDateString());
        }

        if ($end !== null) {
            $query->whereDate($column, '<=', $end->toDateString());
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2).' PKR';
    }
}
