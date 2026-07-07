<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Models\User;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPurchases extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['purchase_number', 'supplier', 'purchaser', 'total_amount', 'delivery_status', 'payment_status', 'dispute_status', 'created_at'];

    public string $search = '';

    public string $deliveryFilter = '';

    public string $paymentFilter = '';

    public string $disputeFilter = '';

    #[On('purchases-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('purchases-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('purchases-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'delivery' => $this->deliveryFilter = $value,
            'payment' => $this->paymentFilter = $value,
            'dispute' => $this->disputeFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('purchases-clear-filters')]
    public function clearFilters(): void
    {
        $this->deliveryFilter = '';
        $this->paymentFilter = '';
        $this->disputeFilter = '';
        $this->resetPage();
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'delivery' => $this->deliveryFilter = '',
            'payment' => $this->paymentFilter = '',
            'dispute' => $this->disputeFilter = '',
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

        if (filled($this->deliveryFilter)) {
            $filters[] = [
                'field' => 'delivery',
                'label' => __('purchases.filters.active_delivery', [
                    'status' => __('purchases.statuses.delivery.' . $this->deliveryFilter),
                ]),
            ];
        }

        if (filled($this->paymentFilter)) {
            $filters[] = [
                'field' => 'payment',
                'label' => __('purchases.filters.active_payment', [
                    'status' => __('purchases.statuses.payment.' . $this->paymentFilter),
                ]),
            ];
        }

        if (filled($this->disputeFilter)) {
            $filters[] = [
                'field' => 'dispute',
                'label' => __('purchases.filters.active_dispute', [
                    'status' => __('purchases.statuses.dispute.' . $this->disputeFilter),
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->deliveryFilter)
            || filled($this->paymentFilter)
            || filled($this->disputeFilter);
    }

    #[On('purchases-column-toggle')]
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
            $this->visibleColumns = ['purchase_number'];
        }

        $this->dispatch('purchases-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('purchases.actions.add'),
            resource: __('agricart.nav.purchasing.purchases'),
            formKey: 'purchases',
        );
    }

    public function openView(int $purchaseId): void
    {
        $purchase = Purchase::query()->with('supplier')->find($purchaseId);

        if ($purchase === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchases.actions.view_short') . ': ' . $purchase->purchase_number,
            resource: __('agricart.nav.purchasing.purchases'),
            formKey: 'purchases',
            purchaseId: $purchaseId,
            mode: 'view',
        );
    }

    public function openEdit(int $purchaseId): void
    {
        $purchase = Purchase::query()->with('supplier')->find($purchaseId);

        if ($purchase === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchases.actions.edit_short') . ': ' . $purchase->purchase_number,
            resource: __('agricart.nav.purchasing.purchases'),
            formKey: 'purchases',
            purchaseId: $purchaseId,
            mode: 'edit',
        );
    }

    public function deletePurchase(int $purchaseId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $purchase = Purchase::query()->find($purchaseId);

        if ($purchase === null) {
            return;
        }

        app(PurchaseService::class)->delete($purchase);
        $this->resetPage();
    }

    public function printPurchase(int $purchaseId): void
    {
        $purchase = Purchase::query()->find($purchaseId);

        if ($purchase === null) {
            return;
        }

        $this->dispatch('purchases-open-print', url: route('admin.purchases.print', $purchase));
    }

    public function render(): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'purchaser'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('purchase_number', 'like', $needle)
                        ->orWhereHas('supplier', function ($supplier) use ($needle): void {
                            $supplier->where('business_name_en', 'like', $needle)
                                ->orWhere('business_name_ur', 'like', $needle)
                                ->orWhere('supplier_code', 'like', $needle);
                        })
                        ->orWhereHas('purchaser', function ($purchaser) use ($needle): void {
                            $purchaser->where('name', 'like', $needle)
                                ->orWhere('email', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->deliveryFilter), fn ($query) => $query->where('delivery_status', $this->deliveryFilter))
            ->when(filled($this->paymentFilter), fn ($query) => $query->where('payment_status', $this->paymentFilter))
            ->when(filled($this->disputeFilter), fn ($query) => $query->where('dispute_status', $this->disputeFilter))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.purchases.list', [
            'purchases' => $purchases,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
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

        return $user->hasPermission('purchasing.' . $action);
    }
}
