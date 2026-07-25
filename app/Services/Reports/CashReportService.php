<?php

namespace App\Services\Reports;

use App\Models\CashBookTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CashReportService
{
    public const INTERACTIVE_ROW_LIMIT = 500;

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     kpis: array<string, int|float|string>,
     *     generated_at: string
     * }
     */
    public function build(?string $search = null, ?string $periodFilter = null, string $cashFilter = 'all'): array
    {
        // Bank ledger lives elsewhere; this report is the cash drawer (cash book).
        if ($cashFilter === 'bank') {
            return [
                'rows' => [],
                'kpis' => [
                    'entries' => 0,
                    'cash_in' => $this->formatMoney(0),
                    'cash_out' => $this->formatMoney(0),
                    'balance' => $this->formatMoney(app(\App\Services\Finance\CashBookService::class)->balance()),
                ],
                'generated_at' => now()->toDateTimeString(),
            ];
        }

        [$start, $end] = ReportPeriod::range($periodFilter);

        $query = CashBookTransaction::query()
            ->orderBy('transaction_date')
            ->orderBy('id');

        $this->applyDateRange($query, 'transaction_date', $start, $end);

        if (filled($search)) {
            $needle = '%'.$search.'%';
            $query->where(function (Builder $inner) use ($needle): void {
                $inner->where('description', 'like', $needle)
                    ->orWhere('notes', 'like', $needle);
            });
        }

        $running = 0.0;
        $inTotal = 0.0;
        $outTotal = 0.0;
        $rows = [];

        foreach ($query->limit(self::INTERACTIVE_ROW_LIMIT)->get() as $tx) {
            $amount = (float) $tx->amount;
            $in = $tx->direction === CashBookTransaction::DIRECTION_IN ? $amount : 0.0;
            $out = $tx->direction === CashBookTransaction::DIRECTION_OUT ? $amount : 0.0;
            $running = round($running + $in - $out, 2);
            $inTotal += $in;
            $outTotal += $out;

            $rows[] = [
                'id' => $tx->id,
                'date' => $tx->transaction_date?->format('d M Y') ?? '—',
                'account' => __('reports.filters.cash_on_hand'),
                'type' => $tx->direction === CashBookTransaction::DIRECTION_IN
                    ? __('agricart.finance.cash_book.directions.in')
                    : __('agricart.finance.cash_book.directions.out'),
                'reference' => (string) ($tx->description ?: '—'),
                'cash_in' => $in > 0 ? $this->formatMoney($in) : '—',
                'cash_out' => $out > 0 ? $this->formatMoney($out) : '—',
                'balance' => $this->formatMoney($running),
            ];
        }

        return [
            'rows' => array_reverse($rows),
            'kpis' => [
                'entries' => count($rows),
                'cash_in' => $this->formatMoney($inTotal),
                'cash_out' => $this->formatMoney($outTotal),
                'balance' => $this->formatMoney(app(\App\Services\Finance\CashBookService::class)->balance()),
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
            'account' => __('reports.columns.account'),
            'type' => __('reports.columns.type'),
            'reference' => __('reports.columns.reference'),
            'cash_in' => __('reports.columns.in'),
            'cash_out' => __('reports.columns.out'),
            'balance' => __('reports.columns.balance'),
        ];

        $columns = array_values(array_filter($visibleColumns, fn (string $col): bool => isset($labels[$col])));
        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $col): string => (string) ($row[$col] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * @param  Builder<\App\Models\CashBookTransaction>  $query
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
