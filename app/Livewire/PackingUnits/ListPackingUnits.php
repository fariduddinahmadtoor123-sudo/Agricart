<?php

namespace App\Livewire\PackingUnits;

use App\Livewire\Concerns\HandlesCatalogPortableArchive;
use App\Models\PackingUnit;
use App\Models\User;
use App\Services\Catalog\CatalogPortableArchiveService;
use App\Services\Catalog\PackingUnitService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ListPackingUnits extends Component
{
    use HandlesCatalogPortableArchive;
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['code', 'name_en', 'name_ur', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('packing-units-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return $this->handleCatalogPortableAction($action);
    }

    #[On('packing-units-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    protected function catalogPortableScope(): string
    {
        return CatalogPortableArchiveService::SCOPE_PACKING_UNITS;
    }

    protected function catalogPortableCan(string $action): bool
    {
        return $this->userCan($action);
    }

    #[On('packing-units-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('packing-units-filter')]
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

    #[On('packing-units-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('packing-units-column-toggle')]
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

        $this->dispatch('packing-units-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $packingUnitId): void
    {
        $this->authorizeAction('view');
        $packingUnit = PackingUnit::query()->find($packingUnitId);

        if ($packingUnit === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.packing_units.actions.view', ['name' => $packingUnit->name_en]),
            resource: __('agricart.pages.catalog.packing_units'),
            formKey: 'packing-units',
            packingUnitId: $packingUnitId,
            mode: 'view',
        );
    }

    public function openEdit(int $packingUnitId): void
    {
        $this->authorizeAction('edit');
        $packingUnit = PackingUnit::query()->find($packingUnitId);

        if ($packingUnit === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.packing_units.actions.edit', ['name' => $packingUnit->name_en]),
            resource: __('agricart.pages.catalog.packing_units'),
            formKey: 'packing-units',
            packingUnitId: $packingUnitId,
            mode: 'edit',
        );
    }

    public function deletePackingUnit(int $packingUnitId): void
    {
        $this->authorizeAction('delete');
        $packingUnit = PackingUnit::query()->find($packingUnitId);

        if ($packingUnit === null) {
            return;
        }

        try {
            app(PackingUnitService::class)->delete($packingUnit);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return;
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizeAction('view');
        $packingUnits = PackingUnit::query()
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

        return view('livewire.packing-units.list', [
            'packingUnits' => $packingUnits,
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

        return $user instanceof User
            && $user->canUseAdmin()
            && ($user->isSuperAdmin() || $user->hasPermission('catalog.'.$action));
    }
}
