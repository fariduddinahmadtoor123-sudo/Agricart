<?php

namespace App\Livewire\Suppliers;

use App\Filament\Forms\CategoryForm;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSuppliers extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['supplier_code', 'business_name', 'contact_name', 'city', 'supplier_type', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    public string $cityFilter = '';

    #[On('suppliers-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('suppliers-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('suppliers-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'status' => $this->statusFilter = $value,
            'city' => $this->cityFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('suppliers-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->cityFilter = '';
        $this->resetPage();
    }

    #[On('suppliers-column-toggle')]
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
            $this->visibleColumns = ['business_name'];
        }

        $this->dispatch('suppliers-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.add'),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
        );
    }

    public function openView(int $supplierId): void
    {
        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.view_short') . ': ' . $supplier->displayBusinessName(),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
            supplierId: $supplierId,
            mode: 'view',
        );
    }

    public function openEdit(int $supplierId): void
    {
        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.edit_short') . ': ' . $supplier->displayBusinessName(),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
            supplierId: $supplierId,
            mode: 'edit',
        );
    }

    public function deleteSupplier(int $supplierId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        app(SupplierService::class)->delete($supplier);
        $this->resetPage();
    }

    public function render(): View
    {
        $suppliers = Supplier::query()
            ->with('categories')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('supplier_code', 'like', $needle)
                        ->orWhere('business_name_en', 'like', $needle)
                        ->orWhere('business_name_ur', 'like', $needle)
                        ->orWhere('city_en', 'like', $needle)
                        ->orWhere('city_ur', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->cityFilter), fn ($query) => $query->where('city_key', $this->cityFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.suppliers.list', [
            'suppliers' => $suppliers,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
            'categoryLabels' => CategoryForm::categoryLabelsByIds(
                $suppliers->getCollection()
                    ->flatMap(fn (Supplier $supplier): array => $supplier->categories->pluck('id')->all())
                    ->unique()
                    ->values()
                    ->all(),
            ),
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

        return $user->hasPermission('contacts.' . $action);
    }
}
