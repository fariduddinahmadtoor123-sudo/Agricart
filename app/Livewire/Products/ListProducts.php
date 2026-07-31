<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\ImportsPortableArchive;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CatalogPortableArchiveService;
use App\Services\Catalog\FacebookCatalogCsvService;
use App\Services\Catalog\ProductSearchQuery;
use App\Services\Catalog\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListProducts extends Component
{
    use ImportsPortableArchive;
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'code', 'label_code', 'name_en', 'name_ur', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    public bool $showImportPanel = false;

    /** @var mixed */
    public $importArchive = null;

    public ?string $portableStatus = null;

    public string $portableStatusTone = 'success';

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

    #[On('products-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return match ($action) {
            'export_archive' => $this->exportArchive(),
            'import_archive' => $this->openImportPanel(),
            'export_facebook_csv' => $this->exportFacebookCsv(),
            default => null,
        };
    }

    public function openImportPanel(): void
    {
        if (! $this->userCan('create')) {
            $this->portableStatus = __('agricart.products.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->showImportPanel = true;
        $this->importArchive = null;
        $this->portableStatus = null;
    }

    public function closeImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->importArchive = null;
    }

    public function exportArchive(): ?BinaryFileResponse
    {
        if (! $this->userCan('view')) {
            $this->portableStatus = __('agricart.products.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return null;
        }

        try {
            $archive = app(CatalogPortableArchiveService::class)->exportAll();
            $this->portableStatus = __('agricart.products.portable.export_ok', ['count' => $archive['count']]);
            $this->portableStatusTone = 'success';

            return response()->download($archive['path'], $archive['filename'])->deleteFileAfterSend(true);
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';

            return null;
        }
    }

    public function exportFacebookCsv(): ?BinaryFileResponse
    {
        if (! $this->userCan('view')) {
            $this->portableStatus = __('agricart.products.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return null;
        }

        $export = app(FacebookCatalogCsvService::class)->exportActiveProducts();
        $this->portableStatus = __('agricart.products.portable.facebook_ok', ['count' => $export['count']]);
        $this->portableStatusTone = 'success';

        return response()->download($export['path'], $export['filename'])->deleteFileAfterSend(true);
    }

    public function importUploadedArchive(): void
    {
        if (! $this->userCan('create')) {
            $this->portableStatus = __('agricart.products.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->validate([
            'importArchive' => ['required', 'file', 'max:1024000'],
        ], [], [
            'importArchive' => __('agricart.products.actions.import_archive'),
        ]);

        $materialized = $this->materializePortableArchiveUpload($this->importArchive, 'catalog-import');
        $absolute = $materialized['path'];

        try {
            $result = app(CatalogPortableArchiveService::class)->importFromPath($absolute);
            $this->portableStatus = __('agricart.products.portable.import_ok', $result);
            $this->portableStatusTone = 'success';
            $this->showImportPanel = false;
            $this->importArchive = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';
        } finally {
            if ($materialized['delete'] && is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    public function openView(int $productId): void
    {
        $this->authorizeAction('view');

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
        $this->authorizeAction('edit');

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
        $this->authorizeAction('delete');

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        try {
            app(ProductService::class)->delete($product);
            $this->dispatch('products-saved');
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('product_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function storeUrl(Product $product): string
    {
        return route('store.product', ['slug' => $product->slug]);
    }

    public function whatsappShareUrl(Product $product): string
    {
        $text = $product->name_en.' — '.$this->storeUrl($product);

        return 'https://wa.me/?text='.rawurlencode($text);
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['brand', 'category'])
            ->when(filled($this->search), function ($query): void {
                ProductSearchQuery::apply($query, trim($this->search));
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.products.list', [
            'products' => $products,
            'canView' => $this->userCan('view'),
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
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
