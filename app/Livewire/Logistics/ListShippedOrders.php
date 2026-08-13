<?php

namespace App\Livewire\Logistics;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListShippedOrders extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'fulfillment_status',
        'shipping',
        'shipped_at',
    ];

    public function mount(): void
    {
        $this->dispatch('shipped-orders-columns-sync', columns: $this->visibleColumns);
    }

    #[On('shipped-orders-saved')]
    #[On('pending-shipments-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('shipped-orders-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('shipped-orders-column-toggle')]
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

        $this->dispatch('shipped-orders-columns-sync', columns: $this->visibleColumns);
    }

    public function openOrder(int $orderId): void
    {
        $order = OnlineSaleOrder::query()->logisticsShipped()->find($orderId);

        if ($order === null) {
            return;
        }

        $canUpdate = $this->userCan('edit');

        $this->dispatch(
            'open-form-panel',
            title: __('logistics_ship.actions.view').': '.$order->order_number,
            resource: __('agricart.pages.logistics.shipped'),
            formKey: 'logistics-ship',
            orderId: $orderId,
            mode: $canUpdate ? 'edit' : 'view',
        );
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'supplierAssignments', 'staffAssignments'])
            ->logisticsShipped()
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
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
                        })
                        ->orWhereHas('staffAssignments', function ($assignment) use ($needle): void {
                            $assignment->where('tracking_number', 'like', $needle)
                                ->orWhere('shipping_reference', 'like', $needle)
                                ->orWhere('carrier_name', 'like', $needle);
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.logistics.list-shipped-orders', [
            'orders' => $orders,
            'hasListConstraints' => filled($this->search),
            'canEdit' => $this->userCan('edit'),
        ]);
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

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'order-dispatch', $ability)
            || \App\Services\Access\ModulePageAccess::can($user, 'logistics', 'shipped', $ability);
    }
}
