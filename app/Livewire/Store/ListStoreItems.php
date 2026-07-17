<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductSearchQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStoreItems extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'name', 'status', 'category', 'display_in', 'sku'];

    public string $search = '';

    public string $statusFilter = 'active';

    #[On('products-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('store-items-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('store-items-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-items-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'active';
        $this->resetPage();
    }

    #[On('store-items-column-toggle')]
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
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('store-items-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null || ! $this->userCan('view')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.products.actions.view', ['name' => $product->name_en]),
            resource: __('agricart.pages.catalog.products'),
            formKey: 'products',
            productId: $productId,
            mode: 'view',
        );
    }

    public function openEdit(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null || ! $this->userCan('edit')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.products.actions.edit', ['name' => $product->name_en]),
            resource: __('agricart.pages.catalog.products'),
            formKey: 'products',
            productId: $productId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['category', 'displayCategories'])
            ->when($this->statusFilter !== '' && $this->statusFilter !== 'all', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when(filled($this->search), function ($query): void {
                ProductSearchQuery::apply($query, trim($this->search));
            })
            ->orderBy('name_en')
            ->paginate(20);

        return view('livewire.store.list-items', [
            'products' => $products,
            'visibleColumns' => $this->visibleColumns,
            'canEdit' => $this->userCan('edit'),
            'canView' => $this->userCan('view'),
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

        return $user->hasPermission('catalog.'.$action) || $user->hasPermission('store.'.$action);
    }
}
