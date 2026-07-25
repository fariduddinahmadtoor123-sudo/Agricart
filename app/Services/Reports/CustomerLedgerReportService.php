<?php

namespace App\Services\Reports;

use App\Filament\Clusters\Contacts\CustomersPage;
use App\Models\Customer;
use App\Models\FundTransfer;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CustomerLedgerReportService
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
        $customers = $this->customerQuery($search)->get();
        $customerIds = $customers->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $beforeAggregates = $start !== null
            ? $this->batchCustomerMovement($customerIds, null, $start->subDay())
            : [];

        $periodAggregates = $this->batchCustomerMovement($customerIds, $start, $end);

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalReceivable = 0.0;
        $totalAdvance = 0.0;

        foreach ($customers as $customer) {
            $opening = $this->numeric($customer->opening_balance);

            if ($start !== null) {
                $before = $beforeAggregates[$customer->id] ?? ['debit' => 0.0, 'credit' => 0.0];
                $opening += $before['debit'] - $before['credit'];
            }

            $movement = $periodAggregates[$customer->id] ?? ['debit' => 0.0, 'credit' => 0.0];
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
                $totalReceivable += $balance;
            } elseif ($balance < 0) {
                $totalAdvance += abs($balance);
            }

            $rows[] = [
                'id' => $customer->id,
                'customer' => $customer->displayLabel(),
                'phone' => $customer->primaryPhone() ?: '—',
                'opening' => $this->formatMoney($opening),
                'opening_numeric' => $opening,
                'debit' => $this->formatMoney($debit),
                'debit_numeric' => $debit,
                'credit' => $this->formatMoney($credit),
                'credit_numeric' => $credit,
                'balance' => $this->formatMoney($balance),
                'balance_numeric' => $balance,
                'balance_type' => $balance > 0 ? 'receivable' : ($balance < 0 ? 'advance' : 'settled'),
                'contact_url' => $this->contactUrl(),
            ];
        }

        return [
            'rows' => $rows,
            'kpis' => [
                'total_customers' => count($rows),
                'total_debit' => $this->formatMoney($totalDebit),
                'total_credit' => $this->formatMoney($totalCredit),
                'receivable' => $this->formatMoney($totalReceivable),
                'advance' => $this->formatMoney($totalAdvance),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Industry-standard dated ledger for one customer:
     * opening → invoices / payments / returns with running balance.
     *
     * @return array{
     *     customer: array{id: int, name: string, phone: string, number: string},
     *     summary: array{opening: string, debit: string, credit: string, balance: string, balance_type: string},
     *     lines: list<array<string, mixed>>,
     *     generated_at: string
     * }
     */
    public function history(int $customerId, ?string $periodFilter = null): array
    {
        $customer = Customer::query()->findOrFail($customerId);
        [$start, $end] = ReportPeriod::range($periodFilter);

        $opening = $this->numeric($customer->opening_balance);

        if ($start !== null) {
            $opening += $this->movementBefore($customer->id, $start);
        }

        $movement = $this->movementWithin($customer->id, $start, $end);
        $closing = $opening + $movement['debit'] - $movement['credit'];

        $events = [];

        $sales = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($sales, 'sale_date', $start, $end);

        foreach ($sales->orderBy('sale_date')->orderBy('id')->get() as $sale) {
            $date = $sale->sale_date?->toDateString() ?? '';
            $group = sprintf('1-sale-%010d', $sale->id);
            $total = $this->numeric($sale->total_amount);
            $paid = $this->numeric($sale->amount_paid);
            $method = $this->paymentMethodLabel((string) ($sale->payment_method ?: 'cash'));

            if ($total > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 10,
                    'date' => $date,
                    'type' => 'sale',
                    'type_label' => __('reports.customer_ledger.types.sale'),
                    'reference' => (string) $sale->sale_number,
                    'particulars' => __('reports.customer_ledger.particulars.sale', [
                        'number' => $sale->sale_number,
                    ]),
                    'debit' => $total,
                    'credit' => 0.0,
                    'method' => $method,
                ];
            }

            if ($paid > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 20,
                    'date' => $date,
                    'type' => 'payment',
                    'type_label' => __('reports.customer_ledger.types.payment'),
                    'reference' => (string) $sale->sale_number,
                    'particulars' => __('reports.customer_ledger.particulars.payment_sale', [
                        'number' => $sale->sale_number,
                        'method' => $method,
                    ]),
                    'debit' => 0.0,
                    'credit' => $paid,
                    'method' => $method,
                ];
            }
        }

        $orders = OnlineSaleOrder::query()
            ->where('customer_id', $customer->id)
            ->whereNull('sale_id')
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CANCELLED);
        $this->applyDateRange($orders, 'order_date', $start, $end);

        foreach ($orders->orderBy('order_date')->orderBy('id')->get() as $order) {
            $date = $order->order_date?->toDateString() ?? '';
            $group = sprintf('2-order-%010d', $order->id);
            $total = $this->numeric($order->total_amount);
            $paid = $this->numeric($order->amount_paid);
            $method = $this->paymentMethodLabel((string) ($order->customer_payment_method ?: $order->payment_method ?: '—'));

            if ($total > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 10,
                    'date' => $date,
                    'type' => 'online_order',
                    'type_label' => __('reports.customer_ledger.types.online_order'),
                    'reference' => (string) $order->order_number,
                    'particulars' => __('reports.customer_ledger.particulars.online_order', [
                        'number' => $order->order_number,
                    ]),
                    'debit' => $total,
                    'credit' => 0.0,
                    'method' => $method,
                ];
            }

            if ($paid > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 20,
                    'date' => $date,
                    'type' => 'payment',
                    'type_label' => __('reports.customer_ledger.types.payment'),
                    'reference' => (string) $order->order_number,
                    'particulars' => __('reports.customer_ledger.particulars.payment_order', [
                        'number' => $order->order_number,
                        'method' => $method,
                    ]),
                    'debit' => 0.0,
                    'credit' => $paid,
                    'method' => $method,
                ];
            }
        }

        $returns = SaleReturn::query()->where('customer_id', $customer->id);
        $this->applyDateRange($returns, 'return_date', $start, $end);

        foreach ($returns->orderBy('return_date')->orderBy('id')->get() as $return) {
            $date = $return->return_date?->toDateString() ?? '';
            $group = sprintf('3-return-%010d', $return->id);
            $refund = $this->numeric($return->refund_total);
            $refundPaid = $this->numeric($return->amount_paid);
            $method = $this->paymentMethodLabel((string) ($return->payment_method ?: 'cash'));

            if ($refund > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 10,
                    'date' => $date,
                    'type' => 'return',
                    'type_label' => __('reports.customer_ledger.types.return'),
                    'reference' => (string) $return->return_number,
                    'particulars' => __('reports.customer_ledger.particulars.return', [
                        'number' => $return->return_number,
                    ]),
                    'debit' => 0.0,
                    'credit' => $refund,
                    'method' => $method,
                ];
            }

            if ($refundPaid > 0) {
                $events[] = [
                    'sort_date' => $date,
                    'sort_group' => $group,
                    'sort_rank' => 20,
                    'date' => $date,
                    'type' => 'refund',
                    'type_label' => __('reports.customer_ledger.types.refund'),
                    'reference' => (string) $return->return_number,
                    'particulars' => __('reports.customer_ledger.particulars.refund', [
                        'number' => $return->return_number,
                        'method' => $method,
                    ]),
                    'debit' => $refundPaid,
                    'credit' => 0.0,
                    'method' => $method,
                ];
            }
        }

        $receipts = FundTransfer::query()
            ->where('destination_customer_id', $customer->id)
            ->where('transaction_type', FundTransfer::TYPE_CUSTOMER_RECEIPT);
        $this->applyDateRange($receipts, 'transaction_date', $start, $end);

        foreach ($receipts->orderBy('transaction_date')->orderBy('id')->get() as $receipt) {
            $date = $receipt->transaction_date?->toDateString() ?? '';
            $amount = $this->numeric($receipt->amount);
            $method = $receipt->source_wallet === FundTransfer::WALLET_BANK
                ? $this->paymentMethodLabel('bank_transfer')
                : $this->paymentMethodLabel('cash');

            if ($amount <= 0) {
                continue;
            }

            $events[] = [
                'sort_date' => $date,
                'sort_group' => sprintf('4-receipt-%010d', $receipt->id),
                'sort_rank' => 10,
                'date' => $date,
                'type' => 'customer_receipt',
                'type_label' => __('reports.customer_ledger.types.customer_receipt'),
                'reference' => (string) $receipt->transfer_number,
                'particulars' => __('reports.customer_ledger.particulars.customer_receipt', [
                    'number' => $receipt->transfer_number,
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
            'type_label' => __('reports.customer_ledger.types.opening'),
            'reference' => '—',
            'particulars' => __('reports.customer_ledger.particulars.opening'),
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
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->displayLabel(),
                'phone' => $customer->primaryPhone() ?: '—',
                'number' => (string) ($customer->customer_number ?: '—'),
            ],
            'party' => [
                'id' => $customer->id,
                'name' => $customer->displayLabel(),
                'phone' => $customer->primaryPhone() ?: '—',
                'number' => (string) ($customer->customer_number ?: '—'),
            ],
            'summary' => [
                'opening' => $this->formatMoney($opening),
                'debit' => $this->formatMoney($movement['debit']),
                'credit' => $this->formatMoney($movement['credit']),
                'balance' => $this->formatMoney($closing),
                'balance_type' => $closing > 0 ? 'receivable' : ($closing < 0 ? 'advance' : 'settled'),
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
            'customer' => __('reports.columns.customer'),
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
            $columns = ['customer', 'phone', 'opening', 'debit', 'credit', 'balance'];
        }

        $matrix = [array_map(fn (string $col): string => $labels[$col], $columns)];

        foreach ($rows as $row) {
            $matrix[] = array_map(fn (string $col): string => (string) ($row[$col] ?? ''), $columns);
        }

        return $matrix;
    }

    /**
     * @return Builder<Customer>
     */
    private function customerQuery(?string $search): Builder
    {
        $query = Customer::query()->orderBy('first_name')->orderBy('last_name');

        if (filled($search)) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('customer_number', 'like', $term)
                    ->orWhere('display_name', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('name_ur', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query;
    }

    private function movementBefore(int $customerId, CarbonImmutable $before): float
    {
        $movement = $this->movementWithin($customerId, null, $before->subDay());

        return $movement['debit'] - $movement['credit'];
    }

    /**
     * @param  list<int>  $customerIds
     * @return array<int, array{debit: float, credit: float}>
     */
    private function batchCustomerMovement(array $customerIds, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));

        if ($customerIds === []) {
            return [];
        }

        $result = [];

        foreach ($customerIds as $customerId) {
            $result[$customerId] = ['debit' => 0.0, 'credit' => 0.0];
        }

        $sales = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->where('status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($sales, 'sale_date', $start, $end);

        foreach ($sales
            ->selectRaw('customer_id, COALESCE(SUM(total_amount), 0) as debit_sum, COALESCE(SUM(amount_paid), 0) as credit_sum')
            ->groupBy('customer_id')
            ->get() as $row) {
            $id = (int) $row->customer_id;
            $result[$id]['debit'] += (float) $row->debit_sum;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        $orders = OnlineSaleOrder::query()
            ->whereIn('customer_id', $customerIds)
            ->whereNull('sale_id')
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CANCELLED);
        $this->applyDateRange($orders, 'order_date', $start, $end);

        foreach ($orders
            ->selectRaw('customer_id, COALESCE(SUM(total_amount), 0) as debit_sum, COALESCE(SUM(amount_paid), 0) as credit_sum')
            ->groupBy('customer_id')
            ->get() as $row) {
            $id = (int) $row->customer_id;
            $result[$id]['debit'] += (float) $row->debit_sum;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        $returns = SaleReturn::query()->whereIn('customer_id', $customerIds);
        $this->applyDateRange($returns, 'return_date', $start, $end);

        foreach ($returns
            ->selectRaw('customer_id, COALESCE(SUM(refund_total), 0) as credit_sum, COALESCE(SUM(amount_paid), 0) as debit_sum')
            ->groupBy('customer_id')
            ->get() as $row) {
            $id = (int) $row->customer_id;
            $result[$id]['debit'] += (float) $row->debit_sum;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        $receipts = FundTransfer::query()
            ->whereIn('destination_customer_id', $customerIds)
            ->where('transaction_type', FundTransfer::TYPE_CUSTOMER_RECEIPT);
        $this->applyDateRange($receipts, 'transaction_date', $start, $end);

        foreach ($receipts
            ->selectRaw('destination_customer_id as customer_id, COALESCE(SUM(amount), 0) as credit_sum')
            ->groupBy('destination_customer_id')
            ->get() as $row) {
            $id = (int) $row->customer_id;
            $result[$id]['credit'] += (float) $row->credit_sum;
        }

        return $result;
    }

    /**
     * @return array{debit: float, credit: float}
     */
    private function movementWithin(int $customerId, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $debit = 0.0;
        $credit = 0.0;

        $sales = Sale::query()
            ->where('customer_id', $customerId)
            ->where('status', Sale::STATUS_COMPLETED);
        $this->applyDateRange($sales, 'sale_date', $start, $end);
        $debit += $this->sumDecimal($sales, 'total_amount');
        $credit += $this->sumDecimal($sales, 'amount_paid');

        $orders = OnlineSaleOrder::query()
            ->where('customer_id', $customerId)
            ->whereNull('sale_id')
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CANCELLED);
        $this->applyDateRange($orders, 'order_date', $start, $end);
        $debit += $this->sumDecimal($orders, 'total_amount');
        $credit += $this->sumDecimal($orders, 'amount_paid');

        $returns = SaleReturn::query()->where('customer_id', $customerId);
        $this->applyDateRange($returns, 'return_date', $start, $end);
        $credit += $this->sumDecimal($returns, 'refund_total');
        $debit += $this->sumDecimal($returns, 'amount_paid');

        $receipts = FundTransfer::query()
            ->where('destination_customer_id', $customerId)
            ->where('transaction_type', FundTransfer::TYPE_CUSTOMER_RECEIPT);
        $this->applyDateRange($receipts, 'transaction_date', $start, $end);
        $credit += $this->sumDecimal($receipts, 'amount');

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

    private function paymentMethodLabel(string $method): string
    {
        $method = trim($method);

        if ($method === '' || $method === '—') {
            return '—';
        }

        $key = 'pos.payment_methods.'.$method;
        $label = __($key);

        return $label === $key
            ? ucfirst(str_replace('_', ' ', $method))
            : $label;
    }

    private function contactUrl(): string
    {
        try {
            return CustomersPage::getUrl();
        } catch (\Throwable) {
            return '/admin/contacts/customers';
        }
    }
}
