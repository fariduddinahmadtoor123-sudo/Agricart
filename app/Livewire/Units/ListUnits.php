<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use App\Models\User;
use App\Services\Catalog\UnitService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListUnits extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['code', 'name_en', 'abbreviation_en', 'name_ur', 'abbreviation_ur', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('units-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('units-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('units-filter')]
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

    #[On('units-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('units-column-toggle')]
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

        $this->dispatch('units-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $unitId): void
    {
        $this->authorizeAction('view');
        $unit = Unit::query()->find($unitId);

        if ($unit === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.units.actions.view', ['name' => $unit->name_en]),
            resource: __('agricart.pages.catalog.units'),
            formKey: 'units',
            unitId: $unitId,
            mode: 'view',
        );
    }

    public function openEdit(int $unitId): void
    {
        $this->authorizeAction('edit');
        $unit = Unit::query()->find($unitId);

        if ($unit === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.units.actions.edit', ['name' => $unit->name_en]),
            resource: __('agricart.pages.catalog.units'),
            formKey: 'units',
            unitId: $unitId,
            mode: 'edit',
        );
    }

    public function deleteUnit(int $unitId): void
    {
        $this->authorizeAction('delete');
        $unit = Unit::query()->find($unitId);

        if ($unit === null) {
            return;
        }

        try {
            app(UnitService::class)->delete($unit);
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
        $units = Unit::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('abbreviation_en', 'like', $needle)
                        ->orWhere('abbreviation_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.units.list', [
            'units' => $units,
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
