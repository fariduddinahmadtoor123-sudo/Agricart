<?php

namespace App\Livewire\QuantityControls;

use App\Models\QuantityControl;
use App\Services\PricingControls\QuantityControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListQuantityControls extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['control_number', 'name_en', 'name_ur', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('quantity-controls-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('quantity-controls-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('quantity-controls-filter')]
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

    #[On('quantity-controls-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('quantity-controls-column-toggle')]
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

        $this->dispatch('quantity-controls-columns-sync', columns: $this->visibleColumns);
    }

    public function openEdit(int $recordId): void
    {
        $record = QuantityControl::query()->find($recordId);

        if ($record === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('quantity_controls.actions.edit', ['name' => $record->name_en]),
            resource: __('agricart.pages.marketplace.quantity-control'),
            formKey: 'quantity-controls',
            quantityControlId: $recordId,
            mode: 'edit',
        );
    }

    public function deleteRecord(int $recordId): void
    {
        $record = QuantityControl::query()->find($recordId);

        if ($record === null) {
            return;
        }

        try {
            app(QuantityControlService::class)->delete($record);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('quantity_control_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $records = QuantityControl::query()
            ->with('creator')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('control_number', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.quantity-controls.list', [
            'records' => $records,
        ]);
    }
}
