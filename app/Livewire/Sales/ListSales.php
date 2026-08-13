<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\SaleService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSales extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'sale_number',
        'channel',
        'customer',
        'cashier',
        'total_amount',
        'invoice_status',
        'payment_status',
        'payment_method',
        'sale_date',
        'created_at',
    ];

    public string $search = '';

    public string $invoiceFilter = '';

    public string $paymentFilter = '';

    public string $methodFilter = '';

    public string $channelFilter = '';

    #[On('sales-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('pos-sales-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('pos-sales-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'invoice' => $this->invoiceFilter = $value,
            'payment' => $this->paymentFilter = $value,
            'method' => $this->methodFilter = $value,
            'channel' => $this->channelFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('pos-sales-clear-filters')]
    public function clearFilters(): void
    {
        $this->invoiceFilter = '';
        $this->paymentFilter = '';
        $this->methodFilter = '';
        $this->channelFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'invoice' => $this->invoiceFilter = '',
            'payment' => $this->paymentFilter = '',
            'method' => $this->methodFilter = '',
            'channel' => $this->channelFilter = '',
            default => null,
        };

        $this->resetPage();
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->invoiceFilter)) {
            $filters[] = [
                'field' => 'invoice',
                'label' => __('pos.filters.active_invoice', [
                    'status' => __('pos.invoice_statuses.' . $this->invoiceFilter),
                ]),
            ];
        }

        if (filled($this->paymentFilter)) {
            $filters[] = [
                'field' => 'payment',
                'label' => __('pos.filters.active_payment', [
                    'status' => __('pos.payment_statuses.' . $this->paymentFilter),
                ]),
            ];
        }

        if (filled($this->methodFilter)) {
            $filters[] = [
                'field' => 'method',
                'label' => __('pos.filters.active_method', [
                    'method' => __('pos.payment_methods.' . $this->methodFilter),
                ]),
            ];
        }

        if (filled($this->channelFilter)) {
            $filters[] = [
                'field' => 'channel',
                'label' => __('pos.filters.active_channel', [
                    'channel' => __('pos.channels.' . $this->channelFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->invoiceFilter)
            || filled($this->paymentFilter)
            || filled($this->methodFilter)
            || filled($this->channelFilter);
    }

    #[On('pos-sales-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['sale_number'];
        }

        $this->dispatch('pos-sales-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $saleId): void
    {
        $sale = Sale::query()->with('customer')->find($saleId);

        if ($sale === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('pos.actions.view_short') . ': ' . $sale->sale_number,
            resource: __('agricart.nav.sales.pos-sales'),
            formKey: 'sales',
            saleId: $saleId,
            mode: 'view',
        );
    }

    public function openEdit(int $saleId): void
    {
        $sale = Sale::query()->with('customer')->find($saleId);

        if ($sale === null) {
            return;
        }

        if ($sale->isCompleted()) {
            $this->openView($saleId);

            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('pos.actions.edit_short') . ': ' . $sale->sale_number,
            resource: __('agricart.nav.sales.pos-sales'),
            formKey: 'sales',
            saleId: $saleId,
            mode: 'edit',
        );
    }

    public function deleteSale(int $saleId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $sale = Sale::query()->find($saleId);

        if ($sale === null) {
            return;
        }

        app(SaleService::class)->delete($sale);
        $this->resetPage();
    }

    public function printSale(int $saleId): void
    {
        $sale = Sale::query()->find($saleId);

        if ($sale === null) {
            return;
        }

        $this->dispatch('sales-open-print', url: route('admin.sales.print', $sale));
    }

    public function render(): View
    {
        $sales = Sale::query()
            ->with(['customer', 'cashier', 'onlineSaleOrder'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('sale_number', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_code', 'like', $needle);
                        })
                        ->orWhereHas('cashier', function ($cashier) use ($needle): void {
                            $cashier->where('name', 'like', $needle);
                        })
                        ->orWhereHas('onlineSaleOrder', function ($online) use ($needle): void {
                            $online->where('order_number', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->invoiceFilter), fn ($query) => $query->where('status', $this->invoiceFilter))
            ->when(filled($this->paymentFilter), fn ($query) => $query->where('payment_status', $this->paymentFilter))
            ->when(filled($this->methodFilter), fn ($query) => $query->where('payment_method', $this->methodFilter))
            ->when($this->channelFilter === 'online', fn ($query) => $query->whereHas('onlineSaleOrder'))
            ->when($this->channelFilter === 'offline', fn ($query) => $query->whereDoesntHave('onlineSaleOrder'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.sales.list', [
            'sales' => $sales,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'pos-sales', $action);
    }
}
