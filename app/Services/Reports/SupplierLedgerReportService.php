<?php

namespace App\Services\Reports;

use App\Models\FundTransfer;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SupplierLedgerReportService
{
    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     kpis: array<string, int|float|string>,
     *     generated_at: string
     * }
     */
    public function build(?string $search = null, ?string $periodFilter = null, ?string $partyFilter = null): array
    {
        [$start, $end] = ReportPeriod::range($periodFilter);
        $suppliers = $this->supplierQuery($search)->get();
        $supplierIds = $suppliers->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $lifetimeAggregates = $this->batchSupplierMovement($supplierIds, null, null);
        $beforeAggregates = $start !== null
            ? $this->batchSupplierMovement($supplierIds, null, $start->subDay())
            : [];
        $periodAggregates = $this->batchSupplierMovement($supplierIds, $start, $end);

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalPayable = 0.0;
        $totalAdvance = 0.0;

        foreach ($suppliers as $supplier) {
            $lifetime = $lifetimeAggregates[$supplier->id] ?? ['debit' => 0.0, 'credit' => 0.0];
            $opening = $this->numeric($supplier->opening_balance) - $lifetime['debit'] + $lifetime['credit'];

            if ($start !== null) {
                $before = $beforeAggregates[$supplier->id] ?? ['debit' => 0.0, 'credit' => 0.0];
                $opening += $before['debit'] - $before['credit'];
            }

            $movement = $periodAggregates[$supplier->id] ?? ['debit' => 0.0, 'credit' => 0.0];
            $debit = $movement['debit'];
            $credit = $movement['credit'];
            $balance = $opening + $debit - $credit;

            if ($partyFilter === 'balance' && abs($balance) < 0.005) {
                continue;
            }

            if ($partyFilter === 'zero' && abs($balance) >= 0.005) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($balance > 0) {
                $totalPayable += $balance;
            } elseif ($balance < 0) {
                $totalAdvance += abs($balance);
            }

            $rows[] = [
                'id' => $supplier->id,
                'supplier' => $supplier->displayBusinessName(),
                'phone' => $supplier->primaryMobile() ?: '—',
                'opening' => $this->formatMoney($opening),
                'opening_numeric' => $opening,
                'debit' => $this->formatMoney($debit),
                'debit_numeric' => $debit,
                'credit' => $this->formatMoney($credit),
                'credit_numeric' => $credit,
                'balance' => $this->formatMoney($balance),
                'balance_numeric' => $balance,
                'balance_type' => $balance > 0 ? 'payable' : ($balance < 0 ? 'advance' : 'settled'),
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'total_suppliers' => count($rows),
                'total_debit' => $this->formatMoney($totalDebit),
                'total_credit' => $this->formatMoney($totalCredit),
                'payable' => $this->formatMoney($totalPayable),
                'advance' => $this->formatMoney($totalAdvance),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Industry-standard dated AP ledger for one supplier:
     * opening → purchase invoices / payments / vendor payments with running balance.
     *
     * @return array{
     *     party: array{id: int, name: string, phone: string, number: string},
     *     summary: array{opening: string, debit: string, credit: string, balance: string, balance_type: string},
     *     lines: list<array<string, mixed>>,
     *     generated_at: string
     * }
     */
    public function history(int $supplierId, ?string $periodFilter = null): array
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        [$start, $end] = ReportPeriod::range($periodFilter);

        $opening = $this->seedOpening($supplier);

        if ($start !== null) {
            $opening += $this->movementBefore($supplier->id, $start);
        }

        $movement = $this->movementWithin($supplier->id, $start, $end);
        $closing = $opening + $movement['debit'] - $movement['credit'];

        $events = [];

        $purchases = Purchase::query()->where('supplier_id', $supplier->id);
        $this->applyDateRange($purchases, 'purchase_date', $start, $end);

        foreach ($purchases->orderBy('purchase_date')->orderBy('id')->get() as $purchase) {
            $date = $purchase->purchase_date?->toDateString() ?? '';
            $group = sprintf('1-purchase-%010d', $purchase->id);
            $total = $this->numeric($purchase->total_amount);
            $paid = $this->numeric($purchase->amount_paid);

            if ($total > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 10,
                    'date' => $date,
                    'type' => 'purchase',
                    'type_label' => __('reports.supplier_ledger.types.purchase'),
                    'reference' => (string) $purchase->purchase_number,
                    'particulars' => __('reports.supplier_ledger.particulars.purchase', [
                        'number' => $purchase->purchase_number,
                    ]),
                    'debit' => $total,
                    'credit' => 0.0,
                    'method' => '—',
                ];
            }

            if ($paid > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 20,
                    'date' => $date,
                    'type' => 'payment',
                    'type_label' => __('reports.supplier_ledger.types.payment'),
                    'reference' => (string) $purchase->purchase_number,
                    'particulars' => __('reports.supplier_ledger.particulars.payment_purchase', [
                        'number' => $purchase->purchase_number,
                    ]),
                    'debit' => 0.0,
                    'credit' => $paid,
                    'method' => __('reports.supplier_ledger.methods.on_invoice'),
                ];
            }
        }

        $transfers = FundTransfer::query()
            ->where('transaction_type', FundTransfer::TYPE_VENDOR_PAYMENT)
            ->where('destination_supplier_id', $supplier->id);
        $this->applyDateRange($transfers, 'transaction_date', $start, $end);

        foreach ($transfers->orderBy('transaction_date')->orderBy('id')->get() as $transfer) {
            $date = $transfer->transaction_date?->toDateString() ?? '';
            $amount = $this->numeric($transfer->amount);
            $method = $this->walletLabel((string) ($transfer->source_wallet ?: '—'));

            if ($amount <= 0) {
                continue;
            }

            $events[] = [
                'sort_date' => $date,
                'sort_group' => sprintf('2-transfer-%010d', $transfer->id),
                'sort_rank' => 10,
                'date' => $date,
                'type' => 'vendor_payment',
                'type_label' => __('reports.supplier_ledger.types.vendor_payment'),
                'reference' => (string) $transfer->transfer_number,
                'particulars' => __('reports.supplier_ledger.particulars.vendor_payment', [
                    'number' => $transfer->transfer_number,
                    'method' => $method,
                ]),
                'debit' => 0.0,
                'credit' => $amount,
                'method' => $method,
            ];
        }

        usort($events, function (array $a, array $b): int {
            return [$a['sort_date'], $a['sort_group'], $a['sort_rank']]
                <=> [$b['sort_date'], $b['sort_group'], $b['sort_rank']];
        });

        $running = $opening;
        $lines = [[
            'date' => $start?->toDateString() ?? '—',
            'type' => 'opening',
            'type_label' => __('reports.supplier_ledger.types.opening'),
            'reference' => '—',
            'particulars' => __('reports.supplier_ledger.particulars.opening'),
            'method' => '—',
            'debit' => $this->formatMoney(0),
            'credit' => $this->formatMoney(0),
            'balance' => $this->formatMoney($running),
        ]];

        foreach ($events as $event) {
            $running += $event['debit'] - $event['credit'];

            $lines[] = [
                'date' => $event['date'],
                'type' => $event['type'],
                'type_label' => $event['type_label'],
                'reference' => $event['reference'],
                'particulars' => $event['particulars'],
                'method' => $event['method'],
                'debit' => $this->formatMoney($event['debit']),
                'credit' => $this->formatMoney($event['credit']),
                'balance' => $this->formatMoney($running),
            ];
        }

        return [
            'party' => [
                'id' => $supplier->id,
                'name' => $supplier->displayBusinessName(),
                'phone' => $supplier->primaryMobile() ?: '—',
                'number' => (string) ($supplier->supplier_code ?: '—'),
            ],
            'summary' => [
                'opening' => $this->formatMoney($opening),
                'debit' => $this->formatMoney($movement['debit']),
                'credit' => $this->formatMoney($movement['credit']),
                'balance' => $this->formatMoney($closing),
                'balance_type' => $closing > 0 ? 'payable' : ($closing < 0 ? 'advance' : 'settled'),
            ],
            'lines' => $lines,
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
            'supplier' => __('reports.columns.supplier'),
            'phone' => __('reports.columns.phone'),
            'opening' => __('reports.columns.opening'),
            'debit' => __('reports.columns.debit'),
            'credit' => __('reports.columns.credit'),
            'balance' => __('reports.columns.balance'),
        ];

        $columns = array_values(array_filter(
            $visibleColumns,
            fn (string $col): bool => isset($labels[$col]),
        ));

        if ($columns === []) {
            $columns = ['supplier', 'phone', 'opening', 'debit', 'credit', 'balance'];
        }

        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $col): string => (string) ($row[$col] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * suppliers.opening_balance is a running AP total. Reconstruct the seeded opening
     * so period ledgers mirror Customer Ledger without double-counting.
     */
    private function seedOpening(Supplier $supplier): float
    {
        $running = $this->numeric($supplier->opening_balance);
        $lifetime = $this->movementWithin($supplier->id, null, null);

        return $running - $lifetime['debit'] + $lifetime['credit'];
    }

    /**
     * @return Builder<Supplier>
     */
    private function supplierQuery(?string $search): Builder
    {
        $query = Supplier::query()
            ->orderBy('business_name_en')
            ->orderBy('business_name_ur');

        if (filled($search)) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('supplier_code', 'like', $term)
                    ->orWhere('business_name_en', 'like', $term)
                    ->orWhere('business_name_ur', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('city_en', 'like', $term)
                    ->orWhere('city_ur', 'like', $term)
                    ->orWhere('contacts', 'like', $term);
            });
        }

        return $query;
    }

    private function movementBefore(int $supplierId, CarbonImmutable $before): float
    {
        $movement = $this->movementWithin($supplierId, null, $before->subDay());

        return $movement['debit'] - $movement['credit'];
    }

    /**
     * @param  list<int>  $supplierIds
     * @return array<int, array{debit: float, credit: float}>
     */
    private function batchSupplierMovement(array $supplierIds, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $supplierIds = array_values(array_unique(array_filter(array_map('intval', $supplierIds))));

        if ($supplierIds === []) {
            return [];
        }

        $result = [];

        foreach ($supplierIds as $supplierId) {
            $result[$supplierId] = ['debit' => 0.0, 'credit' => 0.0];
        }

        $purchases = Purchase::query()->whereIn('supplier_id', $supplierIds);
        $this->applyDateRange($purchases, 'purchase_date', $start, $end);

        foreach ($purchases
            ->selectRaw('supplier_id, COALESCE(SUM(total_amount), 0) as debit_sum, COALESCE(SUM(amount_paid), 0) as credit_sum')
            ->groupBy('supplier_id')
            ->get() as $row) {
            $id = (int) $row->supplier_id;
            $result[$id]['debit'] += (float) $row->debit_sum;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        $transfers = FundTransfer::query()
            ->where('transaction_type', FundTransfer::TYPE_VENDOR_PAYMENT)
            ->whereIn('destination_supplier_id', $supplierIds);
        $this->applyDateRange($transfers, 'transaction_date', $start, $end);

        foreach ($transfers
            ->selectRaw('destination_supplier_id as supplier_id, COALESCE(SUM(amount), 0) as credit_sum')
            ->groupBy('destination_supplier_id')
            ->get() as $row) {
            $id = (int) $row->supplier_id;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        return $result;
    }

    /**
     * @return array{debit: float, credit: float}
     */
    private function movementWithin(int $supplierId, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $debit = 0.0;
        $credit = 0.0;

        $purchases = Purchase::query()->where('supplier_id', $supplierId);
        $this->applyDateRange($purchases, 'purchase_date', $start, $end);
        $debit += $this->sumDecimal($purchases, 'total_amount');
        $credit += $this->sumDecimal($purchases, 'amount_paid');

        $transfers = FundTransfer::query()
            ->where('transaction_type', FundTransfer::TYPE_VENDOR_PAYMENT)
            ->where('destination_supplier_id', $supplierId);
        $this->applyDateRange($transfers, 'transaction_date', $start, $end);
        $credit += $this->sumDecimal($transfers, 'amount');

        return ['debit' => $debit, 'credit' => $credit];
    }

    private function applyDateRange(Builder $query, string $column, ?CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        if ($start !== null) {
            $query->whereDate($column, '>=', $start->toDateString());
        }

        if ($end !== null) {
            $query->whereDate($column, '<=', $end->toDateString());
        }
    }

    /**
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private function sumDecimal(Builder $query, string $column): float
    {
        return (float) $query->sum($column);
    }

    private function numeric(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function walletLabel(string $wallet): string
    {
        $wallet = trim($wallet);

        if ($wallet === '' || $wallet === '—') {
            return '—';
        }

        $key = 'agricart.finance.fund_transfers.source_wallets.'.$wallet;
        $label = __($key);

        return $label === $key
            ? ucfirst(str_replace('_', ' ', $wallet))
            : $label;
    }
}
