<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListProducts extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'code', 'label_code', 'name_en', 'name_ur', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('products-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('products-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('products-filter')]
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

    #[On('products-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('products-column-toggle')]
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
            $this->visibleColumns = ['name_en'];
        }

        $this->dispatch('products-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
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

        if ($product === null) {
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

    public function deleteProduct(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        app(ProductService::class)->delete($product);
        $this->dispatch('labels-saved');
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['brand', 'category'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('code', 'like', $needle)
                        ->orWhere('label_code', 'like', $needle)
                        ->orWhere('sku', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.products.list', [
            'products' => $products,
        ]);
    }
}
