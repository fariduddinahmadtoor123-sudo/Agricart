<?php

namespace App\Livewire\Packages;

use App\Models\ProductPackage;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Catalog\ProductPackageService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPackages extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'image',
        'package_number',
        'name_en',
        'name_ur',
        'discount_percent',
        'status',
        'sort_order',
        'created_at',
    ];

    public string $search = '';

    public string $statusFilter = '';

    #[On('packages-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('packages-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('packages-filter')]
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

    #[On('packages-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('packages-column-toggle')]
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

        $this->dispatch('packages-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->authorizeAction('create');

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.toolbar.add', ['item' => __('agricart.pages.catalog.packages')]),
            resource: __('agricart.pages.catalog.packages'),
            formKey: 'packages',
            mode: 'create',
        );
    }

    public function openView(int $packageId): void
    {
        $this->authorizeAction('view');

        $package = ProductPackage::query()->find($packageId);

        if ($package === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.packages.actions.view', ['name' => $package->name_en]),
            resource: __('agricart.pages.catalog.packages'),
            formKey: 'packages',
            packageId: $packageId,
            mode: 'view',
        );
    }

    public function openEdit(int $packageId): void
    {
        $this->authorizeAction('edit');

        $package = ProductPackage::query()->find($packageId);

        if ($package === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.packages.actions.edit', ['name' => $package->name_en]),
            resource: __('agricart.pages.catalog.packages'),
            formKey: 'packages',
            packageId: $packageId,
            mode: 'edit',
        );
    }

    public function deletePackage(int $packageId): void
    {
        $this->authorizeAction('delete');

        $package = ProductPackage::query()->find($packageId);

        if ($package === null) {
            return;
        }

        try {
            app(ProductPackageService::class)->delete($package);
        } catch (ValidationException $exception) {
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

        $packages = ProductPackage::query()
            ->withCount('items')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('package_number', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.packages.list', [
            'packages' => $packages,
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

        return $user->isSuperAdmin() || ModulePageAccess::can($user, 'catalog', 'packages', $action);
    }
}
