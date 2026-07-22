<?php

namespace App\Livewire\Brands;

use App\Livewire\Concerns\HandlesCatalogPortableArchive;
use App\Models\Brand;
use App\Models\User;
use App\Services\Catalog\BrandService;
use App\Services\Catalog\CatalogPortableArchiveService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ListBrands extends Component
{
    use HandlesCatalogPortableArchive;
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'code', 'name_en', 'name_ur', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('brands-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return $this->handleCatalogPortableAction($action);
    }

    #[On('brands-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    protected function catalogPortableScope(): string
    {
        return CatalogPortableArchiveService::SCOPE_BRANDS;
    }

    protected function catalogPortableCan(string $action): bool
    {
        return $this->userCan($action);
    }

    #[On('brands-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('brands-filter')]
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

    #[On('brands-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('brands-column-toggle')]
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

        $this->dispatch('brands-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $brandId): void
    {
        $this->authorizeAction('view');

        $brand = Brand::query()->find($brandId);

        if ($brand === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.brands.actions.view', ['name' => $brand->name_en]),
            resource: __('agricart.pages.catalog.brands'),
            formKey: 'brands',
            brandId: $brandId,
            mode: 'view',
        );
    }

    public function openEdit(int $brandId): void
    {
        $this->authorizeAction('edit');

        $brand = Brand::query()->find($brandId);

        if ($brand === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.brands.actions.edit', ['name' => $brand->name_en]),
            resource: __('agricart.pages.catalog.brands'),
            formKey: 'brands',
            brandId: $brandId,
            mode: 'edit',
        );
    }

    public function deleteBrand(int $brandId): void
    {
        $this->authorizeAction('delete');

        $brand = Brand::query()->find($brandId);

        if ($brand === null) {
            return;
        }

        try {
            app(BrandService::class)->delete($brand);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return;
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizeAction('view');

        $brands = Brand::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.brands.list', [
            'brands' => $brands,
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
