<?php

namespace App\Services\Reports;

use App\Filament\Clusters\Reports\CashReportPage;
use App\Filament\Clusters\Reports\CustomerLedgerPage;
use App\Filament\Clusters\Reports\ExpenseReportPage;
use App\Filament\Clusters\Reports\ProfitLossPage;
use App\Filament\Clusters\Reports\SalesSummaryPage;
use App\Filament\Clusters\Reports\SeasonalPurchasesPage;
use App\Filament\Clusters\Reports\SeasonalSalesPage;
use App\Filament\Clusters\Reports\StockReportPage;
use App\Filament\Clusters\Reports\SupplierLedgerPage;

class ReportCatalog
{
    /**
     * @return list<array{key: string, page: class-string, sort: int}>
     */
    public static function reportPages(): array
    {
        return [
            ['key' => 'stock-report', 'page' => StockReportPage::class, 'sort' => 2],
            ['key' => 'customer-ledger', 'page' => CustomerLedgerPage::class, 'sort' => 3],
            ['key' => 'supplier-ledger', 'page' => SupplierLedgerPage::class, 'sort' => 4],
            ['key' => 'sales-summary', 'page' => SalesSummaryPage::class, 'sort' => 5],
            ['key' => 'profit-loss', 'page' => ProfitLossPage::class, 'sort' => 6],
            ['key' => 'expense-report', 'page' => ExpenseReportPage::class, 'sort' => 7],
            ['key' => 'cash-report', 'page' => CashReportPage::class, 'sort' => 8],
            ['key' => 'seasonal-sales', 'page' => SeasonalSalesPage::class, 'sort' => 9],
            ['key' => 'seasonal-purchases', 'page' => SeasonalPurchasesPage::class, 'sort' => 10],
        ];
    }

    public static function eventPrefix(string $reportKey): string
    {
        return 'reports-' . $reportKey;
    }

