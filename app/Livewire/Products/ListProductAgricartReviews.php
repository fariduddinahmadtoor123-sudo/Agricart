<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductService;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListProductAgricartReviews extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'code', 'name_en', 'brand', 'agricart_rating', 'status', 'updated_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('product-reviews-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('product-reviews-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('product-reviews-filter')]
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

    #[On('product-reviews-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('product-reviews-column-toggle')]
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

        $this->dispatch('product-reviews-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $productId): void
    {
        $this->authorizeAction('view');

        $product = Product::query()->find($productId);

        if ($product === null || $product->agricart_rating === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.products.review_page.actions.view', ['name' => $product->name_en]),
            resource: __('agricart.pages.catalog.product-reviews'),
            formKey: 'product-reviews',
            productId: $productId,
            mode: 'view',
        );
    }

    public function openEdit(int $productId): void
    {
        $this->authorizeAction('edit');

        $product = Product::query()->find($productId);

        if ($product === null || $product->agricart_rating === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.products.review_page.actions.edit', ['name' => $product->name_en]),
            resource: __('agricart.pages.catalog.product-reviews'),
            formKey: 'product-reviews',
            productId: $productId,
            mode: 'edit',
        );
    }

    public function clearReview(int $productId): void
    {
        $this->authorizeAction('edit');

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        try {
            app(ProductService::class)->updateAgricartReview($product, [
                'agricart_rating' => null,
                'agricart_rating_criteria' => BrandAgricartReviewCriteria::normalizeDetailed(null),
                'agricart_rating_reason_en' => null,
                'agricart_rating_reason_ur' => null,
                'agricart_rating_revision_en' => null,
                'agricart_rating_revision_ur' => null,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('agricart.products.review_page.messages.cleared'))
            ->send();

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizeAction('view');

        $products = Product::query()
            ->with(['brand:id,name_en'])
            ->whereNotNull('agricart_rating')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle)
                        ->orWhere('sku', 'like', $needle)
                        ->orWhereHas('brand', function ($brandQuery) use ($needle): void {
                            $brandQuery->where('name_en', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('updated_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.products.list-agricart-reviews', [
            'products' => $products,
            'canView' => $this->userCan('view'),
            'canEdit' => $this->userCan('edit'),
            'scoreMax' => BrandAgricartReviewCriteria::scoreMax(),
        ]);
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan($action), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('catalog.'.$action);
    }
}
