<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListCancelledOnlineSaleQuotations extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'cancellation_source',
        'cancelled_by',
        'cancellation_reason',
        'cancelled_at',
        'order_date',
        'created_at',
    ];

    public string $search = '';

    public string $sourceFilter = '';

    #[On('online-sale-orders-saved')]
    #[On('online-quotations-saved')]
    #[On('cancelled-quotations-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('cancelled-quotations-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('cancelled-quotations-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'source' && in_array($value, OnlineSaleOrder::CANCEL_SOURCES, true)) {
            $this->sourceFilter = $value;
            $this->resetPage();
        }
    }

    #[On('cancelled-quotations-clear-filters')]
    public function clearFilters(): void
    {
        $this->sourceFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        if ($field === 'source') {
            $this->sourceFilter = '';
            $this->resetPage();
        }
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->sourceFilter)) {
            $filters[] = [
                'field' => 'source',
                'label' => __('online_sale_orders.filters.active_cancel_source', [
                    'source' => __('online_sale_orders.cancel.sources.'.$this->sourceFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search) || filled($this->sourceFilter);
    }

    #[On('cancelled-quotations-column-toggle')]
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

        $this->dispatch('cancelled-quotations-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CANCELLED)
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('online_sale_orders.actions.view_short').': '.$order->order_number,
            resource: __('agricart.pages.sales.cancelled-quotations'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: 'view',
            onlineOrderContext: 'quotation',
        );
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'cancelledBy'])
            ->where('order_status', OnlineSaleOrder::STATUS_CANCELLED)
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('order_number', 'like', $needle)
                        ->orWhere('cancellation_reason', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_number', 'like', $needle)
                                ->orWhere('phone', 'like', $needle);
                        })
                        ->orWhereHas('cancelledBy', function ($user) use ($needle): void {
                            $user->where('name', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->sourceFilter), fn ($query) => $query->where('cancellation_source', $this->sourceFilter))
            ->orderByDesc('cancelled_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.online-sale-orders.list-cancelled-quotations', [
            'orders' => $orders,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
        ]);
    }
}
