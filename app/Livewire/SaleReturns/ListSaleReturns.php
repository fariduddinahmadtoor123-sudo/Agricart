<?php

namespace App\Livewire\SaleReturns;

use App\Models\OnlineOrderReturnRequest;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Sales\OnlineOrderReturnRequestService;
use App\Services\Sales\SaleReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSaleReturns extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['return_number', 'sale_number', 'customer', 'refund_total', 'payment_status', 'payment_method', 'return_date', 'created_at'];

    public string $search = '';

    public string $paymentFilter = '';

    public string $methodFilter = '';

    public ?int $rejectingRequestId = null;

    public string $rejectNote = '';

    #[On('sale-returns-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('sale-returns-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('sale-returns-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'payment' => $this->paymentFilter = $value,
            'method' => $this->methodFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('sale-returns-clear-filters')]
    public function clearFilters(): void
    {
        $this->paymentFilter = '';
        $this->methodFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'payment' => $this->paymentFilter = '',
            'method' => $this->methodFilter = '',
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

        if (filled($this->paymentFilter)) {
            $filters[] = [
                'field' => 'payment',
                'label' => __('pos.filters.active_payment', [
                    'status' => __('pos.payment_statuses.' . $this->paymentFilter),
                ]),
            ];
        }

        if (filled($this->methodFilter)) {
            $filters[] = [
                'field' => 'method',
                'label' => __('pos.filters.active_method', [
                    'method' => __('pos.payment_methods.' . $this->methodFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->paymentFilter)
            || filled($this->methodFilter);
    }

    #[On('sale-returns-column-toggle')]
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
            $this->visibleColumns = ['return_number'];
        }

        $this->dispatch('sale-returns-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $returnId): void
    {
        $return = SaleReturn::query()->find($returnId);

        if ($return === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('sale_returns.actions.view_short') . ': ' . $return->return_number,
            resource: __('agricart.nav.sales.sales-returns'),
            formKey: 'sale-returns',
            returnId: $returnId,
            mode: 'view',
        );
    }

    public function openEdit(int $returnId): void
    {
        $return = SaleReturn::query()->find($returnId);

        if ($return === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('sale_returns.actions.edit_short') . ': ' . $return->return_number,
            resource: __('agricart.nav.sales.sales-returns'),
            formKey: 'sale-returns',
            returnId: $returnId,
            mode: 'edit',
        );
    }

    public function deleteReturn(int $returnId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $return = SaleReturn::query()->find($returnId);

        if ($return === null) {
            return;
        }

        try {
            app(SaleReturnService::class)->delete($return);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('sale_return_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function printReturn(int $returnId): void
    {
        $return = SaleReturn::query()->find($returnId);

        if ($return === null) {
            return;
        }

        $this->dispatch('sale-returns-open-print', url: route('admin.sale-returns.print', $return));
    }

    public function startRejectRequest(int $requestId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $this->rejectingRequestId = $requestId;
        $this->rejectNote = '';
    }

    public function cancelRejectRequest(): void
    {
        $this->rejectingRequestId = null;
        $this->rejectNote = '';
    }

    public function approveRequest(int $requestId): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $request = OnlineOrderReturnRequest::query()->find($requestId);

        if ($request === null) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(OnlineOrderReturnRequestService::class)->approve($request, $user);
            $this->rejectingRequestId = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('sale_return_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function rejectRequest(int $requestId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $request = OnlineOrderReturnRequest::query()->find($requestId);

        if ($request === null) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(OnlineOrderReturnRequestService::class)->reject($request, $user, $this->rejectNote);
            $this->rejectingRequestId = null;
            $this->rejectNote = '';
        } catch (ValidationException $exception) {
            session()->flash('sale_return_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $returns = SaleReturn::query()
            ->with(['customer', 'sale'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('return_number', 'like', $needle)
                        ->orWhereHas('sale', function ($sale) use ($needle): void {
                            $sale->where('sale_number', 'like', $needle);
                        })
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('display_name', 'like', $needle)
                                ->orWhere('customer_code', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->paymentFilter), fn ($query) => $query->where('payment_status', $this->paymentFilter))
            ->when(filled($this->methodFilter), fn ($query) => $query->where('payment_method', $this->methodFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        $pendingRequests = OnlineOrderReturnRequest::query()
            ->with(['customer', 'order', 'items.orderItem'])
            ->where('status', OnlineOrderReturnRequest::STATUS_PENDING)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('livewire.sale-returns.list', [
            'returns' => $returns,
            'pendingRequests' => $pendingRequests,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
            'canApprove' => $this->userCan('create'),
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

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'sales-returns', $action);
    }
}
