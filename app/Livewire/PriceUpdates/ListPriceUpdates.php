<?php

namespace App\Livewire\PriceUpdates;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPriceUpdates extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'thumb',
        'name',
        'code',
        'last_buy_price',
        'retail_price',
        'wholesale_price',
        'super_wholesale_price',
        'distributor_price',
        'updated_at',
    ];

    public string $search = '';

    #[On('price-updates-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('price-updates-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('price-updates-clear-filters')]
    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    #[On('price-updates-column-toggle')]
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

        $this->dispatch('price-updates-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('price_updates.actions.add'),
            resource: __('agricart.pages.purchasing.price-update'),
            formKey: 'price-update',
            mode: 'edit',
        );
    }

    public function openEdit(int $productId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('price_updates.actions.edit', ['name' => $product->name_en]),
            resource: __('agricart.pages.purchasing.price-update'),
            formKey: 'price-update',
            productId: $productId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $products = Product::query()
            ->whereNotNull('last_buy_price')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('code', 'like', $needle)
                        ->orWhere('sku', 'like', $needle);
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('livewire.price-updates.list', [
            'products' => $products,
            'canEdit' => $this->userCan('edit'),
            'hasListConstraints' => filled($this->search),
            'visibleColumns' => $this->visibleColumns,
        ]);
    }

    public function thumbUrl(?Product $product): ?string
    {
        if ($product === null || ! filled($product->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($product->image_path);
    }

    private function userCan(string $action): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'price-update', $action);
    }
}
