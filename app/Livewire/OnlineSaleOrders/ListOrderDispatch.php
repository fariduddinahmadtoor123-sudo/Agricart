<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListOrderDispatch extends Component
{
    use WithPagination;

    public string $search = '';

    public string $staffUserFilter = '';

    public string $supplierFilter = '';

    public string $fulfillmentFilter = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'fulfillment_status',
        'assigned_user',
        'suppliers',
        'order_date',
    ];

    #[On('order-dispatch-saved')]
    #[On('online-sale-orders-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('order-dispatch-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('order-dispatch-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'staff' => $this->staffUserFilter = $value,
            'supplier' => $this->supplierFilter = $value,
            'fulfillment' => $this->fulfillmentFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('order-dispatch-column-toggle')]
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

        $this->dispatch('order-dispatch-columns-sync', columns: $this->visibleColumns);
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->staffUserFilter)) {
            $name = User::query()->find((int) $this->staffUserFilter)?->name ?? $this->staffUserFilter;
            $filters[] = [
                'field' => 'staff',
                'label' => __('order_dispatch.filters.active_staff', ['name' => $name]),
            ];
        }

        if (filled($this->supplierFilter)) {
            $label = Supplier::query()->find((int) $this->supplierFilter)?->displayBusinessName() ?? $this->supplierFilter;
            $filters[] = [
                'field' => 'supplier',
                'label' => __('order_dispatch.filters.active_supplier', ['name' => $label]),
            ];
        }

        if (filled($this->fulfillmentFilter)) {
            $filters[] = [
                'field' => 'fulfillment',
                'label' => __('order_dispatch.filters.active_fulfillment', [
                    'status' => __('order_dispatch.fulfillment_statuses.' . $this->fulfillmentFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->staffUserFilter)
            || filled($this->supplierFilter)
            || filled($this->fulfillmentFilter);
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'staff' => $this->staffUserFilter = '',
            'supplier' => $this->supplierFilter = '',
            'fulfillment' => $this->fulfillmentFilter = '',
            default => null,
        };

        $this->resetPage();
    }

    #[On('order-dispatch-clear-filters')]
    public function clearFilters(): void
    {
        $this->staffUserFilter = '';
        $this->supplierFilter = '';
        $this->fulfillmentFilter = '';
        $this->search = '';
        $this->resetPage();
        $this->dispatch('order-dispatch-clear-search');
    }

    public function openOrder(int $orderId): void
    {
        $order = OnlineSaleOrder::query()->find($orderId);

        if ($order === null) {
            return;
        }

        $canUpdate = $this->userCan('edit') && ! $order->isLocked();

        $this->dispatch(
            'open-form-panel',
            title: __('order_dispatch.actions.open') . ': ' . $order->order_number,
            resource: __('agricart.nav.sales.order-dispatch'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: $canUpdate ? 'edit' : 'view',
        );
    }

    public function printOrder(int $orderId): void
    {
        $order = OnlineSaleOrder::query()->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', $order));
    }

    public function printPackingSlip(int $orderId): void
    {
        $order = OnlineSaleOrder::query()->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.packing-slip', $order));
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'assignedUser', 'supplierAssignments.supplier'])
            ->whereNotIn('order_status', [OnlineSaleOrder::STATUS_CANCELLED, OnlineSaleOrder::STATUS_CONVERTED])
            ->whereIn('payment_status', ['paid', 'partial'])
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
            ->when(filled($this->staffUserFilter), fn ($query) => $query->where('assigned_user_id', (int) $this->staffUserFilter))
            ->when(filled($this->supplierFilter), function ($query): void {
                $supplierId = (int) $this->supplierFilter;
                $query->whereHas('supplierAssignments', fn ($inner) => $inner->where('supplier_id', $supplierId));
            })
            ->when(filled($this->fulfillmentFilter), fn ($query) => $query->where('fulfillment_status', $this->fulfillmentFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.online-sale-orders.list-order-dispatch', [
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

        return $user->hasPermission('sales.' . $action);
    }
}
