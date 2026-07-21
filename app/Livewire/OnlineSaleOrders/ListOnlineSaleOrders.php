<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListOnlineSaleOrders extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'payment_status',
        'queue_status',
        'order_date',
        'created_at',
    ];

    public string $search = '';

    public string $queueFilter = '';

    #[On('online-sale-orders-saved')]
    #[On('online-quotations-saved')]
    #[On('order-dispatch-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('online-orders-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('online-orders-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'queue') {
            $this->queueFilter = $value;
        }

        $this->resetPage();
    }

    #[On('online-orders-clear-filters')]
    public function clearFilters(): void
    {
        $this->queueFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        if ($field === 'queue') {
            $this->queueFilter = '';
        }

        $this->resetPage();
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->queueFilter)) {
            $filters[] = [
                'field' => 'queue',
                'label' => __('online_sale_orders.filters.active_queue_status', [
                    'status' => __('online_sale_orders.queue_statuses.' . $this->queueFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search) || filled($this->queueFilter);
    }

    #[On('online-orders-column-toggle')]
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

        $this->dispatch('online-orders-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('online_sale_orders.actions.view_short') . ': ' . $order->order_number,
            resource: __('agricart.pages.sales.online-orders'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: 'view',
        );
    }

    public function openEdit(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->find($orderId);

        if ($order === null || ! $this->userCan('edit')) {
            return;
        }

        // Invoices are locked; fulfillment is edited from the queue page after Send to Queue.
        $this->dispatch(
            'open-form-panel',
            title: __('online_sale_orders.actions.view_short') . ': ' . $order->order_number,
            resource: __('agricart.pages.sales.online-orders'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: 'view',
        );
    }

    public function printPackingSlip(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.packing-slip', $order));
    }

    public function sendToQueue(int $orderId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->find($orderId);

        if ($order === null) {
            return;
        }

        try {
            app(OnlineSaleOrderService::class)->sendToDispatchQueue($order);
            $this->dispatch('online-sale-orders-saved');
            $this->dispatch('order-dispatch-saved');
        } catch (ValidationException) {
            // Ignore — order not eligible.
        }
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'sale'])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
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
            ->when($this->queueFilter === 'pending', fn ($query) => $query->whereNull('queued_at'))
            ->when($this->queueFilter === 'queued', fn ($query) => $query->whereNotNull('queued_at'))
            ->orderByRaw('CASE WHEN queued_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('converted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.online-sale-orders.list', [
            'orders' => $orders,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'canEdit' => $this->userCan('edit'),
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
