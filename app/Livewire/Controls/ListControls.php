<?php

namespace App\Livewire\Controls;

use App\Models\CatalogControl;
use App\Services\Catalog\ControlService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListControls extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['code', 'name_en', 'value_en', 'name_ur', 'value_ur', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('controls-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('controls-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('controls-filter')]
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

    #[On('controls-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('controls-column-toggle')]
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

        $this->dispatch('controls-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $controlId): void
    {
        $control = CatalogControl::query()->find($controlId);

        if ($control === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.controls.actions.view', ['name' => $control->name_en]),
            resource: __('agricart.pages.catalog.controls'),
            formKey: 'controls',
            controlId: $controlId,
            mode: 'view',
        );
    }

    public function openEdit(int $controlId): void
    {
        $control = CatalogControl::query()->find($controlId);

        if ($control === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.controls.actions.edit', ['name' => $control->name_en]),
            resource: __('agricart.pages.catalog.controls'),
            formKey: 'controls',
            controlId: $controlId,
            mode: 'edit',
        );
    }

    public function deleteControl(int $controlId): void
    {
        $control = CatalogControl::query()->find($controlId);

        if ($control === null) {
            return;
        }

        app(ControlService::class)->delete($control);
        $this->resetPage();
    }

    public function render(): View
    {
        $controls = CatalogControl::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('value_en', 'like', $needle)
                        ->orWhere('value_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.controls.list', [
            'controls' => $controls,
        ]);
    }
}
