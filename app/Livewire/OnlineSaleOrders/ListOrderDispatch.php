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

    /** @var list<int> */
    public array $forwardSelectedItemIds = [];

    public string $forwardSupplierSearch = '';

    public string $forwardStatusMessage = '';

    /** @var list<string> */
    public array $visibleColumns = [
        'order_number',
        'customer',
        'total_amount',
        'queue_document',
        'packing_progress',
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
            onlineOrderContext: 'queue',
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
        $order = $this->queuedOrder($orderId);

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $this->forwardOrderId = $orderId;
        $progress = $dispatch->packingProgress($order);
        // Prefer packing when lines still need suppliers — otherwise invoice/staff mode.
        $this->forwardDocument = ($progress['remaining'] ?? 0) > 0
            ? 'packing'
            : (($order->queue_document === OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE) ? 'invoice' : 'packing');
        $this->forwardStatusMessage = '';
        $this->forwardSupplierSearch = '';
        $this->forwardSelectedItemIds = collect($dispatch->packingLineStates($order))
            ->reject(fn (array $row): bool => $row['sent'])
            ->pluck('item_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function closeForward(): void
    {
        $this->forwardOrderId = null;
        $this->forwardDocument = 'packing';
        $this->forwardSelectedItemIds = [];
        $this->forwardSupplierSearch = '';
        $this->forwardStatusMessage = '';
    }

    public function updatedForwardDocument(string $value): void
    {
        if (! in_array($value, ['packing', 'invoice'], true)) {
            $this->forwardDocument = 'packing';
        }

        $this->forwardStatusMessage = '';
    }

    public function updatedForwardSelectedItemIds(): void
    {
        $this->forwardSelectedItemIds = array_values(array_unique(array_map('intval', $this->forwardSelectedItemIds)));
        $this->forwardStatusMessage = '';
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
        $actor = auth()->user() instanceof User ? auth()->user() : null;

        try {
            if ($recipientType === 'supplier') {
                if ($document !== 'packing') {
                    $this->forwardStatusMessage = __('order_dispatch.validation.packing_for_suppliers');

                    return;
                }

                $supplier = $this->resolveForwardSupplier($recipientId);

                if ($supplier === null) {
                    $this->forwardStatusMessage = __('order_dispatch.validation.supplier_required');

                    return;
                }

                if ($dispatch->normalizePhoneDigits($supplier->primaryMobile()) === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.whatsapp_no_phone');

                    return;
                }

                $itemIds = array_values(array_unique(array_map('intval', $this->forwardSelectedItemIds)));
                $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, $actor, $itemIds);
                $url = $dispatch->whatsAppUrlForSupplier($order->fresh(['items']), $supplier, $document, $itemIds);
                $this->afterPackingForward($order, $dispatch);

                if ($url === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.whatsapp_unavailable');

                    return;
                }
            }

            if ($recipientType === 'staff') {
                $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

                if ($staff === null) {
                    return;
                }

                if ($dispatch->normalizePhoneDigits($dispatch->primaryPhoneForUser($staff)) === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.whatsapp_no_phone');

                    return;
                }

                $url = $dispatch->whatsAppUrlForStaff($order, $staff, $document);

                if ($url === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.whatsapp_unavailable');

                    return;
                }
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->forwardStatusMessage = (string) (collect($exception->errors())->flatten()->first() ?? '');

            return;
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
        $actor = auth()->user() instanceof User ? auth()->user() : null;

        try {
            if ($recipientType === 'supplier') {
                if ($document !== 'packing') {
                    $this->forwardStatusMessage = __('order_dispatch.validation.packing_for_suppliers');

                    return;
                }

                $supplier = $this->resolveForwardSupplier($recipientId);

                if ($supplier === null) {
                    $this->forwardStatusMessage = __('order_dispatch.validation.supplier_required');

                    return;
                }

                if (! filled($supplier->email)) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.email_missing');

                    return;
                }

                $itemIds = array_values(array_unique(array_map('intval', $this->forwardSelectedItemIds)));
                $url = $dispatch->mailToUrlForSupplier($order, $supplier, $document, $itemIds);

                if ($url === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.email_missing');

                    return;
                }

                $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, $actor, $itemIds);
                $this->afterPackingForward($order, $dispatch);
            }

            if ($recipientType === 'staff') {
                $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

                if ($staff === null) {
                    return;
                }

                if (! filled($staff->email)) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.email_missing');

                    return;
                }

                $url = $dispatch->mailToUrlForStaff($order, $staff, $document);

                if ($url === null) {
                    $this->forwardStatusMessage = __('online_sale_orders.forward.email_missing');

                    return;
                }
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->forwardStatusMessage = (string) (collect($exception->errors())->flatten()->first() ?? '');

            return;
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
        $itemIds = [];
        $actor = auth()->user() instanceof User ? auth()->user() : null;

        try {
            if ($recipientType === 'supplier') {
                if ($this->forwardDocument !== 'packing') {
                    $this->forwardStatusMessage = __('order_dispatch.validation.packing_for_suppliers');

                    return;
                }

                $supplier = $this->resolveForwardSupplier($recipientId);

                if ($supplier === null) {
                    $this->forwardStatusMessage = __('order_dispatch.validation.supplier_required');

                    return;
                }

                $supplierId = $supplier->id;
                $itemIds = array_values(array_unique(array_map('intval', $this->forwardSelectedItemIds)));
                $dispatch->ensureAndMarkSupplierSent($order, $supplier->id, $actor, $itemIds);
                $this->afterPackingForward($order, $dispatch);
            }

            if ($recipientType === 'staff') {
                $staff = User::query()->whereKey($recipientId)->where('status', User::STATUS_ACTIVE)->first();

                if ($staff === null) {
                    return;
                }

                $staffId = $staff->id;
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->forwardStatusMessage = (string) (collect($exception->errors())->flatten()->first() ?? '');

            return;
        }

        $url = $dispatch->documentUrl($order, $this->forwardDocument, $supplierId, $staffId, $itemIds);
        $this->dispatch('online-sale-orders-open-print', url: $url);
    }

    public function render(): View
    {
        $dispatch = app(OnlineOrderDispatchService::class);

        $orders = OnlineSaleOrder::query()
            ->with(['customer', 'assignedUser', 'supplierAssignments.supplier', 'supplierAssignments.lines', 'items'])
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
        $forwardLineStates = [];
        $forwardProgress = null;
        $forwardMatchedSuppliers = [];
        $forwardOtherSuppliers = [];
        $forwardStaff = [];

        if ($forwardOrder !== null) {
            $forwardLineStates = $dispatch->packingLineStates($forwardOrder);
            $forwardProgress = $dispatch->packingProgress($forwardOrder);

            $selection = $this->forwardSelectedItemIds !== []
                ? $this->forwardSelectedItemIds
                : collect($forwardLineStates)->reject(fn (array $row): bool => $row['sent'])->pluck('item_id')->all();

            $matched = $dispatch->suggestSuppliersForItemIds($forwardOrder, array_map('intval', $selection));
            $matchedIds = $matched->pluck('id')->all();
            $needle = mb_strtolower(trim($this->forwardSupplierSearch));

            $matchesSearch = static function (Supplier $supplier) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', array_filter([
                    $supplier->displayBusinessName(),
                    $supplier->primaryMobile(),
                    (string) ($supplier->email ?? ''),
                    (string) ($supplier->supplier_code ?? ''),
                ])));

                return str_contains($haystack, $needle);
            };

            $forwardMatchedSuppliers = $matched
                ->filter($matchesSearch)
                ->map(fn (Supplier $supplier): array => $this->mapForwardSupplier($supplier, $dispatch))
                ->values()
                ->all();

            $forwardOtherSuppliers = $dispatch->allActiveSuppliers()
                ->reject(fn (Supplier $supplier): bool => in_array($supplier->id, $matchedIds, true))
                ->filter($matchesSearch)
                ->map(fn (Supplier $supplier): array => $this->mapForwardSupplier($supplier, $dispatch))
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

        $orderProgress = [];

        foreach ($orders as $order) {
            $orderProgress[$order->id] = $dispatch->packingProgress($order);
        }

        return view('livewire.online-sale-orders.list-order-dispatch', [
            'orders' => $orders,
            'orderProgress' => $orderProgress,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'forwardOrder' => $forwardOrder,
            'forwardLineStates' => $forwardLineStates,
            'forwardProgress' => $forwardProgress,
            'forwardMatchedSuppliers' => $forwardMatchedSuppliers,
            'forwardOtherSuppliers' => $forwardOtherSuppliers,
            'forwardStaff' => $forwardStaff,
        ]);
    }

    private function afterPackingForward(OnlineSaleOrder $order, OnlineOrderDispatchService $dispatch): void
    {
        $this->dispatch('order-dispatch-saved');
        $fresh = $order->fresh(['items', 'supplierAssignments.supplier', 'supplierAssignments.lines']);

        if ($fresh === null) {
            return;
        }

        $progress = $dispatch->packingProgress($fresh);
        $this->forwardSelectedItemIds = collect($dispatch->packingLineStates($fresh))
            ->reject(fn (array $row): bool => $row['sent'])
            ->pluck('item_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $this->forwardStatusMessage = $progress['complete']
            ? __('order_dispatch.messages.packing_complete')
            : __('order_dispatch.messages.packing_partial', [
                'sent' => $progress['sent'],
                'total' => $progress['total'],
                'remaining' => $progress['remaining'],
            ]);
    }

    /**
     * @return array{id: int, name: string, phone: string|null, email: string|null, can_whatsapp: bool, can_email: bool}
     */
    private function mapForwardSupplier(Supplier $supplier, OnlineOrderDispatchService $dispatch): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->displayBusinessName(),
            'phone' => $supplier->primaryMobile(),
            'email' => filled($supplier->email) ? (string) $supplier->email : null,
            'can_whatsapp' => $dispatch->normalizePhoneDigits($supplier->primaryMobile()) !== null,
            'can_email' => filled($supplier->email),
        ];
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
            ])
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->find($this->forwardOrderId);
    }

    private function resolveForwardSupplier(int $supplierId): ?Supplier
    {
        return Supplier::query()->whereKey($supplierId)->where('status', 'active')->first();
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
