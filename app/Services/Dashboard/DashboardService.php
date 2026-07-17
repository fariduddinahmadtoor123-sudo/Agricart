<?php

namespace App\Services\Dashboard;

use App\Filament\Clusters\Approvals\StaffPage as ApprovalsStaffPage;
use App\Filament\Clusters\Finance\CashBookPage;
use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\Reports\CustomerLedgerPage;
use App\Filament\Clusters\Reports\StockReportPage;
use App\Filament\Clusters\Reports\SupplierLedgerPage;
use App\Filament\Clusters\Sales\OnlineOrdersPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Filament\Clusters\Sales\SalesReturnsPage;
use App\Models\ExpensePayment;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Finance\CashBookService;
use App\Services\Reports\CustomerLedgerReportService;
use App\Services\Reports\SupplierLedgerReportService;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /**
     * @return list<array{key: string, label: string, value: int|string, meta: string, url: string}>
     */
    public function overviewCards(): array
    {
        $todaySales = $this->salesFor('today');
        $openOrders = OnlineSaleOrder::query()
            ->whereNotIn('order_status', [OnlineSaleOrder::STATUS_CONVERTED, OnlineSaleOrder::STATUS_CANCELLED])
            ->count();
        $lowStock = Product::query()
            ->where('status', 'active')
            ->whereRaw('COALESCE(stock_qty, 0) <= COALESCE(alert_qty, 0)')
            ->count();
        $pendingApprovals = User::query()->where('status', User::STATUS_PENDING)->count();
        $customerKpis = app(CustomerLedgerReportService::class)->build()['kpis'];
        $supplierKpis = app(SupplierLedgerReportService::class)->build()['kpis'];

        return $this->visibleCards([
            $this->card(
                'sales_today',
                $this->formatMoney($todaySales['amount']),
                __('agricart.dashboard.overview.meta.invoices', ['count' => number_format($todaySales['count'])]),
                PosSalesPage::class,
            ),
            $this->card(
                'open_orders',
                $openOrders,
                __('agricart.dashboard.overview.meta.requires_action'),
                OnlineOrdersPage::class,
            ),
            $this->card(
                'low_stock',
                $lowStock,
                __('agricart.dashboard.overview.meta.reorder_attention'),
                StockReportPage::class,
            ),
            $this->card(
                'cash',
                $this->formatMoney(app(CashBookService::class)->balance()),
                __('agricart.dashboard.overview.meta.cash_balance'),
                CashBookPage::class,
            ),
            $this->card(
                'receivable',
                ((string) ($customerKpis['receivable'] ?? '0.00')).' PKR',
                __('agricart.dashboard.overview.meta.customer_balance'),
                CustomerLedgerPage::class,
            ),
            $this->card(
                'payable',
                ((string) ($supplierKpis['payable'] ?? '0.00')).' PKR',
                __('agricart.dashboard.overview.meta.supplier_balance'),
                SupplierLedgerPage::class,
            ),
            $this->card(
                'approvals',
                $pendingApprovals,
                __('agricart.dashboard.overview.meta.pending_staff'),
                ApprovalsStaffPage::class,
            ),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, count: int, meta: string, url: string}>
     */
    public function attentionItems(): array
    {
        return $this->visibleCards([
            $this->card(
                'pending_orders',
                OnlineSaleOrder::query()
                    ->whereNotIn('order_status', [OnlineSaleOrder::STATUS_CONVERTED, OnlineSaleOrder::STATUS_CANCELLED])
                    ->count(),
                __('agricart.dashboard.attention.pending_orders_hint'),
                OnlineOrdersPage::class,
                'attention',
            ),
            $this->card(
                'pending_purchases',
                Purchase::query()->where('delivery_status', '!=', Purchase::DELIVERY_RECEIVED)->count(),
                __('agricart.dashboard.attention.pending_purchases_hint'),
                PurchasesPage::class,
                'attention',
            ),
            $this->card(
                'low_stock',
                Product::query()
                    ->where('status', 'active')
                    ->whereRaw('COALESCE(stock_qty, 0) <= COALESCE(alert_qty, 0)')
                    ->count(),
                __('agricart.dashboard.attention.low_stock_hint'),
                StockReportPage::class,
                'attention',
            ),
            $this->card(
                'pending_approvals',
                User::query()->where('status', User::STATUS_PENDING)->count(),
                __('agricart.dashboard.attention.pending_approvals_hint'),
                ApprovalsStaffPage::class,
                'attention',
            ),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, today: string, month: string, url: string}>
     */
    public function summaryRows(): array
    {
        return $this->visibleRows([
            $this->summaryRow('sales', $this->salesAmount('today'), $this->salesAmount('month'), PosSalesPage::class),
            $this->summaryRow('returns', $this->returnsAmount('today'), $this->returnsAmount('month'), SalesReturnsPage::class),
            $this->summaryRow('purchases', $this->purchasesAmount('today'), $this->purchasesAmount('month'), PurchasesPage::class),
            $this->summaryRow('expenses', $this->expensesAmount('today'), $this->expensesAmount('month'), CashBookPage::class),
        ]);
    }

    /**
     * @return array{count: int, amount: float}
     */
    private function salesFor(string $period): array
    {
        $query = Sale::query()->where('status', Sale::STATUS_COMPLETED);
        $this->applyPeriod($query, 'sale_date', $period);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) $query->sum('total_amount'),
        ];
    }

    private function salesAmount(string $period): float
    {
        return $this->salesFor($period)['amount'];
    }

    private function returnsAmount(string $period): float
    {
        $query = SaleReturn::query();
        $this->applyPeriod($query, 'return_date', $period);

        return (float) $query->sum('refund_total');
    }

    private function purchasesAmount(string $period): float
    {
        $query = Purchase::query();
        $this->applyPeriod($query, 'purchase_date', $period);

        return (float) $query->sum('total_amount');
    }

    private function expensesAmount(string $period): float
    {
        $query = ExpensePayment::query();
        $this->applyPeriod($query, 'payment_date', $period);

        return (float) $query->sum('amount');
    }

    private function applyPeriod(Builder $query, string $column, string $period): void
    {
        if ($period === 'today') {
            $query->whereDate($column, today());

            return;
        }

        $query->whereDate($column, '>=', now()->startOfMonth()->toDateString())
            ->whereDate($column, '<=', now()->endOfMonth()->toDateString());
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, value: int|string, meta: string, url: string, page: class-string}
     */
    private function card(
        string $key,
        int|string $value,
        string $meta,
        string $pageClass,
        string $group = 'overview',
    ): array {
        return [
            'key' => $key,
            'label' => (string) __("agricart.dashboard.{$group}.cards.{$key}"),
            'value' => $value,
            'meta' => $meta,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, today: string, month: string, url: string, page: class-string}
     */
    private function summaryRow(string $key, float $today, float $month, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __("agricart.dashboard.summary.rows.{$key}"),
            'today' => $this->formatMoney($today),
            'month' => $this->formatMoney($month),
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private function visibleCards(array $cards): array
    {
        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, array_filter($cards, fn (array $card): bool => $card['page']::canAccess())));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function visibleRows(array $rows): array
    {
        return $this->visibleCards($rows);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2).' PKR';
    }
}
