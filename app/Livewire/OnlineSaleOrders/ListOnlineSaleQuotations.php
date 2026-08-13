<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListOnlineSaleQuotations extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'order_status',
        'receipt_status',
        'payment_status',
        'customer_payment_method',
        'order_date',
        'created_at',
    ];

    public string $search = '';

    public string $orderStatusFilter = '';

    public string $paymentFilter = '';

    #[On('online-sale-orders-saved')]
    #[On('online-quotations-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('online-quotations-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('online-quotations-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'order' => $this->orderStatusFilter = $value === OnlineSaleOrder::STATUS_CONVERTED ? '' : $value,
            'payment' => $this->paymentFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('online-quotations-clear-filters')]
    public function clearFilters(): void
    {
        $this->orderStatusFilter = '';
        $this->paymentFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'order' => $this->orderStatusFilter = '',
            'payment' => $this->paymentFilter = '',
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

        if (filled($this->orderStatusFilter)) {
            $filters[] = [
                'field' => 'order',
                'label' => __('online_sale_orders.filters.active_order_status', [
                    'status' => __('online_sale_orders.order_statuses.' . $this->orderStatusFilter),
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

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->orderStatusFilter)
            || filled($this->paymentFilter);
    }

    #[On('online-quotations-column-toggle')]
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
            $this->visibleColumns = ['order_number'];
        }

        $this->dispatch('online-quotations-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CONVERTED)
            ->find($orderId);

        if ($order === null) {
            return;
        }

        // Quotations are view-opened only; payment verification still works via canConfirmPayment().
        $this->dispatch(
            'open-form-panel',
            title: __('online_sale_orders.actions.view_short') . ': ' . $order->order_number,
            resource: __('agricart.pages.sales.online-quotations'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: 'view',
            onlineOrderContext: 'quotation',
        );
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'sale'])
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CONVERTED)
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('order_number', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_number', 'like', $needle)
                                ->orWhere('phone', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->orderStatusFilter), fn ($query) => $query->where('order_status', $this->orderStatusFilter))
            ->when(filled($this->paymentFilter), fn ($query) => $query->where('payment_status', $this->paymentFilter))
            // Payment-notified quotations first (staff priority), then newest.
            ->orderByRaw("CASE customer_payment_proof_status WHEN 'submitted' THEN 0 WHEN 'verified' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.online-sale-orders.list-quotations', [
            'orders' => $orders,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
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

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'online-quotations', $action);
    }
}
