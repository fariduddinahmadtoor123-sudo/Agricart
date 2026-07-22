<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use App\Services\Staff\StaffMessagingService;
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

    public ?int $forwardOrderId = null;

    public string $forwardStaffSearch = '';

    public string $forwardStatusMessage = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'invoice_number',
        'customer',
        'total_amount',
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
        return [];
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search);
    }

    public function removeFilter(string $field): void
    {
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
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('online_sale_orders.actions.view_short') . ': ' . $order->order_number,
            resource: __('agricart.nav.sales.order-dispatch'),
            formKey: 'online-orders',
            orderId: $orderId,
            mode: 'view',
            onlineOrderContext: 'queue',
        );
    }

    #[On('order-dispatch-open-forward')]
    public function openForwardFromPanel(int $orderId): void
    {
        $this->openForward($orderId);
    }

    public function openForward(int $orderId): void
    {
        $order = $this->queuedOrder($orderId);

        if ($order === null) {
            return;
        }

        $this->forwardOrderId = $orderId;
        $this->forwardStatusMessage = '';
        $this->forwardStaffSearch = '';
    }

    public function closeForward(): void
    {
        $this->forwardOrderId = null;
        $this->forwardStaffSearch = '';
        $this->forwardStatusMessage = '';
    }

    public function forwardToInbox(int $staffId): void
    {
        $order = $this->forwardOrder();

        if ($order === null) {
            return;
        }

        $actor = auth()->user() instanceof User ? auth()->user() : null;

        if ($actor === null) {
            return;
        }

        $staff = User::query()
            ->whereKey($staffId)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->first();

        if ($staff === null) {
            $this->forwardStatusMessage = __('staff_inbox.validation.staff_required');

            return;
        }

        try {
            app(StaffMessagingService::class)
                ->forwardOrderToStaff($order, $staff, $actor, 'invoice');

            $this->forwardStatusMessage = __('staff_inbox.messages.forwarded_ok', ['name' => $staff->name]);
            $this->dispatch('forwarded-orders-saved');
            $this->dispatch('order-dispatch-saved');
            $this->dispatch('topbar-notifications-refresh');

            $fresh = $order->fresh(['items', 'supplierAssignments.lines', 'staffAssignments']) ?? $order;

            if (! app(OnlineOrderDispatchService::class)->orderStillNeedsForward($fresh)) {
                $this->closeForward();
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->forwardStatusMessage = (string) (collect($exception->errors())->flatten()->first() ?? '');
        }
    }

    public function render(): View
    {
        $dispatch = app(OnlineOrderDispatchService::class);

        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'sale', 'staffAssignments', 'items'])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('order_number', 'like', $needle)
                        ->orWhereHas('sale', fn ($sale) => $sale->where('sale_number', 'like', $needle))
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
            ->orderByDesc('queued_at')
            ->orderByDesc('converted_at')
            ->get()
            ->filter(fn (OnlineSaleOrder $order): bool => $dispatch->orderStillNeedsForward($order))
            ->values();

        $page = $this->getPage();
        $perPage = 20;
        $orders = new \Illuminate\Pagination\LengthAwarePaginator(
            $orders->forPage($page, $perPage)->values(),
            $orders->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        $forwardOrder = $this->forwardOrder();
        $forwardStaff = [];

        if ($forwardOrder !== null) {
            $actorId = (int) (auth()->id() ?? 0);
            $needle = mb_strtolower(trim($this->forwardStaffSearch));

            $forwardStaff = User::query()
                ->with('role')
                ->whereNotNull('role_id')
                ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_INACTIVE])
                ->orderByRaw(
                    'CASE WHEN status = ? THEN 0 ELSE 1 END',
                    [User::STATUS_ACTIVE],
                )
                ->orderBy('name')
                ->get()
                ->filter(function (User $user) use ($needle): bool {
                    if ($needle === '') {
                        return true;
                    }

                    $haystack = mb_strtolower(implode(' ', array_filter([
                        (string) $user->name,
                        (string) ($user->email ?? ''),
                        (string) ($user->user_number ?? ''),
                        (string) ($user->role?->name ?? ''),
                    ])));

                    return str_contains($haystack, $needle);
                })
                ->map(function (User $user) use ($actorId): array {
                    $isSelf = $actorId > 0 && $user->id === $actorId;
                    $isActive = $user->status === User::STATUS_ACTIVE;

                    return [
                        'id' => $user->id,
                        'name' => (string) $user->name,
                        'email' => filled($user->email) ? (string) $user->email : null,
                        'role' => filled($user->role?->name) ? (string) $user->role->name : null,
                        'status' => (string) $user->status,
                        'is_self' => $isSelf,
                        'can_forward' => $isActive,
                    ];
                })
                ->values()
                ->all();
        }

        return view('livewire.online-sale-orders.list-order-dispatch', [
            'orders' => $orders,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'forwardOrder' => $forwardOrder,
            'forwardStaff' => $forwardStaff,
        ]);
    }

    private function queuedOrder(int $orderId): ?OnlineSaleOrder
    {
        return OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($orderId);
    }

    private function forwardOrder(): ?OnlineSaleOrder
    {
        if ($this->forwardOrderId === null) {
            return null;
        }

        return OnlineSaleOrder::query()
            ->with([
                'items.product.category.parent',
                'customer',
                'supplierAssignments.supplier',
                'supplierAssignments.lines',
                'staffAssignments',
            ])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($this->forwardOrderId);
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