    public static function supportsCsvExport(string $reportKey): bool
    {
        foreach (static::reportPages() as $entry) {
            if ($entry['key'] === $reportKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *     filters: list<array{label: string, value: string}>,
     *     filterGroups: list<array{label: string, options: list<array{label: string, value: string}>}>,
     *     columns: list<array{label: string, value: string, visible: bool}>,
     *     defaultVisibleColumns: list<string>
     * }|null
     */
    public static function definition(string $reportKey): ?array
    {
        $columns = static::columnsFor($reportKey);
        $visible = array_values(array_filter(
            array_column(array_filter($columns, fn (array $col): bool => $col['visible']), 'value'),
        ));

        return match ($reportKey) {
            'stock-report' => [
                'filters' => [],
                'filterGroups' => [
                    [
                        'label' => __('reports.filters.stock_status'),
                        'filters' => static::stockStatusFilters(),
                    ],
                    [
                        'label' => __('reports.filters.category'),
                        'filters' => app(StockReportService::class)->categoryFilterOptions(),
                    ],
                ],
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'customer-ledger' => [
                'filters' => [],
                'filterGroups' => [
                    ...static::commonFilterGroups(),
                    [
                        'label' => __('reports.filters.customer_balance'),
                        'filters' => static::ledgerPartyFilters($reportKey),
                    ],
                ],
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'supplier-ledger' => [
                'filters' => [],
                'filterGroups' => [
                    ...static::commonFilterGroups(),
                    [
                        'label' => __('reports.filters.supplier_balance'),
                        'filters' => static::ledgerPartyFilters($reportKey),
                    ],
                ],
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'sales-summary' => [
                'filters' => [],
                'filterGroups' => [
                    ...static::commonFilterGroups(),
                    [
                        'label' => __('reports.filters.channel'),
                        'filters' => static::salesChannelFilters(),
                    ],
                    [
                        'label' => __('reports.filters.group_by'),
                        'filters' => static::salesGroupFilters(),
                    ],
                ],
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'seasonal-sales' => [
                'filters' => static::salesChannelFilters(),
                'filterGroups' => static::commonFilterGroups(),
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'profit-loss' => [
                'filters' => [],
                'filterGroups' => [
                    ...static::commonFilterGroups(),
                    [
                        'label' => __('reports.filters.result'),
                        'filters' => static::profitResultFilters(),
                    ],
                ],
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'expense-report' => [
                'filters' => static::expenseCategoryFilters(),
                'filterGroups' => static::commonFilterGroups(),
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'cash-report' => [
                'filters' => static::cashAccountFilters(),
                'filterGroups' => static::commonFilterGroups(),
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            'seasonal-purchases' => [
                'filters' => static::supplierFilters(),
                'filterGroups' => static::commonFilterGroups(),
                'columns' => $columns,
                'defaultVisibleColumns' => $visible,
            ],
            default => null,
        };
    }

    /**
     * @return list<array{label: string, options: list<array{label: string, value: string}>}>
     */
    public static function commonFilterGroups(): array
    {
        return [
            [
                'label' => __('reports.filters.period'),
                'filters' => [
                    ['label' => __('reports.filters.period_today'), 'value' => 'period:today'],
                    ['label' => __('reports.filters.period_week'), 'value' => 'period:week'],
                    ['label' => __('reports.filters.period_month'), 'value' => 'period:month'],
                    ['label' => __('reports.filters.period_quarter'), 'value' => 'period:quarter'],
                    ['label' => __('reports.filters.period_year'), 'value' => 'period:year'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function stockStatusFilters(): array
    {
        return [
            ['label' => __('reports.filters.stock_all'), 'value' => 'stock:all'],
            ['label' => __('reports.filters.stock_in'), 'value' => 'stock:in'],
            ['label' => __('reports.filters.stock_low'), 'value' => 'stock:low'],
            ['label' => __('reports.filters.stock_out'), 'value' => 'stock:out'],
            ['label' => __('reports.filters.stock_reorder'), 'value' => 'stock:reorder'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function ledgerPartyFilters(string $reportKey): array
    {
        $prefix = $reportKey === 'customer-ledger' ? 'customer' : 'supplier';

        return [
            ['label' => __('reports.filters.all_parties'), 'value' => $prefix . ':all'],
            ['label' => __('reports.filters.with_balance'), 'value' => $prefix . ':balance'],
            ['label' => __('reports.filters.zero_balance'), 'value' => $prefix . ':zero'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function salesGroupFilters(): array
    {
        return [
            ['label' => __('reports.filters.group_day'), 'value' => 'group:day'],
            ['label' => __('reports.filters.group_month'), 'value' => 'group:month'],
            ['label' => __('reports.filters.group_category'), 'value' => 'group:category'],
            ['label' => __('reports.filters.group_customer'), 'value' => 'group:customer'],
            ['label' => __('reports.filters.group_product'), 'value' => 'group:product'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function salesChannelFilters(): array
    {
        return [
            ['label' => __('reports.filters.channel_all'), 'value' => 'channel:all'],
            ['label' => __('reports.filters.channel_pos'), 'value' => 'channel:pos'],
            ['label' => __('reports.filters.channel_online'), 'value' => 'channel:online'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function profitResultFilters(): array
    {
        return [
            ['label' => __('reports.filters.result_all'), 'value' => 'result:all'],
            ['label' => __('reports.filters.result_profit'), 'value' => 'result:profit'],
            ['label' => __('reports.filters.result_loss'), 'value' => 'result:loss'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function expenseCategoryFilters(): array
    {
        return [
            ['label' => __('reports.filters.expense_all'), 'value' => 'expense:all'],
            ['label' => __('reports.filters.expense_operating'), 'value' => 'expense:operating'],
            ['label' => __('reports.filters.expense_other'), 'value' => 'expense:other'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function cashAccountFilters(): array
    {
        return [
            ['label' => __('reports.filters.cash_all'), 'value' => 'cash:all'],
            ['label' => __('reports.filters.cash_on_hand'), 'value' => 'cash:hand'],
            ['label' => __('reports.filters.cash_bank'), 'value' => 'cash:bank'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected static function supplierFilters(): array
    {
        return [
            ['label' => __('reports.filters.all_suppliers'), 'value' => 'supplier:all'],
            ['label' => __('reports.filters.active_suppliers'), 'value' => 'supplier:active'],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    protected static function columnsFor(string $reportKey): array
    {
        return match ($reportKey) {
            'stock-report' => [
                ['label' => __('reports.columns.product'), 'value' => 'product', 'visible' => true],
                ['label' => __('reports.columns.sku'), 'value' => 'sku', 'visible' => true],
                ['label' => __('reports.columns.category'), 'value' => 'category', 'visible' => true],
                ['label' => __('reports.columns.on_hand'), 'value' => 'on_hand', 'visible' => true],
                ['label' => __('reports.columns.reserved'), 'value' => 'reserved', 'visible' => false],
                ['label' => __('reports.columns.available'), 'value' => 'available', 'visible' => true],
                ['label' => __('reports.columns.alert_qty'), 'value' => 'alert_qty', 'visible' => true],
                ['label' => __('reports.columns.unit'), 'value' => 'unit', 'visible' => false],
                ['label' => __('reports.columns.stock_status'), 'value' => 'stock_status', 'visible' => true],
            ],
            'customer-ledger' => [
                ['label' => __('reports.columns.customer'), 'value' => 'customer', 'visible' => true],
                ['label' => __('reports.columns.phone'), 'value' => 'phone', 'visible' => true],
                ['label' => __('reports.columns.opening'), 'value' => 'opening', 'visible' => true],
                ['label' => __('reports.columns.debit'), 'value' => 'debit', 'visible' => true],
                ['label' => __('reports.columns.credit'), 'value' => 'credit', 'visible' => true],
                ['label' => __('reports.columns.balance'), 'value' => 'balance', 'visible' => true],
            ],
            'supplier-ledger' => [
                ['label' => __('reports.columns.supplier'), 'value' => 'supplier', 'visible' => true],
                ['label' => __('reports.columns.phone'), 'value' => 'phone', 'visible' => true],
                ['label' => __('reports.columns.opening'), 'value' => 'opening', 'visible' => true],
                ['label' => __('reports.columns.debit'), 'value' => 'debit', 'visible' => true],
                ['label' => __('reports.columns.credit'), 'value' => 'credit', 'visible' => true],
                ['label' => __('reports.columns.balance'), 'value' => 'balance', 'visible' => true],
            ],
            'sales-summary' => [
                ['label' => __('reports.columns.group'), 'value' => 'group', 'visible' => true],
                ['label' => __('reports.columns.invoices'), 'value' => 'invoices', 'visible' => true],
                ['label' => __('reports.columns.gross_sales'), 'value' => 'gross_sales', 'visible' => true],
                ['label' => __('reports.columns.returns'), 'value' => 'returns', 'visible' => true],
                ['label' => __('reports.columns.net_sales'), 'value' => 'net_sales', 'visible' => true],
                ['label' => __('reports.columns.avg_ticket'), 'value' => 'avg_ticket', 'visible' => true],
                ['label' => __('reports.columns.channel'), 'value' => 'channel', 'visible' => false],
            ],
            'profit-loss' => [
                ['label' => __('reports.columns.invoice'), 'value' => 'invoice', 'visible' => true],
                ['label' => __('reports.columns.date'), 'value' => 'date', 'visible' => true],
                ['label' => __('reports.columns.customer'), 'value' => 'customer', 'visible' => true],
                ['label' => __('reports.columns.net_sales'), 'value' => 'net_sales', 'visible' => true],
                ['label' => __('reports.columns.cogs'), 'value' => 'cogs', 'visible' => true],
                ['label' => __('reports.columns.profit'), 'value' => 'profit', 'visible' => true],
                ['label' => __('reports.columns.margin'), 'value' => 'margin', 'visible' => true],
                ['label' => __('reports.columns.result'), 'value' => 'result', 'visible' => true],
                ['label' => __('reports.columns.gross_sales'), 'value' => 'gross_sales', 'visible' => false],
                ['label' => __('reports.columns.returns'), 'value' => 'returns', 'visible' => false],
            ],
            'expense-report' => [
                ['label' => __('reports.columns.date'), 'value' => 'date', 'visible' => true],
                ['label' => __('reports.columns.category'), 'value' => 'category', 'visible' => true],
                ['label' => __('reports.columns.reference'), 'value' => 'reference', 'visible' => true],
                ['label' => __('reports.columns.description'), 'value' => 'description', 'visible' => true],
                ['label' => __('reports.columns.amount'), 'value' => 'amount', 'visible' => true],
            ],
            'cash-report' => [
                ['label' => __('reports.columns.date'), 'value' => 'date', 'visible' => true],
                ['label' => __('reports.columns.account'), 'value' => 'account', 'visible' => true],
                ['label' => __('reports.columns.type'), 'value' => 'type', 'visible' => true],
                ['label' => __('reports.columns.reference'), 'value' => 'reference', 'visible' => true],
                ['label' => __('reports.columns.in'), 'value' => 'cash_in', 'visible' => true],
                ['label' => __('reports.columns.out'), 'value' => 'cash_out', 'visible' => true],
                ['label' => __('reports.columns.balance'), 'value' => 'balance', 'visible' => true],
            ],
            'seasonal-sales' => [
                ['label' => __('reports.columns.season'), 'value' => 'season', 'visible' => true],
                ['label' => __('reports.columns.product'), 'value' => 'product', 'visible' => true],
                ['label' => __('reports.columns.qty_sold'), 'value' => 'qty_sold', 'visible' => true],
                ['label' => __('reports.columns.net_sales'), 'value' => 'net_sales', 'visible' => true],
                ['label' => __('reports.columns.avg_price'), 'value' => 'avg_price', 'visible' => false],
            ],
            'seasonal-purchases' => [
                ['label' => __('reports.columns.season'), 'value' => 'season', 'visible' => true],
                ['label' => __('reports.columns.product'), 'value' => 'product', 'visible' => true],
                ['label' => __('reports.columns.qty_purchased'), 'value' => 'qty_purchased', 'visible' => true],
                ['label' => __('reports.columns.purchase_value'), 'value' => 'purchase_value', 'visible' => true],
                ['label' => __('reports.columns.supplier'), 'value' => 'supplier', 'visible' => false],
            ],
            default => [],
        };
    }
}
