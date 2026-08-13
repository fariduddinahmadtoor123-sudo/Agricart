<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderService;
use Filament\Notifications\Notification;
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
        'sale_number',
        'customer',
        'total_amount',
        'payment_status',
        'order_date',
        'created_at',
    ];

    public string $search = '';

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
        // Ready for Queue has no list filters — keep handler for toolbar compatibility.
    }

    #[On('online-orders-clear-filters')]
    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        return [];
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search);
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
            ->whereNull('queued_at')
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
            onlineOrderContext: 'invoice',
        );
    }

    public function openEdit(int $orderId): void
    {
        $this->openView($orderId);
    }

    public function printPackingSlip(int $orderId): void
    {
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNull('queued_at')
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.packing-slip', $order));
    }

    public function sendInvoiceToQueue(int $orderId): void
    {
        $this->sendToQueue($orderId, OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE);
    }

    public function sendPackingListToQueue(int $orderId): void
    {
        $this->sendToQueue($orderId, OnlineSaleOrder::QUEUE_DOCUMENT_PACKING_LIST);
    }

    public function sendToQueue(int $orderId, string $document = OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE): void
    {
        if (! $this->userCan('edit')) {
            Notification::make()
                ->title(__('online_sale_orders.messages.send_queue_denied'))
                ->danger()
                ->send();

            return;
        }

        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNull('queued_at')
            ->find($orderId);

        if ($order === null) {
            Notification::make()
                ->title(__('online_sale_orders.messages.send_queue_missing'))
                ->danger()
                ->send();

            return;
        }

        try {
            app(OnlineSaleOrderService::class)->sendToDispatchQueue($order, $document);
            $this->dispatch('online-sale-orders-saved');
            $this->dispatch('order-dispatch-saved');
            $this->dispatch('pending-shipments-saved');

            Notification::make()
                ->title(__('online_sale_orders.messages.sent_to_queue', ['number' => $order->order_number]))
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title((string) (collect($exception->errors())->flatten()->first()
                    ?? __('online_sale_orders.messages.send_queue_failed')))
                ->danger()
                ->send();
        }
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'sale'])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNull('queued_at')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('order_number', 'like', $needle)
                        ->orWhereHas('sale', function ($sale) use ($needle): void {
                            $sale->where('sale_number', 'like', $needle);
                        })
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_number', 'like', $needle)
                                ->orWhere('phone', 'like', $needle);
                        });
                });
            })
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

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'online-orders', $action);
    }
}
