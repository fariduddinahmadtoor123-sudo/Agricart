<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
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

    /** @var 'packing'|'invoice' */
    public string $forwardDocument = 'packing';

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
        $order = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($orderId);

        if ($order === null) {
            return;
        }

        $canUpdate = $this->userCan('edit');

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
        $order = $this->queuedOrder($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', $order));
    }

    public function printPackingSlip(int $orderId): void
    {
        $order = $this->queuedOrder($orderId);

        if ($order === null) {
            return;
        }

        $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.packing-slip', $order));
    }

    public function openForward(int $orderId): void
    {
        if ($this->queuedOrder($orderId) === null) {
            return;
        }

        $this->forwardOrderId = $orderId;
        $this->forwardDocument = 'packing';
    }

    public function closeForward(): void
    {
        $this->forwardOrderId = null;
        $this->forwardDocument = 'packing';
    }

    public function updatedForwardDocument(string $value): void
    {
        if (! in_array($value, ['packing', 'invoice'], true)) {
            $this->forwardDocument = 'packing';
        }
    }

    public function forwardWhatsApp(string $recipientType, int $recipientId): void
    {
        $order = $this->forwardOrder();

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $document = $this->forwardDocument;
        $url = null;

        if ($recipientType === 'supplier') {
            $supplier = $this->resolveForwardSupplier($order, $recipientId, $dispatch);

            if ($supplier === null) {
                return;
            }

            $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, auth()->user() instanceof User ? auth()->user() : null);
            $url = $dispatch->whatsAppUrlForSupplier($order, $supplier, $document);
        }

        if ($recipientType === 'staff') {
            $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

            if ($staff === null) {
                return;
            }

            $url = $dispatch->whatsAppUrlForStaff($order, $staff, $document);
        }

        if ($url !== null) {
            $this->dispatch('online-sale-orders-open-external', url: $url);
        }
    }

    public function forwardEmail(string $recipientType, int $recipientId): void
    {
        $order = $this->forwardOrder();

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $document = $this->forwardDocument;
        $url = null;

        if ($recipientType === 'supplier') {
            $supplier = $this->resolveForwardSupplier($order, $recipientId, $dispatch);

            if ($supplier === null) {
                return;
            }

            $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, auth()->user() instanceof User ? auth()->user() : null);
            $url = $dispatch->mailToUrlForSupplier($order, $supplier, $document);
        }

        if ($recipientType === 'staff') {
            $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

            if ($staff === null) {
                return;
            }

            $url = $dispatch->mailToUrlForStaff($order, $staff, $document);
        }

        if ($url !== null) {
            $this->dispatch('online-sale-orders-open-external', url: $url);
        }
    }

    public function forwardOpen(string $recipientType, int $recipientId): void
    {
        $order = $this->forwardOrder();

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $supplierId = null;
        $staffId = null;

        if ($recipientType === 'supplier') {
            $supplier = $this->resolveForwardSupplier($order, $recipientId, $dispatch);

            if ($supplier === null) {
                return;
            }

            $supplierId = $supplier->id;
            $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, auth()->user() instanceof User ? auth()->user() : null);
        }

        if ($recipientType === 'staff') {
            $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

            if ($staff === null) {
                return;
            }

            $staffId = $staff->id;
        }

        $url = $dispatch->documentUrl($order, $this->forwardDocument, $supplierId, $staffId);
        $this->dispatch('online-sale-orders-open-print', url: $url);
    }

    public function render(): View
    {
        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'assignedUser', 'supplierAssignments.supplier'])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
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
            ->orderByDesc('queued_at')
            ->orderByDesc('converted_at')
            ->paginate(20);

        $forwardOrder = $this->forwardOrder();
        $dispatch = app(OnlineOrderDispatchService::class);

        $forwardSuppliers = [];
        $forwardStaff = [];

        if ($forwardOrder !== null) {
            $forwardSuppliers = $dispatch->suggestSuppliersForOrder($forwardOrder)
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'name' => $supplier->displayBusinessName(),
                    'phone' => $supplier->primaryMobile(),
                    'email' => filled($supplier->email) ? (string) $supplier->email : null,
                    'can_whatsapp' => $dispatch->normalizePhoneDigits($supplier->primaryMobile()) !== null,
                    'can_email' => filled($supplier->email),
                ])
                ->values()
                ->all();

            $forwardStaff = User::query()
                ->where('status', User::STATUS_ACTIVE)
                ->orderBy('name')
                ->get()
                ->map(function (User $user) use ($dispatch): array {
                    $phone = $dispatch->primaryPhoneForUser($user);

                    return [
                        'id' => $user->id,
                        'name' => (string) $user->name,
                        'phone' => $phone,
                        'email' => filled($user->email) ? (string) $user->email : null,
                        'can_whatsapp' => $dispatch->normalizePhoneDigits($phone) !== null,
                        'can_email' => filled($user->email),
                    ];
                })
                ->all();
        }

        return view('livewire.online-sale-orders.list-order-dispatch', [
            'orders' => $orders,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'forwardOrder' => $forwardOrder,
            'forwardSuppliers' => $forwardSuppliers,
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
            ->with(['items.product.category.parent', 'customer'])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($this->forwardOrderId);
    }

    private function resolveForwardSupplier(
        OnlineSaleOrder $order,
        int $supplierId,
        OnlineOrderDispatchService $dispatch,
    ): ?Supplier {
        return $dispatch->suggestSuppliersForOrder($order)->firstWhere('id', $supplierId);
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
