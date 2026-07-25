<?php

namespace App\Livewire\Logistics;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPendingShipments extends Component
{
    use WithPagination;

    public string $search = '';

    public string $fulfillmentFilter = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'queue_document',
        'packing_progress',
        'fulfillment_status',
        'shipping',
        'queued_at',
    ];

    public function mount(): void
    {
        $this->dispatch('pending-shipments-columns-sync', columns: $this->visibleColumns);
    }

    #[On('pending-shipments-saved')]
    #[On('online-sale-orders-saved')]
    #[On('order-dispatch-saved')]
    #[On('forwarded-orders-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('pending-shipments-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('pending-shipments-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'fulfillment') {
            $this->fulfillmentFilter = $value;
            $this->resetPage();
        }
    }

    #[On('pending-shipments-column-toggle')]
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

        $this->dispatch('pending-shipments-columns-sync', columns: $this->visibleColumns);
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

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
        return filled($this->search) || filled($this->fulfillmentFilter);
    }

    public function removeFilter(string $field): void
    {
        if ($field === 'fulfillment') {
            $this->fulfillmentFilter = '';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->fulfillmentFilter = '';
        $this->search = '';
        $this->resetPage();
        $this->dispatch('pending-shipments-clear-search');
    }

    public function openOrder(int $orderId): void
    {
        $order = $this->pendingOrder($orderId);

        if ($order === null) {
            return;
        }

        $canUpdate = $this->userCan('edit');

        $this->dispatch(
            'open-form-panel',
            title: __('logistics_ship.actions.ship') . ': ' . $order->order_number,
            resource: __('agricart.pages.logistics.shipments'),
            formKey: 'logistics-ship',
            orderId: $orderId,
            mode: $canUpdate ? 'edit' : 'view',
        );
    }

    public function printOrder(int $orderId): void
    {
        $order = $this->pendingOrder($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', $order));
    }

    public function render(): View
    {
        $dispatch = app(OnlineOrderDispatchService::class);

        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'items', 'supplierAssignments.lines', 'staffAssignments'])
            ->logisticsPending()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('order_number', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_number', 'like', $needle)
                                ->orWhere('phone', 'like', $needle);
                        })
                        ->orWhereHas('supplierAssignments', function ($assignment) use ($needle): void {
                            $assignment->where('tracking_number', 'like', $needle)
                                ->orWhere('shipping_reference', 'like', $needle)
                                ->orWhere('carrier_name', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->fulfillmentFilter), fn ($query) => $query->where('fulfillment_status', $this->fulfillmentFilter))
            ->orderByDesc('queued_at')
            ->orderByDesc('id')
            ->paginate(20);

        $orderProgress = [];

        foreach ($orders as $order) {
            $orderProgress[$order->id] = $dispatch->packingProgressFromLoadedRelations($order);
        }

        return view('livewire.logistics.list-pending-shipments', [
            'orders' => $orders,
            'orderProgress' => $orderProgress,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'canEdit' => $this->userCan('edit'),
        ]);
    }

    private function pendingOrder(int $orderId): ?OnlineSaleOrder
    {
        return OnlineSaleOrder::query()->logisticsPending()->find($orderId);
    }

    private function userCan(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('sales.' . $ability) || $user->hasPermission('logistics.' . $ability);
    }
}
